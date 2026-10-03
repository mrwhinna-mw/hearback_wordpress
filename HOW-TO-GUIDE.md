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
key" below). Once you have one, the
[illustrated walkthrough](#walkthrough-try-the-whole-thing-in-the-demo) takes
you through it screen by screen. You can test with a real
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
check it against the original. You pick which ones to keep, they're saved as
drafts, and a person approves each one before it goes public.

It also **follows a case across meetings.** When a document covers something
already on your docket — matched by case number, however it's written — you
don't get a duplicate. Instead the review screen shows what that document
*adds*: "BZA hearing postponed to September 16," say, with the sentence it
came from. Tick it and a dated line is added to that item, and you can set
its "Next step" at the same time. If the new document contradicts what's
recorded, both versions are shown and the row is left unticked for you to
decide.

One thing it deliberately won't do is record an outcome. A recommendation on
an agenda is a proposed motion, not a decision — the vote happens at the
meeting — so outcomes stay a human's job.

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

## Walkthrough: try the whole thing in the demo

About fifteen minutes, start to finish. You need the free Gemini key from
above. Nothing here touches a real website — everything runs inside your
browser tab and disappears when you close it.

### Part 1 — Open the demo

**1. Open the project page on GitHub.**

![The project page on GitHub](docs/images/step-01-open-repo.png)

**2. Click the `playground.wordpress.net` link near the top of that page.**
A complete WordPress site builds itself inside your browser. Give it about
thirty seconds — you'll land on the Public Docket page with three example
items.

![Clicking the demo link in the README](docs/images/step-02-launch-demo.png)

**3. Click the address box in the dark toolbar at the bottom.** This is the
demo's own address bar, not your browser's.

![The demo's address box in the bottom toolbar](docs/images/step-03-address-bar.png)

**4. Choose "Dashboard" from the list that appears.** That takes you behind
the scenes, to the WordPress admin area where staff work.

![Choosing Dashboard from the menu](docs/images/step-04-dashboard.png)

### Part 2 — Connect your AI key

**5. In the left-hand menu, click "Meow Apps", then "AI Engine".** Meow Apps
is the company that makes the AI Engine plugin, which is the bridge between
this site and Google's AI.

![The AI Engine link in the sidebar](docs/images/step-05-ai-engine.png)

**6. Click the "Settings" tab, then the "AI" tab beneath it.**

![The Settings tab in AI Engine](docs/images/step-06-settings-tab.png)

**7. Find the box headed "Environments for AI" and click the Name field.**
An "environment" is just one connection to one AI company.

![The Name field under Environments for AI](docs/images/step-07-name-field.png)

**8. Type `Gemini`** — this is only a label, so you can recognize it later.

**9. Open the "Type" dropdown and choose "Google".** This tells it which
company's AI you're connecting to.

![The Type dropdown](docs/images/step-10-type-dropdown.png)

![Choosing Google](docs/images/step-11-choose-google.png)

**10. Click the "API Key" field and paste your Gemini key.** It shows as
dots, which is normal — it's hidden on purpose.

![The API Key field](docs/images/step-12-api-key-field.png)

**11. Click "Refresh Models".** This asks Google which AI models your key is
allowed to use, and fills in the list. Without this the model menus stay
empty.

![The Refresh Models button](docs/images/step-14-refresh-models.png)

**12. Scroll down to "Default Environments for AI" and open the Model
dropdown.**

![The default model dropdown](docs/images/step-15-default-model.png)

**13. Choose a "Flash" or "Flash-Lite" model.** These are the quick,
inexpensive ones, and they handle reading documents perfectly well.

![Choosing Gemini Flash-Lite](docs/images/step-16-choose-model.png)

### Part 3 — Upload a meeting document

**14. In the left menu, click "Public Docket", then "Ingest Document".**

![The Ingest Document menu item](docs/images/step-17-ingest-menu.png)

**15. Click "Choose File" and pick a meeting agenda.** If you don't have one
handy, download a real ANC 6A agenda from the
[test documents](https://github.com/mrwhinna-mw/hearback_wordpress/tree/main/test-fixtures/case-tracking)
first. Plain text (`.txt`) and Word (`.docx`) files work; PDFs don't yet, so
open the PDF, copy the text, and save it as a text file.

![The Choose File button](docs/images/step-18-choose-file.png)

**16. Check the AI provider and Model boxes below.** They should already say
Gemini and the model you picked earlier.

![The provider and model dropdowns](docs/images/step-20-pick-model.png)

**17. Click "Analyze Document" and wait a few seconds.** The AI is reading
the document now. Nothing has been saved to the site yet.

![The Analyze Document button](docs/images/step-22-analyze.png)

### Part 4 — Check what it found

This screen is a proposal, not a result. Nothing is saved until you click the
button at the bottom, and nothing becomes public even then.

**18. Read the "Updates to items already on the docket" section first.**
These are cases the site already knows about, where this document adds
something new — a hearing being rescheduled, for instance. The quote on the
right is copied word for word from your document, so you can check it.

![An update with its Next step checkbox](docs/images/step-23-next-step-tick.png)

**19. Tick the updates you want to apply.** If a box is already ticked and
mentions replacing something, read it carefully first — that means a person
had already written that field by hand.

![Ticking an update to apply](docs/images/step-24-apply-update.png)

**20. Then look at "New items".** These are matters the site hasn't seen
before. Each shows a "Drafted question" — the one piece of text the AI wrote
itself rather than copied, so give it a read. Untick anything you don't want.

![Ticking a new item](docs/images/step-25-tick-new-item.png)

**21. Click "Apply Selected".** The new items are saved as drafts and the
updates are added. Still nothing is public.

![The Apply Selected button](docs/images/step-26-apply-selected.png)

### Part 5 — Review and publish

**22. Click "Review in Workspace".** The Workspace is where you check and
finish an item before residents see it.

![The Review in Workspace button](docs/images/step-27-review-workspace.png)

**23. Decide whether this item should collect public comments.** Leave it on
for anything you want feedback on; turn it off for notices that are purely
informational.

![The Collect public comments checkbox](docs/images/step-29-collect-comments.png)

**24. Leave "Outcome" alone until a decision has actually been made.** An
agenda only ever contains a *proposed* motion — the vote happens at the
meeting. Setting an outcome early would tell residents something was decided
when it wasn't.

![The Outcome dropdown](docs/images/step-31-outcome.png)

**25. When the item looks right, tick "Make this item public when saving".**
Until you do, it stays a draft that only staff can see.

![The make public checkbox](docs/images/step-32-make-public.png)

**26. Click "Save everything".** That's it — the item is now live on the
public docket page.

![The Save everything button](docs/images/step-33-save-everything.png)

**27. Use the dropdown at the top to switch to the next item** and repeat.

![The item switcher at the top of the Workspace](docs/images/step-34-item-switcher.png)

To publish several at once instead, go to **All Docket Items**, tick the ones
you want, and choose **Bulk actions → Edit → Status: Published**.

---

## Installing on a real WordPress site

If you already have a WordPress site (self-hosted, or via a host like
WP Engine, Bluehost, etc.):

1. **Install Public Docket**
   - Download
     [public-docket.zip](https://github.com/mrwhinna-mw/hearback_wordpress/releases/latest/download/public-docket.zip)
     from the latest release.
   - In your WordPress admin, go to *Plugins → Add New → Upload Plugin*,
     choose the file, and click Install Now.
   - Activate it.
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
   - Download
     [docket-ingest.zip](https://github.com/mrwhinna-mw/hearback_wordpress/releases/latest/download/docket-ingest.zip)
     and install it the same way. It needs Public Docket and AI Engine
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
   - Optionally use **Extra instructions** to tell the AI about your own
     conventions (for example, "our case numbers look like ZC-2026-14"). It's
     remembered between uploads, and can't override the rules that stop the
     AI inventing details.
   - Open **Public Docket → Workspace** to check each draft. When it looks
     right, tick **"Make this item public when saving"** and click Save
     everything — that publishes it without going near the post editor. Each
     draft has an **"Ingested From Document"** panel showing the exact quote
     it came from.
   - To publish several at once, go to **All Docket Items**, tick them, and
     choose **Bulk actions → Edit → Status: Published**.

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
