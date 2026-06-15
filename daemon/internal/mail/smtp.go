package mail

import (
	"bytes"
	"fmt"
	"mime/multipart"
	"mime/quotedprintable"
	"net/smtp"
	"net/textproto"
	"os"
	"strings"
	"time"
)

// SMTPClient sends outgoing email through Postfix on the internal Docker network.
// Port 25 is used for internal submission (no auth required from trusted networks).
type SMTPClient struct {
	host string // e.g. "xpanel-mail:25" or "xpanel-mail:587"
}

func NewSMTPClient() *SMTPClient {
	host := os.Getenv("XPANEL_MAIL_SMTP_HOST")
	if host == "" {
		host = "xpanel-mail:25"
	}
	return &SMTPClient{host: host}
}

// SendRequest holds all the fields needed to send an email.
type SendRequest struct {
	From        string   `json:"from"`
	To          []string `json:"to"`
	Cc          []string `json:"cc"`
	Bcc         []string `json:"bcc"`
	Subject     string   `json:"subject"`
	TextBody    string   `json:"text"`
	HTMLBody    string   `json:"html"`
	InReplyTo   string   `json:"in_reply_to,omitempty"`
	References  string   `json:"references,omitempty"`
}

// Send delivers the message via SMTP.
func (c *SMTPClient) Send(req SendRequest) error {
	if req.From == "" {
		return fmt.Errorf("from address is required")
	}
	if len(req.To) == 0 {
		return fmt.Errorf("at least one recipient is required")
	}

	var buf bytes.Buffer
	mw := multipart.NewWriter(&buf)

	// Build headers.
	allRecipients := append(append([]string{}, req.To...), append(req.Cc, req.Bcc...)...)

	headers := new(bytes.Buffer)
	fmt.Fprintf(headers, "From: %s\r\n", req.From)
	fmt.Fprintf(headers, "To: %s\r\n", strings.Join(req.To, ", "))
	if len(req.Cc) > 0 {
		fmt.Fprintf(headers, "Cc: %s\r\n", strings.Join(req.Cc, ", "))
	}
	fmt.Fprintf(headers, "Subject: %s\r\n", encodeHeader(req.Subject))
	fmt.Fprintf(headers, "Date: %s\r\n", time.Now().UTC().Format(time.RFC1123Z))
	fmt.Fprintf(headers, "MIME-Version: 1.0\r\n")
	if req.InReplyTo != "" {
		fmt.Fprintf(headers, "In-Reply-To: %s\r\n", req.InReplyTo)
	}
	if req.References != "" {
		fmt.Fprintf(headers, "References: %s\r\n", req.References)
	}
	fmt.Fprintf(headers, "Content-Type: multipart/alternative; boundary=%q\r\n", mw.Boundary())
	fmt.Fprintf(headers, "\r\n")

	// Text part.
	if req.TextBody != "" {
		th := textproto.MIMEHeader{}
		th.Set("Content-Type", "text/plain; charset=utf-8")
		th.Set("Content-Transfer-Encoding", "quoted-printable")
		tw, _ := mw.CreatePart(th)
		qw := quotedprintable.NewWriter(tw)
		qw.Write([]byte(req.TextBody))
		qw.Close()
	}

	// HTML part.
	if req.HTMLBody != "" {
		hh := textproto.MIMEHeader{}
		hh.Set("Content-Type", "text/html; charset=utf-8")
		hh.Set("Content-Transfer-Encoding", "quoted-printable")
		hw, _ := mw.CreatePart(hh)
		qw := quotedprintable.NewWriter(hw)
		qw.Write([]byte(req.HTMLBody))
		qw.Close()
	}

	mw.Close()

	// Full message = headers + body.
	message := append(headers.Bytes(), buf.Bytes()...)

	// Send via SMTP (no auth on internal port 25).
	return smtp.SendMail(c.host, nil, req.From, allRecipients, message)
}

func encodeHeader(s string) string {
	for _, r := range s {
		if r > 127 {
			return "=?utf-8?q?" + mimeQEncode(s) + "?="
		}
	}
	return s
}

func mimeQEncode(s string) string {
	var b strings.Builder
	for _, r := range s {
		if r == ' ' {
			b.WriteByte('_')
		} else if r > 127 || r < 33 || strings.ContainsRune("=?_", r) {
			fmt.Fprintf(&b, "=%02X", r)
		} else {
			b.WriteRune(r)
		}
	}
	return b.String()
}
