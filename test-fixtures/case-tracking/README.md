# Case-tracking test set

Four real ANC 6A agendas, uploaded in order, to test how Docket Ingest handles
the **same matter showing up across several documents** — a case that's
introduced at one meeting, updated at the next, and decided at a third.

Every document here is real. Nothing was invented to make a test case work.

## Upload order

Use a fresh demo (the link in the main README), then upload these one at a
time under **Public Docket → Ingest Document**, reviewing and creating the
items from each before uploading the next:

1. `01-edz-2026-06-17-agenda.txt` — EDZ committee, June 17, 2026
2. `02-anc-2026-07-09-agenda.txt` — full commission, July 9, 2026
3. `03-edz-2026-07-15-agenda.txt` — EDZ committee, July 15, 2026
4. `../anc6a-2026-09-10-agenda.txt` — full commission, September 10, 2026

The demo already contains three docket items seeded from the September 10
meeting (1226 F Street NE, Indochine, and the H Street BID letter), which
some of these documents will run into.

## The five matters that repeat

| Matter | Case number as written | Appears in | What changes between documents |
|---|---|---|---|
| **1226 F Street NE** — third-story addition | `BZA 21475` in docs 1 and 3; `BZA# 21475` in doc 4 and the demo | 1, 3, 4 | BZA hearing **Sept 2** (doc 1) → **postponed to Sept 16**, neighbors in negotiations (doc 3) → no agreement reached, 14 letters of opposition, commission withholds support (doc 4) |
| **628 15th Street NE** — alley | `BZA 21349` | 1, 3 | BZA asks for more information; hearing continuation set for Sept 2 (doc 3) |
| **1331 North Carolina Ave NE** — rear addition | `HP 26-294` | 1, 2 | Committee noted six letters of support; commission recommends supporting it (doc 2). **The documents disagree** on when notice was posted: May 30 (doc 1) vs March 30 (doc 2) |
| **800 10th Street NE** — built without approval | `BZA# 21502` | 3, 4 | Committee declines to weigh in on already-built elements; three letters of support; commission refrains from comment; BZA hearing Oct 7 (doc 4) |
| **H Street Business Improvement District** | *none* | 2, 4 | Community presentation (doc 2) → motion to send a letter supporting its formation (doc 4) |

## What the current version does

Docket Ingest **recognizes repeat cases but doesn't track updates.** When an
extracted item's case number matches one already on the docket — ignoring
differences like `BZA 21475` vs `BZA# 21475` — the review table names the
existing item, shows how its case number was written, and leaves the new one
unchecked. But the new details from that document are dropped. Expect to see:

- **1226 F Street is recognized across all three documents.** Docs 1 and 3
  write `BZA 21475`; the seeded item and doc 4 write `BZA# 21475`. Each upload
  should say it's already on the docket as "Third-story addition at 1226 F
  Street NE (BZA# 21475)." *(Before this was fixed, upload 1 created a second
  1226 F Street item.)*
- **Updates are lost.** When 628 15th Street reappears in doc 3, the hearing
  continuation isn't added to the item. Same for 800 10th Street's outcome in
  doc 4, and 1226 F Street's postponed hearing.
- **The 1331 North Carolina conflict goes unnoticed.** Doc 2 is marked as a
  duplicate, so nobody is told the two documents disagree about the posting
  date.
- **H Street BID is never matched.** With no case number, there's nothing to
  compare, so any items the AI extracts about it are offered as new —
  potentially duplicating the seeded BID letter item.

AI extraction varies somewhat between runs: it may skip items it judges
procedural (presentations, "comments due" notes), and could occasionally
reformat a case number despite being told not to — which matching now
tolerates, as long as the prefix and number themselves are right.

## What "tracking" should do

These documents double as the acceptance test for tracking updates across
documents. It should:

1. **Recognize the same matter despite formatting.** `BZA 21475`,
   `BZA# 21475`, and `BZA #21475` are one case. *Done.*
2. **Keep what's new instead of dropping it.** When a document mentions an
   existing item, show its new details beside what's already on the item, and
   let the reviewer add them as a dated update — with the source quote, like
   everything else.
3. **Surface conflicts rather than picking a side.** For 1331 North Carolina,
   show both posting dates and let a person decide.
4. **Suggest, never auto-merge, when there's no case number.** For H Street
   BID, propose likely matches by address or topic for a person to confirm.
5. **Not match on Square and Lot alone.** In these official documents, both
   1331 North Carolina Ave NE and 800 10th Street NE are listed as
   "Square 1035, Lot 065" — almost certainly an error in the source records.
   Matching on those would merge two unrelated cases.
6. **Record provenance for every update:** which document, which meeting,
   and the exact quote it came from.

## Sources

Transcribed from the official PDFs published at
[anc6a.org/agendas](https://anc6a.org/agendas/):

- [EDZA0626.pdf](https://anc6a.org/wp-content/uploads/EDZA0626.pdf) — doc 1
- [ANCA0726.pdf](https://anc6a.org/wp-content/uploads/ANCA0726.pdf) — doc 2
- [EDZA0726.pdf](https://anc6a.org/wp-content/uploads/EDZA0726.pdf) — doc 3
- [ANCA0926.pdf](https://anc6a.org/wp-content/uploads/ANCA0926.pdf) — doc 4

Text is verbatim, including real-world messiness like an unfinished staff note
("[I spoke with the architect and…]") and a pasted browser-extension link in
doc 3 — both good tests of what the AI chooses to include. Tables of zoning
relief are flattened into labeled lines, since the plugin reads plain text.
The committee chairs' personal contact emails at the foot of the EDZ agendas
were left out, as they aren't needed for testing.
