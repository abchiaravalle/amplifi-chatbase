# Amplifi Chatbase

An elegant, **iMessage-style alternate front end for [Chatbase](https://www.chatbase.co)**. Drop a beautiful AI assistant onto any WordPress site in three flavors, fully themeable, with your API key safely hidden on the server.

> Polished. Subtle. Suited to any website.

---

> ⚠️ **Alpha software — v1.0.0-alpha.1.** This is an early public alpha released for testing and feedback. It is provided **"as is", with ABSOLUTELY NO WARRANTY** of any kind, express or implied (see [LICENSE](LICENSE), GPLv2 §11–12). Not recommended for production use. Options, REST routes, and markup may change without notice before a stable `1.0.0`. Use at your own risk.

---

## Why

Chatbase's default widget is fine, but it's not *yours*. Amplifi Chatbase gives you a clean, modern, iMessage-inspired chat experience you can color-match to any brand, place exactly where you want it, and ship with confidence. The Chatbase secret key never touches the browser, it is proxied through a WordPress REST endpoint.

## Features

- **Three placements, one plugin**
  - **Hero prompt box** — a pretty input whose placeholder *types and backspaces* through your suggested questions. Submit at any moment and it opens the glassy modal with that question already answered (just like indicodata.ai / mednition.com heroes).
  - **Inline chat window** — a fixed-height, always-open iMessage-style window that lives in the page flow.
  - **Popup modal** — a full-screen blurred glass backdrop with bubbles floating up from the bottom. Close by clicking outside the panel or the X.
- **iMessage aesthetic** — soft gradient user bubbles, gray bot bubbles, typing dots, pop-in animation, smooth auto-scroll (that politely steps aside when the visitor scrolls up).
- **Real streaming** when Chatbase supports it, with a fast fake-typing fallback otherwise.
- **Fully customizable colors** — accent, user/bot bubbles, text, backgrounds, modal tint.
- **Auto light/dark** following the device, or force light/dark from the admin.
- **Inherits your site font** for body text, while **font-sizes are forced inline with `!important`** so your theme can never break the layout.
- **Per-shortcode overrides** for accent, name, welcome, placeholder, questions, icon, theme, and more.
- **Configurable bot identity** — name, custom icon, or no icon at all.
- **Clickable suggested questions** defined right in the shortcode.
- **Conversation persistence** across visits via `localStorage`.
- **Sound off by default** with a one-tap unmute control.
- **Mobile-first** — the modal goes full screen on phones.
- **Accessible** — ARIA roles, keyboard support, focus management, and `prefers-reduced-motion` respect.
- **Hardened proxy** — nonce-checked REST endpoint, per-IP rate limiting, input sanitization.

## Installation

1. Upload the `amplifi-chatbase` folder to `/wp-content/plugins/`, or install the zip via **Plugins → Add New → Upload**.
2. Activate **Amplifi Chatbase**.
3. Go to **Settings → Amplifi Chatbase** and enter your **Chatbase API Key** and **Chatbot ID**.
4. Tune colors, theme, and behavior. Watch the live preview update.
5. Drop a shortcode on any page.

## Shortcodes

**Hero prompt box**
```
[amplifi_chat_hero questions="What do you offer?|How much does it cost?|Do you integrate with Salesforce?"]
```

**Inline chat window**
```
[amplifi_chat_inline height="520px"]
```

**Popup modal trigger**
```
[amplifi_chat_modal open_text="Chat with us"]
```

### Shortcode attributes

| Attribute | Applies to | Description |
|-----------|-----------|-------------|
| `accent` | all | Hex accent color override, e.g. `#0A84FF`. |
| `name` | all | Override the bot name. |
| `welcome` | all | Override the first message. |
| `placeholder` | all | Override the input placeholder. |
| `questions` | hero, inline | Pipe-separated suggested questions (`A|B|C`). |
| `icon` | all | `show`, `hide`, or an image URL. |
| `theme` | all | `auto`, `light`, or `dark`. |
| `height` | inline | Window height, e.g. `520px`, `60vh`. |
| `open_text` | modal | Trigger button label. |

A floating chat bubble (optional, toggled in settings) opens the modal on every page.

## How the key stays safe

The browser only ever talks to `/wp-json/amplifi-chatbase/v1/chat`. That endpoint verifies a WordPress nonce, rate-limits per IP, sanitizes the message history, then calls Chatbase server-side with your `Authorization: Bearer` key. The key is never localized into JS, never exposed in markup.

## Development

```
amplifi-chatbase/
├── amplifi-chatbase.php           # Bootstrap
├── includes/
│   ├── class-amplifi-chatbase.php           # Loader / orchestrator
│   ├── class-amplifi-chatbase-settings.php  # Options + admin page
│   ├── class-amplifi-chatbase-assets.php    # Enqueue + dynamic CSS vars
│   ├── class-amplifi-chatbase-shortcodes.php# 3 shortcodes + markup
│   └── class-amplifi-chatbase-rest.php      # Secure proxy (stream + buffered)
├── assets/
│   ├── css/chat.css               # iMessage UI + glass modal + motion
│   └── js/chat.js                 # Hero typing, chat engine, modal, sound
├── admin/
│   ├── views/settings-page.php    # Settings UI + live preview
│   ├── css/admin.css
│   └── js/admin.js                # Color pickers, live preview, media picker
├── languages/amplifi-chatbase.pot
├── uninstall.php
├── readme.txt                     # WordPress.org readme
└── LICENSE
```

## License

GPL-2.0-or-later. This is free and open source software. See [LICENSE](LICENSE).

Built by [Amplifi](https://amplifi.studio).
