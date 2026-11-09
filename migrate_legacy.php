<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/includes/functions.php';

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "This PHP installation does not have the pdo_sqlite extension enabled.\n");
    exit(1);
}

$source = $argv[1] ?? __DIR__ . DIRECTORY_SEPARATOR . 'MedCRM' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'Debug' . DIRECTORY_SEPARATOR . 'medCRM.db';
$sourcePath = realpath($source);
if ($sourcePath === false || !is_file($sourcePath)) {
    fwrite(STDERR, "Legacy database not found. Usage: php migrate_legacy.php [path-to-medCRM.db]\n");
    exit(1);
}

$legacy = new PDO('sqlite:' . $sourcePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$target = database();
if (!$target->query("SHOW TABLES LIKE 'patients'")->fetchColumn()) {
    fwrite(STDERR, "Careflow tables are missing. Complete setup at install.php first.\n");
    exit(1);
}

$tables = $legacy->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
$available = array_fill_keys($tables, true);
$get = static fn(array $row, string $key, mixed $default = null): mixed => $row[$key] ?? $row[strtolower($key)] ?? $default;
$asDate = static function (mixed $value): ?string {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    foreach (['!Y-m-d', '!m/d/Y', '!n/j/Y', '!d/m/Y'] as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value);
        if ($date && $date->format(substr($format, 1)) === $value) {
            return $date->format('Y-m-d');
        }
    }
    try {
        return (new DateTimeImmutable($value))->format('Y-m-d');
    } catch (Throwable) {
        return null;
    }
};
$asTime = static function (mixed $value): ?string {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    foreach (['!H:i', '!H:i:s', '!g:i A', '!g:i a'] as $format) {
        $time = DateTimeImmutable::createFromFormat($format, $value);
        if ($time) {
            return $time->format('H:i:s');
        }
    }
    return null;
};
$existing = static function (PDO $pdo, string $table, int $legacyId): ?int {
    $statement = $pdo->prepare("SELECT id FROM {$table} WHERE legacy_source_id=?");
    $statement->execute([$legacyId]);
    $id = $statement->fetchColumn();
    return $id === false ? null : (int) $id;
};
$counts = ['patients' => 0, 'appointments' => 0, 'reminders' => 0, 'discussions' => 0, 'replies' => 0];
$patientMap = [];
$forumMap = [];

