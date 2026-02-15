<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Oslo');

const BREAK_START_MINUTES = 11 * 60 + 30;
const BREAK_END_MINUTES = 12 * 60;
const WEEKS_WINDOW = 8;

/**
 * Converts a time string in the format HH:mm to minutes from midnight.
 *
 * @param string $time Time string in the format HH:mm.
 * @return int Minutes from midnight.
 */
function minutesFromTimeString(string $time): int
{
    [$hours, $minutes] = array_map('intval', explode(':', $time));
    return $hours * 60 + $minutes;
}

/**
 * Calculates the number of break minutes between a check-in and check-out time.
 *
 * A break is counted only when the full 11:30-12:00 window is covered.
 *
 * @param int $inMinutes Minutes from midnight for the check-in time.
 * @param int $outMinutes Minutes from midnight for the check-out time.
 * @return int Number of break minutes.
 */
function breakMinutesForPair(int $inMinutes, int $outMinutes): int
{
    if ($inMinutes <= BREAK_START_MINUTES && $outMinutes >= BREAK_END_MINUTES) {
        return BREAK_END_MINUTES - BREAK_START_MINUTES;
    }
    return 0;
}

/**
 * Formats minutes as decimal hours with two digits after the decimal point.
 *
 * Examples:
 * - 60 minutes is formatted as "1.00"
 * - 90 minutes is formatted as "1.50"
 *
 * @param int $minutes Number of minutes to format.
 * @return string Decimal hour value using a dot separator.
 */
function formatHoursFromMinutes(int $minutes): string
{
    return number_format($minutes / 60, 2, '.', '');
}

/**
 * Builds a compact display range for one ISO week.
 *
 * Examples:
 * - 2026-02-09 to 2026-02-15 => "Feb 9-15"
 * - 2026-03-30 to 2026-04-05 => "Mar 30-Apr 5"
 *
 * @param DateTimeImmutable $monday Week start date.
 * @param DateTimeImmutable $sunday Week end date.
 * @return string Week range label.
 */
function formatWeekRange(DateTimeImmutable $monday, DateTimeImmutable $sunday): string
{
    $left = $monday->format('M j');
    $right = $sunday->format('M j');
    if ($monday->format('M') === $sunday->format('M')) {
        $right = $sunday->format('j');
    }
    return $left . '-' . $right;
}

/**
 * Ensures required SQLite schema elements exist.
 *
 * @param PDO $db Database connection.
 * @return void
 */
function initializeSchema(PDO $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS time_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            check_in_at TEXT NOT NULL,
            check_out_at TEXT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $db->exec('CREATE INDEX IF NOT EXISTS idx_time_entries_check_in_at ON time_entries(check_in_at)');
}

/**
 * Fetches all rows from an executed PDO statement as associative arrays.
 *
 * @param PDOStatement $statement Executed statement.
 * @return array<int, array<string, mixed>> Result rows.
 */
function fetchAllAssoc(PDOStatement $statement): array
{
    $rows = [];
    while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Keeps only one open entry by auto-closing older open rows if needed.
 *
 * The most recent open row is kept. Older open rows are closed at the later
 * of their own check-in time or the keeper check-in time.
 *
 * @param PDO $db Database connection.
 * @return array<string, mixed>|null Remaining open row or null when none exists.
 */
function enforceSingleOpenEntry(PDO $db): ?array
{
    $statement = $db->query(
        'SELECT id, check_in_at
         FROM time_entries
         WHERE check_out_at IS NULL
         ORDER BY check_in_at DESC, id DESC'
    );
    $openRows = $statement === false ? [] : fetchAllAssoc($statement);

    if (count($openRows) === 0) {
        return null;
    }
    if (count($openRows) === 1) {
        return $openRows[0];
    }

    $keeper = $openRows[0];
    $keeperCheckIn = (string) $keeper['check_in_at'];

    $db->exec('BEGIN IMMEDIATE TRANSACTION');
    try {
        $updateStatement = $db->prepare('UPDATE time_entries SET check_out_at = :check_out_at WHERE id = :id');
        for ($index = 1, $count = count($openRows); $index < $count; $index++) {
            $row = $openRows[$index];
            $rowCheckIn = (string) $row['check_in_at'];
            $closeAt = strcmp($keeperCheckIn, $rowCheckIn) >= 0 ? $keeperCheckIn : $rowCheckIn;

            $updateStatement->bindValue(':check_out_at', $closeAt, PDO::PARAM_STR);
            $updateStatement->bindValue(':id', (int) $row['id'], PDO::PARAM_INT);
            $updateStatement->execute();
            $updateStatement->closeCursor();
        }
        $db->exec('COMMIT');
    } catch (Throwable $error) {
        $db->exec('ROLLBACK');
        throw $error;
    }

    return $keeper;
}

/**
 * Validates a date string in YYYY-MM-DD format.
 *
 * @param string $date Candidate date.
 * @return string|null Normalized date or null when invalid.
 */
function normalizeDate(string $date): ?string
{
    $candidate = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if ($candidate === false || $candidate->format('Y-m-d') !== $date) {
        return null;
    }
    return $date;
}

/**
 * Validates a strict HH:mm time string.
 *
 * @param string $time Candidate time.
 * @return string|null Normalized time or null when invalid.
 */
function normalizeTime(string $time): ?string
{
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        return null;
    }

    [$hours, $minutes] = array_map('intval', explode(':', $time));
    if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
        return null;
    }

    return sprintf('%02d:%02d', $hours, $minutes);
}

