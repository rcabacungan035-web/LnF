<?php
session_start();

header("Content-Type: application/json");

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../../notify.php";

/* Send a JSON response and stop */
function respond(int $code, bool $success, string $message, array $extra = []): void
{
    http_response_code($code);
    echo json_encode(array_merge(
        ["success" => $success, "message" => $message],
        $extra
    ));
    exit;
}

/* Make sure the user is logged in */
if (!isset($_SESSION["user"])) {
    respond(401, false, "You must be logged in.");
}

if (!isset($_SESSION["user"]["student_id"])) {
    respond(401, false, "Student account is not connected.");
}

$studentId = $_SESSION["user"]["student_id"];

/* Only accept POST requests */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(405, false, "Invalid request method.");
}

/* Get form data */
$itemName     = trim($_POST["item_name"] ?? "");
$description  = trim($_POST["description"] ?? "");
$location     = trim($_POST["location"] ?? "");
$dateReported = trim($_POST["date_reported"] ?? "");
$status       = "Lost";
$itemType     = "Lost";

/* Validate required fields */
if ($itemName === "" || $description === "" || $location === "" || $dateReported === "") {
    respond(400, false, "Please complete all required fields.");
}

$d = DateTime::createFromFormat("Y-m-d", $dateReported);
if (!$d || $d->format("Y-m-d") !== $dateReported) {
    respond(400, false, "Please enter a valid report date.");
}

/* Handle photo upload */
$photoName = null;

if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {
        respond(400, false, "There was a problem uploading the photo.");
    }

    /* Allowed types, mapped to a safe extension */
    $extensions = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp",
    ];

    $fileType = mime_content_type($_FILES["photo"]["tmp_name"]);

    if (!isset($extensions[$fileType])) {
        respond(400, false, "Only JPG, PNG, and WEBP images are allowed.");
    }

    /* Limit file size to 5 MB */
    if ($_FILES["photo"]["size"] > 5 * 1024 * 1024) {
        respond(400, false, "Photo must not exceed 5 MB.");
    }

    /* Unique filename; extension comes from the real file type, not the user's filename */
    $photoName = "item_" . bin2hex(random_bytes(12)) . "." . $extensions[$fileType];

    $uploadDirectory = __DIR__ . "/../../uploads/";

    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
        respond(500, false, "Unable to create the uploads folder.");
    }

    if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $uploadDirectory . $photoName)) {
        respond(500, false, "Failed to save the uploaded photo.");
    }
}

/* Insert the lost item into the database */
try {

    $stmt = $pdo->prepare(
        "INSERT INTO item
        (
            student_id,
            item_name,
            Description,
            location,
            Date_reported,
            Photo,
            Item_type,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $studentId,
        $itemName,
        $description,
        $location,
        $dateReported,
        $photoName,
        $itemType,
        $status
    ]);

    $itemId = $pdo->lastInsertId();

} catch (PDOException $e) {

    /* Remove uploaded photo if database insertion fails */
    if ($photoName !== null) {
        $uploadedFile = __DIR__ . "/../../uploads/" . $photoName;
        if (file_exists($uploadedFile)) {
            unlink($uploadedFile);
        }
    }

    error_log("Lost item post error: " . $e->getMessage());
    respond(500, false, "Failed to save lost item.");
}

/* Email all registered users and admins about the new item */
$notified = 0;

try {
    $res = notifyNewItem($pdo, [
        "type"        => $itemType,
        "name"        => $itemName,
        "description" => $description,
        "location"    => $location,
        "date"        => $dateReported,
    ]);
    $notified = (int) $res["sent"];
} catch (Throwable $mailError) {
    error_log("Item notification failed: " . $mailError->getMessage());
}

respond(200, true, "Lost item reported successfully.", [
    "item_id"  => $itemId,
    "notified" => $notified,
]);