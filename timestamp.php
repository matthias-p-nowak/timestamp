<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Oslo');

const BREAK_START_MINUTES = 11 * 60 + 30;
const BREAK_END_MINUTES = 12 * 60;
const WEEKS_WINDOW = 8;

function minutesFromTimeString(string $time): int
{
    [$hours, $minutes] = array_map('intval', explode(':', $time));
    return $hours * 60 + $minutes;
}

function breakMinutesForPair(int $inMinutes, int $outMinutes): int
{
    if ($inMinutes <= BREAK_START_MINUTES && $outMinutes >= BREAK_END_MINUTES) {
        return BREAK_END_MINUTES - BREAK_START_MINUTES;
    }
    return 0;
}

function formatHoursFromMinutes(int $minutes): string
{
    return number_format($minutes / 60, 2, '.', '');
}

function formatWeekRange(DateTimeImmutable $monday, DateTimeImmutable $sunday): string
{
    $left = $monday->format('M j');
    $right = $sunday->format('M j');
    if ($monday->format('M') === $sunday->format('M')) {
        $right = $sunday->format('j');
    }
    return $left . '-' . $right;
}

function initializeSchema(SQLite3 $db): void
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

function findOpenEntry(SQLite3 $db): ?array
{
    $result = $db->query(
        'SELECT id, check_in_at
         FROM time_entries
         WHERE check_out_at IS NULL
         ORDER BY check_in_at DESC
         LIMIT 1'
    );

    if ($result === false) {
        return null;
    }

    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row !== false ? $row : null;
}

function enforceSingleOpenEntry(SQLite3 $db): ?array
{
    $result = $db->query(
        'SELECT id, check_in_at
         FROM time_entries
         WHERE check_out_at IS NULL
         ORDER BY check_in_at DESC, id DESC'
    );
    if ($result === false) {
        return null;
    }

    $openRows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if ($row === false) {
            break;
        }
        $openRows[] = $row;
    }

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

            $updateStatement->bindValue(':check_out_at', $closeAt, SQLITE3_TEXT);
            $updateStatement->bindValue(':id', (int) $row['id'], SQLITE3_INTEGER);
            $updateStatement->execute();
            $updateStatement->clear();
        }
        $db->exec('COMMIT');
    } catch (Throwable $error) {
        $db->exec('ROLLBACK');
        throw $error;
    }

    return $keeper;
}

function normalizeDate(string $date): ?string
{
    $candidate = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if ($candidate === false || $candidate->format('Y-m-d') !== $date) {
        return null;
    }
    return $date;
}

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

function handleToggleAction(SQLite3 $db, DateTimeImmutable $now): void
{
    $nowSql = $now->format('Y-m-d H:i:s');
    $openEntry = enforceSingleOpenEntry($db);

    if ($openEntry === null) {
        $statement = $db->prepare('INSERT INTO time_entries (check_in_at, check_out_at) VALUES (:check_in_at, NULL)');
        $statement->bindValue(':check_in_at', $nowSql, SQLITE3_TEXT);
        $statement->execute();
    } else {
        $statement = $db->prepare('UPDATE time_entries SET check_out_at = :check_out_at WHERE id = :id');
        $statement->bindValue(':check_out_at', $nowSql, SQLITE3_TEXT);
        $statement->bindValue(':id', (int) $openEntry['id'], SQLITE3_INTEGER);
        $statement->execute();
    }

    header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
    exit;
}