/**
 * Normalizes editable time input from the modal.
 *
 * Accepts HH:mm and compact 3-4 digit values (for example 745, 1712).
 *
 * @param string $time Raw input string.
 * @return string|null Normalized HH:mm value or null when invalid/empty.
 */
function normalizeEditableTime(string $time): ?string
{
    $raw = trim($time);
    if ($raw === '') {
        return null;
    }

    $strict = normalizeTime($raw);
    if ($strict !== null) {
        return $strict;
    }

    if (!preg_match('/^\d{3,4}$/', $raw)) {
        return null;
    }

    if (strlen($raw) === 3) {
        $raw = '0' . $raw;
    }

    $hours = (int) substr($raw, 0, 2);
    $minutes = (int) substr($raw, 2, 2);
    if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
        return null;
    }

    return sprintf('%02d:%02d', $hours, $minutes);
}

/**
 * Handles header toggle action: check in when closed, check out when open.
 *
 * @param PDO $db Database connection.
 * @param DateTimeImmutable $now Current request timestamp.
 * @return void
 */
function handleToggleAction(PDO $db, DateTimeImmutable $now): void
{
    $nowSql = $now->format('Y-m-d H:i:s');
    $openEntry = enforceSingleOpenEntry($db);

    if ($openEntry === null) {
        $statement = $db->prepare('INSERT INTO time_entries (check_in_at, check_out_at) VALUES (:check_in_at, NULL)');
        $statement->bindValue(':check_in_at', $nowSql, PDO::PARAM_STR);
        $statement->execute();
    } else {
        $statement = $db->prepare('UPDATE time_entries SET check_out_at = :check_out_at WHERE id = :id');
        $statement->bindValue(':check_out_at', $nowSql, PDO::PARAM_STR);
        $statement->bindValue(':id', (int) $openEntry['id'], PDO::PARAM_INT);
        $statement->execute();
    }

    header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
    exit;
}

/**
 * Checks whether a date is editable inside the configured trailing week window.
 *
 * @param DateTimeImmutable $now Current request timestamp.
 * @param string $date Candidate date in YYYY-MM-DD.
 * @return bool True when date is inside the edit window.
 */
function isDateWithinEditWindow(DateTimeImmutable $now, string $date): bool
{
    $candidate = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if ($candidate === false || $candidate->format('Y-m-d') !== $date) {
        return false;
    }

    $windowStart = $now
        ->modify('monday this week')
        ->setTime(0, 0, 0)
        ->modify('-' . (WEEKS_WINDOW - 1) . ' weeks');
    $windowEnd = $now->setTime(0, 0, 0);
    $candidateDay = $candidate->setTime(0, 0, 0);

    return $candidateDay >= $windowStart && $candidateDay <= $windowEnd;
}

/**
 * Handles day-editor save requests.
 *
 * Replaces all rows for the posted date with validated pairs and then
 * re-applies single-open-entry safety.
 *
 * @param PDO $db Database connection.
 * @param DateTimeImmutable $now Current request timestamp.
 * @return void
 */
