<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$db = database();
$page = (string) ($_GET['page'] ?? 'dashboard');
$allowedPages = ['dashboard', 'patients', 'appointments', 'reminders', 'team', 'activity', 'forum', 'messages'];
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$pagePermissions = [
    'patients' => 'patients.view',
    'appointments' => 'appointments.view',
    'reminders' => 'reminders.view',
    'team' => 'staff.manage',
    'activity' => 'activity.view',
    'forum' => 'forum.view',
    'messages' => 'messages.view',
];
if (isset($pagePermissions[$page])) {
    require_permission($pagePermissions[$page]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if (in_array($action, ['save_patient', 'delete_patient'], true)) {
            require_permission('patients.edit');
            if ($action === 'save_patient') {
                $id = (int) ($_POST['id'] ?? 0);
                $name = post_string('name', 160);
                $birthday = post_string('birthday', 10);
                if ($name === '' || !valid_date($birthday)) {
                    throw new InvalidArgumentException('Enter a patient name and a valid date of birth.');
                }
                $values = [
                    $name,
                    post_string('contact', 40) ?: null,
                    post_string('email', 190) ?: null,
                    $birthday ?: null,
                    post_string('blood_type', 5) ?: null,
                    post_string('gender', 30) ?: null,
                    post_string('address', 255) ?: null,
                    post_string('notes', 5000) ?: null,
                ];
                if ($id > 0) {
                    $values[] = $id;
                    $db->prepare('UPDATE patients SET name=?, contact=?, email=?, birthday=?, blood_type=?, gender=?, address=?, notes=? WHERE id=?')->execute($values);
                    audit('updated', 'patient', $id, $name);
                    flash('success', 'Patient record updated.');
                } else {
                    $values[] = (int) current_user()['id'];
                    $db->prepare('INSERT INTO patients (name, contact, email, birthday, blood_type, gender, address, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)')->execute($values);
                    $id = (int) $db->lastInsertId();
                    audit('created', 'patient', $id, $name);
                    flash('success', 'Patient added to your records.');
                }
            } else {
                $id = (int) ($_POST['id'] ?? 0);
                $statement = $db->prepare('SELECT name FROM patients WHERE id=?');
                $statement->execute([$id]);
                $patientName = $statement->fetchColumn();
                if (!$patientName) {
                    throw new InvalidArgumentException('Patient record not found.');
                }
                $db->prepare('DELETE FROM patients WHERE id=?')->execute([$id]);
                audit('deleted', 'patient', $id, (string) $patientName);
                flash('success', 'Patient record deleted.');
            }
            redirect('patients');
        }

        if (in_array($action, ['save_appointment', 'delete_appointment'], true)) {
            require_permission('appointments.edit');
            if ($action === 'save_appointment') {
                $id = (int) ($_POST['id'] ?? 0);
                $date = post_string('appointment_date', 10);
                $time = post_string('appointment_time', 5);
                $patientId = (int) ($_POST['patient_id'] ?? 0);
                $patientName = post_string('interviewee', 160);
                $interviewer = post_string('interviewer', 120);
                if (!valid_date($date) || $date === '' || $patientName === '' || $interviewer === '') {
                    throw new InvalidArgumentException('Choose a valid date and enter the patient and care provider names.');
                }
                if ($patientId > 0) {
                    $check = $db->prepare('SELECT name FROM patients WHERE id=?');
                    $check->execute([$patientId]);
                    $patientName = (string) ($check->fetchColumn() ?: $patientName);
                } else {
                    $patientId = 0;
                }
                $status = post_string('status', 20);
                if (!in_array($status, ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'], true)) {
                    $status = 'scheduled';
                }
                $values = [
                    $patientId ?: null,
                    $interviewer,
                    $patientName,
                    post_string('interviewee_position', 100) ?: null,
                    post_string('facility', 120) ?: null,
                    post_string('description', 2000) ?: null,
                    $date,
                    $time ?: null,
                    $status,
                ];
                if ($id > 0) {
                    $values[] = $id;
                    $db->prepare('UPDATE appointments SET patient_id=?, interviewer=?, interviewee=?, interviewee_position=?, facility=?, description=?, appointment_date=?, appointment_time=?, status=? WHERE id=?')->execute($values);
                    audit('updated', 'appointment', $id, $patientName . ' · ' . $date);
                    flash('success', 'Appointment updated.');
                } else {
                    $values[] = (int) current_user()['id'];
                    $db->prepare('INSERT INTO appointments (patient_id, interviewer, interviewee, interviewee_position, facility, description, appointment_date, appointment_time, status, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute($values);
                    $id = (int) $db->lastInsertId();
                    audit('created', 'appointment', $id, $patientName . ' · ' . $date);
                    flash('success', 'Appointment scheduled.');
                }
            } else {
                $id = (int) ($_POST['id'] ?? 0);
                $db->prepare('DELETE FROM appointments WHERE id=?')->execute([$id]);
                audit('deleted', 'appointment', $id);
                flash('success', 'Appointment removed.');
            }
            redirect('appointments');
        }

        if (in_array($action, ['save_reminder', 'delete_reminder'], true)) {
            require_permission('reminders.edit');
            if ($action === 'save_reminder') {
                $id = (int) ($_POST['id'] ?? 0);
                $title = post_string('title', 180);
                $date = post_string('reminder_date', 10);
                if ($title === '' || !valid_date($date)) {
                    throw new InvalidArgumentException('Enter a reminder title and a valid date.');
                }
                $repeat = post_string('repeat_rule', 10);
                if (!in_array($repeat, ['none', 'daily', 'weekly', 'monthly'], true)) {
                    $repeat = 'none';
                }
                $values = [
                    $title,
                    post_string('description', 2000) ?: null,
                    $date ?: null,
                    post_string('reminder_time', 5) ?: null,
                    $repeat,
                ];
                if ($id > 0) {
                    $values[] = $id;
                    $db->prepare('UPDATE reminders SET title=?, description=?, reminder_date=?, reminder_time=?, repeat_rule=? WHERE id=?')->execute($values);
                    audit('updated', 'reminder', $id, $title);
                    flash('success', 'Reminder updated.');
                } else {
                    $values[] = (int) current_user()['id'];
                    $db->prepare('INSERT INTO reminders (title, description, reminder_date, reminder_time, repeat_rule, created_by) VALUES (?,?,?,?,?,?)')->execute($values);
                    $id = (int) $db->lastInsertId();
                    audit('created', 'reminder', $id, $title);
                    flash('success', 'Reminder created.');
                }
            } else {
                $id = (int) ($_POST['id'] ?? 0);
                $db->prepare('DELETE FROM reminders WHERE id=?')->execute([$id]);
                audit('deleted', 'reminder', $id);
                flash('success', 'Reminder deleted.');
            }
            redirect('reminders');
        }

        if (in_array($action, ['add_user', 'toggle_user'], true)) {
            require_permission('staff.manage');
            if ($action === 'add_user') {
                $name = post_string('name', 120);
                $email = strtolower(post_string('email', 190));
                $password = (string) ($_POST['password'] ?? '');
                $role = post_string('role', 20);
                if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Enter a valid name and work email.');
                }
                if (strlen($password) < 12) {
                    throw new InvalidArgumentException('New user passwords must be at least 12 characters.');
                }
                if (!in_array($role, ['admin', 'doctor', 'nurse', 'receptionist'], true)) {
                    throw new InvalidArgumentException('Choose a valid team role.');
                }
                $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)')->execute([
                    $name, $email, password_hash($password, PASSWORD_DEFAULT), $role,
                ]);
                $id = (int) $db->lastInsertId();
                audit('added team member', 'user', $id, $name . ' · ' . $role);
                flash('success', 'Team member added.');
            } else {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id === (int) current_user()['id']) {
                    throw new InvalidArgumentException('You cannot deactivate your own account.');
                }
                $db->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id=?")->execute([$id]);
                audit('changed account status', 'user', $id);
                flash('success', 'Team member status updated.');
            }
            redirect('team');
        }

        if ($action === 'create_post') {
            $title = post_string('title', 180);
            $body = post_string('body', 4000);
            if ($title === '' || $body === '') {
                throw new InvalidArgumentException('Add a title and a message to start a discussion.');
            }
            $db->prepare('INSERT INTO team_posts (title, body, author_id, author_name) VALUES (?,?,?,?)')->execute([
                $title, $body, (int) current_user()['id'], current_user()['name'],
            ]);
            $id = (int) $db->lastInsertId();
            audit('started a discussion', 'team post', $id, $title);
            flash('success', 'Your discussion has been posted.');
            redirect('forum');
        }

        if ($action === 'add_comment') {
            $postId = (int) ($_POST['post_id'] ?? 0);
            $body = post_string('body', 2000);
            $check = $db->prepare('SELECT id FROM team_posts WHERE id=?');
            $check->execute([$postId]);
            if (!$check->fetchColumn() || $body === '') {
                throw new InvalidArgumentException('Choose a discussion and enter a reply.');
            }
            $db->prepare('INSERT INTO team_comments (post_id, author_id, author_name, body) VALUES (?,?,?,?)')->execute([
                $postId, (int) current_user()['id'], current_user()['name'], $body,
            ]);
            audit('replied to a discussion', 'team post', $postId);
            flash('success', 'Your reply has been added.');
            redirect('forum');
        }

        if ($action === 'send_message') {
            $recipientId = (int) ($_POST['recipient_id'] ?? 0);
            $body = post_string('body', 2000);
            if ($recipientId < 1 || $recipientId === (int) current_user()['id'] || $body === '') {
                throw new InvalidArgumentException('Choose a teammate and enter a message.');
            }
            $recipient = $db->prepare("SELECT id, name FROM users WHERE id=? AND status='active'");
            $recipient->execute([$recipientId]);
            $teammate = $recipient->fetch();
            if (!$teammate) {
                throw new InvalidArgumentException('That teammate is not available.');
            }
            $db->prepare('INSERT INTO direct_messages (sender_id, recipient_id, body) VALUES (?,?,?)')->execute([
                (int) current_user()['id'], $recipientId, $body,
            ]);
            audit('sent a message', 'team member', $recipientId, $teammate['name']);
            header('Location: index.php?page=messages&with=' . $recipientId);
            exit;
        }

        throw new InvalidArgumentException('This action is not available.');
    } catch (InvalidArgumentException $exception) {
        flash('error', $exception->getMessage());
        redirect($page);
    } catch (PDOException $exception) {
        error_log('Careflow database action failed: ' . $exception->getMessage());
        flash('error', 'We could not save those changes. Check for duplicate email addresses and try again.');
        redirect($page);
    }
}

