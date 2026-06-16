package mail

import (
	"fmt"
	"io"
	"mime"
	"os"
	"sort"
	"strings"
	"time"

	"github.com/emersion/go-imap"
	"github.com/emersion/go-imap/client"
	gomessage "github.com/emersion/go-message/mail"
)

// IMAPClient proxies IMAP operations to the mail server using Dovecot master-user auth.
// The daemon authenticates as "account*masterUser" with masterPass so it never
// needs the user's own password. This is the standard approach for webmail systems.
type IMAPClient struct {
	host       string // e.g. "xpanel-mail:143"
	masterUser string // Dovecot master username (from XPANEL_MAIL_MASTER_USER env)
	masterPass string // Dovecot master password  (from XPANEL_MAIL_MASTER_PASS env)
}

func NewIMAPClient() *IMAPClient {
	host := os.Getenv("XPANEL_MAIL_IMAP_HOST")
	if host == "" {
		host = "xpanel-mail:143"
	}
	return &IMAPClient{
		host:       host,
		masterUser: os.Getenv("XPANEL_MAIL_MASTER_USER"),
		masterPass: os.Getenv("XPANEL_MAIL_MASTER_PASS"),
	}
}

func (c *IMAPClient) connect(account string) (*client.Client, error) {
	cl, err := client.DialTLS(strings.Replace(c.host, ":143", ":993", 1), nil)
	if err != nil {
		// Fall back to plain IMAP (internal network, no TLS needed)
		cl, err = client.Dial(c.host)
		if err != nil {
			return nil, fmt.Errorf("imap dial failed: %w", err)
		}
	}

	loginUser := account
	if c.masterUser != "" {
		loginUser = account + "*" + c.masterUser
	}
	if err := cl.Login(loginUser, c.masterPass); err != nil {
		cl.Logout()
		return nil, fmt.Errorf("imap login failed for %s: %w", account, err)
	}
	return cl, nil
}

// FolderInfo describes an IMAP mailbox with its message counts.
type FolderInfo struct {
	Name     string `json:"name"`
	Unseen   uint32 `json:"unseen"`
	Total    uint32 `json:"total"`
	Icon     string `json:"icon"`
}

// Folders returns the list of mailboxes for the given account.
func (c *IMAPClient) Folders(account string) ([]FolderInfo, error) {
	cl, err := c.connect(account)
	if err != nil {
		return nil, err
	}
	defer cl.Logout()

	ch := make(chan *imap.MailboxInfo, 20)
	done := make(chan error, 1)
	go func() { done <- cl.List("", "*", ch) }()

	var names []string
	for mb := range ch {
		names = append(names, mb.Name)
	}
	if err := <-done; err != nil {
		return nil, err
	}

	// Sort: standard folders first, then custom alphabetically.
	order := map[string]int{"INBOX": 0, "Drafts": 1, "Sent": 2, "Junk": 3, "Trash": 4, "Archive": 5}
	sort.Slice(names, func(i, j int) bool {
		oi, oj := order[names[i]], order[names[j]]
		if oi != oj {
			return oi < oj
		}
		if (oi == 0) != (oj == 0) {
			return oi == 0
		}
		return names[i] < names[j]
	})

	icons := map[string]string{
		"INBOX":   "ki-sms",
		"Drafts":  "ki-notepad",
		"Sent":    "ki-send",
		"Junk":    "ki-information-2",
		"Trash":   "ki-trash",
		"Archive": "ki-archive",
	}

	var folders []FolderInfo
	for _, name := range names {
		status, err := cl.Status(name, []imap.StatusItem{imap.StatusMessages, imap.StatusUnseen})
		total, unseen := uint32(0), uint32(0)
		if err == nil && status != nil {
			total = status.Messages
			unseen = status.Unseen
		}
		icon := icons[name]
		if icon == "" {
			icon = "ki-folder"
		}
		folders = append(folders, FolderInfo{Name: name, Total: total, Unseen: unseen, Icon: icon})
	}
	return folders, nil
}

// MessageSummary is a lightweight message descriptor for the list view.
type MessageSummary struct {
	UID         uint32    `json:"uid"`
	From        string    `json:"from"`
	FromName    string    `json:"from_name"`
	Subject     string    `json:"subject"`
	Date        time.Time `json:"date"`
	Seen        bool      `json:"seen"`
	Flagged     bool      `json:"flagged"`
	HasAttach   bool      `json:"has_attachments"`
	Preview     string    `json:"preview"`
}

