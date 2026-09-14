# How-To Guide: Bringing Public Docket to Your Organization

This guide is for anyone on a neighborhood commission, civic association, or
similar local government body who wants to try this out — no coding
background assumed. It walks through the three pieces that work together:

1. **Public Docket** — the plugin that runs your public comment process
2. **AI Engine** — the chatbot residents can ask questions
3. **Agenda Ingestion** *(coming soon)* — upload your existing agendas and
   minutes and have the site build itself from them

---

## Before you start: try it with zero setup

You don't need a website, hosting, or any technical setup to see this
running. Open this link in your browser:

**[Try the live demo →](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/mrwhinna-mw/hearback_wordpress/main/blueprint.json)**

This runs a complete, real WordPress site *inside your browser tab* — it's
free, requires no account, and nothing you do there affects any real
website. It resets when you close the tab, so it's purely for exploring:
click around the Docket page, submit a test comment, look at the Agendas
page. When your organization is ready to actually use this, you'd install it
on a real WordPress site instead (see below).

---

## What each piece does for you

### Public Docket

This is the actual public-comment system. For each item your commission is
deciding on:

- Residents can read a plain-language question and submit a comment (name
  and email optional, comment required).
- Your staff sort comments into themes and publish a summary of "what we
  heard," with anonymized quotes from residents who agreed to be quoted.
- Once a decision is made, you post the outcome and — importantly — what's
  still actionable ("BZA hearing is September 16" isn't the end of the
  story for a resident who wants to keep engaging).
- Not every posting needs public comment — purely informational items can
  skip straight to showing an outcome.

### AI Engine (the chatbot)

A general-purpose AI chatbot plugin. In this demo it's placed on the Agendas
page so residents can ask things like "what happened with the liquor license
on H Street?" It needs an API key from an AI provider (OpenAI, Google
Gemini, Anthropic, and others are supported) — more on that below.

### Agenda Ingestion *(not built yet — see the README's Future Vision
section for the technical plan)*

The long-term goal: instead of your staff manually typing every agenda item
into the site, you'd upload the PDF agenda (or minutes, or a meeting
transcript) you already produce, and the plugin would draft the docket items
and agenda page content for you — leaving a human to review and approve
before anything goes public. This guide will be updated with install/setup
steps once that piece exists.

---

## Installing on a real WordPress site

If you already have a WordPress site (self-hosted, or via a host like
WP Engine, Bluehost, etc.):

1. **Install Public Docket**
   - Download or clone this repo, then upload the `public-docket/` folder to
     `wp-content/plugins/` on your site (or zip it and use *Plugins → Add
     New → Upload Plugin*).
   - Activate it from your WordPress admin's Plugins screen.
   - Go to **Public Docket → Settings** and review the outcome options
     (Proceed / Do not proceed / Not yet, by default — fully editable to
     match how your organization actually talks).
   - Go to **Public Docket → Add New Docket Item** to create your first
     item, or use the **Workspace** screen to manage everything about an
     item — details, themes, submissions, and publishing — from one place.
   - Visit `/docket/` on your site to see the public listing.

2. **Install a theme (optional)**
   - The `anc6a-demo-theme/` folder is a minimal example theme. You likely
     want your organization's *own* theme/branding rather than this one —
     Public Docket's content will render inside whatever theme you're
     already using. Use the demo theme only as a reference for what
     header/nav markup around the plugin's content can look like.

3. **Install AI Engine (if you want the chatbot)**
   - Install AI Engine from the WordPress Plugin directory (*Plugins → Add
     New*, search "AI Engine").
   - Get an API key from an AI provider. Options and what they cost:
     - **OpenAI** — does not offer a meaningful free ongoing tier; you'll
       need to add billing.
     - **Google Gemini** — currently has a free tier for API access, and is
       the easiest no-cost way to try this.
     - **Anthropic (Claude)** and others are also supported.
   - In your WordPress admin, go to **Meow Apps → AI Engine → Settings →
     AI**, and paste your key into the matching provider card.
   - Go to the **Chatbots** tab, and confirm the "Default" chatbot is set to
     a valid, currently-supported model for your provider — *provider model
     names and availability change often*; if the chatbot responds with a
     generic "I couldn't produce a response" error, that's usually a sign
     the selected model needs updating, not a problem with your key.
   - Add the chatbot to a page using its shortcode (shown at the top of the
     Chatbots tab, e.g. `[mwai_chatbot id="default"]`) — we recommend
     placing it on whichever page has the context you want it answering
     about, rather than site-wide, so its answers stay relevant.

4. **Populate your Agendas page**
   - Until the ingestion plugin exists, this is a manual step: create a page
     with your meeting's real agenda content (date, time, each item, case
     numbers, recommendations). Keep to what's actually in your published
     agenda or minutes — don't have the chatbot or a person invent details
     that aren't in the source record.

---

## A word on accuracy

Every piece of this system was built around one rule: **never invent civic
content.** Real resident comments are the only comments that appear as
"what we heard." Real case numbers, real recommendations, real dates. Where
a demo needed placeholder content (to show what a feature looks like without
real data available yet), it says so explicitly rather than presenting
invented text as if a resident said it. If you adopt this for your own
organization, we'd encourage keeping that same discipline — especially once
AI-assisted agenda ingestion is involved, where an approval step by a human
before anything publishes isn't optional.

---

## Questions / feedback

This is an early-stage demo built to show a neighborhood commission (and
other similar organizations) what's possible before committing to a full
build. If you're evaluating this for your own organization and hit
something confusing or broken, that's useful information — please pass it
along to whoever shared this guide with you.