function handleSaveDayAction(PDO $db, DateTimeImmutable $now): void
{
    $date = isset($_POST['day_date']) ? trim((string) $_POST['day_date']) : '';
    $normalizedDate = normalizeDate($date);
    if ($normalizedDate === null) {
        header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
        exit;
    }
    if (!isDateWithinEditWindow($now, $normalizedDate)) {
        header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
        exit;
    }

    $pairIns = isset($_POST['pair_in']) && is_array($_POST['pair_in']) ? $_POST['pair_in'] : [];
    $pairOuts = isset($_POST['pair_out']) && is_array($_POST['pair_out']) ? $_POST['pair_out'] : [];
    $count = max(count($pairIns), count($pairOuts));

    $normalizedPairs = [];
    for ($index = 0; $index < $count; $index++) {
        $inRaw = isset($pairIns[$index]) ? trim((string) $pairIns[$index]) : '';
        $outRaw = isset($pairOuts[$index]) ? trim((string) $pairOuts[$index]) : '';

        if ($inRaw === '' && $outRaw === '') {
            continue;
        }

        $inTime = normalizeEditableTime($inRaw);
        if ($inTime === null) {
            continue;
        }

        $outTime = null;
        if ($outRaw !== '') {
            $outTime = normalizeEditableTime($outRaw);
            if ($outTime === null) {
                continue;
            }
            if (minutesFromTimeString($outTime) < minutesFromTimeString($inTime)) {
                continue;
            }
        }

        $normalizedPairs[] = ['in' => $inTime, 'out' => $outTime];
    }

    $db->exec('BEGIN IMMEDIATE TRANSACTION');
    try {
        $deleteStatement = $db->prepare('DELETE FROM time_entries WHERE date(check_in_at) = :day_date');
        $deleteStatement->bindValue(':day_date', $normalizedDate, PDO::PARAM_STR);
        $deleteStatement->execute();
        $deleteStatement->closeCursor();

        if (count($normalizedPairs) > 0) {
            $insertStatement = $db->prepare('INSERT INTO time_entries (check_in_at, check_out_at) VALUES (:check_in_at, :check_out_at)');
            foreach ($normalizedPairs as $pair) {
                $insertStatement->bindValue(':check_in_at', $normalizedDate . ' ' . $pair['in'] . ':00', PDO::PARAM_STR);
                if ($pair['out'] === null) {
                    $insertStatement->bindValue(':check_out_at', null, PDO::PARAM_NULL);
                } else {
                    $insertStatement->bindValue(':check_out_at', $normalizedDate . ' ' . $pair['out'] . ':00', PDO::PARAM_STR);
                }
                $insertStatement->execute();
                $insertStatement->closeCursor();
            }
        }

        $db->exec('COMMIT');
    } catch (Throwable $error) {
        $db->exec('ROLLBACK');
        throw $error;
    }

    enforceSingleOpenEntry($db);

    header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
    exit;
}

/**
 * Routes supported POST actions.
 *
 * @param PDO $db Database connection.
 * @param DateTimeImmutable $now Current request timestamp.
 * @return void
 */
function handlePostActions(PDO $db, DateTimeImmutable $now): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';
    if ($action === 'toggle-check') {
        handleToggleAction($db, $now);
    }
    if ($action === 'save-day') {
        handleSaveDayAction($db, $now);
    }
}

/**
 * Loads and shapes week/day view data for rendering.
 *
 * @param PDO $db Database connection.
 * @param DateTimeImmutable $now Current request timestamp.
 * @return array<int, array<string, mixed>> Week list with populated and missing days.
 */