function handleSaveDayAction(SQLite3 $db): void
{
    $date = isset($_POST['day_date']) ? trim((string) $_POST['day_date']) : '';
    $normalizedDate = normalizeDate($date);
    if ($normalizedDate === null) {
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
        $deleteStatement->bindValue(':day_date', $normalizedDate, SQLITE3_TEXT);
        $deleteStatement->execute();

        if (count($normalizedPairs) > 0) {
            $insertStatement = $db->prepare('INSERT INTO time_entries (check_in_at, check_out_at) VALUES (:check_in_at, :check_out_at)');
            foreach ($normalizedPairs as $pair) {
                $insertStatement->bindValue(':check_in_at', $normalizedDate . ' ' . $pair['in'] . ':00', SQLITE3_TEXT);
                if ($pair['out'] === null) {
                    $insertStatement->bindValue(':check_out_at', null, SQLITE3_NULL);
                } else {
                    $insertStatement->bindValue(':check_out_at', $normalizedDate . ' ' . $pair['out'] . ':00', SQLITE3_TEXT);
                }
                $insertStatement->execute();
                $insertStatement->clear();
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

function handlePostActions(SQLite3 $db, DateTimeImmutable $now): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';
    if ($action === 'toggle-check') {
        handleToggleAction($db, $now);
    }
    if ($action === 'save-day') {
        handleSaveDayAction($db);
    }
}

function loadWeeks(SQLite3 $db, DateTimeImmutable $now): array
{
    $startOfThisWeek = $now->modify('monday this week')->setTime(0, 0, 0);
    $windowStart = $startOfThisWeek->modify('-' . (WEEKS_WINDOW - 1) . ' weeks');

    $statement = $db->prepare(
        'SELECT check_in_at, check_out_at
         FROM time_entries
         WHERE check_in_at >= :window_start
         ORDER BY check_in_at ASC'
    );
    $statement->bindValue(':window_start', $windowStart->format('Y-m-d H:i:s'), SQLITE3_TEXT);
    $queryResult = $statement->execute();

    $weeks = [];
    while ($row = $queryResult->fetchArray(SQLITE3_ASSOC)) {
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
        $week['days'] = $days;
        unset($week['week_start']);
    }
    unset($week);

    return $weeks;
}

$now = new DateTimeImmutable('now');
$db = new SQLite3(__DIR__ . '/timestamp.db');
$db->enableExceptions(true);
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
$weeks = loadWeeks($db, $now);

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Timestamp Tracker</title>
    <style>
      :root {
        font-family: "Source Sans Pro", system-ui, -apple-system, BlinkMacSystemFont,
          "Segoe UI", sans-serif;
        color: #111;
        background: #f4f4f6;
      }

      * {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        padding: 1rem;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        background: linear-gradient(180deg, #f4f4f6 0%, #e9edef 100%);
      }

      .device-frame {
        width: min(420px, 100%);
        background: #fff;
        border-radius: 32px;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
        padding-bottom: 1rem;
        overflow: hidden;
      }

      header {
        padding: 1rem 1.25rem;
        background: #1d7a0a;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        position: sticky;
        top: 0;
      }

      header h1 {
        margin: 0;
        font-size: 1rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
      }

      header form {
        margin: 0;
      }

      header button {
        border: none;
        border-radius: 999px;
        padding: 0.65rem 1.5rem;
        font-size: 0.9rem;
        color: #183812;
        background: #08f700;
        font-weight: 600;
        box-shadow: inset 0 0 0 1px rgba(18, 28, 56, 0.15);
      }

      main {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        padding: 1rem 1rem 1.25rem;
      }

      .week-card {
        background: #f9fbff;
        border-radius: 18px;
        padding: 0.95rem 1rem 1rem;
        border: 1px solid rgba(18, 28, 56, 0.08);
      }

      .week-heading {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #3d4f71;
        margin-bottom: 0.5rem;
        letter-spacing: 0.05em;
      }

      .day-row {
        display: grid;
        grid-template-columns: 1.5fr 1.5fr 1fr;
        padding: 0.45rem 0;
        border-bottom: 1px solid rgba(18, 28, 56, 0.08);
        font-size: 0.9rem;
        gap: 0.35rem;
        align-items: center;
        cursor: pointer;
      }

      .day-row:last-child {
        border-bottom: none;
      }

      .day-row:hover {
        background: rgba(29, 122, 10, 0.04);
      }

      .day-row:focus-visible {
        outline: 2px solid #1d7a0a;
        outline-offset: 2px;
        border-radius: 6px;
      }

      .weekday {
        font-weight: 600;
        color: #1f2c45;
      }

      .times {
        color: #4a5770;
      }

      .time-pairs {
        display: grid;
        justify-items: start;
        gap: 0.15rem;
      }

      .time-pair {
        white-space: nowrap;
      }

      .totals {
        font-family: "JetBrains Mono", "Courier New", monospace;
        color: #0c7b3a;
        text-align: right;
        white-space: nowrap;
      }

      .editor-overlay {
        position: fixed;
        inset: 0;
        background: rgba(12, 18, 27, 0.55);
        display: none;
        align-items: flex-end;
        justify-content: center;
        padding: 0.8rem;
        z-index: 50;
      }

      .editor-overlay.is-open {
        display: flex;
      }

      .editor-modal {
        width: min(420px, 100%);
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 22px 45px rgba(10, 15, 24, 0.35);
        padding: 1rem;
      }

      .pair-grid {
        display: grid;
        gap: 0.45rem;
      }

      .pair-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.45rem;
      }

      .pair-input {
        width: 100%;
        border: 1px solid #d4dce8;
        border-radius: 9px;
        padding: 0.42rem 0.52rem;
        font-size: 0.86rem;
        color: #1f2c45;
      }

      .pair-input.is-invalid {
        border-color: #c62828;
        background: #fff3f2;
      }

      .editor-errors {
        margin-top: 0.6rem;
        color: #b42318;
        font-size: 0.76rem;
        min-height: 1.1rem;
      }

      .editor-hint {
        margin-top: 0.35rem;
        color: #666d78;
        font-size: 0.72rem;
        line-height: 1.3;
      }

      .editor-actions {
        display: flex;
        justify-content: flex-start;
        margin-top: 0.65rem;
      }

      .add-pair-btn {
        border: none;
        border-radius: 999px;
        background: #e8eef8;
        color: #1f2c45;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.42rem 0.85rem;
      }

      .editor-footer {
        margin-top: 0.85rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.45rem;
      }

      .modal-btn {
        border: none;
        border-radius: 999px;
        padding: 0.5rem 0.95rem;
        font-size: 0.82rem;
        font-weight: 600;
      }

      .modal-btn-cancel {
        background: #edf1f8;
        color: #1f2c45;
      }

      .modal-btn-save {
        background: #1f8a0a;
        color: #fff;
      }

      .pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
        background: #e1ffeb;
        color: #03a125;
        font-size: 0.75rem;
      }

      .version-stamp {
        margin: 0.75rem 0.25rem 0;
        text-align: center;
        color: #8a9099;
        font-size: 0.66rem;
        letter-spacing: 0.03em;
      }

      .visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: -1px;
        border: 0;
        padding: 0;
        overflow: hidden;
        clip: rect(0 0 0 0);
      }
    </style>
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
            if (count($visibleDays) === 0) {
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
          </section>
        <?php endforeach; ?>
      </main>
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

    <script>
      const overlay = document.getElementById("dayEditor");
      const pairGrid = document.getElementById("pairGrid");
      const addPairBtn = document.getElementById("addPairBtn");
      const cancelButton = document.getElementById("editorCancel");
      const saveButton = document.getElementById("editorSave");
      const dayDateInput = document.getElementById("editorDayDate");
      const editorForm = overlay.querySelector("form");
      const editorErrors = document.getElementById("editorErrors");
      const breakHint = document.getElementById("breakHint");
      const BREAK_START_MINUTES = (11 * 60) + 30;
      const BREAK_END_MINUTES = 12 * 60;

      function parseCompactTime(value) {
        const raw = value.trim();
        if (raw === "") {
          return null;
        }

        const fullMatch = raw.match(/^(\d{2}):(\d{2})$/);
        if (fullMatch) {
          const hours = Number(fullMatch[1]);
          const minutes = Number(fullMatch[2]);
          if (hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59) {
            return { formatted: `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`, totalMinutes: (hours * 60) + minutes };
          }
          return null;
        }

        if (!/^\d{3,4}$/.test(raw)) {
          return null;
        }

        const normalized = raw.length === 3 ? `0${raw}` : raw;
        const hours = Number(normalized.slice(0, 2));
        const minutes = Number(normalized.slice(2, 4));
        if (hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
          return null;
        }

        return { formatted: `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`, totalMinutes: (hours * 60) + minutes };
      }

      function addPairInputRow(inValue, outValue) {
        const row = document.createElement("div");
        row.className = "pair-row";

        const inInput = document.createElement("input");
        inInput.className = "pair-input";
        inInput.type = "text";
        inInput.placeholder = "Check in";
        inInput.name = "pair_in[]";
        inInput.value = inValue || "";

        const outInput = document.createElement("input");
        outInput.className = "pair-input";
        outInput.type = "text";
        outInput.placeholder = "Check out";
        outInput.name = "pair_out[]";
        outInput.value = outValue || "";

        inInput.addEventListener("input", validateEditorRows);
        outInput.addEventListener("input", validateEditorRows);

        row.appendChild(inInput);
        row.appendChild(outInput);
        pairGrid.appendChild(row);
      }

      function setInputValidity(input, isValid) {
        input.classList.toggle("is-invalid", !isValid);
      }

      function validateEditorRows() {
        const rows = Array.from(pairGrid.querySelectorAll(".pair-row"));
        let firstError = "";
        let hasError = false;
        let hasOverlapWithBreakWindow = false;

        rows.forEach((row) => {
          const inInput = row.querySelector('input[name="pair_in[]"]');
          const outInput = row.querySelector('input[name="pair_out[]"]');
          const inValue = inInput.value.trim();
          const outValue = outInput.value.trim();
          let rowValid = true;

          if (inValue === "" && outValue === "") {
            setInputValidity(inInput, true);
            setInputValidity(outInput, true);
            return;
          }

          const inParsed = parseCompactTime(inValue);
          const outParsed = outValue === "" ? null : parseCompactTime(outValue);

          if (inValue === "") {
            rowValid = false;
            if (!firstError) {
              firstError = "Each non-empty row needs a check-in time.";
            }
          } else if (inParsed === null) {
            rowValid = false;
            if (!firstError) {
              firstError = "Use time as HH:mm, 745, or 1712.";
            }
          }

          if (outValue !== "" && outParsed === null) {
            rowValid = false;
            if (!firstError) {
              firstError = "Check-out has invalid time format.";
            }
          }

          if (inParsed !== null && outParsed !== null && outParsed.totalMinutes < inParsed.totalMinutes) {
            rowValid = false;
            if (!firstError) {
              firstError = "Check-out cannot be earlier than check-in.";
            }
          }

          if (rowValid && inParsed !== null && outParsed !== null) {
            if (inParsed.totalMinutes < BREAK_END_MINUTES && outParsed.totalMinutes > BREAK_START_MINUTES) {
              hasOverlapWithBreakWindow = true;
            }
          }

          setInputValidity(inInput, rowValid || inParsed !== null);
          setInputValidity(outInput, rowValid || outValue === "" || outParsed !== null);

          if (!rowValid) {
            hasError = true;
          }
        });

        editorErrors.textContent = hasError ? firstError : "";
        breakHint.hidden = !hasOverlapWithBreakWindow;
        saveButton.disabled = hasError;
        saveButton.setAttribute("aria-disabled", hasError ? "true" : "false");
        return !hasError;
      }

      function fillModalFromRow(row) {
        pairGrid.innerHTML = "";
        const pairs = Array.from(row.querySelectorAll(".time-pair")).map((item) => item.textContent.trim());

        if (pairs.length === 0) {
          addPairInputRow("", "");
          return;
        }

        pairs.forEach((text) => {
          const split = text.split("-");
          const inValue = split[0] ? split[0].trim() : "";
          const outValue = split[1] ? split[1].trim() : "";
          addPairInputRow(inValue, outValue);
        });
      }

      function openModal(row) {
        fillModalFromRow(row);
        dayDateInput.value = row.dataset.date || "";
        overlay.classList.add("is-open");
        overlay.setAttribute("aria-hidden", "false");
        validateEditorRows();
      }

      function closeModal() {
        overlay.classList.remove("is-open");
        overlay.setAttribute("aria-hidden", "true");
      }

      document.querySelectorAll(".day-row").forEach((row) => {
        row.addEventListener("click", () => {
          openModal(row);
        });

        row.addEventListener("keydown", (event) => {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            openModal(row);
          }
        });
      });

      cancelButton.addEventListener("click", closeModal);

      addPairBtn.addEventListener("click", () => {
        addPairInputRow("", "");
        validateEditorRows();
      });

      overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
          closeModal();
        }
      });

      editorForm.addEventListener("submit", (event) => {
        if (!validateEditorRows()) {
          event.preventDefault();
        }
      });
    </script>
  </body>
</html>
