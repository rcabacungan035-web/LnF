<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Administrator login required.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST required.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../notify.php';

function failPost(string $message): void
{
    $_SESSION['post_message'] = $message;
    $_SESSION['post_message_type'] = 'error';
    header('Location: ../../admin-post-item.php');
    exit;
}

$type        = trim($_POST['item_type'] ?? '');
$name        = trim($_POST['item_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$location    = trim($_POST['location'] ?? '');
$date        = trim($_POST['date_reported'] ?? '');

if (!in_array($type, ['Lost', 'Found'], true) || $name === '' || $description === '' || $location === '' || $date === '') {
    failPost('Please complete all required fields.');
}

$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    failPost('Please enter a valid report date.');
}

// ---------- Photo upload ----------
$photoName = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK || $_FILES['photo']['size'] > 5 * 1024 * 1024) {
        failPost('Photo upload failed or exceeds 5 MB.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['photo']['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime])) {
        failPost('Only JPG, PNG, and WEBP images are allowed.');
    }

    $photoName = 'admin_' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    $uploadDir = __DIR__ . '/../../uploads/';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        failPost('Unable to create uploads directory.');
    }

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoName)) {
        failPost('Could not save the uploaded photo.');
    }
}

// ---------- Save item ----------
try {
    // Keep the current item.student_id foreign-key structure intact by using a system reporter student.
    $cfg = require __DIR__ . '/../config/super-admin.php';
    $systemEmail = trim((string) ($cfg['email'] ?? ''));

    if (
        $systemEmail === ''
        || strpos($systemEmail, 'REPLACE_WITH_') === 0
        || !filter_var($systemEmail, FILTER_VALIDATE_EMAIL)
    ) {
        failPost('Set your real Super Admin email in api/config/super-admin.php first.');
    }

    $stmt = $pdo->prepare('SELECT student_id FROM student WHERE email = ? LIMIT 1');
    $stmt->execute([$systemEmail]);
    $systemStudent = $stmt->fetch();

    if (!$systemStudent) {
        $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO student (fname, lname, email, password) VALUES (?, ?, ?, ?)');
        $stmt->execute(['MU', 'System', $systemEmail, $hash]);
        $studentId = (int) $pdo->lastInsertId();
    } else {
        $studentId = (int) $systemStudent['student_id'];
    }

    $status = $type === 'Found' ? 'Available' : 'Lost';

    $stmt = $pdo->prepare(
        'INSERT INTO item (student_id, item_name, Description, location, Date_reported, Photo, Item_type, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$studentId, $name, $description, $location, $date, $photoName, $type, $status]);

    // ---------- Email all registered users about the new item ----------
    $note = '';
    try {
        $res = notifyNewItem($pdo, [
            'type'        => $type,
            'name'        => $name,
            'description' => $description,
            'location'    => $location,
            'date'        => $date,
        ]);
        $note = $res['failed'] > 0
            ? ' Some email notifications failed to send.'
            : " Email notification sent to {$res['sent']} user(s).";
    } catch (Throwable $mailError) {
        error_log('Item notification failed: ' . $mailError->getMessage());
        $note = ' The item was posted, but email notifications failed.';
    }

    $_SESSION['post_message'] = 'Item published successfully.' . $note;
    $_SESSION['post_message_type'] = 'success';
    header('Location: ../../admin-post-item.php');
    exit;
} catch (Throwable $e) {
    if ($photoName && is_file(__DIR__ . '/../../uploads/' . $photoName)) {
        @unlink(__DIR__ . '/../../uploads/' . $photoName);
    }
    error_log('Admin item post error: ' . $e->getMessage());
    failPost('Unable to publish the item. Check the database structure and server error log.');
}