// Messages returns the latest messages from a folder (newest first, up to limit).
func (c *IMAPClient) Messages(account, folder string, page, perPage int) ([]MessageSummary, uint32, error) {
	cl, err := c.connect(account)
	if err != nil {
		return nil, 0, err
	}
	defer cl.Logout()

	mbox, err := cl.Select(folder, true)
	if err != nil {
		return nil, 0, fmt.Errorf("select %s: %w", folder, err)
	}
	total := mbox.Messages
	if total == 0 {
		return nil, 0, nil
	}

	// Compute sequence range for the requested page (newest first).
	end := total - uint32((page-1)*perPage)
	if end > total {
		end = total
	}
	if end == 0 {
		return nil, total, nil
	}
	start := uint32(1)
	if end > uint32(perPage) {
		start = end - uint32(perPage) + 1
	}

	seqset := new(imap.SeqSet)
	seqset.AddRange(start, end)

	items := []imap.FetchItem{imap.FetchEnvelope, imap.FetchFlags, imap.FetchUid, imap.FetchBodyStructure}
	ch := make(chan *imap.Message, perPage)
	done := make(chan error, 1)
	go func() { done <- cl.Fetch(seqset, items, ch) }()

	var summaries []MessageSummary
	for msg := range ch {
		if msg == nil {
			continue
		}
		s := MessageSummary{
			UID:  msg.Uid,
			Date: msg.Envelope.Date,
		}
		// From
		if len(msg.Envelope.From) > 0 {
			addr := msg.Envelope.From[0]
			s.FromName = addr.PersonalName
			if addr.MailboxName != "" && addr.HostName != "" {
				s.From = addr.MailboxName + "@" + addr.HostName
			}
			if s.FromName == "" {
				s.FromName = s.From
			}
		}
		s.Subject = msg.Envelope.Subject
		// Flags
		for _, f := range msg.Flags {
			switch f {
			case imap.SeenFlag:
				s.Seen = true
			case imap.FlaggedFlag:
				s.Flagged = true
			}
		}
		// Attachments
		if msg.BodyStructure != nil {
			s.HasAttach = hasAttachments(msg.BodyStructure)
		}
		summaries = append(summaries, s)
	}
	if err := <-done; err != nil {
		return nil, total, err
	}

	// Reverse so newest is first.
	for i, j := 0, len(summaries)-1; i < j; i, j = i+1, j-1 {
		summaries[i], summaries[j] = summaries[j], summaries[i]
	}

	return summaries, total, nil
}

// Attachment describes a file attached to an email.
type Attachment struct {
	Filename    string `json:"filename"`
	ContentType string `json:"content_type"`
	Size        int    `json:"size"`
}

// MessageFull is the full content of a single message.
type MessageFull struct {
	UID         uint32       `json:"uid"`
	From        string       `json:"from"`
	FromName    string       `json:"from_name"`
	To          []string     `json:"to"`
	Cc          []string     `json:"cc"`
	ReplyTo     string       `json:"reply_to"`
	Subject     string       `json:"subject"`
	Date        time.Time    `json:"date"`
	Seen        bool         `json:"seen"`
	Flagged     bool         `json:"flagged"`
	TextBody    string       `json:"text"`
	HTMLBody    string       `json:"html"`
	Attachments []Attachment `json:"attachments"`
}

// Message fetches the full content of a message by UID.
func (c *IMAPClient) Message(account, folder string, uid uint32) (*MessageFull, error) {
	cl, err := c.connect(account)
	if err != nil {
		return nil, err
	}
	defer cl.Logout()

	if _, err := cl.Select(folder, false); err != nil {
		return nil, fmt.Errorf("select %s: %w", folder, err)
	}

	seqset := new(imap.SeqSet)
	seqset.AddNum(uid)

	section := &imap.BodySectionName{}
	items := []imap.FetchItem{imap.FetchEnvelope, imap.FetchFlags, imap.FetchUid, section.FetchItem()}
	ch := make(chan *imap.Message, 1)
	done := make(chan error, 1)
	go func() { done <- cl.UidFetch(seqset, items, ch) }()

	var raw *imap.Message
	for msg := range ch {
		raw = msg
	}
	if err := <-done; err != nil {
		return nil, err
	}
	if raw == nil {
		return nil, fmt.Errorf("message %d not found in %s", uid, folder)
	}

	full := &MessageFull{
		UID:     raw.Uid,
		Subject: raw.Envelope.Subject,
		Date:    raw.Envelope.Date,
	}

	// From
	if len(raw.Envelope.From) > 0 {
		addr := raw.Envelope.From[0]
		full.FromName = addr.PersonalName
		if addr.MailboxName != "" && addr.HostName != "" {
			full.From = addr.MailboxName + "@" + addr.HostName
		}
	}
	// To
	for _, addr := range raw.Envelope.To {
		if addr.MailboxName != "" && addr.HostName != "" {
			full.To = append(full.To, addr.MailboxName+"@"+addr.HostName)
		}
	}
	// Cc
	for _, addr := range raw.Envelope.Cc {
		if addr.MailboxName != "" && addr.HostName != "" {
			full.Cc = append(full.Cc, addr.MailboxName+"@"+addr.HostName)
		}
	}
	// ReplyTo
	if len(raw.Envelope.ReplyTo) > 0 {
		a := raw.Envelope.ReplyTo[0]
		full.ReplyTo = a.MailboxName + "@" + a.HostName
	}
	// Flags
	for _, f := range raw.Flags {
		switch f {
		case imap.SeenFlag:
			full.Seen = true
		case imap.FlaggedFlag:
			full.Flagged = true
		}
	}

	// Mark as seen.
	_ = cl.UidStore(seqset, imap.AddFlags, []interface{}{imap.SeenFlag}, nil)

	// Parse body.
	if literal := raw.GetBody(section); literal != nil {
		mr, err := gomessage.CreateReader(literal)
		if err == nil {
			full.TextBody, full.HTMLBody, full.Attachments = parseMessageParts(mr)
		}
	}

	return full, nil
}

