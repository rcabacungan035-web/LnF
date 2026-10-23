<?php

session_start();

header("Content-Type: application/json");

try {

    require_once "../config/database.php";


    /* =========================
       CHECK DATABASE
    ========================= */

    if (!isset($pdo)) {
        throw new Exception(
            "PDO database connection was not found."
        );
    }


    /* =========================
       CHECK ADMIN LOGIN
    ========================= */

    if (!isset($_SESSION['admin_id'])) {
        throw new Exception(
            "Administrator session not found."
        );
    }


    /* =========================
       CHECK REQUEST
    ========================= */

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception(
            "Invalid request method."
        );
    }


    /* =========================
       GET DATA
    ========================= */

    $claimId =
        intval($_POST['claim_id'] ?? 0);

    $status =
        trim($_POST['status'] ?? '');


    if ($claimId <= 0) {
        throw new Exception(
            "Invalid claim ID."
        );
    }


    if (
        $status !== 'Approved' &&
        $status !== 'Rejected'
    ) {
        throw new Exception(
            "Invalid status: " . $status
        );
    }


    /* =========================
       GET CLAIM INFORMATION
    ========================= */

    $stmt = $pdo->prepare("
        SELECT
            claim_id,
            student_id,
            item_id,
            claimstatus
        FROM claim
        WHERE claim_id = :claim_id
        LIMIT 1
    ");

    $stmt->execute([
        ':claim_id' => $claimId
    ]);

    $claim =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$claim) {

        throw new Exception(
            "Claim ID " .
            $claimId .
            " was not found."
        );

    }


    /* =========================
       UPDATE CLAIM
    ========================= */

    $stmt = $pdo->prepare("
        UPDATE claim
        SET
            claimstatus = :status,
            Verification_Date = CURDATE()
        WHERE claim_id = :claim_id
    ");

    $stmt->execute([
        ':status' => $status,
        ':claim_id' => $claimId
    ]);


    /* =========================
       CREATE NOTIFICATION
    ========================= */

    if ($status === 'Approved') {

        $message =
            "Your claim for the item has been approved. "
            . "Please coordinate with the administrator "
            . "for the item release.";

    } else {

        $message =
            "Your claim for the item has been rejected. "
            . "Please contact the administrator if you "
            . "need more information.";

    }


    $stmt = $pdo->prepare("
        INSERT INTO email_notification
        (
            student_id,
            item_id,
            Message,
            DateSent,
            IsRead
        )
        VALUES
        (
            :student_id,
            :item_id,
            :message,
            NOW(),
            0
        )
    ");

    $stmt->execute([
        ':student_id' =>
            $claim['student_id'],

        ':item_id' =>
            $claim['item_id'],

        ':message' =>
            $message
    ]);


    /* =========================
       SUCCESS
    ========================= */

    echo json_encode([
        "success" => true,
        "message" =>
            "Claim successfully marked as "
            . $status
            . " and notification sent."
    ]);


} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "file" =>
            basename($e->getFile()),
        "line" =>
            $e->getLine()
    ]);

}

exit;