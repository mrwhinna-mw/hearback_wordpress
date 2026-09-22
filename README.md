# Public Docket for ANC6A

A WordPress plugin and live demo built for Advisory Neighborhood Commission 6A
(Capitol Hill, Washington DC) to close a specific gap in how local government
bodies handle public input: residents submit comments on an agenda item, and
the only thing that ever comes back is minutes almost nobody reads. Public
Docket gives every open item a public comment period, a themed synthesis of
what residents said, and a dated outcome with a concrete next step. A
companion plugin, Docket Ingest, drafts those items from the agendas and
minutes a commission already produces, and an AI chatbot lets residents ask
questions about past meetings.

This repo is a **working demo**, not yet a production deployment. It exists to
show ANC6A (and other neighborhood commissions) what this could look like
before committing engineering time to build it for real.

**Try it live:** [playground.wordpress.net/?blueprint-url=...](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/mrwhinna-mw/hearback_wordpress/main/blueprint.json) — runs entirely in your browser via [WordPress Playground](https://wordpress.github.io/wordpress-playground/), no hosting or install required.

---

## Vision: from meeting documents to a public docket

### The problem

A commission meets monthly, publishes an agenda and, later, minutes or a
recording. Turning that into public docket items by hand — retyping case
numbers, addresses, and recommendation text into WordPress — doesn't scale,
and it's exactly the kind of tedious step that gets skipped.

### Where it stands

A first version, **Docket Ingest**, is built and running in the demo (details
under [What's built today](#whats-built-today)). An admin uploads an agenda,
AI Engine extracts the commentable items, and they land as **pending** docket
items for a human to review and approve. It has been verified end to end
against the real September 10, 2026 ANC6A agenda, which is included in
`test-fixtures/` so anyone can repeat the test.

How the pieces fit:

1. **Upload** an agenda, minutes document, or transcript. *Built for .txt,
   .md, and .docx; PDF is on the roadmap.*
2. **Extract** each commentable item — topic, case/license number, address,
   and recommendation — using whichever AI provider the site has configured
   in AI Engine. *Built.*
3. **Draft, don't publish.** Every item is created as a Public Docket item
   with status **Pending** and `_hb_source = 'scraped'`. Nothing reaches the
   public site without a human approving it. *Built.*
4. **Match, don't duplicate.** Before creating anything, check the item's case
   number against existing items; a re-uploaded "BZA# 21475" is flagged as
   already on the docket instead of being created twice. *Built.*
5. **Review in the existing Workspace.** Pending items appear in Public
   Docket's Workspace alongside published ones, where an admin edits anything
   the AI got wrong and publishes. *Built — Public Docket needed no changes.*

### Why the approval gate is non-negotiable

This project has been deliberately careful, throughout, never to invent
resident testimony or civic facts — see the placeholder-text decisions
documented in `public-docket-project-context.md`. An LLM extracting "case
number: BZA# 21475" or summarizing a recommendation *can* misread a document
or hallucinate a detail.

So a human approving each item is the hard requirement, and Docket Ingest
also makes that review meaningful rather than a rubber stamp: the model must
return a **verbatim quote** from the document for every item, shown beside
the item in the review table and again on its edit screen, so a reviewer can
check a case number against the source's own words. The one field the model
writes rather than copies — the plain-language question put to residents,
since an agenda never contains one — is labeled as AI-drafted.

### Roadmap

- **PDF uploads.** ANC6A's current agendas are digitally generated PDFs with
  real selectable text, so a text-extraction step would handle them well.
  Archived minutes, or other bodies' documents, may be scanned images and
  would need an OCR fallback.
- **Meetings and Case Threads.** Today each extracted item becomes a single
  docket item, and a later document mentioning the same case is only flagged
  as a duplicate — its new details are dropped. `test-fixtures/case-tracking/`
  is a set of four real agendas that follow five cases across meetings, with
  written acceptance criteria for tracking them properly. The relational model prototyped in
  `anc6a_civic_engagement_database.xlsx` (Committees → Meetings →
  Agenda_Items → Case_Threads) would let one matter like "1226 F Street NE"
  be tracked across several meetings over months, and would give addresses
  and committee recommendations first-class fields — Public Docket currently
  has none, so Docket Ingest folds them into the item's text.
- **Generated Agendas pages.** Build each meeting's Agendas page from the
  upload too, so the chatbot's context grows with every meeting instead of
  covering one hardcoded month.
- **Transcripts.** Comments derived from a meeting transcript need an extra
  consent gate. Public Docket's data model already anticipates this —
  submissions carry a `source` field (`form` vs. `transcript`) — and anything
  from a transcript must **never** default to consented-for-public-quoting;
  only an explicit human checkbox should set that.

### Known risks to plan around

- **Provider churn.** AI model names are retired constantly. In building this
  we hit placeholder defaults that didn't exist, a model retired for new
  accounts, and a model temporarily unavailable under load. Any org running
  this needs to expect to update the model setting periodically.
- **Output format drift.** Models don't reliably return clean data even when
  asked — Gemini wraps its JSON in markdown fences. Docket Ingest handles
  this, but it's a reminder that model output is untrusted input.
- **Cost.** Extracting a full meeting package is a bigger call than a single
  chatbot reply — budget per meeting, not per query.
- **Free tiers under load.** Free API keys are deprioritized when a provider
  is busy. For a live demo, run the extraction beforehand rather than
  depending on it in front of an audience.

### Open questions

- **Dependency on AI Engine.** Docket Ingest uses AI Engine's configured
  connection rather than its own API key, so one key serves both the chatbot
  and ingestion — but it means AI Engine is required even for an org that
  doesn't want a chatbot. A small swappable hook would allow other AI
  backends later without a rewrite.
- **Where does embeddings/RAG fit in,** so the chatbot answers from the
  *whole* archive instead of one page's static text? This becomes the
  natural next step once there's more than one Agendas page's worth of
  content to search.

---

## What's built today

### 1. Public Docket (the plugin) — `public-docket/`

A generalized, many-item version of an earlier single-item pilot called
HearBack. Every docket item is independent — a commission can have any number
open for comment at once, and not every item needs to end in an up-or-down
vote.

- **Three content types**: a Docket Item (the public-facing question), Themes
  (what admins group similar comments into), and Submissions (individual
  resident comments, private until an admin publishes the synthesis).
- **4-stage public timeline**, computed automatically from timestamps: *Open
  for input → Synthesis published → Board reviewing → Outcome.* Items that
  don't need public comment (purely informational postings) skip straight to
  showing the outcome.
- **Configurable outcome vocabulary** — no hardcoded Proceed/Do-not-proceed;
  admins define their own outcome options under Settings, each tagged with a
  tone (positive/neutral/negative) so the public page still gets a sensible
  accent color.
- **One-page admin Workspace** — edit an item's details, manage its themes,
  sort submissions into them, and publish, all with a single Save button.
- **Built for automation**: every decision field is exposed over the REST
  API, each item carries a `source` (manual vs. scraped) and an
  `external_reference` (a case/license number) so an ingestion tool can
  recognize "this is the same matter resurfacing" instead of creating a
  duplicate, and anything created programmatically lands as WordPress's
  native **Pending** status. Docket Ingest is built entirely on this
  groundwork.
- Spam protection (honeypot + nonce), no login required to comment, email
  never shown publicly.

Shortcodes: `[public_docket]` (archive listing), `[public_docket_item
id="123"]` (embed one item elsewhere).

### 2. Docket Ingest (the document plugin) — `docket-ingest/`

A separate plugin that turns an uploaded agenda, minutes, or transcript into
pending Public Docket items. It never modifies Public Docket's code; it only
writes items through Public Docket's registered post type and fields, keeping
its own data under a separate `_di_` prefix.

- Upload **.txt, .md, or .docx** under **Public Docket → Ingest Document**.
  Word files are read natively, with no extra libraries. PDF and old-style
  .doc files are refused with instructions to save as text first.
- Extraction runs through **AI Engine**, using whichever provider the site
  has configured there.
- A **review table** shows every extracted item with its verbatim source
  quote. Items whose case number is already on the docket are flagged and
  unchecked — matched regardless of formatting, since official documents
  write the same case as both `BZA 21475` and `BZA# 21475` — and the table
  names the existing item they matched. The admin picks which to create.
- Selected items are created as **Pending**, then reviewed and published in
  Public Docket's existing Workspace. An **"Ingested From Document"** panel
  on each item's edit screen shows the source quote, file, and a link to the
  original.

**Requires** Public Docket and AI Engine to be active. To use it in the demo:

1. Add your API key in **Meow Apps → AI Engine → Settings → AI**. For
   Gemini, the demo already turns on **"Use Standard API"** at boot — AI
   Engine's default Gemini route returned empty replies in testing.
2. On the Ingest Document page, pick your provider and a model from the
   dropdowns. Models marked "(always latest)" follow the provider's newest
   release, so they're the least likely to be retired.
3. Upload `test-fixtures/anc6a-2026-09-10-agenda.txt` to see it work on a
   real ANC6A agenda. To test how it handles the same case across several
   meetings, see `test-fixtures/case-tracking/`.

### 3. Demo theme — `anc6a-demo-theme/`

A minimal theme reproducing ANC6A's real header/nav/footer around the
plugin's content, styled from an approved design mockup (Lora/Lato type,
green-and-gold ANC6A palette). The plugin itself only renders docket
*content* — a theme (or a page template in a client's existing site) supplies
the surrounding chrome.

### 4. Chatbot integration (AI Engine)

The [AI Engine](https://wordpress.org/plugins/ai-engine/) plugin is installed
and its chatbot widget is placed on the **Agendas** page specifically (not
site-wide), so residents can ask questions about what's been discussed at
past meetings. The Agendas page itself is seeded with the real September 10,
2026 ANC6A agenda (sourced directly from
[anc6a.org](https://anc6a.org/agendas/)), including case numbers, addresses,
and committee recommendations, verbatim.

The API key is never stored in this repo or the Blueprint — a public repo is
not a safe place for a live key — so you add your own in the running
Playground instance. The chatbot also needs a current model selected under
AI Engine's **Chatbots** tab; its defaults can name models that don't exist
or have been retired.

**Status:** with a Gemini key, the chatbot originally returned empty replies.
The cause turned out to be AI Engine routing Gemini through its newer
"Interactions" API by default; its "Use Standard API" setting fixes this, and
the demo now turns that on at boot. That fix was verified through Docket
Ingest, which uses the same connection, but hasn't been re-tested in the
chatbot itself yet. OpenAI doesn't offer a meaningful free API tier, so that
path needs a paid key.

### 5. Demo content

Three real ANC6A docket items, deliberately left at three different points in
their lifecycle so the demo shows the full range of what the plugin does —
one fully answered (comments off, because the real record only has aggregate
counts, not theme-level breakdowns), one under board review, and one open for
live public comment. See `public-docket-project-context.md` for the full
sourcing and reasoning behind each choice — including where we explicitly
used non-testimony placeholder text rather than ever inventing what a
resident might have said.

---

## Repo structure

```
public-docket/                     the Public Docket plugin
docket-ingest/                     the Docket Ingest plugin
anc6a-demo-theme/                  the demo theme
test-fixtures/                     real ANC6A documents for testing ingestion
blueprint.json                     Playground blueprint for the public demo
blueprint-dev.json                 Playground blueprint for the docket-ingest dev branch
public-docket-project-context.md   full build history / decisions log
README.md                          this file
HOW-TO-GUIDE.md                    install guide for organizations trying this
GITHUB-DESKTOP-BRANCHING-GUIDE.md  branching guide for GitHub Desktop
```

---

## Notes for anyone continuing this work

- Internal code identifiers still say `hb_decision` / `hb_theme` /
  `hb_submission` and are prefixed `_hb_` — this plugin was originally called
  "HearBack Cabinet" before being generalized and renamed to Public Docket.
  Renaming the identifiers themselves was judged not worth touching every
  file for a purely internal, no-user-visible benefit.
- Docket Ingest keeps its own fields under `_di_` so Public Docket's schema
  stays untouched.
- New Docket Ingest work happens on the `docket-ingest` branch and is tested
  with `blueprint-dev.json` before merging, so the public demo on `main`
  stays working.
- All UI work intentionally stays in PHP/CSS files, not the WordPress
  block/page editor.
- See `public-docket-project-context.md` for the full history: every bug hit
  and fixed, every content-sourcing decision, and why.
