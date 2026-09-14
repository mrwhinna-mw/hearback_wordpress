=== Public Docket ===
Contributors: (your wordpress.org username)
Tags: civic engagement, public comment, local government, feedback
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A multi-item public docket for local government bodies: residents
comment on open items, admins sort feedback into themes, and the board
posts an outcome with a next step. Built on the HearBack model.

== Description ==

Public Docket is a generalized, many-item version of the original
HearBack pilot: a small tool built around one specific failure mode in
public meetings, where residents submit testimony and the only artifact
that comes back is minutes almost nobody reads.

Not every docket item ends in an up-or-down vote, so this plugin
doesn't assume one. Each item can:

* Collect public comments on a plain-language question, with a
  4-stage public timeline (Open for input -> Synthesis published ->
  Board reviewing -> Outcome) - or skip the comment period entirely
  for purely informational postings.
* Have its outcome worded however your organization actually talks -
  the outcome options (Proceed / Do not proceed / Not yet, by default)
  are fully editable under Settings, each tagged with a tone
  (positive/neutral/negative) so the public page still gets a sensible
  accent color for wording the plugin has never seen before.
* Show a "Next step" - what's still live and what a resident can still
  do about it - even after an outcome is posted.

Residents can submit a comment with just the comment itself required;
name, email, and neighborhood are all optional, and email is never
shown on any public page. Admins sort submissions into themes, then
publish "What we heard" - aggregated themes plus anonymized quotes from
residents who consented to be quoted.

= Built for future automation =

This plugin doesn't scrape or transcribe anything itself, but its data
model is ready for something else to. Every docket item field is
exposed over the REST API (`show_in_rest`), and each item carries a
source (manual/scraped), a source URL, and a stable external
reference/case number so an ingestion script can recognize "this is
the same matter resurfacing" instead of creating a duplicate. Anything
created that way should land as WordPress's native "Pending" status,
not published - the Workspace screen shows pending items to admins for
review, but they stay invisible to residents until a human approves
them. Submissions carry a source field too (form vs. transcript);
comments derived from a transcript should never be marked as consented
to a public quote by anything other than a human affirmatively
checking that box.

= Extending this plugin =

Key actions fire at the moments an add-on would want to hook in
(`hb_submission_created`, `hb_decision_saved`). Things like an agenda/
transcript ingestion pipeline, or a chatbot that answers resident
questions from published items, are meant to be separate plugins that
read and write through the REST API rather than changes to this
plugin's core.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/public-docket`, or
   install the zip through Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Under Public Docket > Settings, review or edit the outcome options.
4. Go to Public Docket > Add New Docket Item to create your first one.
5. Go to Public Docket > Workspace to edit that item's details, manage
   its themes, sort its submissions, and publish - all from one screen
   with a single Save button.
6. Visit `/docket/` to see the public archive.

== Frequently Asked Questions ==

= Can I have more than one open item at a time? =

Yes - each item is independent, and there's no limit on how many can
be open for comment at once.

= Does every item need public comments? =

No. Turn off "Collect public comments" on an item to post it as purely
informational - it skips the comment form, themes, and timeline, and
shows just the outcome once one is posted.

= Where do public comments go? =

Into a private submission post per comment. Nothing is public until an
admin assigns themes and publishes the synthesis.

= Is there spam protection on the comment form? =

A honeypot field and a nonce are built in. For a public-facing site,
also consider adding Akismet (bundled with WordPress) if spam becomes
an issue.

== Changelog ==

= 0.2.0 =
* Renamed from HearBack Cabinet / "Decision" to Public Docket / "Docket
  Item," since not every item resolves as a decision.
* Outcome options are now configurable under Settings instead of a
  hardcoded Proceed/Do not proceed/Not yet.
* Added a per-item toggle to turn public comments off for purely
  informational postings.
* Added source/source URL/external reference fields and REST exposure
  on docket item meta, in preparation for a future scraper/ingestion
  add-on.

= 0.1.0 =
* Initial release.
