# Overview
This application records check-in and check-out timestamps at work and calculates time spent at work as input for most timekeeping applications.

## Details
- time is always formatted using 24hours and minutes "HH:mm"
- timezone is Europe/Oslo
- authentication is handled via .htaccess
- this is a single user application, there are no other users

## UX

The app is mainly used from a mobile device with 1080 × 2340 with DPR 3.

- 'check-in/check-out' is a button on top that displays either "check in" or "check out"
- weeks are displayed with the current week on top
- at most 8 weeks are displayed
- `<wd>` now covers the full Monday-through-Sunday span, so every weekday entry is rendered—including Saturday and Sunday.
- the row `<monday> <tuesday> <wednesday> <thursday> <friday> <saturday> <sunday>` lists those seven daily totals with two decimal precision.

## Page
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

# Details discoved in chat sessions with agent