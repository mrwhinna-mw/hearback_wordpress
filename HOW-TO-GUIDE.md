# How-To Guide: Bringing Public Docket to Your Organization

This guide is for anyone on a neighborhood commission, civic association, or
similar local government body who wants to try this out — no coding
background assumed. It walks through the three pieces that work together:

1. **Public Docket** — runs your public comment process
2. **Docket Ingest** — upload the agendas and minutes you already produce,
   and get draft docket items to review instead of typing them in by hand
3. **AI Engine** — a free WordPress plugin that connects your site to an AI
   provider. Docket Ingest needs it to read your documents, and it also
   powers an optional chatbot residents can ask questions

---

## Before you start: try it with zero setup

You don't need a website, hosting, or any technical setup to see this
running. Open this link in your browser:

**[Try the live demo →](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/mrwhinna-mw/hearback_wordpress/main/blueprint.json)**

This runs a complete, real WordPress site *inside your browser tab* — it's
free, requires no account, and nothing you do there affects any real
website. Click around the Docket page, submit a test comment, and look at the
Agendas page.

Everything you do in the demo disappears when you close the tab or leave the
page. If you lose it by accident, the **Playgrounds** button in the toolbar
at the bottom can restore a recent session.

**Trying document ingestion in the demo** needs an AI key (see "Getting an AI
key" below). Once you have one, follow step 3 of the install instructions
inside the demo — it's already set up for Gemini. You can test with a real
ANC 6A agenda: download
[anc6a-2026-09-10-agenda.txt](https://raw.githubusercontent.com/mrwhinna-mw/hearback_wordpress/main/test-fixtures/anc6a-2026-09-10-agenda.txt)
and upload it. There's also a
[set of four real agendas](https://github.com/mrwhinna-mw/hearback_wordpress/tree/main/test-fixtures/case-tracking)
that follow the same cases across several meetings, with notes on what to
look for.

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

### Docket Ingest

Instead of retyping every agenda item into the site, you upload the agenda,
minutes, or transcript you already produce. Docket Ingest has AI read it and
pull out each item residents could comment on — the topic, any case or
license number, the address, and the committee's recommendation.

**Nothing is published automatically.** You get a review screen listing
every item it found, each with an exact quote from your document so you can
check it against the original. Items already on your docket (matched by case
number) are flagged so you don't create duplicates. You pick which ones to
keep, they're saved as drafts, and a person approves each one before it goes
public.

What it can't do yet:

- **PDF files.** For now, open the PDF, copy its text, and save it as a .txt
  or Word (.docx) file. Old-style .doc Word files need the same treatment.
- **Build your Agendas page.** It creates docket items only; an Agendas page
  is still a manual step (see step 5 below).

### AI Engine and the chatbot

AI Engine is the bridge between your site and an AI provider. Docket Ingest
uses it to read documents. It also offers a chatbot — in the demo it's placed
on the Agendas page so residents can ask things like "what happened with the
liquor license on H Street?" The chatbot is optional; you can use Docket
Ingest without ever putting a chatbot on your site.

---

## Getting an AI key

Both Docket Ingest and the chatbot need an API key from an AI provider:

- **Google Gemini** — currently has a free tier, and is the easiest no-cost
  way to try this. Free keys can be slowed or paused when Google is busy.
- **OpenAI** — does not offer a meaningful free ongoing tier; you'll need to
  add billing.
- **Anthropic (Claude)** and others are also supported.

Treat the key like a password: never put it in a document, email, or public
file.

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

2. **Install AI Engine**
   - Install it from the WordPress Plugin directory (*Plugins → Add New*,
     search "AI Engine").
   - Go to **Meow Apps → AI Engine → Settings → AI**, and paste your key
     into the matching provider card.
   - **If you're using Gemini:** on that same screen, in the **General**
     column, tick **"Use Standard API."** Without it, Gemini returns empty
     replies. (The demo turns this on for you; a real site doesn't.)

3. **Install and use Docket Ingest**
   - Upload the `docket-ingest/` folder to `wp-content/plugins/` the same way
     as Public Docket, and activate it. It needs Public Docket and AI Engine
     active, and will tell you if either is missing.
   - Go to **Public Docket → Ingest Document** and choose:
     - **AI provider** — the connection you set up in AI Engine, e.g.
       "Gemini (Google Gemini)". Ignore any marked "no API key added"; AI
       Engine creates an empty OpenAI one on its own.
     - **Model** — pick one from the list. Models marked "(always latest)"
       follow your provider's newest release, so they're the least likely to
       be retired. If the one you want isn't listed, choose **Other** and type
       its name.
   - Choose your document and click **Analyze Document**.
   - On the review screen, read each item. **Check the "Drafted question"
     especially** — it's the only part the AI writes itself rather than
     copying from your document. Untick anything you don't want, then click
     **Create Selected as Pending**.
   - Open **Public Docket → Workspace** to edit and publish each draft. Each
     one has an **"Ingested From Document"** panel showing the exact quote it
     came from.

4. **Add the chatbot (optional)**
   - In AI Engine, go to the **Chatbots** tab and confirm the "Default"
     chatbot is set to a current model for your provider — its default can
     name a model that doesn't exist or has been retired.
   - Add the chatbot to a page using its shortcode (shown at the top of the
     Chatbots tab, e.g. `[mwai_chatbot id="default"]`). We recommend placing
     it on whichever page has the context you want it answering about,
     rather than site-wide, so its answers stay relevant.

5. **Populate your Agendas page**
   - Create a page with your meeting's real agenda content (date, time, each
     item, case numbers, recommendations). Keep to what's actually in your
     published agenda or minutes — don't have the chatbot or a person invent
     details that aren't in the source record.

6. **Install a theme (optional)**
   - The `anc6a-demo-theme/` folder is a minimal example theme. You likely
     want your organization's *own* theme/branding rather than this one —
     Public Docket's content will render inside whatever theme you're
     already using. Use the demo theme only as a reference for what
     header/nav markup around the plugin's content can look like.

---

## When something goes wrong

These are the errors we actually ran into while building this, and what
they mean:

| What you see | What it means | What to do |
|---|---|---|
| "The model 'gpt-…' is not available" (while using Gemini) | The AI provider dropdown is set to AI Engine's empty OpenAI connection | Choose your own provider in the AI provider dropdown |
| "This model is no longer available to new users…" | Your provider retired that model | Switch to the replacement named in the message |
| "This model is currently experiencing high demand" | Your provider is busy — common on free keys | Wait a minute and retry, or try a "lite" model |
| "The AI returned an empty reply" | Usually Gemini without "Use Standard API" turned on | Tick it in AI Engine → Settings → AI → General |
| The chatbot says "Sorry, I couldn't produce a response" | Usually the chatbot's model setting is invalid | Check the model in AI Engine's Chatbots tab, and the Gemini setting above |

---

## A word on accuracy

Every piece of this system was built around one rule: **never invent civic
content.** Real resident comments are the only comments that appear as
"what we heard." Real case numbers, real recommendations, real dates. Where
a demo needed placeholder content (to show what a feature looks like without
real data available yet), it says so explicitly rather than presenting
invented text as if a resident said it.

Docket Ingest is built the same way: every item comes with an exact quote
from your document, the one AI-written field is labeled as such, and nothing
reaches the public without a person approving it. If you adopt this, please
keep that approval step real — read the quotes, don't just click through.

---

## Questions / feedback

This is an early-stage demo built to show a neighborhood commission (and
other similar organizations) what's possible before committing to a full
build. If you're evaluating this for your own organization and hit
something confusing or broken, that's useful information — please pass it
along to whoever shared this guide with you.
