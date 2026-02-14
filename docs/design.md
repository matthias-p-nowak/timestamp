# Overview
This application records check-in and check-out timestamps at work and calculates time spent at work as input for most timekeeping applications.

## Core requirements
- Time is always formatted using 24-hour minutes (`HH:mm`).
- Timezone is `Europe/Oslo`.
- Authentication is handled via `.htaccess`.
- This is a single-user application.
- Backend is implemented in `timestamp.php` with `timestamp.db` (SQLite3) as data storage.

## UX behavior
- The app is mainly used from a mobile device with 1080 × 2340 and DPR 3.
- The top action is a `check-in/check-out` button that displays either `check in` or `check out`.
- At the bottom of the page, show a version stamp in tiny grey text using format `yyyy-mm-dd-HH-MM`.
- Version stamp source is the last-modified timestamp of `timestamp.php`.
- Weeks are displayed with the current week on top.
- At most 8 weeks are displayed.
- Days without time events are not shown in the daily list.
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