$user = current_user();
$counts = [
    'patients' => (int) $db->query('SELECT COUNT(*) FROM patients')->fetchColumn(),
    'appointments' => (int) $db->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND status NOT IN ('cancelled','no_show')")->fetchColumn(),
    'upcoming' => (int) $db->query('SELECT COUNT(*) FROM appointments WHERE appointment_date >= CURDATE() AND status IN ("scheduled","confirmed")')->fetchColumn(),
    'reminders' => (int) $db->query('SELECT COUNT(*) FROM reminders WHERE reminder_date >= CURDATE()')->fetchColumn(),
];
$todayAppointments = $db->query(
    "SELECT a.*, p.name AS linked_patient FROM appointments a LEFT JOIN patients p ON p.id=a.patient_id
     WHERE a.appointment_date = CURDATE() AND a.status NOT IN ('cancelled','no_show')
     ORDER BY a.appointment_time LIMIT 6"
)->fetchAll();
$recentActivity = can('activity.view')
    ? $db->query('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 6')->fetchAll()
    : [];
$unreadQuery = $db->prepare('SELECT COUNT(*) FROM direct_messages WHERE recipient_id=? AND read_at IS NULL');
$unreadQuery->execute([(int) $user['id']]);
$unreadMessages = (int) $unreadQuery->fetchColumn();
$search = trim((string) ($_GET['q'] ?? ''));
$patients = [];
$appointments = [];
$reminders = [];
$team = [];
$activities = [];
$totalPatients = $counts['patients'];

