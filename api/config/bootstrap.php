<?php

/*
 * Shared start-up code and helper functions.
 *
 *   require_once __DIR__ . '/../config/bootstrap.php';   (inside api/*)
 *   require_once __DIR__ . '/api/config/bootstrap.php';  (inside root pages)
 *
 * It starts the session with safe cookie settings and gives every page
 * and API the same helpers, so the logic is written once.
 */

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

date_default_timezone_set('Asia/Manila');


/* =========================
   LIMITS
========================= */

const MAX_PHOTO_BYTES   = 5 * 1024 * 1024;
const MAX_NAME_LENGTH   = 100;
const MAX_LOCATION_LEN  = 150;
const MAX_DESCRIPTION   = 1000;


/* =========================
   ERRORS
========================= */

/* A problem the user can fix. Its message is safe to show. */
class UserError extends RuntimeException
{
    public function __construct(string $message, int $httpStatus = 400)
    {
        parent::__construct($message, $httpStatus);
    }
}


/* =========================
   OUTPUT HELPERS
========================= */

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function json_out(array $data, int $status = 200): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
    }

    http_response_code($status);

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/*
 * Runs an API handler and turns the result (or any error) into JSON.
 * Unexpected errors are logged, never shown to the visitor.
 */
function run_json(callable $handler): void
{
    header('Content-Type: application/json; charset=UTF-8');

    try {

        $result = $handler();

        json_out(is_array($result) ? $result : ['success' => true]);

    } catch (UserError $e) {

        $status = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 400;

        json_out(['success' => false, 'message' => $e->getMessage()], $status);

    } catch (Throwable $e) {

        error_log(
            'MU Lost & Found API error in ' . basename($e->getFile()) .
            ':' . $e->getLine() . ' - ' . $e->getMessage()
        );

        json_out([
            'success' => false,
            'message' => 'Something went wrong on the server. Please try again.'
        ], 500);
    }
}


/* =========================
   REQUEST CHECKS
========================= */

/* Rejects form posts that come from another website. */
function same_origin_ok(): bool
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origin === '') {
        return true;
    }

    if ($origin === 'null') {
        return false;
    }

    $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
    $serverHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));

    return $originHost !== '' && $originHost === $serverHost;
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new UserError('Invalid request method.', 405);
    }

    if (!same_origin_ok()) {
        throw new UserError('This request was blocked for security reasons.', 403);
    }
}

/* Returns the logged-in student's student_id. */
function require_student(PDO $pdo): int
{
    if (empty($_SESSION['user'])) {
        throw new UserError('You must be logged in.', 401);
    }

    $studentId = (int) ($_SESSION['user']['student_id'] ?? 0);

    if ($studentId <= 0) {

        $email = $_SESSION['user']['email'] ?? '';

        if ($email !== '') {

            $stmt = $pdo->prepare(
                'SELECT student_id FROM student WHERE email = :email LIMIT 1'
            );

            $stmt->execute([':email' => $email]);

            $studentId = (int) $stmt->fetchColumn();
        }
    }

    if ($studentId <= 0) {
        throw new UserError('Student account was not found. Please log in again.', 401);
    }

    return $studentId;
}

/* Returns the logged-in administrator's admin_id. */
function require_admin(): int
{
    $adminId = (int) ($_SESSION['admin_id'] ?? 0);

    if ($adminId <= 0) {
        throw new UserError('Administrator login required. Please log in again.', 401);
    }

    return $adminId;
}


/* =========================
   DATABASE HELPERS
========================= */

