# Changelog

All notable changes to this plugin are documented here. Format based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), versions follow
[Semantic Versioning](https://semver.org/).

## [1.0.0] – unreleased

First public release. Replaces the unfinished `anschluss.php` prototype (which only searched the
legacy *relatives* table and was limited to a single hard-coded user) with a complete ad
management for wanted characters.

### Added
- Ad types **single**, **group** and **loose**; any number of forums can be assigned to a type in
  the ACP, optionally flagged as **archive** (ads there count as finished).
- Configurable fields per ad type (text, multi-line text, select, radio, checkboxes, number,
  age range, connection list) with order, required flag, options, icon and visibility in thread
  list, profile and header; filterable fields appear in the filter bar and on the search page.
- Core fields with fixed logic: status (free / reserved / taken, *done* in archive forums),
  group slots (filled / total, open-ended) with automatic "taken" when all slots are filled,
  age range with optional display text.
- Ad fields in the new-thread and edit-post forms of assigned forums, validated by MyBB's post
  data handler; drafts can be saved incomplete.
- Enhanced thread list rows (status badge, field line, number of connected characters),
  rendered from the template `anschlusssuche_forumdisplay_thread`.
- Connection list field: name + role, names can be linked to accounts with username suggestions.
- Loose ads: own ads in the User CP (create, edit, delete) plus persons marked as wanted in the
  optional *Relatives* plugin (version 2.2.0 or later), shown as cards above the thread list of a
  forum of type *loose*. In the forum list such a forum counts the loose ads in *Threads*/*Posts*
  and shows the newest loose ad as *Last post*, so it does not look empty.
- Profile block `{$anschlusssuche_profile}` with the ads of all accounts of a player
  (Enhanced Account Switcher aware); persons from *Relatives* can be hidden there with the setting
  *Hide Relatives persons in profile* to avoid showing them twice next to the Relatives wanted box.
- The open loose ads of a character are appended to the Relatives wanted box in its profile
  (hook `relatives_profile_wanted_cards`). A Relatives person in several trees is shown with the
  closest relative as the searching character (uses `closeness` from the Relatives search API).
- German wording: loose ads are called *Anschluss-Angebote*.
- Header teaser `{$anschlusssuche_header}` with fixed, time-limited entries (managed in the ACP)
  and random ads from selected forums.
- Search page `anschluss.php` over all ad types with tabs, status, age overlap, text and field
  filters; guests optional.
- Public API for migrations and other plugins: `anschlusssuche_entry_save()`,
  `anschlusssuche_header_add()`, `anschlusssuche_get_entries_for_uids()`; hooks
  `anschlusssuche_entry_saved` and `anschlusssuche_header_collect`.
- Data model prepared for buying header slots in the NewPoints shop (sources `acp`/`shop`,
  credits table); the shop integration itself is not part of this release.
- ACP permission `tools/anschlusssuche` is granted on activation to every admin that already has
  Tools & Maintenance access.
- CLI tests `tests/cli-plugin.php` (lifecycle) and `tests/cli-selftest.php` (logic and DB
  round trips).

### Fixed (during development, before release)
- The thread-list row template was escaped twice when it replaced `forumdisplay_thread`.
- Validation errors in the new-thread form showed the language key instead of the message, and
  only the last of several errors was shown.
- Checking the "groups allowed to create loose ads" setting crashed the loose-ads forum
  (wrong return type).
- Editing a group ad inside an archive forum of type *single* would have converted it into a
  single ad; the stored type now always wins.