if ($page === 'patients') {
    $query = 'SELECT p.*, u.name AS added_by FROM patients p LEFT JOIN users u ON u.id=p.created_by';
    $params = [];
    if ($search !== '') {
        $query .= ' WHERE p.name LIKE ? OR p.contact LIKE ? OR p.email LIKE ?';
        $like = '%' . $search . '%';
        $params = [$like, $like, $like];
    }
    $query .= ' ORDER BY p.updated_at DESC LIMIT 200';
    $statement = $db->prepare($query);
    $statement->execute($params);
    $patients = $statement->fetchAll();
    $patientStatement = $db->query('SELECT id, name FROM patients ORDER BY name');
}
if ($page === 'appointments') {
    $appointments = $db->query('SELECT a.*, p.name AS linked_patient FROM appointments a LEFT JOIN patients p ON p.id=a.patient_id ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 200')->fetchAll();
    $patientStatement = $db->query('SELECT id, name FROM patients ORDER BY name');
    $patientOptions = $patientStatement->fetchAll();
}
if ($page === 'reminders') {
    $reminders = $db->query('SELECT r.*, u.name AS created_by_name FROM reminders r LEFT JOIN users u ON u.id=r.created_by ORDER BY r.reminder_date, r.reminder_time LIMIT 200')->fetchAll();
}
if ($page === 'team') {
    $team = $db->query('SELECT id, name, email, role, status, created_at FROM users ORDER BY name')->fetchAll();
}
if ($page === 'activity') {
    $activities = $db->query('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 300')->fetchAll();
}
if ($page === 'forum') {
    $discussionPosts = $db->query(
        'SELECT p.*, (SELECT COUNT(*) FROM team_comments c WHERE c.post_id=p.id) AS comment_count
         FROM team_posts p ORDER BY p.created_at DESC LIMIT 100'
    )->fetchAll();
    $commentRows = $db->query('SELECT * FROM team_comments ORDER BY created_at')->fetchAll();
    $commentsByPost = [];
    foreach ($commentRows as $comment) {
        $commentsByPost[$comment['post_id']][] = $comment;
    }
}
if ($page === 'messages') {
    $memberQuery = $db->prepare(
        "SELECT u.id, u.name, u.role,
            (SELECT MAX(m.created_at) FROM direct_messages m
             WHERE (m.sender_id=u.id AND m.recipient_id=?) OR (m.sender_id=? AND m.recipient_id=u.id)) AS latest_message,
            (SELECT COUNT(*) FROM direct_messages m
             WHERE m.sender_id=u.id AND m.recipient_id=? AND m.read_at IS NULL) AS unread_count
         FROM users u WHERE u.status='active' AND u.id<>?
         ORDER BY latest_message IS NULL, latest_message DESC, u.name"
    );
    $memberQuery->execute([(int) $user['id'], (int) $user['id'], (int) $user['id'], (int) $user['id']]);
    $teammates = $memberQuery->fetchAll();
    $selectedId = (int) ($_GET['with'] ?? 0);
    $selectedTeammate = null;
    foreach ($teammates as $teammate) {
        if ((int) $teammate['id'] === $selectedId) {
            $selectedTeammate = $teammate;
            break;
        }
    }
    if (!$selectedTeammate && $teammates) {
        $selectedTeammate = $teammates[0];
    }
    $threadMessages = [];
    if ($selectedTeammate) {
        $db->prepare('UPDATE direct_messages SET read_at=CURRENT_TIMESTAMP WHERE sender_id=? AND recipient_id=? AND read_at IS NULL')
            ->execute([(int) $selectedTeammate['id'], (int) $user['id']]);
        $threadQuery = $db->prepare(
            'SELECT * FROM direct_messages
             WHERE (sender_id=? AND recipient_id=?) OR (sender_id=? AND recipient_id=?)
             ORDER BY created_at ASC LIMIT 500'
        );
        $threadQuery->execute([(int) $user['id'], (int) $selectedTeammate['id'], (int) $selectedTeammate['id'], (int) $user['id']]);
        $threadMessages = $threadQuery->fetchAll();
    }
}

$flashMessage = take_flash();
$pageTitles = [
    'dashboard' => 'Overview',
    'patients' => 'Patients',
    'appointments' => 'Appointments',
    'reminders' => 'Reminders',
    'team' => 'Team access',
    'activity' => 'Activity log',
    'forum' => 'Team board',
    'messages' => 'Messages',
];
$pageDescriptions = [
    'dashboard' => 'A clear view of today’s care and what needs attention.',
    'patients' => 'Securely manage your patient directory and care notes.',
    'appointments' => 'Plan visits and keep every appointment on track.',
    'reminders' => 'A shared place for the details your team should not miss.',
    'team' => 'Manage staff accounts and their workspace access.',
    'activity' => 'A record of recent changes across your workspace.',
    'forum' => 'Share updates and keep team discussions in one place.',
    'messages' => 'Have a private conversation with a teammate.',
];
require __DIR__ . '/includes/header.php';
require __DIR__ . '/pages/' . $page . '.php';
require __DIR__ . '/includes/footer.php';
