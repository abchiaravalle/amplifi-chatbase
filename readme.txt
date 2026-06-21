=== Amplifi Chatbase ===
Contributors: amplifistudio
Tags: chatbase, chatbot, ai, chat, imessage
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0-alpha.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An elegant, iMessage-style front end for Chatbase. Hero prompt box, inline chat, and a glassy popup modal. Customizable colors, auto light/dark.

== Description ==

Amplifi Chatbase gives your Chatbase assistant a clean, modern, iMessage-inspired interface you can color-match to any brand and place exactly where you want it. Your Chatbase secret key never reaches the browser, it is proxied through a secure WordPress REST endpoint.

Three placements, one plugin:

* **Hero prompt box** — an input whose placeholder types and backspaces through your suggested questions; submit at any moment to open the modal with that question answered.
* **Inline chat window** — a fixed-height, always-open chat that lives in the page.
* **Popup modal** — a full-screen blurred glass backdrop with bubbles floating up from the bottom.

Highlights:

* iMessage aesthetic: soft bubbles, typing dots, subtle pop-in animation, smooth auto-scroll.
* Real streaming responses when supported, with a fast fake-typing fallback.
* Fully customizable colors and auto light/dark (or forced).
* Inherits your site font; font-sizes forced inline so your theme cannot override them.
* Per-shortcode overrides, configurable bot name/icon, clickable suggested questions.
* Conversation persistence across visits, optional sounds (off by default), optional floating bubble.
* Mobile full-screen modal, accessibility, and reduced-motion support.
* Hardened proxy: nonce-checked, per-IP rate limited, sanitized.

== Installation ==

1. Upload the `amplifi-chatbase` folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload.
2. Activate the plugin.
3. Go to Settings → Amplifi Chatbase and enter your Chatbase API Key and Chatbot ID.
4. Customize colors and behavior, then add a shortcode to any page.

== Frequently Asked Questions ==

= Is my API key safe? =
Yes. The key is stored server-side and every message is proxied through a nonce-verified, rate-limited WordPress REST endpoint. It is never sent to the browser.

= What shortcodes are available? =
`[amplifi_chat_hero]`, `[amplifi_chat_inline]`, and `[amplifi_chat_modal]`. Each accepts attributes to override global settings.

= Does it support streaming? =
Yes, when your Chatbase plan/endpoint supports it. Otherwise it falls back to a fast typing reveal.

== Changelog ==

= 1.0.0-alpha.1 =
* First public **alpha**. Hero, inline, and modal shortcodes; secure proxy; full theming; auto light/dark; persistence; sound; accessibility.
* **Alpha software**: provided "as is", with **absolutely no warranty** of any kind. Not yet recommended for production. APIs, options, and markup may change before a stable release.

== Upgrade Notice ==

= 1.0.0-alpha.1 =
First public alpha. No warranty; not production-ready. Expect breaking changes before 1.0.0.
