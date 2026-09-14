# Public Docket for ANC6A

A WordPress plugin and live demo built for Advisory Neighborhood Commission 6A
(Capitol Hill, Washington DC) to close a specific gap in how local government
bodies handle public input: residents submit comments on an agenda item, and
the only thing that ever comes back is minutes almost nobody reads. Public
Docket gives every open item a public comment period, a themed synthesis of
what residents said, and a dated outcome with a concrete next step — plus an
AI chatbot residents can ask questions of.

This repo is a **working demo**, not yet a production deployment. It exists to
show ANC6A (and other neighborhood commissions) what this could look like
before committing engineering time to build it for real.

**Try it live:** [playground.wordpress.net/?blueprint-url=...](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/mrwhinna-mw/hearback_wordpress/main/blueprint.json) — runs entirely in your browser via [WordPress Playground](https://wordpress.github.io/wordpress-playground/), no hosting or install required.

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
- **Built for future automation, already**: every decision field is exposed
  over the REST API, each item carries a `source` (manual vs. scraped) and an
  `external_reference` (a case/license number) so a future ingestion tool can
  recognize "this is the same matter resurfacing" instead of creating
  duplicates, and anything created programmatically lands as WordPress's
  native **Pending** status — invisible to the public until a human approves
  it. This groundwork is what Phase 2 below builds on.
- Spam protection (honeypot + nonce), no login required to comment, email
  never shown publicly.

Shortcodes: `[public_docket]` (archive listing), `[public_docket_item
id="123"]` (embed one item elsewhere).

### 2. Demo theme — `anc6a-demo-theme/`

A minimal theme reproducing ANC6A's real header/nav/footer around the
plugin's content, styled from an approved design mockup (Lora/Lato type,
green-and-gold ANC6A palette). The plugin itself only renders docket
*content* — a theme (or a page template in a client's existing site) supplies
the surrounding chrome.

### 3. Chatbot integration (AI Engine)

The [AI Engine](https://wordpress.org/plugins/ai-engine/) plugin is installed
and its chatbot widget is placed on the **Agendas** page specifically (not
site-wide), so residents can ask questions about what's been discussed at
past meetings. The Agendas page itself is seeded with the real September 10,
2026 ANC6A agenda (sourced directly from
[anc6a.org](https://anc6a.org/agendas/)), including case numbers, addresses,
and committee recommendations, verbatim.

**Current limitation:** the chatbot's API key is never stored in this repo or
the Blueprint (a public repo is not a safe place for a live key) — you add
your own key live in the running Playground instance. In our own testing with
a Gemini free-tier key inside WordPress Playground specifically, currently
valid Gemini models return an empty response through AI Engine's newer
"Interactions" API integration, while the direct Gemini API itself works
fine — this looks like a sandbox/plugin compatibility gap rather than an
account problem, and may not reproduce on a real WordPress host. OpenAI does
not offer a meaningful ongoing free API tier, so that path needs a paid key
to test.

### 4. Demo content

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
public-docket/            the plugin
anc6a-demo-theme/          the demo theme
blueprint.json              WordPress Playground blueprint (boots the demo)
public-docket-project-context.md   full build history / decisions log
README.md                   this file
HOW-TO-GUIDE.md              install guide for organizations trying this
```

---

## Future vision: an ingestion plugin

### The problem

Everything in the demo today was entered by hand (a `runPHP` seed script in
the Blueprint, standing in for what an admin would type into the Workspace
screen). That doesn't scale — a commission meets monthly, publishes a PDF
agenda and, later, minutes or a recording, and none of that should require
manually retyping case numbers and recommendation text into WordPress.

### The plan

A **separate plugin** (per Public Docket's own `readme.txt`, extensions like
this are meant to read and write through the REST API rather than modify
Public Docket's core) that lets an admin:

1. **Upload** an agenda, minutes document, or meeting transcript (PDF, DOCX,
   or plain text).
2. **Extract** structured data from it using an LLM — committee, meeting
   date, each agenda item's topic, case/license number, address,
   recommendation text, and sponsor — modeled closely on the relational
   structure we already prototyped by hand in
   `anc6a_civic_engagement_database.xlsx` (Committees → Meetings →
   Agenda_Items → Case_Threads, where a Case Thread tracks one matter like
   "1226 F Street NE" across multiple meetings over months).
3. **Draft, don't publish.** Every extracted item is created as a
   `hb_decision` (or a new, lighter `Meeting`/`Case Thread` post type — see
   Open Questions) with `post_status = pending` and `_hb_source = 'scraped'`.
   Nothing reaches the public site without a human clicking Approve.
4. **Match, don't duplicate.** Before creating a new post, check
   `_hb_external_reference` against existing items — if "BZA# 21475" already
   exists, update/append to that thread instead of creating a second one.
5. **Review in the existing Workspace UI.** The admin sees pending items
   alongside published ones (the Workspace already supports this), edits
   anything the AI got wrong, and approves — at which point the item behaves
   exactly like any manually-created docket item, including becoming
   queryable by the Agendas-page chatbot once its content is live on the
   site.

### Why the approval gate is non-negotiable

This project has been deliberately careful, throughout, never to invent
resident testimony or civic facts — see the placeholder-text decisions
documented in `public-docket-project-context.md`. An LLM extracting "case
number: BZA# 21475" or summarizing a recommendation *can* misread a PDF or
hallucinate a detail. The mitigation isn't just "a human reviews it before
publish" (though that's the hard requirement) — the extraction step should
also **cite the source text** it pulled each field from, so a reviewer can
verify at a glance instead of re-reading the whole source document.

### Known risks to plan around

- **PDF parsing reliability.** Not every agenda PDF is clean, selectable text
  — some are scans. A generic web-fetch failed on ANC6A's own agenda PDF
  during this project; a proper PDF-to-text step (or OCR fallback for
  scanned documents) is a hard requirement, not a nice-to-have.
- **Cost.** LLM extraction on a full meeting package is a bigger one-off call
  than a single chatbot reply — budget for it accordingly per meeting, not
  per query.
- **Transcript-derived comments need an extra consent gate.** Public Docket's
  data model already anticipates this: submissions carry a `source` field
  (`form` vs. `transcript`), and anything derived from a transcript should
  **never** default to consented-for-public-quoting — only an explicit human
  checkbox should set that.

### Open questions

- Does a "Meeting" deserve its own post type (matching `Meetings` in the
  spreadsheet), or is a lighter meta field on `hb_decision` enough? A
  dedicated type would make the Agendas page (and the chatbot's context)
  scale to every past meeting, not just one hardcoded month.
- Which LLM provider/model for extraction — likely whatever the org already
  configured in AI Engine for the chatbot, to avoid a second API key/vendor.
- Where does embeddings/RAG fit in, so the chatbot answers from the *whole*
  archive instead of one page's static text? (See the "Knowledge & Context"
  discussion in the project history — this is the natural next step once
  there's more than one Agendas page's worth of content to search.)

---

## Notes for anyone continuing this work

- Internal code identifiers still say `hb_decision` / `hb_theme` /
  `hb_submission` and are prefixed `_hb_` — this plugin was originally called
  "HearBack Cabinet" before being generalized and renamed to Public Docket.
  Renaming the identifiers themselves was judged not worth touching every
  file for a purely internal, no-user-visible benefit.
- All UI work intentionally stays in PHP/CSS files, not the WordPress
  block/page editor.
- See `public-docket-project-context.md` for the full history: every bug hit
  and fixed, every content-sourcing decision, and why.
