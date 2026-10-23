
<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

$pdo = null;

try {
    // Connect to the database
    require_once __DIR__ . "/../config/database.php";

    if (!isset($pdo)) {
        throw new Exception("PDO database connection was not found.");
    }

    // Verify administrator session
    if (!isset($_SESSION['admin_id']) || intval($_SESSION['admin_id']) <= 0) {
        http_response_code(401);
        throw new Exception("Administrator login required.");
    }

    $adminId = intval($_SESSION['admin_id']);

    // Allow POST requests only
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception("Invalid request method. Use POST.");
    }

    // Get submitted values
    $itemId = intval($_POST['item_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if ($itemId <= 0) {
        http_response_code(400);
        throw new Exception("Invalid item ID.");
    }

    // Use the exact status values supported by the system
    $allowedStatuses = [
        'Available',
        'Claimed / Returned',
        'Rejected'
    ];

    if (!in_array($status, $allowedStatuses, true)) {
        http_response_code(400);
        throw new Exception("Invalid item status.");
    }

    // Verify that the administrator exists
    $stmt = $pdo->prepare("
        SELECT admin_id
        FROM admin
        WHERE admin_id = :admin_id
        LIMIT 1
    ");

    $stmt->execute([':admin_id' => $adminId]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(401);
        throw new Exception("Administrator account was not found.");
    }

    // Find the item
    $stmt = $pdo->prepare("
        SELECT item_id, Item_type, status
        FROM item
        WHERE item_id = :item_id
        LIMIT 1
    ");

    $stmt->execute([':item_id' => $itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        http_response_code(404);
        throw new Exception("Item ID {$itemId} was not found.");
    }

    // Only found items can have their availability changed
    if ($item['Item_type'] !== 'Found') {
        http_response_code(400);
        throw new Exception(
            "Only found items can have their availability status changed."
        );
    }

    if ($status === 'Rejected' && $item['status'] !== 'Found') {
        http_response_code(400);
        throw new Exception(
            "Only pending found-item submissions can be rejected."
        );
    }

    // Do not record a duplicate change
    if ($item['status'] === $status) {
        http_response_code(400);
        throw new Exception("Item is already marked as {$status}.");
    }

    // Update the item and record the history together
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE item
        SET status = :status
        WHERE item_id = :item_id
    ");

    $stmt->execute([
        ':status' => $status,
        ':item_id' => $itemId
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO item_history
            (item_id, admin_id, Date_Changed)
        VALUES
            (:item_id, :admin_id, NOW())
    ");

    $stmt->execute([
        ':item_id' => $itemId,
        ':admin_id' => $adminId
    ]);

    $pdo->commit();

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "message" => "Item status successfully changed to {$status}.",
        "item_id" => $itemId,
        "status" => $status
    ]);

} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Preserve intentional HTTP error codes; use 500 for unexpected errors.
    if (http_response_code() < 400) {
        http_response_code(500);
    }

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

exit;


