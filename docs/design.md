# Overview
This application records check-in and check-out timestamps at work and calculates time spent at work as input for most timekeeping applications.

## Core requirements
- Time is always formatted using 24-hour minutes (`HH:mm`).
- Timezone is `Europe/Oslo`.
- Authentication is handled via `.htaccess`.
- This is a single-user application.
- Backend is implemented in `timestamp.php` with `timestamp.db` (SQLite3) as data storage.
- Do not use cache-busting mechanisms for `timestamp.css` and `timestamp.js`.
- European formats rule
  - weeks start on monday
  - date format displayed should be yyyy-mm-dd

## UX behavior
- The app is mainly used from a mobile device with 1080 × 2340 and DPR 3.
- The top action is a `check-in/check-out` button that displays either `check in` or `check out`.
- At the bottom of the page, show a version stamp in tiny grey text using format `yyyy-mm-dd-HH-MM`.
- Version stamp source is the last-modified timestamp of `timestamp.php`.
- Weeks are displayed with the current week on top.
- At most 8 weeks are displayed.
- Days without time events are not shown in the daily list.
- At the bottom of each displayed week section, show weekdays that currently have no time entries.
- Each missing-weekday item is clickable/tappable and opens the same add/edit flow so time entries can be created for that day.
- Above the version note at the bottom of the page, show a small calendar/date button.
- Clicking the calendar/date button opens a date picker.
- The date picker allows selection only within the last 8 weeks.
- After selecting a date, open the same add/edit modal flow used for day rows so time entries can be created for that selected day.
- Day editing is triggered by tapping/clicking the day row itself.
- When the user is checked in:
  - `header h1` shows the check-in timestamp.
  - The current day includes a line showing `<check-in>` with no end time.
  - The header button says `Check out`.
- When the user is not checked in:
  - `header h1` shows `Timestamp`.
  - The header button shows the current time.

## Data rules
- A day may contain any number of check-in/check-out pairs (no fixed upper limit).
- Daily output still resolves to one day-level `<total>/<break>` summary.
- For each check-in/check-out row, break is counted only when the full interval `11:30` to `12:00` is inside that row’s check-in/check-out period.
- When editing times, `:` may be omitted:
  - `745` means `07:45`.
  - `1712` means `17:12`.
- Clearing both check-in and check-out values for a row deletes that row from storage.
- Whenever a time is edited, totals are recalculated on reload.

## Interaction model
- There is no need for partial page replacement; the full page can be reloaded after actions.
- In the modal editor, `Save` is blocked while any row is invalid, invalid fields are highlighted, and an inline validation message is shown.
- In the modal editor, a hint is shown only when any valid row overlaps `11:30` to `12:00`; the hint must state that break is counted only when the full `11:30` to `12:00` window is covered.

## Page layout
~~~
+----------------------------------------------------+
|       'check-in/check-out'                         |
+----------------------------------------------------+
|      "week" <week-number>                          |
| <wd> 'check-in' - 'check-out' =  <total>/<break>   |
| <wd> 'check-in' - 'check-out' =  <total>/<break>   |
| ....................repeated.......................|
+----------------------------------------------------+
|      "week" <week-number>                          |
| <wd> 'check-in' - 'check-out' =  <total>/<break>   |
| <wd> 'check-in' - 'check-out' =  <total>/<break>   |
| ....................repeated.......................|
+----------------------------------------------------+
.....................repeated.........................
~~~

## New specs (draft)
- Add new candidate specifications here first.
- After review/acceptance, move each item into the canonical section above and remove it from this draft list.
- Ambiguity checklist (fill before promotion):
  - user intent and scope are explicit (what changes, where, and for whom)
  - interaction trigger and expected result are concrete
  - data/format rules and edge cases are defined
  - acceptance criteria are testable
  - unresolved ambiguities have been clarified with the user