$target->beginTransaction();
try {
    if (isset($available['patients'])) {
        foreach ($legacy->query('SELECT * FROM patients ORDER BY ID') as $row) {
            $legacyId = (int) $get($row, 'ID', 0);
            if ($legacyId < 1) {
                continue;
            }
            $values = [
                (string) $get($row, 'Name', 'Unknown patient'),
                (string) $get($row, 'Contact', ''),
                null,
                $asDate($get($row, 'Birthday')),
                (string) $get($row, 'BloodType', ''),
                (string) $get($row, 'Gender', ''),
                (string) $get($row, 'Address', ''),
                (string) $get($row, 'Notes', ''),
                $legacyId,
            ];
            $id = $existing($target, 'patients', $legacyId);
            if ($id) {
                $target->prepare('UPDATE patients SET name=?, contact=?, email=?, birthday=?, blood_type=?, gender=?, address=?, notes=? WHERE legacy_source_id=?')->execute($values);
            } else {
                $target->prepare('INSERT INTO patients (name, contact, email, birthday, blood_type, gender, address, notes, legacy_source_id) VALUES (?,?,?,?,?,?,?,?,?)')->execute($values);
                $id = (int) $target->lastInsertId();
                $counts['patients']++;
            }
            $patientMap[$legacyId] = $id;
        }
    }

    if (isset($available['appointments'])) {
        foreach ($legacy->query('SELECT * FROM appointments ORDER BY ID') as $row) {
            $legacyId = (int) $get($row, 'ID', 0);
            $date = $asDate($get($row, 'Date'));
            if ($legacyId < 1 || !$date) {
                continue;
            }
            $status = strtolower(trim((string) $get($row, 'Status', 'scheduled')));
            if (!in_array($status, ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'], true)) {
                $status = 'scheduled';
            }
            $patientId = null;
            $patientName = trim((string) $get($row, 'Interviewee', ''));
            foreach ($patientMap as $oldId => $newId) {
                $statement = $target->prepare('SELECT name FROM patients WHERE id=?');
                $statement->execute([$newId]);
                if (strcasecmp((string) $statement->fetchColumn(), $patientName) === 0) {
                    $patientId = $newId;
                    break;
                }
            }
            $values = [
                $patientId,
                (string) $get($row, 'Interviewer', 'Care team'),
                $patientName ?: 'Patient visit',
                (string) $get($row, 'IntervieweePosition', ''),
                (string) $get($row, 'Facility', ''),
                (string) $get($row, 'Description', ''),
                $date,
                $asTime($get($row, 'Time')),
                $status,
                $legacyId,
            ];
            if ($existing($target, 'appointments', $legacyId)) {
                $target->prepare('UPDATE appointments SET patient_id=?, interviewer=?, interviewee=?, interviewee_position=?, facility=?, description=?, appointment_date=?, appointment_time=?, status=? WHERE legacy_source_id=?')->execute($values);
            } else {
                $target->prepare('INSERT INTO appointments (patient_id, interviewer, interviewee, interviewee_position, facility, description, appointment_date, appointment_time, status, legacy_source_id) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute($values);
                $counts['appointments']++;
            }
        }
    }

    if (isset($available['reminders'])) {
        foreach ($legacy->query('SELECT * FROM reminders ORDER BY ID') as $row) {
            $legacyId = (int) $get($row, 'ID', 0);
            if ($legacyId < 1) {
                continue;
            }
            $repeat = strtolower(trim((string) $get($row, 'Repeat', 'none')));
            if (!in_array($repeat, ['none', 'daily', 'weekly', 'monthly'], true)) {
                $repeat = 'none';
            }
            $values = [
                (string) $get($row, 'Title', 'Reminder'),
                (string) $get($row, 'Description', ''),
                $asDate($get($row, 'Date')),
                $asTime($get($row, 'Time')),
                $repeat,
                $legacyId,
            ];
            if ($existing($target, 'reminders', $legacyId)) {
                $target->prepare('UPDATE reminders SET title=?, description=?, reminder_date=?, reminder_time=?, repeat_rule=? WHERE legacy_source_id=?')->execute($values);
            } else {
                $target->prepare('INSERT INTO reminders (title, description, reminder_date, reminder_time, repeat_rule, legacy_source_id) VALUES (?,?,?,?,?,?)')->execute($values);
                $counts['reminders']++;
            }
        }
    }

    if (isset($available['forum'])) {
        foreach ($legacy->query('SELECT * FROM forum ORDER BY ID') as $row) {
            $legacyId = (int) $get($row, 'ID', 0);
            if ($legacyId < 1) {
                continue;
            }
            $values = [
                (string) $get($row, 'Title', 'Team update'),
                (string) $get($row, 'Description', ''),
                (string) $get($row, 'Lead', 'Legacy team member'),
                $legacyId,
            ];
            $id = $existing($target, 'team_posts', $legacyId);
            if ($id) {
                $target->prepare('UPDATE team_posts SET title=?, body=?, author_name=? WHERE legacy_source_id=?')->execute($values);
            } else {
                $target->prepare('INSERT INTO team_posts (title, body, author_name, legacy_source_id) VALUES (?,?,?,?)')->execute($values);
                $id = (int) $target->lastInsertId();
                $counts['discussions']++;
            }
            $forumMap[$legacyId] = $id;
        }
    }

    if (isset($available['comments'])) {
        foreach ($legacy->query('SELECT * FROM comments ORDER BY ID') as $row) {
            $legacyId = (int) $get($row, 'ID', 0);
            $postId = $forumMap[(int) $get($row, 'ForumID', 0)] ?? null;
            $body = trim((string) $get($row, 'Comment', ''));
            if ($legacyId < 1 || !$postId || $body === '') {
                continue;
            }
            $values = [
                $postId,
                (string) $get($row, 'Name', 'Legacy team member'),
                $body,
                $legacyId,
            ];
            if ($existing($target, 'team_comments', $legacyId)) {
                $target->prepare('UPDATE team_comments SET post_id=?, author_name=?, body=? WHERE legacy_source_id=?')->execute($values);
            } else {
                $target->prepare('INSERT INTO team_comments (post_id, author_name, body, legacy_source_id) VALUES (?,?,?,?)')->execute($values);
                $counts['replies']++;
            }
        }
    }

    $target->commit();
} catch (Throwable $exception) {
    if ($target->inTransaction()) {
        $target->rollBack();
    }
    fwrite(STDERR, 'Migration failed; no records were imported: ' . $exception->getMessage() . "\n");
    exit(1);
}

printf(
    "Legacy import complete. Added %d patients, %d appointments, %d reminders, %d discussions, and %d replies. Existing imported records were refreshed; run again safely to resume.\n",
    $counts['patients'],
    $counts['appointments'],
    $counts['reminders'],
    $counts['discussions'],
    $counts['replies']
);
fwrite(STDOUT, "Profile PINs, staff sessions, images, and private messages were not imported.\n");
