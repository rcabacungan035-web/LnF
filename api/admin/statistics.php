
<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

try {
    require_once __DIR__ . "/../config/database.php";

    // Match the administrator session used by admin-claims.php.
    if (empty($_SESSION['admin_id'])) {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Administrator login required. Please log in again."
        ]);
        exit;
    }

    // Count lost items.
    $lostStmt = $pdo->query("
        SELECT COUNT(*)
        FROM item
        WHERE Item_type = 'Lost'
    ");
    $lostItems = (int) $lostStmt->fetchColumn();

    // Count found items.
    $foundStmt = $pdo->query("
        SELECT COUNT(*)
        FROM item
        WHERE Item_type = 'Found'
    ");
    $foundItems = (int) $foundStmt->fetchColumn();

    // Count pending claims.
    $pendingStmt = $pdo->query("
        SELECT COUNT(*)
        FROM claim
        WHERE claimstatus = 'Pending'
    ");
    $pendingClaims = (int) $pendingStmt->fetchColumn();

    // Count item history records.
    $historyStmt = $pdo->query("
        SELECT COUNT(*)
        FROM item_history
    ");
    $historyRecords = (int) $historyStmt->fetchColumn();

    echo json_encode([
        "success" => true,
        "statistics" => [
            "lost_items" => $lostItems,
            "found_items" => $foundItems,
            "pending_claims" => $pendingClaims,
            "history_records" => $historyRecords
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

exit;