function with_transaction(PDO $pdo, callable $work)
{
    $pdo->beginTransaction();

    try {

        $result = $work();

        $pdo->commit();

        return $result;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

function add_notification(PDO $pdo, int $studentId, int $itemId, string $message): void
{
    $stmt = $pdo->prepare("
        INSERT INTO email_notification
            (student_id, item_id, Message, DateSent, IsRead)
        VALUES
            (:student_id, :item_id, :message, NOW(), 0)
    ");

    $stmt->execute([
        ':student_id' => $studentId,
        ':item_id'    => $itemId,
        ':message'    => $message
    ]);
}

function log_item_change(PDO $pdo, int $itemId, int $adminId): void
{
    $stmt = $pdo->prepare("
        INSERT INTO item_history (item_id, admin_id, Date_Changed)
        VALUES (:item_id, :admin_id, NOW())
    ");

    $stmt->execute([
        ':item_id'  => $itemId,
        ':admin_id' => $adminId
    ]);
}


/* =========================
   PHOTO UPLOAD
========================= */

/*
 * Saves an uploaded photo and returns the new file name (or null if none
 * was uploaded). The file type comes from the file's real contents and the
 * extension is chosen by us, never copied from the visitor's file name.
 */
function save_uploaded_photo(string $field, string $prefix): ?string
{
    if (
        !isset($_FILES[$field]) ||
        $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    $file = $_FILES[$field];

    if (is_array($file['error'])) {
        throw new UserError('Invalid upload.');
    }

    if (
        $file['error'] === UPLOAD_ERR_INI_SIZE ||
        $file['error'] === UPLOAD_ERR_FORM_SIZE
    ) {
        throw new UserError('Photo must not exceed 5 MB.');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new UserError('There was a problem uploading the photo.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new UserError('Invalid upload.');
    }

    if ($file['size'] > MAX_PHOTO_BYTES) {
        throw new UserError('Photo must not exceed 5 MB.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!isset($allowed[$mime])) {
        throw new UserError('Only JPG, PNG, and WEBP images are allowed.');
    }

    if (@getimagesize($file['tmp_name']) === false) {
        throw new UserError('The uploaded file is not a valid image.');
    }

    $directory = __DIR__ . '/../../uploads/';

    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException('Could not create the uploads folder.');
    }

    $name = $prefix . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];

    if (!move_uploaded_file($file['tmp_name'], $directory . $name)) {
        throw new RuntimeException('Could not save the uploaded photo.');
    }

    @chmod($directory . $name, 0644);

    return $name;
}

function delete_photo(?string $name): void
{
    if ($name === null || $name === '') {
        return;
    }

    $path = __DIR__ . '/../../uploads/' . basename($name);

    if (is_file($path)) {
        @unlink($path);
    }
}


/* =========================
   LOST / FOUND REPORTS
========================= */

/*
 * Shared by api/items/create.php (Lost) and api/items/create-found.php (Found).
 *
 * Lost  items start as status "Lost".
 * Found items start as status "Found" = waiting for admin review. The admin
 * approves them on admin-claims.php, which changes the status to "Available"
 * so students can claim them.
 */
function report_item(PDO $pdo, string $type, string $status, string $successMessage): array
{
    require_post();

    $studentId = require_student($pdo);

    $itemName     = trim((string) ($_POST['item_name'] ?? ''));
    $description  = trim((string) ($_POST['description'] ?? ''));
    $location     = trim((string) ($_POST['location'] ?? ''));
    $dateReported = trim((string) ($_POST['date_reported'] ?? ''));

    if ($itemName === '' || $description === '' || $location === '' || $dateReported === '') {
        throw new UserError('Please complete all required fields.');
    }

    if (mb_strlen($itemName) > MAX_NAME_LENGTH) {
        throw new UserError('Item name must not exceed ' . MAX_NAME_LENGTH . ' characters.');
    }

    if (mb_strlen($location) > MAX_LOCATION_LEN) {
        throw new UserError('Location must not exceed ' . MAX_LOCATION_LEN . ' characters.');
    }

    if (mb_strlen($description) > MAX_DESCRIPTION) {
        throw new UserError('Description must not exceed ' . MAX_DESCRIPTION . ' characters.');
    }

    $date = DateTime::createFromFormat('!Y-m-d', $dateReported);

    if (!$date || $date->format('Y-m-d') !== $dateReported) {
        throw new UserError('Please enter a valid date.');
    }

    // One day of tolerance so a different server time zone never blocks "today".
    $latest = (new DateTime('today'))->modify('+1 day');

    if ($date > $latest) {
        throw new UserError('The date cannot be in the future.');
    }

    $photoName = save_uploaded_photo('photo', $type === 'Found' ? 'found_' : 'item_');

    try {

        $stmt = $pdo->prepare("
            INSERT INTO item
                (student_id, item_name, Description, location,
                 Date_reported, Photo, Item_type, status)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $studentId,
            $itemName,
            $description,
            $location,
            $dateReported,
            $photoName,
            $type,
            $status
        ]);

        return [
            'success' => true,
            'message' => $successMessage,
            'item_id' => (int) $pdo->lastInsertId()
        ];

    } catch (Throwable $e) {

        delete_photo($photoName);

        throw $e;
    }
}


/* =========================
   SESSION / ADMIN HELPERS
========================= */

function destroy_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax',
        ]);
    }

    session_destroy();
}

/* The admin table's name column is unknown, so try the usual ones. */
function admin_display_name(array $row): string
{
    $first = $row['fname'] ?? $row['Fname'] ?? $row['first_name'] ?? '';
    $last  = $row['lname'] ?? $row['Lname'] ?? $row['last_name'] ?? '';

    $full = trim($first . ' ' . $last);

    if ($full !== '') {
        return $full;
    }

    foreach (['Name', 'name', 'Admin_name', 'admin_name', 'Username', 'username'] as $key) {
        if (!empty($row[$key])) {
            return (string) $row[$key];
        }
    }

    return 'System Administrator';
}
