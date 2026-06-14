<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="en">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Correos - XPanel</title>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport" />
    <meta name="robots" content="noindex, nofollow, noarchive" />
    <link href="{{ asset('assets/media/app/apple-touch-icon.png') }}" rel="apple-touch-icon" sizes="180x180" />
    <link href="{{ asset('assets/media/app/favicon-32x32.png') }}" rel="icon" sizes="32x32" type="image/png" />
    <link href="{{ asset('assets/media/app/favicon-16x16.png') }}" rel="icon" sizes="16x16" type="image/png" />
    <link href="{{ asset('assets/media/app/favicon.ico') }}" rel="shortcut icon" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/xpanel.css') }}" rel="stylesheet" />
<style>
        .xmail-app {
            display: grid;
            grid-template-columns: 180px minmax(270px, 350px) minmax(0, 1fr) 30px;
            gap: 6px;
            height: 100dvh;
            padding: 10px;
            background: #f4f4f5;
            color: #18181b;
            font-size: 12px;
            line-height: 1.3;
        }

        .xmail-app.is-sidebar-collapsed {
            grid-template-columns: 48px minmax(270px, 350px) minmax(0, 1fr) 30px;
        }

        .xmail-app.is-sidebar-collapsed .xmail-sidebar {
            padding-left: 2px;
            padding-right: 2px;
            align-items: center;
        }

        .xmail-app.is-sidebar-collapsed .xmail-sidebar-text,
        .xmail-app.is-sidebar-collapsed .xmail-sidebar-count,
        .xmail-app.is-sidebar-collapsed .xmail-sidebar-section {
            display: none !important;
        }

        .xmail-app.is-sidebar-collapsed .xmail-mail-section {
            display: block !important;
            width: 100%;
            padding-inline: 0 !important;
            text-align: center;
        }

        .xmail-app.is-sidebar-collapsed .xmail-sidebar > .flex:first-child {
            justify-content: center;
            padding-bottom: 20px !important;
        }

        .xmail-app.is-sidebar-collapsed .xmail-sidebar > .flex:first-child .size-11 {
            width: 36px !important;
            height: 36px !important;
        }

        .xmail-app.is-sidebar-collapsed .xmail-compose {
            width: 42px;
            height: 42px;
            padding: 0 !important;
            box-shadow: none;
        }

        .xmail-app.is-sidebar-collapsed .xmail-folder {
            width: 40px;
            height: 40px;
            justify-content: center;
            padding: 0 !important;
            border-radius: 10px;
        }

        .xmail-app.is-sidebar-collapsed .xmail-folder i {
            font-size: 16px !important;
        }

        .xmail-app.is-sidebar-collapsed .xmail-direct-stack,
        .xmail-app.is-sidebar-collapsed .xmail-bottom-stack {
            align-items: center;
            width: 100%;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .xmail-app.is-sidebar-collapsed .xmail-direct-person {
            justify-content: center;
        }

        .xmail-app.is-sidebar-collapsed .xmail-sidebar-bottom {
            width: 100%;
        }

        .xmail-panel {
            min-height: 0;
            border: 1px solid #dedfe3;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        }

        .xmail-sidebar-profile {
            gap: 10px !important;
            padding: 6px 8px 16px !important;
        }

        .xmail-sidebar {
            min-height: 0;
            background: #f4f4f5;
            padding: 2px 6px 0 6px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #d4d4d8 transparent;
        }

        .xmail-app .text-xs {
            font-size: 10px !important;
            line-height: 1.2 !important;
        }

        .xmail-app .text-sm {
            font-size: 11.5px !important;
            line-height: 1.3 !important;
        }

        .xmail-app .text-base {
            font-size: 12.5px !important;
            line-height: 1.3 !important;
        }

        .xmail-app .text-lg {
            font-size: 13.5px !important;
            line-height: 1.3 !important;
        }

        .xmail-app .text-xl {
            font-size: 15px !important;
            line-height: 1.3 !important;
        }

        .xmail-app .text-2xl {
            font-size: 18px !important;
            line-height: 1.25 !important;
        }

        .xmail-folder {
            height: 32px;
            border-radius: 9px;
            color: #27272a;
            gap: 8px !important;
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        .xmail-folder.is-active {
            background: #ffffff;
            color: #4f7cff;
            box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
        }

        .xmail-compose {
            height: 36px;
            border-radius: 8px;
            background: #4f7cff;
            color: #ffffff;
            box-shadow: 0 8px 16px rgba(79, 124, 255, 0.18);
            font-size: 13px !important;
        }

        .xmail-list-panel,
        .xmail-reader {
            border-radius: 14px;
            overflow: hidden;
        }

        .xmail-message {
            border-radius: 9px;
            cursor: pointer;
            gap: 10px !important;
            padding: 9px 10px !important;
        }

        .xmail-message.is-active {
            background: #f0f0f2;
            box-shadow: inset 0 0 0 1px rgba(79, 124, 255, 0.08);
        }

        .xmail-message:hover {
            background: #f7f7f8;
        }

        .xmail-message.is-active:hover {
            background: #f0f0f2;
        }

        .xmail-search {
            height: 38px;
            border-radius: 8px;
            border: 1px solid #dfe3ea;
            background: #ffffff;
        }

        .xmail-category-wrap {
            position: relative;
        }

        .xmail-category-menu {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            z-index: 8;
            display: none;
            width: 150px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #ffffff;
            padding: 6px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        }

        .xmail-category-menu.is-open {
            display: block;
        }

        .xmail-category-option {
            display: flex;
            width: 100%;
            align-items: center;
            gap: 8px;
            border-radius: 8px;
            padding: 7px 8px;
            color: #52525b;
            text-align: left;
        }

        .xmail-category-option:hover,
        .xmail-category-option.is-active {
            background: #f4f4f5;
            color: #18181b;
        }

        .xmail-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: 1px solid transparent;
            color: #6b7280;
            font-size: 13px;
        }

        .xmail-icon-btn:hover {
            border-color: #e5e7eb;
            background: #f8fafc;
            color: #111827;
        }

        .xmail-app.is-sidebar-collapsed #xmail_sidebar_toggle i {
            transform: rotate(180deg);
        }

        .xmail-rail-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #6b7280;
            box-shadow: 0 1px 5px rgba(15, 23, 42, 0.08);
            font-size: 13px;
        }

        .xmail-rail-btn i {
            font-size: 15px !important;
        }

        .xmail-rail-btn.is-active {
            color: #4f7cff;
            border-color: rgba(79, 124, 255, 0.28);
            background: #ffffff;
        }

        .xmail-attachment {
            height: 36px;
            border-radius: 9px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            gap: 8px !important;
            padding-left: 10px !important;
            padding-right: 8px !important;
        }

        .xmail-sidebar .size-11,
        .xmail-message .size-11 {
            width: 32px !important;
            height: 32px !important;
        }

        .xmail-sidebar .size-8 {
            width: 26px !important;
            height: 26px !important;
        }

        .xmail-reader .size-10 {
            width: 32px !important;
            height: 32px !important;
        }

        .xmail-reader .size-9 {
            width: 30px !important;
            height: 30px !important;
        }

        .xmail-reader .size-8 {
            width: 28px !important;
            height: 28px !important;
        }

        .xmail-reader .size-5 {
            width: 18px !important;
            height: 18px !important;
        }

        .xmail-app .kt-btn {
            min-height: 30px;
            padding-inline: 10px;
            font-size: 12px;
            border-radius: 8px;
        }

        .xmail-app .kt-btn-sm {
            min-height: 28px;
            padding-inline: 8px;
            font-size: 11.5px;
        }

        .xmail-reader-toolbar {
            height: 50px !important;
            padding-left: 14px !important;
            padding-right: 14px !important;
            gap: 8px !important;
        }

        .xmail-reader-actions {
            min-width: 0;
        }

        .xmail-message-card {
            padding: 24px 28px !important;
        }

        .xmail-message-card p {
            max-width: 900px;
        }

        .xmail-compose-box {
            display: none;
            border: 1px solid #e1e4ea;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .xmail-compose-box.is-open {
            display: block;
        }

        .xmail-app.is-compose-open .xmail-reader-footer {
            display: none !important;
        }

        .xmail-compose-row {
            min-height: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .xmail-recipient-chip {
            min-height: 28px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid #e1e4ea;
            border-radius: 999px;
            padding: 0 10px 0 8px;
            background: #ffffff;
        }

        .xmail-compose-area {
            min-height: 82px;
            width: 100%;
            resize: vertical;
            border: 0;
            outline: 0;
            padding: 12px;
            color: #18181b;
            font-size: 12.5px;
            line-height: 1.45;
        }

        .xmail-compose-tools {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            padding: 8px 12px;
            border-top: 1px solid #eef0f4;
        }

        .xmail-reader > .grow {
            scrollbar-width: thin;
            scrollbar-color: #d4d4d8 transparent;
        }

        .xmail-thread-header {
            padding: 15px 18px !important;
        }

        .xmail-reader-scroll {
            min-height: 0;
        }

        .xmail-reader-footer {
            padding: 8px 18px !important;
        }

        .xmail-sidebar .mt-7 {
            margin-top: 15px !important;
        }

        .xmail-sidebar .mt-3 {
            margin-top: 10px !important;
        }

        .xmail-sidebar .space-y-3 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 8px !important;
        }

        .xmail-list-panel > .flex:first-child {
            gap: 8px !important;
            padding: 10px !important;
        }

        #xmail_message_list {
            padding-left: 12px !important;
            padding-right: 12px !important;
            padding-bottom: 12px !important;
        }

        #xmail_message_list .space-y-2 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 6px !important;
        }

        .xmail-reader > .grow > .border-b {
            padding: 15px 18px !important;
        }

        .xmail-reader h1 {
            font-weight: 500;
            letter-spacing: 0;
        }

        .xmail-reader article {
            padding: 15px 18px !important;
        }

        .xmail-reader article > .flex {
            gap: 14px !important;
        }

        .xmail-reader .mt-8 {
            margin-top: 16px !important;
        }

        .xmail-reader .mt-7 {
            margin-top: 14px !important;
        }

        .xmail-reader .mt-9 {
            margin-top: 18px !important;
        }

        .xmail-reader > .border-t {
            padding: 8px 18px !important;
        }

        .xmail-rail {
            gap: 10px !important;
            padding-top: 2px !important;
        }

        @media (max-width: 1280px) {
            .xmail-app {
                grid-template-columns: 170px minmax(260px, 340px) minmax(0, 1fr);
            }

            .xmail-app.is-sidebar-collapsed {
                grid-template-columns: 48px minmax(260px, 340px) minmax(0, 1fr);
            }

            .xmail-rail {
                display: none;
            }
        }

        @media (max-width: 1024px) {
            .xmail-app {
                grid-template-columns: 50px minmax(0, 1fr);
                overflow: auto;
            }

            .xmail-app.is-sidebar-collapsed {
                grid-template-columns: 50px minmax(0, 1fr);
            }

            .xmail-app .xmail-sidebar {
                padding-left: 2px;
                padding-right: 2px;
                align-items: center;
            }

            .xmail-app .xmail-sidebar-text,
            .xmail-app .xmail-sidebar-count,
            .xmail-app .xmail-sidebar-section {
                display: none !important;
            }

            .xmail-app .xmail-mail-section {
                display: block !important;
                width: 100%;
                padding-inline: 0 !important;
                text-align: center;
            }

            .xmail-app .xmail-compose {
                width: 42px;
                height: 42px;
                padding: 0 !important;
                box-shadow: none;
            }

            .xmail-app .xmail-folder {
                width: 40px;
                height: 40px;
                justify-content: center;
                padding: 0 !important;
            }

            .xmail-app .xmail-direct-person {
                justify-content: center;
            }

            .xmail-reader {
                position: fixed;
                z-index: 25;
                inset: 10px;
                display: flex;
                transform: translateX(110%);
                transition: transform 180ms ease;
                box-shadow: 0 16px 48px rgba(15, 23, 42, 0.18);
            }

            .xmail-app.is-reader-open .xmail-reader {
                transform: translateX(0);
            }
        }

        @media (max-width: 760px) {
            body {
                overflow: auto;
            }

            .xmail-app {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                height: 100dvh;
                min-height: 0;
                overflow: hidden;
            }

            .xmail-sidebar {
                position: fixed;
                z-index: 30;
                inset: 12px auto 12px 12px;
                width: 280px;
                border-radius: 14px;
                border: 1px solid #dedfe3;
                box-shadow: 0 16px 48px rgba(15, 23, 42, 0.18);
                transform: translateX(-110%);
                transition: transform 180ms ease;
            }

            .xmail-app.is-mobile-sidebar-open .xmail-sidebar {
                transform: translateX(0);
            }

            .xmail-app.is-sidebar-collapsed .xmail-sidebar {
                align-items: stretch;
                width: 280px;
            }

            .xmail-app .xmail-sidebar {
                align-items: stretch;
            }

            .xmail-app .xmail-sidebar-text,
            .xmail-app .xmail-sidebar-count,
            .xmail-app .xmail-sidebar-section,
            .xmail-app.is-sidebar-collapsed .xmail-sidebar-text,
            .xmail-app.is-sidebar-collapsed .xmail-sidebar-count,
            .xmail-app.is-sidebar-collapsed .xmail-sidebar-section {
                display: block !important;
            }

            .xmail-app .xmail-folder,
            .xmail-app.is-sidebar-collapsed .xmail-folder {
                width: 100%;
                justify-content: flex-start;
                padding-left: 11px !important;
                padding-right: 11px !important;
            }

            .xmail-app .xmail-compose,
            .xmail-app.is-sidebar-collapsed .xmail-compose {
                width: 100%;
                padding: 0 16px !important;
            }

            .xmail-app .xmail-direct-person,
            .xmail-app.is-sidebar-collapsed .xmail-direct-person {
                justify-content: flex-start;
            }

            .xmail-list-panel {
                min-height: 0;
            }

            .xmail-reader {
                inset: 10px;
            }
        }
    </style>
