=== Public Docket ===
Contributors: YOUR-WORDPRESS-ORG-USERNAME
Tags: public comment, local government, civic engagement, agenda, transparency
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Residents comment on the items before your board, staff group the feedback into themes, and the board publishes an outcome with a next step.

== Description ==

Public comment usually goes one way. Residents testify, email, and fill
in forms, and the only thing that comes back is minutes almost nobody
reads. Public Docket is built around closing that loop: every item your
board considers gets its own page, its own comment period, and - when
the board has decided - a published outcome that says what happened and
what a resident can still do about it.

It was built for a Washington DC Advisory Neighborhood Commission and
generalized from there. It suits any body that takes public input on a
list of items: neighborhood commissions, advisory boards, planning
committees, school councils, community coalitions.

= What each docket item does =

* Collects comments on one plain-language question, with a four-stage
  public timeline - Open for input, Synthesis published, Board
  reviewing, Answered - so nobody has to ask where a matter stands.
* Skips the comment period entirely when an item is informational.
* Records an outcome in your own words. The options (Proceed / Do not
  proceed / Not yet, by default) are editable under Settings, and each
  carries a tone so the public page still picks a sensible accent color
  for wording the plugin has never seen.
* Shows a "Next step" even after an outcome is posted - a hearing date,
  an appeal window, a matter returning next month.

= What residents get =

Commenting asks for the comment and nothing else. Name, email and
neighborhood are optional, no account is needed, and an email address is
never shown on a public page. A resident's words are only ever quoted
publicly if they ticked the box agreeing to be quoted.

= What staff get =

One Workspace screen holds the item, its themes and its submissions
together, instead of scattering them across menus. Staff sort
submissions into themes and publish a "What we heard" summary - the
recurring points, plus consented quotes - then post the outcome. Items
can be published straight from the Workspace.

= Drafting items from your agenda (optional) =

Reading a 100-page meeting package and typing out each item is the part
that stops organizations doing this at all. Public Docket can read an
agenda, minutes or transcript you upload (.txt, .md or .docx) and draft
the items for you: case numbers, addresses, and a plain question for
each one.

Every drafted item is shown beside the exact sentence it came from. A
later document about the same case updates the item already on your
docket rather than duplicating it, and when a new document contradicts
what is recorded, it says so and leaves the decision to you. Nothing is
ever published automatically - drafts arrive as Pending and stay
invisible to residents until a person approves them.

This feature is entirely optional. It appears only in the admin, and the
rest of the plugin works without it.

== External services ==

The document drafting feature described above sends text to an AI
provider. It is the only part of this plugin that contacts a third
party, it runs only when an administrator uploads a document and presses
Analyze Document, and it is off until you configure it.

Public Docket does not include an AI service of its own and has no
account, server or API key of ours involved. It uses the connection you
set up in the separate, free AI Engine plugin
(https://wordpress.org/plugins/ai-engine/), under your own API key with
the provider you choose - for example OpenAI, Anthropic, Google,
OpenRouter, Mistral, Perplexity or Replicate.

What is sent, and when:

* Sent: the text extracted from the document you upload, up to roughly
  60,000 characters, together with the instructions asking the model to
  list the matters it contains. When you ask it to check for updates, the
  title, question and recorded outcome of the existing docket items being
  compared are sent too.
* Also sent: any extra instructions you choose to save on the Ingest
  Document screen.
* Not sent: resident submissions, names, email addresses, or anything
  else from your site.
* When: only on an explicit upload by a logged-in administrator. Nothing
  is sent on a schedule, on page views, or from the public site.

Because you choose the provider, the terms that apply are that
provider's. Their privacy policy and terms of service govern what they
do with the text you send. Please read them before uploading documents
that are not already public records, and check the provider's own
documentation for whether submitted data may be retained or used for
training. The providers' policies are linked from AI Engine's settings
screen, where you enter the key.

== Installation ==

1. Install and activate Public Docket.
2. Visit Public Docket > Settings to set your outcome options and the
   name your organization uses for its board.
3. Add your first item under Public Docket > Add New Docket Item, or let
   residents find the docket at /docket/ on your site.

To draft items from documents, additionally install the free AI Engine
plugin, add an API key for the provider of your choice in its settings,
and then use Public Docket > Ingest Document. The screen tells you what
is missing if AI Engine is not there.

== Frequently Asked Questions ==

= Does this send anything to an AI service on its own? =

No. The only outbound request happens when an administrator uploads a
document and presses Analyze Document. Running a docket, collecting
comments and publishing outcomes involve no third party at all. See the
External services section above.

= Do residents need an account to comment? =

No. Only the comment itself is required; name, email and neighborhood
are optional, and email is never displayed publicly.

= Can a resident's comment be quoted publicly? =

Only if they ticked the consent box on the form. Nothing else, including
the drafting feature, can mark a comment as quotable.

= Our board does not vote on things. Does that matter? =

No. Outcome options are editable, so a body that recommends, advises or
simply notes a matter is not forced into Approve and Deny.

= Can I try it before installing? =

Yes. A complete demo runs in your browser through WordPress Playground,
with no hosting or install required. The link is on the project page in
the Plugin URI above.

= Will it read a PDF? =

Not yet. Save the document as plain text (.txt) and upload that. Word
(.docx), Markdown and plain text are supported today.

== Screenshots ==

1. A docket item on the public site: the question, the outcome, and the next step.
2. The Workspace: one item with its themes and submissions on a single screen.
3. Ingest Document: an uploaded agenda drafted into items, each beside the text it came from.
4. An update found in a later document, with a contradiction flagged for a human to settle.
5. Settings: outcome options worded the way your organization talks.

== Changelog ==

= 0.4.0 =
* Document ingest is now part of Public Docket rather than a separate
  Docket Ingest plugin. One install instead of two; the Ingest Document
  screen appears under Public Docket. Upgrading from the separate plugin:
  deactivate and delete Docket Ingest, and your existing items, sources
  and update history are untouched.
* The plugin no longer loads a webfont from a third-party CDN. The
  stylesheet asks for Lora and Lato and falls back to Georgia and Arial,
  so a theme supplying those faces still gets them.
* Renamed the text domain to public-docket.

= 0.3.1 =
* Fixed: switching items in the Workspace could fail with "Cannot load
  hb-workspace." The item list now links to each item's full address
  instead of letting the browser rebuild it.

= 0.3.0 =
* The Workspace can now publish an item directly, instead of sending you
  to the post editor just to change its status. Appears only for items
  that are not public yet, and only for users who can publish.

= 0.2.0 =
* Renamed from HearBack Cabinet / "Decision" to Public Docket / "Docket
  item", and generalized from a single decision to a many-item docket.

= 0.1.0 =
* First release.

== Upgrade Notice ==

= 0.4.0 =
Docket Ingest is now built in. If you installed it separately, deactivate
and delete it after upgrading; your items and history are unaffected.