// SetFlag adds or removes an IMAP flag on a message.
func (c *IMAPClient) SetFlag(account, folder string, uid uint32, flag string, set bool) error {
	cl, err := c.connect(account)
	if err != nil {
		return err
	}
	defer cl.Logout()

	if _, err := cl.Select(folder, false); err != nil {
		return err
	}
	seqset := new(imap.SeqSet)
	seqset.AddNum(uid)
	op := imap.AddFlags
	if !set {
		op = imap.RemoveFlags
	}
	return cl.UidStore(seqset, imap.StoreItem(op), []interface{}{flag}, nil)
}

// Move copies a message to targetFolder then deletes it from folder.
func (c *IMAPClient) Move(account, folder string, uid uint32, targetFolder string) error {
	cl, err := c.connect(account)
	if err != nil {
		return err
	}
	defer cl.Logout()

	if _, err := cl.Select(folder, false); err != nil {
		return err
	}
	seqset := new(imap.SeqSet)
	seqset.AddNum(uid)

	if err := cl.UidCopy(seqset, targetFolder); err != nil {
		return fmt.Errorf("copy to %s: %w", targetFolder, err)
	}
	if err := cl.UidStore(seqset, imap.AddFlags, []interface{}{imap.DeletedFlag}, nil); err != nil {
		return err
	}
	return cl.Expunge(nil)
}

// Delete permanently deletes a message.
func (c *IMAPClient) Delete(account, folder string, uid uint32) error {
	cl, err := c.connect(account)
	if err != nil {
		return err
	}
	defer cl.Logout()

	if _, err := cl.Select(folder, false); err != nil {
		return err
	}
	seqset := new(imap.SeqSet)
	seqset.AddNum(uid)
	if err := cl.UidStore(seqset, imap.AddFlags, []interface{}{imap.DeletedFlag}, nil); err != nil {
		return err
	}
	return cl.Expunge(nil)
}

// CreateFolder creates a new IMAP mailbox.
func (c *IMAPClient) CreateFolder(account, name string) error {
	cl, err := c.connect(account)
	if err != nil {
		return err
	}
	defer cl.Logout()
	return cl.Create(name)
}

// DeleteFolder deletes an IMAP mailbox.
func (c *IMAPClient) DeleteFolder(account, name string) error {
	cl, err := c.connect(account)
	if err != nil {
		return err
	}
	defer cl.Logout()
	return cl.Delete(name)
}

// ---- helpers ----

func hasAttachments(bs *imap.BodyStructure) bool {
	if bs == nil {
		return false
	}
	disp := strings.ToLower(bs.Disposition)
	if disp == "attachment" || disp == "inline" {
		if bs.DispositionParams["filename"] != "" {
			return true
		}
	}
	for _, part := range bs.Parts {
		if hasAttachments(part) {
			return true
		}
	}
	return false
}

func parseMessageParts(mr *gomessage.Reader) (textBody, htmlBody string, attachments []Attachment) {
	for {
		p, err := mr.NextPart()
		if err == io.EOF {
			break
		}
		if err != nil {
			break
		}
		switch h := p.Header.(type) {
		case *gomessage.InlineHeader:
			ct, _, _ := h.ContentType()
			body, _ := io.ReadAll(p.Body)
			switch strings.ToLower(ct) {
			case "text/plain":
				if textBody == "" {
					textBody = string(body)
				}
			case "text/html":
				if htmlBody == "" {
					htmlBody = string(body)
				}
			}
		case *gomessage.AttachmentHeader:
			filename, _ := h.Filename()
			if filename == "" {
				filename = "attachment"
			}
			if decoded, err := (&mime.WordDecoder{}).DecodeHeader(filename); err == nil {
					filename = decoded
				}
			ct, _, _ := h.ContentType()
			body, _ := io.ReadAll(p.Body)
			attachments = append(attachments, Attachment{
				Filename:    filename,
				ContentType: ct,
				Size:        len(body),
			})
		}
	}
	return
}