</head>

<body class="antialiased h-full text-base text-foreground bg-muted overflow-hidden">
    <script>
        const defaultThemeMode = 'light';
        let themeMode;

        if (document.documentElement) {
            if (localStorage.getItem('kt-theme')) {
                themeMode = localStorage.getItem('kt-theme');
            } else if (document.documentElement.hasAttribute('data-kt-theme-mode')) {
                themeMode = document.documentElement.getAttribute('data-kt-theme-mode');
            } else {
                themeMode = defaultThemeMode;
            }

            if (themeMode === 'system') {
                themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            document.documentElement.classList.add(themeMode);
        }
    </script>

@php
    use Illuminate\Support\Facades\Auth;

    $user = Auth::guard('web')->user();
    $displayName = $user?->name ?: $tenant->name;
    $displayEmail = $primaryAccount?->email ?: $user?->email;
    $avatar = asset('assets/media/avatars/300-1.png');

    $mailFolders = [
        ['label' => 'Inbox', 'count' => 3, 'icon' => 'ki-sms', 'active' => true],
        ['label' => 'Draft', 'count' => 45, 'icon' => 'ki-notepad', 'active' => false],
        ['label' => 'Sent', 'count' => 9, 'icon' => 'ki-send', 'active' => false],
    ];

    $labelFolders = [
        ['label' => 'Archive', 'count' => 12, 'icon' => 'ki-archive', 'active' => false],
        ['label' => 'Snoozed', 'count' => 4, 'icon' => 'ki-time', 'active' => false],
        ['label' => 'Spam', 'count' => 3, 'icon' => 'ki-information-2', 'active' => false],
        ['label' => 'Trash', 'count' => 34, 'icon' => 'ki-trash', 'active' => false],
    ];

    $messages = [
        [
            'from' => 'Figma',
            'email' => 'updates@figma.com',
            'subject' => 'Remote access for Figma MCP server',
            'preview' => 'Remote access for Figma MCP server',
            'date' => 'Sep 23',
            'time' => '11:20 PM',
            'avatar' => 'F',
            'accent' => '#7c5cff',
            'active' => true,
            'body' => 'Ready to learn how to build and deploy your own AI agents? Join the 5-Day AI Agents Intensive Course with Google, happening from November 10 to 14.',
        ],
        ['from' => 'Attio', 'email' => 'tasks@attio.com', 'subject' => '[Keenthemes] 23 Sep -> 2 tasks overdue', 'preview' => '[Keenthemes] 23 Sep -> 2 tasks overdue', 'date' => 'Sep 23', 'time' => '9:18 PM', 'avatar' => 'A', 'accent' => '#111827', 'active' => false, 'body' => 'You have overdue tasks waiting in the workspace. Review the list and close the pending items.'],
        ['from' => 'Pepper Potts', 'email' => 'pepper@example.com', 'subject' => 'Meeting about the new project', 'preview' => 'Meeting about the new project', 'date' => 'Sep 22', 'time' => '4:42 PM', 'avatar' => 'P', 'accent' => '#4f8cff', 'active' => false, 'body' => 'The project meeting is ready for review. I added the notes and the next steps for the deployment window.'],
        ['from' => 'GitHub', 'email' => 'noreply@github.com', 'subject' => 'Pull request #1234 has been merged', 'preview' => 'Pull request #1234 has been merged', 'date' => 'Sep 21', 'time' => '2:04 PM', 'avatar' => 'G', 'accent' => '#111827', 'active' => false, 'body' => 'Your pull request was merged successfully. The changes are now available in the main branch.'],
        ['from' => 'Slack', 'email' => 'notifications@slack.com', 'subject' => 'New message in #general channel', 'preview' => 'New message in #general channel', 'date' => 'Sep 21', 'time' => '10:31 AM', 'avatar' => 'S', 'accent' => '#16a34a', 'active' => false, 'body' => 'There is a new message in #general with updates for the current sprint and release checklist.'],
        ['from' => 'Notion', 'email' => 'notify@notion.so', 'subject' => 'Document shared: Project Planning', 'preview' => 'Document shared: Project Planning', 'date' => 'Sep 20', 'time' => '8:10 PM', 'avatar' => 'N', 'accent' => '#111827', 'active' => false, 'body' => 'A new planning document was shared with your team. Open the workspace to review the latest notes.'],
        ['from' => 'Discord', 'email' => 'noreply@discord.com', 'subject' => 'Server update: New features available', 'preview' => 'Server update: New features available', 'date' => 'Sep 20', 'time' => '6:12 PM', 'avatar' => 'D', 'accent' => '#5865f2', 'active' => false, 'body' => 'Your server has new features available. Review the update summary and permissions.'],
        ['from' => 'Stripe', 'email' => 'receipts@stripe.com', 'subject' => 'Payment processed successfully', 'preview' => 'Payment processed successfully', 'date' => 'Sep 19', 'time' => '9:45 AM', 'avatar' => 'S', 'accent' => '#635bff', 'active' => false, 'body' => 'Your payment was processed successfully. The receipt is attached to this thread.'],
    ];

    $directMessages = [
        ['name' => 'Kou Tanaka', 'avatar' => '300-3.png', 'status' => 'online'],
        ['name' => 'Isabella Stewart', 'avatar' => '300-4.png', 'status' => 'idle'],
        ['name' => 'Yui Mimura', 'avatar' => '300-5.png', 'status' => 'offline'],
    ];
@endphp

<div class="xmail-app" id="xmail_app">
        <aside class="xmail-sidebar flex flex-col min-w-0" aria-label="Mail navigation">
            <div class="xmail-sidebar-profile flex items-center">
                <img class="size-11 rounded-full object-cover" src="{{ $avatar }}" alt="{{ $displayName }}">
                <div class="xmail-sidebar-text min-w-0">
                    <div class="text-lg font-semibold text-mono truncate">{{ $displayName }}</div>
                    <div class="text-sm text-secondary-foreground truncate">{{ $displayEmail }}</div>
                </div>
            </div>

            <button class="xmail-compose inline-flex items-center justify-center gap-2 px-4 text-base font-semibold" type="button" aria-label="New mail">
                <i class="ki-filled ki-notepad-edit text-lg"></i>
                <span class="xmail-sidebar-text">New Mail</span>
            </button>

            <div class="xmail-sidebar-section xmail-mail-section mt-7 text-sm text-secondary-foreground px-2">Mail</div>
            <nav class="mt-2 space-y-1" aria-label="Mail folders">
                @foreach($mailFolders as $folder)
                    <button class="xmail-folder {{ $folder['active'] ? 'is-active' : '' }} flex w-full items-center gap-3 px-4 text-start"
                            type="button"
                            aria-current="{{ $folder['active'] ? 'page' : 'false' }}">
                        <i class="ki-filled {{ $folder['icon'] }} text-lg"></i>
                        <span class="xmail-sidebar-text grow font-medium">{{ $folder['label'] }}</span>
                        <span class="xmail-sidebar-count text-sm text-secondary-foreground">{{ $folder['count'] }}</span>
                    </button>
                @endforeach
            </nav>

            <div class="xmail-sidebar-section mt-7 flex items-center justify-between px-2">
                <span class="text-sm text-secondary-foreground">Labels</span>
                <button class="xmail-icon-btn size-8" type="button" title="Nuevo label">
                    <i class="ki-filled ki-plus"></i>
                </button>
            </div>

            <nav class="mt-2 space-y-1" aria-label="Labels">
                @foreach($labelFolders as $folder)
                    <button class="xmail-folder flex w-full items-center gap-3 px-4 text-start" type="button">
                        <i class="ki-filled {{ $folder['icon'] }} text-lg"></i>
                        <span class="xmail-sidebar-text grow font-medium">{{ $folder['label'] }}</span>
                        <span class="xmail-sidebar-count text-sm text-secondary-foreground">{{ $folder['count'] }}</span>
                    </button>
                @endforeach
            </nav>

            <div class="xmail-sidebar-section mt-7 text-sm text-secondary-foreground px-2">Direct Messages</div>
            <div class="xmail-direct-stack mt-3 space-y-3 px-3" aria-label="Direct messages">
                @foreach($directMessages as $person)
                    <button class="xmail-direct-person flex w-full items-center gap-3 text-start" type="button" title="{{ $person['name'] }}">
                        <span class="relative">
                            <img class="size-8 rounded-full object-cover" src="{{ asset('assets/media/avatars/' . $person['avatar']) }}" alt="{{ $person['name'] }}">
                            <span class="absolute -bottom-0.5 -right-0.5 size-2.5 rounded-full border-2 border-muted {{ $person['status'] === 'online' ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                        </span>
                        <span class="xmail-sidebar-text text-sm text-mono">{{ $person['name'] }}</span>
                    </button>
                @endforeach
            </div>

            <div class="xmail-sidebar-bottom xmail-bottom-stack mt-auto space-y-1 pb-2">
                <button class="xmail-folder flex w-full items-center gap-3 px-4" type="button" title="Add Teammates">
                    <i class="ki-filled ki-plus text-lg"></i>
                    <span class="xmail-sidebar-text grow font-medium">Add Teammates</span>
                </button>
                <button class="xmail-folder flex w-full items-center gap-3 px-4" type="button" title="Support">
                    <i class="ki-filled ki-support text-lg"></i>
                    <span class="xmail-sidebar-text grow font-medium">Support</span>
                </button>
                <a class="xmail-folder flex w-full items-center gap-3 px-4" href="{{ route('client.emails.index') }}" title="Settings">
                    <i class="ki-filled ki-setting-2 text-lg"></i>
                    <span class="xmail-sidebar-text grow font-medium">Settings</span>
                </a>
                <a class="xmail-folder flex w-full items-center gap-3 px-4" href="{{ route('client.dashboard') }}" title="Feedback">
                    <i class="ki-filled ki-message-text-2 text-lg"></i>
                    <span class="xmail-sidebar-text grow font-medium">Feedback</span>
                </a>
            </div>
        </aside>

        <section class="xmail-panel xmail-list-panel flex flex-col min-w-0" aria-label="Message list">
            <div class="xmail-list-toolbar flex items-center gap-3 p-4">
                <button class="xmail-icon-btn shrink-0" type="button" id="xmail_sidebar_toggle" title="Contraer menu">
                    <i class="ki-filled ki-black-left"></i>
                </button>
                <label class="xmail-search flex grow items-center gap-3 px-4" aria-label="Search messages">
                    <i class="ki-filled ki-magnifier text-xl text-secondary-foreground"></i>
                    <input class="w-full border-0 bg-transparent text-base outline-none placeholder:text-secondary-foreground" id="xmail_filter" type="search" placeholder="Search...">
                </label>
                <div class="xmail-category-wrap shrink-0">
                    <button class="kt-btn kt-btn-ghost gap-2" type="button" id="xmail_category_toggle" aria-haspopup="menu" aria-expanded="false">
                        <span id="xmail_category_label">Categories</span>
                        <i class="ki-filled ki-down text-xs"></i>
                    </button>
                    <div class="xmail-category-menu" id="xmail_category_menu" role="menu">
                        <button class="xmail-category-option is-active" type="button" data-category-label="Categories" role="menuitem">
                            <i class="ki-filled ki-category text-sm"></i>
                            All mail
                        </button>
                        <button class="xmail-category-option" type="button" data-category-label="Unread" role="menuitem">
                            <i class="ki-filled ki-notification-status text-sm"></i>
                            Unread
                        </button>
                        <button class="xmail-category-option" type="button" data-category-label="Attachments" role="menuitem">
                            <i class="ki-filled ki-paper-clip text-sm"></i>
                            Attachments
                        </button>
                        <button class="xmail-category-option" type="button" data-category-label="Starred" role="menuitem">
                            <i class="ki-filled ki-star text-sm"></i>
                            Starred
                        </button>
                    </div>
                </div>
                <button class="xmail-icon-btn shrink-0" type="button" title="Actualizar">
                    <i class="ki-filled ki-arrows-circle"></i>
                </button>
            </div>

            <div class="grow overflow-y-auto px-4 pb-4" id="xmail_message_list">
                <div class="space-y-2" role="list">
                    @foreach($messages as $message)
                        <button class="xmail-message {{ $message['active'] ? 'is-active' : '' }} flex w-full items-center gap-4 px-3 py-4 text-start"
                                type="button"
                                role="listitem"
                                data-mail-item
                                data-from="{{ $message['from'] }}"
                                data-email="{{ $message['email'] }}"
                                data-subject="{{ $message['subject'] }}"
                                data-time="{{ $message['time'] }}"
                                data-body="{{ $message['body'] }}"
                                aria-pressed="{{ $message['active'] ? 'true' : 'false' }}">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white font-semibold" style="color: {{ $message['accent'] }}">
                                {{ $message['avatar'] }}
                            </span>
                            <span class="min-w-0 grow">
                                <span class="flex items-center gap-2">
                                    <span class="text-base font-semibold text-mono truncate">{{ $message['from'] }}</span>
                                    <span class="size-2 rounded-full bg-blue-400"></span>
                                </span>
                                <span class="mt-1 block truncate text-sm text-secondary-foreground">{{ $message['preview'] }}</span>
                            </span>
                            <span class="shrink-0 self-start text-sm text-mono">{{ $message['date'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        <main class="xmail-panel xmail-reader flex flex-col min-w-0" aria-label="Message detail">
            <div class="xmail-reader-toolbar flex items-center justify-between border-b border-border">
                <div class="flex items-center gap-2">
                    <button class="xmail-icon-btn" type="button" id="xmail_reader_back" title="Anterior">
                        <i class="ki-filled ki-left"></i>
                    </button>
                    <button class="xmail-icon-btn" type="button" title="Siguiente">
                        <i class="ki-filled ki-right"></i>
                    </button>
                    <span class="ms-3 rounded-lg bg-muted px-4 py-2 text-sm font-semibold text-mono">Normal</span>
                    <button class="xmail-icon-btn" type="button" title="Vista">
                        <i class="ki-filled ki-eye"></i>
                    </button>
                </div>
                <div class="xmail-reader-actions flex items-center gap-1">
                    <button class="kt-btn kt-btn-light gap-2" type="button" data-compose-action="reply-all">
                        <i class="ki-filled ki-left"></i>
                        Reply all
                    </button>
                    <button class="xmail-icon-btn" type="button" title="Documento"><i class="ki-filled ki-document"></i></button>
                    <button class="xmail-icon-btn" type="button" title="Marcar"><i class="ki-filled ki-flag"></i></button>
                    <button class="xmail-icon-btn" type="button" title="Favorito"><i class="ki-filled ki-star"></i></button>
                    <button class="xmail-icon-btn" type="button" title="Copiar"><i class="ki-filled ki-copy"></i></button>
                    <button class="xmail-icon-btn bg-red-50 text-red-500" type="button" title="Eliminar"><i class="ki-filled ki-trash"></i></button>
                    <button class="xmail-icon-btn" type="button" title="Mas"><i class="ki-filled ki-dots-horizontal"></i></button>
                </div>
            </div>

            <div class="xmail-reader-scroll grow overflow-y-auto">
                <div class="xmail-thread-header border-b border-border">
                    <h1 class="text-2xl font-medium text-mono" id="xmail_reader_subject">{{ $messages[0]['subject'] }}</h1>
                    <div class="mt-4 flex items-center gap-3">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-blue-500 text-white">
                            <i class="ki-filled ki-sms"></i>
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-lg border border-border bg-white px-3 py-1.5 text-sm font-medium">
                            <span class="flex size-5 items-center justify-center rounded-full bg-purple-100 text-purple-600">F</span>
                            <span id="xmail_reader_tag">{{ $messages[0]['from'] }}</span>
                        </span>
                    </div>

                    <div class="mt-7">
                        <h2 class="text-lg font-semibold text-mono">Thread Attachments <span class="text-secondary-foreground">[2]</span></h2>
                        <div class="mt-3 grid gap-3 xl:grid-cols-2">
                            <div class="xmail-attachment flex items-center gap-3 px-4">
                                <i class="ki-filled ki-folder text-xl text-pink-500"></i>
                                <span class="min-w-0 grow truncate text-sm font-medium text-mono">Cover-Letter-2025-10.pdf</span>
                                <span class="text-sm text-secondary-foreground">0.05 MB</span>
                                <button class="xmail-icon-btn size-9 border-s border-border rounded-none" type="button"><i class="ki-filled ki-exit-down"></i></button>
                            </div>
                            <div class="xmail-attachment flex items-center gap-3 px-4">
                                <i class="ki-filled ki-folder text-xl text-pink-500"></i>
                                <span class="min-w-0 grow truncate text-sm font-medium text-mono">Resume-2025-10.pdf</span>
                                <span class="text-sm text-secondary-foreground">0.06 MB</span>
                                <button class="xmail-icon-btn size-9 border-s border-border rounded-none" type="button"><i class="ki-filled ki-exit-down"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <article class="px-6 py-6">
                    <div class="flex items-start gap-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full border border-border bg-white text-purple-600">F</span>
                        <div class="min-w-0 grow">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-mono" id="xmail_reader_from">{{ $messages[0]['from'] }}</span>
                                        <button class="text-sm underline text-secondary-foreground" type="button">Details</button>
                                    </div>
                                    <div class="mt-1 text-sm text-secondary-foreground">To: You</div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-mono" id="xmail_reader_time">{{ $messages[0]['time'] }}</span>
                                    <button class="xmail-icon-btn size-9" type="button"><i class="ki-filled ki-dots-horizontal"></i></button>
                                </div>
                            </div>

                            <div class="xmail-message-card mt-8 rounded-xl bg-muted px-8 py-10">
                                <h3 class="text-xl font-semibold text-mono">Hi Tonny,</h3>
                                <p class="mt-7 text-base leading-7 text-mono" id="xmail_reader_body">
                                    {{ $messages[0]['body'] }}
                                </p>
                                <div class="mt-9 text-center">
                                    <button class="kt-btn kt-btn-dark" type="button">Register Here</button>
                                </div>
                            </div>

                            <div class="xmail-compose-box mt-7" id="xmail_compose_box">
                                <div class="xmail-compose-row">
                                    <span class="text-base font-semibold text-secondary-foreground">To:</span>
                                    <span class="xmail-recipient-chip">
                                        <span class="flex size-5 items-center justify-center rounded-full bg-purple-100 text-purple-600 text-xs">F</span>
                                        <span id="xmail_compose_to">noreply@figma.com</span>
                                        <button class="text-secondary-foreground hover:text-mono" type="button" aria-label="Quitar destinatario">
                                            <i class="ki-filled ki-cross"></i>
                                        </button>
                                    </span>
                                    <div class="ms-auto flex items-center gap-3 text-base font-semibold text-secondary-foreground">
                                        <button class="hover:text-mono" type="button">Cc</button>
                                        <button class="hover:text-mono" type="button">Bcc</button>
                                        <button class="hover:text-mono" type="button" id="xmail_compose_close" title="Cerrar respuesta">
                                            <i class="ki-filled ki-cross text-xl"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="xmail-compose-row">
                                    <span class="text-base font-semibold text-secondary-foreground">Subject:</span>
                                    <input class="w-full border-0 bg-transparent text-base outline-none placeholder:text-secondary-foreground" id="xmail_compose_subject" type="text" placeholder="Enter subject">
                                </div>
                                <textarea class="xmail-compose-area" id="xmail_compose_body" placeholder="Type your message here."></textarea>
                                <div class="xmail-compose-tools">
                                    <button class="kt-btn kt-btn-primary gap-2" type="button">
                                        <i class="ki-filled ki-send"></i>
                                        Send
                                    </button>
                                    <button class="kt-btn kt-btn-light gap-2" type="button">
                                        <i class="ki-filled ki-plus"></i>
                                        Add
                                    </button>
                                    <button class="kt-btn kt-btn-light" type="button">Templates</button>
                                    <button class="xmail-icon-btn border border-border bg-white" type="button" title="Formato">
                                        <i class="ki-filled ki-text"></i>
                                    </button>
                                    <button class="kt-btn kt-btn-light gap-2 ms-auto" type="button">
                                        <i class="ki-filled ki-artificial-intelligence"></i>
                                        Generate
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div class="xmail-reader-footer border-t border-border">
                <div class="grid gap-3 xl:grid-cols-2">
                    <div class="xmail-attachment flex items-center gap-3 px-4">
                        <i class="ki-filled ki-folder text-xl text-pink-500"></i>
                        <span class="min-w-0 grow truncate text-sm font-medium text-mono">Invoice-JBXADX8F-2025-10.pdf</span>
                        <span class="text-sm text-secondary-foreground">0.03 MB</span>
                        <button class="xmail-icon-btn size-9 border-s border-border rounded-none" type="button"><i class="ki-filled ki-exit-down"></i></button>
                    </div>
                    <div class="xmail-attachment flex items-center gap-3 px-4">
                        <i class="ki-filled ki-folder text-xl text-pink-500"></i>
                        <span class="min-w-0 grow truncate text-sm font-medium text-mono">Receipt-2595-6889.pdf</span>
                        <span class="text-sm text-secondary-foreground">0.03 MB</span>
                        <button class="xmail-icon-btn size-9 border-s border-border rounded-none" type="button"><i class="ki-filled ki-exit-down"></i></button>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button class="kt-btn kt-btn-light kt-btn-sm gap-2" type="button" data-compose-action="reply"><i class="ki-filled ki-left"></i> Reply <span class="rounded border border-border px-2 text-xs">r</span></button>
                    <button class="kt-btn kt-btn-light kt-btn-sm gap-2" type="button" data-compose-action="reply-all"><i class="ki-filled ki-double-left"></i> Reply All <span class="rounded border border-border px-2 text-xs">a</span></button>
                    <button class="kt-btn kt-btn-light kt-btn-sm gap-2" type="button" data-compose-action="forward"><i class="ki-filled ki-right"></i> Forward <span class="rounded border border-border px-2 text-xs">f</span></button>
                    <button class="xmail-icon-btn ms-auto border border-border bg-white" type="button" title="Abrir editor" data-compose-action="reply"><i class="ki-filled ki-sms"></i></button>
                </div>
            </div>
        </main>

        <aside class="xmail-rail flex flex-col items-center gap-5 pt-3">
            <button class="xmail-rail-btn is-active" type="button" title="Cuenta"><i class="ki-filled ki-profile-circle text-xl"></i></button>
            <button class="xmail-rail-btn" type="button" title="Analitica"><i class="ki-filled ki-chart-simple text-xl"></i></button>
            <button class="xmail-rail-btn" type="button" title="Configuracion"><i class="ki-filled ki-setting-2 text-xl"></i></button>
            <button class="xmail-rail-btn" type="button" title="Contactos"><i class="ki-filled ki-people text-xl"></i></button>
            <button class="xmail-rail-btn" type="button" title="Seguridad"><i class="ki-filled ki-shield-tick text-xl"></i></button>
            <button class="xmail-rail-btn" type="button" title="Nuevo"><i class="ki-filled ki-plus text-xl"></i></button>
        </aside>
    </div>

    <script src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
<script>
        (() => {
            const mailApp = document.getElementById('xmail_app');
            const sidebarToggle = document.getElementById('xmail_sidebar_toggle');
            const readerBack = document.getElementById('xmail_reader_back');
            const items = Array.from(document.querySelectorAll('[data-mail-item]'));
            const filter = document.getElementById('xmail_filter');
            const subject = document.getElementById('xmail_reader_subject');
            const from = document.getElementById('xmail_reader_from');
            const tag = document.getElementById('xmail_reader_tag');
            const time = document.getElementById('xmail_reader_time');
            const body = document.getElementById('xmail_reader_body');
            const composeBox = document.getElementById('xmail_compose_box');
            const composeClose = document.getElementById('xmail_compose_close');
            const composeTo = document.getElementById('xmail_compose_to');
            const composeSubject = document.getElementById('xmail_compose_subject');
            const composeBody = document.getElementById('xmail_compose_body');
            const composeActions = Array.from(document.querySelectorAll('[data-compose-action]'));
            const categoryToggle = document.getElementById('xmail_category_toggle');
            const categoryMenu = document.getElementById('xmail_category_menu');
            const categoryLabel = document.getElementById('xmail_category_label');
            const categoryOptions = Array.from(document.querySelectorAll('[data-category-label]'));
            const isMobile = () => window.matchMedia('(max-width: 760px)').matches;
            const isOverlayReader = () => window.matchMedia('(max-width: 1024px)').matches;

            if (mailApp && localStorage.getItem('xmail-sidebar-collapsed') === '1') {
                mailApp.classList.add('is-sidebar-collapsed');
            }

            sidebarToggle?.addEventListener('click', () => {
                if (!mailApp) {
                    return;
                }

                if (isMobile()) {
                    mailApp.classList.toggle('is-mobile-sidebar-open');
                    return;
                }

                mailApp.classList.toggle('is-sidebar-collapsed');
                localStorage.setItem('xmail-sidebar-collapsed', mailApp.classList.contains('is-sidebar-collapsed') ? '1' : '0');
            });

            document.addEventListener('click', (event) => {
                const target = event.target;

                if (categoryMenu?.classList.contains('is-open') && !categoryMenu.contains(target) && !categoryToggle?.contains(target)) {
                    categoryMenu.classList.remove('is-open');
                    categoryToggle?.setAttribute('aria-expanded', 'false');
                }

                if (!mailApp || !isMobile() || !mailApp.classList.contains('is-mobile-sidebar-open')) {
                    return;
                }

                const sidebar = mailApp.querySelector('.xmail-sidebar');
                if (sidebar?.contains(target) || sidebarToggle?.contains(target)) {
                    return;
                }

                mailApp.classList.remove('is-mobile-sidebar-open');
            });

            categoryToggle?.addEventListener('click', (event) => {
                event.stopPropagation();
                const isOpen = categoryMenu?.classList.toggle('is-open') ?? false;
                categoryToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            categoryOptions.forEach((option) => {
                option.addEventListener('click', () => {
                    categoryOptions.forEach((item) => item.classList.remove('is-active'));
                    option.classList.add('is-active');
                    if (categoryLabel) {
                        categoryLabel.textContent = option.dataset.categoryLabel || 'Categories';
                    }
                    categoryMenu?.classList.remove('is-open');
                    categoryToggle?.setAttribute('aria-expanded', 'false');
                });
            });

            readerBack?.addEventListener('click', () => {
                if (mailApp && isOverlayReader() && mailApp.classList.contains('is-reader-open')) {
                    mailApp.classList.remove('is-reader-open');
                }
            });

            const openComposer = (mode = 'reply') => {
                const activeItem = document.querySelector('[data-mail-item].is-active');
                const currentFrom = activeItem?.dataset.from || 'Figma';
                const currentEmail = activeItem?.dataset.email || 'noreply@figma.com';
                const currentSubject = activeItem?.dataset.subject || subject?.textContent || '';
                const prefix = mode === 'forward' ? 'Fwd:' : 'Re:';

                if (composeTo) {
                    composeTo.textContent = mode === 'forward' ? '' : currentEmail;
                }

                if (composeSubject) {
                    composeSubject.value = currentSubject.startsWith(prefix) ? currentSubject : `${prefix} ${currentSubject}`;
                }

                if (composeBody) {
                    composeBody.value = mode === 'forward'
                        ? `\n\n---------- Forwarded message ----------\nFrom: ${currentFrom} <${currentEmail}>\nSubject: ${currentSubject}\n`
                        : '';
                }

                composeBox?.classList.add('is-open');
                mailApp?.classList.add('is-compose-open');
                composeBox?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                setTimeout(() => composeBody?.focus(), 180);
            };

            composeActions.forEach((button) => {
                button.addEventListener('click', () => openComposer(button.dataset.composeAction || 'reply'));
            });

            composeClose?.addEventListener('click', () => {
                composeBox?.classList.remove('is-open');
                mailApp?.classList.remove('is-compose-open');
            });

            items.forEach((item) => {
                item.addEventListener('click', () => {
                    items.forEach((mail) => {
                        mail.classList.remove('is-active');
                        mail.setAttribute('aria-pressed', 'false');
                    });
                    item.classList.add('is-active');
                    item.setAttribute('aria-pressed', 'true');
                    subject.textContent = item.dataset.subject || '';
                    from.textContent = item.dataset.from || '';
                    tag.textContent = item.dataset.from || '';
                    time.textContent = item.dataset.time || '';
                    body.textContent = item.dataset.body || '';
                    composeBox?.classList.remove('is-open');
                    mailApp?.classList.remove('is-compose-open');

                    if (mailApp && isOverlayReader()) {
                        mailApp.classList.add('is-reader-open');
                    }
                });
            });

            filter?.addEventListener('input', () => {
                const query = filter.value.trim().toLowerCase();
                items.forEach((item) => {
                    const haystack = `${item.dataset.from} ${item.dataset.email} ${item.dataset.subject}`.toLowerCase();
                    item.classList.toggle('hidden', query !== '' && !haystack.includes(query));
                });
            });
        })();
    </script>
</body>

</html>