function loadWeeks(PDO $db, DateTimeImmutable $now): array
{
    $startOfThisWeek = $now->modify('monday this week')->setTime(0, 0, 0);
    $windowStart = $startOfThisWeek->modify('-' . (WEEKS_WINDOW - 1) . ' weeks');

    $statement = $db->prepare(
        'SELECT check_in_at, check_out_at
         FROM time_entries
         WHERE check_in_at >= :window_start
         ORDER BY check_in_at ASC'
    );
    $statement->bindValue(':window_start', $windowStart->format('Y-m-d H:i:s'), PDO::PARAM_STR);
    $statement->execute();

    $weeks = [];
    foreach (fetchAllAssoc($statement) as $row) {
        $checkInAt = new DateTimeImmutable($row['check_in_at']);
        $checkOutAt = $row['check_out_at'] !== null ? new DateTimeImmutable($row['check_out_at']) : null;

        $weekYear = (int) $checkInAt->format('o');
        $weekNumber = (int) $checkInAt->format('W');
        $weekKey = $weekYear . '-' . str_pad((string) $weekNumber, 2, '0', STR_PAD_LEFT);
        $dayKey = $checkInAt->format('Y-m-d');

        if (!isset($weeks[$weekKey])) {
            $monday = (new DateTimeImmutable('now'))->setISODate($weekYear, $weekNumber)->setTime(0, 0, 0);
            $sunday = $monday->modify('+6 days');
            $weeks[$weekKey] = [
                'week_number' => $weekNumber,
                'range' => formatWeekRange($monday, $sunday),
                'week_start' => $monday->format('Y-m-d'),
                'days' => [],
            ];
        }

        if (!isset($weeks[$weekKey]['days'][$dayKey])) {
            $weeks[$weekKey]['days'][$dayKey] = [
                'date' => $dayKey,
                'weekday' => $checkInAt->format('l'),
                'pairs' => [],
                'total_minutes' => 0,
                'break_minutes' => 0,
            ];
        }

        $checkInLabel = $checkInAt->format('H:i');
        $checkOutLabel = $checkOutAt !== null ? $checkOutAt->format('H:i') : '';
        $weeks[$weekKey]['days'][$dayKey]['pairs'][] = ['in' => $checkInLabel, 'out' => $checkOutLabel];

        if ($checkOutAt !== null) {
            $inMinutes = minutesFromTimeString($checkInLabel);
            $outMinutes = minutesFromTimeString($checkOutLabel);
            $pairMinutes = max(0, $outMinutes - $inMinutes);
            $breakMinutes = breakMinutesForPair($inMinutes, $outMinutes);

            $weeks[$weekKey]['days'][$dayKey]['break_minutes'] += $breakMinutes;
            $weeks[$weekKey]['days'][$dayKey]['total_minutes'] += max(0, $pairMinutes - $breakMinutes);
        }
    }

    usort(
        $weeks,
        static fn(array $left, array $right): int => strcmp($right['week_start'], $left['week_start'])
    );
    $weeks = array_slice($weeks, 0, WEEKS_WINDOW);

    foreach ($weeks as &$week) {
        ksort($week['days']);
        $days = [];
        foreach ($week['days'] as $day) {
            if (count($day['pairs']) === 0) {
                continue;
            }

            $days[] = [
                'date' => $day['date'],
                'weekday' => $day['weekday'],
                'pairs' => $day['pairs'],
                'total' => formatHoursFromMinutes($day['total_minutes']),
                'break' => formatHoursFromMinutes($day['break_minutes']),
            ];
        }

        $missingDays = [];
        $weekStart = new DateTimeImmutable($week['week_start']);
        for ($offset = 0; $offset < 7; $offset++) {
            $candidate = $weekStart->modify('+' . $offset . ' days');
            $candidateKey = $candidate->format('Y-m-d');
            if (isset($week['days'][$candidateKey])) {
                continue;
            }

            $missingDays[] = [
                'date' => $candidateKey,
                'weekday' => $candidate->format('l'),
            ];
        }

        $week['days'] = $days;
        $week['missing_days'] = $missingDays;
        unset($week['week_start']);
    }
    unset($week);

    return $weeks;
}

