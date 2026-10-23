

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
       CHECK STUDENT LOGIN
    ========================= */

    if (!isset($_SESSION['user'])) {
        throw new Exception(
            "Student is not logged in."
        );
    }


    /* =========================
       GET STUDENT ID
    ========================= */

    $studentId = intval(
        $_SESSION['user']['student_id'] ?? 0
    );

    if ($studentId <= 0) {

        $email = $_SESSION['user']['email'] ?? '';

        if ($email === '') {
            throw new Exception(
                "Student email was not found."
            );
        }

        $stmt = $pdo->prepare("
            SELECT student_id
            FROM student
            WHERE email = :email
            LIMIT 1
        ");

        $stmt->execute([
            ':email' => $email
        ]);

        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            throw new Exception(
                "Student record was not found."
            );
        }

        $studentId = intval($student['student_id']);
    }

    if ($studentId <= 0) {
        throw new Exception("Invalid student ID.");
    }


    /* =========================
       CHECK REQUEST METHOD
    ========================= */

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception(
            "Invalid request method."
        );
    }


    /* =========================
       GET ITEM ID
    ========================= */

    $itemId = intval($_POST['item_id'] ?? 0);

    if ($itemId <= 0) {
        throw new Exception("Invalid item ID.");
    }


    /* =========================
       START TRANSACTION
    ========================= */

    $pdo->beginTransaction();


    /* =========================
       LOCK AND CHECK ITEM
    ========================= */

    $stmt = $pdo->prepare("
        SELECT
            item_id,
            student_id,
            item_name,
            Item_type,
            status
        FROM item
        WHERE item_id = :item_id
        FOR UPDATE
    ");

    $stmt->execute([
        ':item_id' => $itemId
    ]);

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception(
            "Item ID " . $itemId . " was not found."
        );
    }


    /* =========================
       ONLY FOUND ITEMS
       CAN BE CLAIMED
    ========================= */

    if ($item['Item_type'] !== 'Found') {
        throw new Exception(
            "Only found items can be claimed."
        );
    }


    /* =========================
       CHECK AVAILABILITY
    ========================= */

    if ($item['status'] !== 'Available') {
        throw new Exception(
            "This item is no longer available for claiming."
        );
    }


    /* =========================
       PREVENT SELF-CLAIM
    ========================= */

    if (intval($item['student_id']) === $studentId) {
        throw new Exception(
            "You cannot claim an item that you reported as found."
        );
    }


    /* =========================
       PREVENT DUPLICATE CLAIM
    ========================= */

    $stmt = $pdo->prepare("
        SELECT claim_id
        FROM claim
        WHERE item_id = :item_id
          AND student_id = :student_id
        LIMIT 1
    ");

    $stmt->execute([
        ':item_id' => $itemId,
        ':student_id' => $studentId
    ]);

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(
            "You have already submitted a claim for this item."
        );
    }


    /* =========================
       CHECK DEFAULT ADMIN
    ========================= */

    $adminId = 1;

    $stmt = $pdo->prepare("
        SELECT admin_id
        FROM admin
        WHERE admin_id = :admin_id
        LIMIT 1
    ");

    $stmt->execute([
        ':admin_id' => $adminId
    ]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(
            "Default administrator account was not found."
        );
    }


    /* =========================
       CREATE CLAIM
    ========================= */

    $stmt = $pdo->prepare("
        INSERT INTO claim
        (
            item_id,
            student_id,
            claimdate,
            claimstatus,
            Verification_Date,
            admin_id
        )
        VALUES
        (
            :item_id,
            :student_id,
            CURDATE(),
            'Pending',
            NULL,
            :admin_id
        )
    ");

    $stmt->execute([
        ':item_id' => $itemId,
        ':student_id' => $studentId,
        ':admin_id' => $adminId
    ]);


    /* =========================
       COMMIT CLAIM
    ========================= */

    $pdo->commit();


    /* =========================
       SUCCESS
    ========================= */

    echo json_encode([
        "success" => true,
        "message" =>
            "Claim submitted successfully. "
            . "Please wait for administrator verification."
    ]);

} catch (Throwable $e) {

    if (
        isset($pdo) &&
        $pdo->inTransaction()
    ) {
        $pdo->rollBack();
    }

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}

exit;

