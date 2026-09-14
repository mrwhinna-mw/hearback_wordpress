# Public Docket + ANC6A Playground Demo — Project Context

## Goal
Build a WordPress plugin ("Public Docket") that lets residents comment on
open civic items, has admins sort feedback into themes, and publishes a
dated outcome with a "next step" — then a live browser demo (WordPress
Playground) showing it populated with real content from ANC6A
(Advisory Neighborhood Commission 6A, Washington DC), styled to match
an approved Claude Design mockup.

## Origin
Started from reverse-engineering an existing small pilot app called
**HearBack** (Next.js + Prisma + SQLite, repo:
`github.com/mrwhinna-mw/heaback_mw`, main branch). HearBack only
handled **one** decision at a time. This project generalizes it into a
many-item WordPress plugin.

## Repo
`https://github.com/mrwhinna-mw/hearback_wordpress`, branch `main`.
Root contains two sibling folders:
- `public-docket/` — the plugin
- `anc6a-demo-theme/` — the demo theme
- `public-docket-demo.blueprint.json` — the WordPress Playground Blueprint

(Note: an unrelated `Civic decisions tracker design/` folder — the raw
Claude Design export — was also pushed to this repo at one point and
should probably be removed or ignored; it's not used by anything.)

## The plugin: Public Docket
- Originally called "HearBack Cabinet" / post type labeled "Decision" —
  **renamed** to "Public Docket" / "Docket Item" because not every item
  resolves as a decision. Internal code identifiers were deliberately
  **not** renamed (post type is still `hb_decision`, meta keys still
  prefixed `_hb_`) to avoid touching every file for no user-visible
  benefit.
- Three custom post types, mirroring HearBack's three-table Prisma
  schema:
  - `hb_decision` (label: Docket Item) — public, archive at `/docket/`
  - `hb_theme` — private, linked to its decision via `_hb_decision_id`
    meta (not `post_parent`)
  - `hb_submission` — private, same linking pattern, plus `_hb_theme_id`
    once assigned
- Key meta fields on `hb_decision`: `_hb_decision_question`,
  `_hb_comment_open_at`, `_hb_response_by_date`,
  `_hb_response_owner_name/role`, `_hb_synthesis_published_at`,
  `_hb_response_status`, `_hb_response_rationale`,
  `_hb_response_next_step`, `_hb_response_published_at`,
  `_hb_comments_enabled` (bool, default true), `_hb_external_reference`,
  `_hb_source` (manual/scraped), `_hb_source_url`.
- Key meta fields on `hb_submission`: `_hb_name`, `_hb_email`,
  `_hb_neighborhood`, `_hb_consent`, `_hb_featured`, `_hb_theme_id`,
  `_hb_decision_id`, `_hb_source` (form/transcript).
- **4-stage public timeline** (only when comments are enabled), derived
  from timestamps, not stored directly: Open for input → Synthesis
  published → Board reviewing → Outcome. See `includes/timeline.php`
  (`hb_get_timeline()`, `hb_get_decision_status()`).
- **Per-item toggle** to disable public comments entirely for purely
  informational postings (`_hb_comments_enabled`) — skips straight to
  showing the outcome if one exists.
- **Configurable outcome options** under Public Docket → Settings
  (`includes/settings.php`) — replaced a hardcoded
  Proceed/Do-not-proceed/Not-yet with an editable list, each tagged
  with a tone (positive/neutral/negative) for accent-color purposes.
- **One-page admin Workspace** (`includes/admin-workspace.php`) —
  consolidates editing item details, managing themes, sorting
  submissions, and publishing, all with a single Save button, instead
  of jumping across separate CPT screens. Includes pending/draft items
  (not just published) so a future automated scraper's drafts are
  reviewable.
- **Automation-readiness** built in for a *future* (not yet built)
  ingestion pipeline that would scrape agendas/minutes/transcripts:
  - All decision meta fields are `register_post_meta(..., show_in_rest
    => true)` — needed because WordPress hides underscore-prefixed meta
    from REST by default.
  - `_hb_external_reference` (e.g. a case number) exists so a scraper
    can dedupe/update the same real-world matter across meetings
    instead of creating duplicates.
  - Anything auto-created should land as WP's native "pending" status,
    never auto-published — a human must approve.
  - Submission `_hb_source` field anticipates transcript-derived
    comments; these should **never** default to consented-for-quoting —
    only an explicit human check should set `_hb_consent`.
- Public comment form has a honeypot + nonce for spam protection, no
  login required, only comment field required (name/email/neighborhood
  optional, email never shown publicly).
- Shortcodes: `[public_docket]` (archive listing) and
  `[public_docket_item id="123"]` (embed one item elsewhere).
- All PHP has been linted (`php -l`) and core logic (timeline
  calculation, comments-enabled short-circuit, outcome option lookup)
  has unit tests against stubbed WP functions — all passing as of last
  build.

## Design
An actual Claude Design mockup was built and approved (file: `ANC 6A
Decisions.dc.html`, exported as `Civic_decisions_tracker_design.zip`).
Extracted palette and type, used directly in the plugin's CSS
(`assets/css/hearback.css`) and the demo theme's CSS (`style.css`):

- Headings: **Lora**, weight 600 (Google Font)
- Body: **Lato** (Google Font)
- `--hb-green-dark: #1e4628` (primary — headers, nav, timeline band)
- `--hb-gold: #ffc942` (accent — active states, "Next step" badge)
- `--hb-rust: #a1571a` (open-for-input status, hover states)
- `--hb-bg: #e9e6e0` (page background), white cards, muted borders/text
- The **"Next step" callout** and the **4-stage tracker** were called
  out as the two most visually important elements — the tracker is a
  dark green band with numbered circles; Next step is a gold-topped
  highlighted box with an absolute-positioned badge.
- Public comment placeholders deliberately say **"This is where a
  public comment would go if a resident had submitted one"** rather
  than inventing sample resident testimony — this was an explicit
  decision to avoid fabricating opinions, even fictional ones, in a
  civic-engagement context.

## Demo theme: `anc6a-demo-theme`
A minimal companion theme (the plugin only renders docket *content*,
not page chrome) reproducing the design's header/nav/footer: ANC6A seal
image (`assets/images/anc-seal.jpg`, pulled from the design export),
green nav bar with a "Docket" tab, gold accent line, plain footer.
Files: `style.css`, `header.php`, `footer.php`, `single.php`, `page.php`,
`index.php`, `functions.php`. All linted clean.

## Demo content: real ANC6A data, not invented
Sourced from `https://anc6a.org/agendas/` and the actual September 10,
2026 ANC6A meeting agenda PDF (scraped earlier in this project). Three
items seeded at three different lifecycle stages via a `runPHP`
Blueprint step:

1. **1226 F Street NE** (BZA# 21475, third-story addition) — comments
   **disabled**, shown as fully Answered. Real facts: 14 letters of
   opposition, EDZ declined to weigh in pending neighbor negotiation, 8
   neighbors met Aug 18 with no agreement, ANC6A withheld support Sept
   10, BZA hearing Sept 16, 2026. Comments were deliberately turned off
   here because the real record has aggregate counts but no theme-level
   breakdown — inventing themes would misrepresent it.
2. **Indochine Cuisine and Lounge liquor license** (ABRA-136346, 1025 H
   St NE) — shown as Board reviewing (synthesis published, no outcome
   yet). Two themes ("Hours and noise," "Support for local business")
   each carry one demo submission using the **placeholder line**
   verbatim, not invented opinions — this is the one place synthetic
   demo data was added, and it's explicitly non-testimony.
3. **H Street BID letter** (Commissioner Shapiro's real motion) — shown
   Open for input, so a live comment can actually be submitted on the
   demo.

## WordPress Playground Blueprint
File: `public-docket-demo.blueprint.json`. Structure: `login` step (as
admin) → `installTheme` (git:directory resource, path
`/anc6a-demo-theme`) → `installPlugin` for Public Docket (git:directory
resource, path `/public-docket`) → `installPlugin` for **AI Engine**
(from wordpress.org/plugins, for the future chatbot piece) → `runPHP`
step containing the seed-content script above. `landingPage` is set to
`/docket/`. Both git:directory resources point at
`github.com/mrwhinna-mw/hearback_wordpress`, branch `main`.

**Demo link** (once everything below is correctly in place):
```
https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/mrwhinna-mw/hearback_wordpress/main/blueprint.json
```
(Filename in the repo needs to actually match whatever's in this URL —
this was one of the bugs hit below.)

## Bugs hit and fixed so far, in order
1. **Wrong folder pushed**: the *original Claude Design export*
   (`Civic decisions tracker design/`) got pushed to the repo instead
   of the actual `anc6a-demo-theme/` theme folder built afterward.
   Fixed by pushing the correct theme folder.
2. **Filename mismatch**: Blueprint was pushed as
   `public-docket-demo.blueprint.json` but the shared link pointed to
   `blueprint.json` → 404. Needs the repo filename and the URL to
   actually match (either rename the file to `blueprint.json` in the
   repo, or keep the longer name and always use that in the URL).
3. **Schema validation error**: Blueprint's `meta` object was missing
   the required `author` field. Fixed by adding
   `"author": "mrwhinna-mw"`.

## Known open items / not yet done
- The Blueprint has **not yet been successfully loaded end-to-end** on
  real Playground — only validated as JSON/schema so far, after fixing
  the three bugs above. This should be the very next thing tried.
- Nothing in this plugin has been tested inside an actual running
  WordPress instance (only linted + unit-tested in isolation via PHP
  stubs) — the first real WordPress runtime test will be whatever
  happens when the Playground demo actually boots.
- No ingestion/scraping automation has been built — only the data-model
  groundwork (REST exposure, source fields, external reference,
  pending-status handling) to make one possible later, per an earlier
  design discussion in this project.
- AI Engine (chatbot) plugin is installed in the Blueprint but not yet
  configured with an API key or pointed at any content — that's a
  manual step to do live in the running demo (deliberately not baked
  into the Blueprint, since embedding a real API key in a public repo
  file would be a bad idea).
- The user explicitly wants to avoid the WordPress block/page drag-and-
  drop editor entirely — all UI work should stay in PHP/CSS files, not
  the editor.
