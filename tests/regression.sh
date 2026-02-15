#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB_PATH="$ROOT_DIR/timestamp.db"
PHP_FILE="$ROOT_DIR/timestamp.php"
JS_FILE="$ROOT_DIR/timestamp.js"

restore_db() {
  if [[ -n "${DB_BACKUP_PATH:-}" && -f "${DB_BACKUP_PATH:-}" ]]; then
    cp "$DB_BACKUP_PATH" "$DB_PATH"
    rm -f "$DB_BACKUP_PATH"
  elif [[ "${DB_CREATED_FOR_TEST:-0}" -eq 1 ]]; then
    rm -f "$DB_PATH"
  fi
}
trap restore_db EXIT

if [[ -f "$DB_PATH" ]]; then
  DB_BACKUP_PATH="$(mktemp)"
  cp "$DB_PATH" "$DB_BACKUP_PATH"
  DB_CREATED_FOR_TEST=0
else
  DB_BACKUP_PATH=""
  DB_CREATED_FOR_TEST=1
fi

assert_eq() {
  local actual="$1"
  local expected="$2"
  local message="$3"
  if [[ "$actual" != "$expected" ]]; then
    echo "FAIL: $message (expected '$expected', got '$actual')" >&2
    exit 1
  fi
}

query_scalar() {
  local sql="$1"
  php -r "\$db=new SQLite3('$DB_PATH'); \$v=\$db->querySingle(\"$sql\"); echo \$v;"
}

run_post() {
  local payload_php="$1"
  php -r "\$_SERVER['REQUEST_METHOD']='POST'; \$_SERVER['REQUEST_URI']='/timestamp.php'; \$_POST=$payload_php; include '$PHP_FILE';"
}

render_once() {
  php -r "ob_start(); include '$PHP_FILE'; ob_end_clean();"
}

in_window_day() {
  local week_shift="$1"
  local day_shift="$2"
  php -r "date_default_timezone_set('Europe/Oslo'); \$now=new DateTimeImmutable('now'); \$d=\$now->modify('monday this week')->modify('${week_shift} weeks')->modify('${day_shift} days'); echo \$d->format('Y-m-d');"
}

SAVE_DAY_A="$(in_window_day -1 0)"
SAVE_DAY_B="$(in_window_day -2 2)"
FUTURE_DAY="$(php -r "date_default_timezone_set('Europe/Oslo'); echo (new DateTimeImmutable('now'))->modify('+1 day')->format('Y-m-d');")"

echo "Preparing clean DB state..."
render_once
php -r "\$db=new SQLite3('$DB_PATH'); \$db->exec('DELETE FROM time_entries');"

echo "Test 1/7: check-in/check-out toggle behavior"
assert_eq "$(query_scalar "SELECT COUNT(*) FROM time_entries WHERE check_out_at IS NULL")" "0" "open rows before toggle"
run_post "['action'=>'toggle-check']"
assert_eq "$(query_scalar "SELECT COUNT(*) FROM time_entries WHERE check_out_at IS NULL")" "1" "open rows after check-in"
run_post "['action'=>'toggle-check']"
assert_eq "$(query_scalar "SELECT COUNT(*) FROM time_entries WHERE check_out_at IS NULL")" "0" "open rows after check-out"

echo "Test 2/7: save-day persistence and delete-on-empty"
php -r "\$db=new SQLite3('$DB_PATH'); \$db->exec(\"INSERT INTO time_entries (check_in_at, check_out_at) VALUES ('${SAVE_DAY_A} 08:00:00','${SAVE_DAY_A} 10:00:00')\");"
run_post "['action'=>'save-day','day_date'=>'${SAVE_DAY_A}','pair_in'=>['09:15'],'pair_out'=>['11:45']]"
assert_eq "$(query_scalar "SELECT COUNT(*) FROM time_entries WHERE date(check_in_at)='${SAVE_DAY_A}'")" "1" "day replaced with one row"
assert_eq "$(query_scalar "SELECT check_in_at FROM time_entries WHERE date(check_in_at)='${SAVE_DAY_A}'")" "${SAVE_DAY_A} 09:15:00" "saved check-in value"
assert_eq "$(query_scalar "SELECT check_out_at FROM time_entries WHERE date(check_in_at)='${SAVE_DAY_A}'")" "${SAVE_DAY_A} 11:45:00" "saved check-out value"
run_post "['action'=>'save-day','day_date'=>'${SAVE_DAY_A}','pair_in'=>[''],'pair_out'=>['']]"
assert_eq "$(query_scalar "SELECT COUNT(*) FROM time_entries WHERE date(check_in_at)='${SAVE_DAY_A}'")" "0" "day deleted when row empty"

echo "Test 3/7: compact time parsing (745, 1712)"
run_post "['action'=>'save-day','day_date'=>'${SAVE_DAY_B}','pair_in'=>['745','1712'],'pair_out'=>['1130','1830']]"
assert_eq "$(query_scalar "SELECT check_in_at FROM time_entries WHERE date(check_in_at)='${SAVE_DAY_B}' ORDER BY check_in_at ASC LIMIT 1")" "${SAVE_DAY_B} 07:45:00" "compact check-in parse"
assert_eq "$(query_scalar "SELECT check_out_at FROM time_entries WHERE date(check_in_at)='${SAVE_DAY_B}' ORDER BY check_in_at ASC LIMIT 1")" "${SAVE_DAY_B} 11:30:00" "compact check-out parse"

echo "Test 4/7: break rule full coverage only"
break_values="$(php -r "ob_start(); include '$PHP_FILE'; ob_end_clean(); echo breakMinutesForPair((11*60)+40,(12*60)+10).' '.breakMinutesForPair((11*60)+0,(12*60)+30);")"
assert_eq "$break_values" "0 30" "break rule partial vs full coverage"

echo "Test 5/7: modal overlap hint visibility rule (source check)"
grep -q "inParsed.totalMinutes < BREAK_END_MINUTES && outParsed.totalMinutes > BREAK_START_MINUTES" "$JS_FILE"
grep -q "breakHint.hidden = !hasOverlapWithBreakWindow;" "$JS_FILE"
grep -q "Break is counted only when the full 11:30-12:00 window is covered." "$PHP_FILE"

echo "Test 6/7: missing-weekday add-entry wiring (source check)"
grep -q "missing_days" "$PHP_FILE"
grep -q "class=\"missing-day-btn\"" "$PHP_FILE"
grep -q "missingDayButtons: \".missing-day-btn\"" "$JS_FILE"
grep -q "querySelectorAll(UI_CONFIG.selectors.missingDayButtons)" "$JS_FILE"

echo "Test 7/7: date picker range enforcement (server + source check)"
run_post "['action'=>'save-day','day_date'=>'${FUTURE_DAY}','pair_in'=>['09:00'],'pair_out'=>['10:00']]"
assert_eq "$(query_scalar "SELECT COUNT(*) FROM time_entries WHERE date(check_in_at)='${FUTURE_DAY}'")" "0" "future date rejected by backend"
grep -q "id=\"calendarDateInput\"" "$PHP_FILE"
grep -q "id=\"calendarAddBtn\"" "$PHP_FILE"
grep -q "setDatePickerRangeToLast8Weeks" "$JS_FILE"

echo "PASS: all regression checks passed."