$now = new DateTimeImmutable('now');
$db = new PDO('sqlite:' . __DIR__ . '/timestamp.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
initializeSchema($db);
handlePostActions($db, $now);

$openEntry = enforceSingleOpenEntry($db);
$isCheckedIn = $openEntry !== null;

$headerTitle = $isCheckedIn
    ? (new DateTimeImmutable((string) $openEntry['check_in_at']))->format('H:i')
    : 'Timestamp';
$actionLabel = $isCheckedIn ? 'Check out' : $now->format('H:i');
$versionTimestamp = (new DateTimeImmutable('@' . (string) filemtime(__FILE__)))
    ->setTimezone(new DateTimeZone('Europe/Oslo'))
    ->format('Y-m-d-H-i');
$editWindowStartDate = $now
    ->modify('monday this week')
    ->setTime(0, 0, 0)
    ->modify('-' . (WEEKS_WINDOW - 1) . ' weeks')
    ->format('Y-m-d');
$editWindowEndDate = $now->format('Y-m-d');
$weeks = loadWeeks($db, $now);

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#1d7a0a" />
    <title>Timestamp Tracker</title>
    <link rel="manifest" href="manifest.json" crossorigin="use-credentials" />
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
    <link rel="icon" href="favicon-32x32.png" type="image/png" sizes="32x32" />
    <link rel="apple-touch-icon" href="apple-touch-icon.png" sizes="180x180" />
    <link rel="stylesheet" href="timestamp.css" />
  </head>
  <body>
    <div class="device-frame" role="main">
      <header>
        <h1><?= htmlspecialchars($headerTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <form method="post">
          <input type="hidden" name="action" value="toggle-check" />
          <button type="submit" aria-label="Check in or out"><?= htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8') ?></button>
        </form>
      </header>

      <main>
        <?php foreach ($weeks as $week): ?>
          <?php
            $visibleDays = array_values(array_filter(
                $week['days'],
                static fn(array $day): bool => count($day['pairs']) > 0
            ));
            $missingDays = $week['missing_days'] ?? [];
            if (count($visibleDays) === 0 && count($missingDays) === 0) {
                continue;
            }
          ?>
          <section class="week-card" aria-label="Week <?= (int) $week['week_number'] ?> overview">
            <div class="week-heading">
              <span class="pill">Week <?= (int) $week['week_number'] ?></span>
              <span aria-hidden="true">•</span>
              <span><?= htmlspecialchars($week['range'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <?php foreach ($visibleDays as $day): ?>
              <div
                class="day-row"
                role="button"
                tabindex="0"
                data-day="<?= htmlspecialchars($day['weekday'], ENT_QUOTES, 'UTF-8') ?>"
                data-date="<?= htmlspecialchars($day['date'], ENT_QUOTES, 'UTF-8') ?>"
                aria-label="Edit <?= htmlspecialchars($day['weekday'], ENT_QUOTES, 'UTF-8') ?>"
              >
                <span class="weekday"><?= htmlspecialchars($day['weekday'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="times">
                  <span class="time-pairs">
                    <?php foreach ($day['pairs'] as $pair): ?>
                      <span class="time-pair">
                        <?= htmlspecialchars($pair['in'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($pair['out'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    <?php endforeach; ?>
                  </span>
                </span>
                <span class="totals">
                  <?= htmlspecialchars($day['total'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($day['break'], ENT_QUOTES, 'UTF-8') ?>
                </span>
              </div>
            <?php endforeach; ?>

            <?php if (count($missingDays) > 0): ?>
              <div class="missing-days" aria-label="Weekdays without entries">
                <div class="missing-days-list">
                  <?php foreach ($missingDays as $missingDay): ?>
                    <button
                      type="button"
                      class="missing-day-btn"
                      data-day="<?= htmlspecialchars($missingDay['weekday'], ENT_QUOTES, 'UTF-8') ?>"
                      data-date="<?= htmlspecialchars($missingDay['date'], ENT_QUOTES, 'UTF-8') ?>"
                      aria-label="Add entries for <?= htmlspecialchars($missingDay['weekday'], ENT_QUOTES, 'UTF-8') ?>"
                    >
                      <?= htmlspecialchars($missingDay['weekday'], ENT_QUOTES, 'UTF-8') ?>
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </section>
        <?php endforeach; ?>
      </main>
      <div class="footer-tools">
        <input
          type="date"
          id="calendarDateInput"
          class="visually-hidden"
          min="<?= htmlspecialchars($editWindowStartDate, ENT_QUOTES, 'UTF-8') ?>"
          max="<?= htmlspecialchars($editWindowEndDate, ENT_QUOTES, 'UTF-8') ?>"
          aria-label="Select date"
        />
        <button type="button" id="calendarAddBtn" class="calendar-add-btn" aria-label="Add entries for date">
          📅 add entries for other days
        </button>
      </div>
      <footer class="version-stamp" aria-label="Version stamp">
        <?= htmlspecialchars($versionTimestamp, ENT_QUOTES, 'UTF-8') ?>
      </footer>
    </div>

    <div class="editor-overlay" id="dayEditor" aria-hidden="true">
      <form class="editor-modal" method="post" role="dialog" aria-modal="true" aria-label="Edit day">
        <input type="hidden" name="action" value="save-day" />
        <input type="hidden" name="day_date" id="editorDayDate" value="" />
        <h2 class="visually-hidden" id="editorDayLabel">Edit day</h2>
        <div class="pair-grid" id="pairGrid"></div>
        <div class="editor-errors" id="editorErrors" aria-live="polite"></div>
        <div class="editor-hint" id="breakHint" hidden>
          Overlap with 11:30-12:00 detected. Break is counted only when the full 11:30-12:00 window is covered.
        </div>
        <div class="editor-actions">
          <button type="button" class="add-pair-btn" id="addPairBtn">Add pair</button>
        </div>
        <div class="editor-footer">
          <button type="button" class="modal-btn modal-btn-cancel" id="editorCancel">Cancel</button>
          <button type="submit" class="modal-btn modal-btn-save" id="editorSave">Save</button>
        </div>
      </form>
    </div>

    <script src="timestamp.js"></script>
  </body>
</html>
