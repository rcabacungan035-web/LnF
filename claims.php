<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

require_once "api/config/database.php";


/* =========================
   GET STUDENT ID
========================= */

$studentId =
    intval($_SESSION['user']['student_id'] ?? 0);


/*
   If student_id is not stored directly
   in the session, find it using email.
*/

if ($studentId <= 0) {

    $email =
        $_SESSION['user']['email'] ?? '';

    $stmt = $pdo->prepare("
        SELECT student_id
        FROM student
        WHERE email = :email
        LIMIT 1
    ");

    $stmt->execute([
        ':email' => $email
    ]);

    $student =
        $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {

        $studentId =
            intval($student['student_id']);

    }

}


/* =========================
   GET STUDENT CLAIMS
========================= */

$stmt = $pdo->prepare("
    SELECT

        c.claim_id,
        c.claimdate,
        c.claimstatus,
        c.Verification_Date,

        i.item_id,
        i.item_name,
        i.Description,
        i.location,
        i.Photo,
        i.status AS item_status

    FROM claim c

    INNER JOIN item i
        ON c.item_id = i.item_id

    WHERE c.student_id = :student_id

    ORDER BY c.claim_id DESC
");

$stmt->execute([
    ':student_id' => $studentId
]);

$claims =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Claims - MU Lost & Found</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

</head>

<body class="dashboard-body">


<!-- NAVIGATION -->

<nav class="navbar">

    <div class="nav-brand">
        MU Lost & Found
    </div>

    <div class="nav-right">

        <span class="user-name">
            My Claims
        </span>

    </div>

</nav>


<!-- MAIN -->

<main class="dashboard-container">


    <!-- HEADER -->

    <section class="dashboard-header">

        <h1>
            My Claims
        </h1>

        <p>
            Track the status of the items you have claimed.
        </p>

    </section>


    <!-- CLAIMS -->

    <section class="recent-section">

        <h2>
            Claim History
        </h2>


        <?php if (empty($claims)): ?>

            <p class="empty-message">
                You have not submitted any claims yet.
            </p>

        <?php else: ?>


            <div
                style="
                    overflow-x:auto;
                    margin-top:20px;
                "
            >

                <table
                    style="
                        width:100%;
                        border-collapse:collapse;
                        font-size:14px;
                    "
                >

                    <thead>

                        <tr>

                            <th
                                style="
                                    padding:12px;
                                    text-align:left;
                                "
                            >
                                Claim ID
                            </th>


                            <th
                                style="
                                    padding:12px;
                                    text-align:left;
                                "
                            >
                                Item
                            </th>


                            <th
                                style="
                                    padding:12px;
                                    text-align:left;
                                "
                            >
                                Location
                            </th>


                            <th
                                style="
                                    padding:12px;
                                    text-align:left;
                                "
                            >
                                Claim Date
                            </th>


                            <th
                                style="
                                    padding:12px;
                                    text-align:left;
                                "
                            >
                                Status
                            </th>


                            <th
                                style="
                                    padding:12px;
                                    text-align:left;
                                "
                            >
                                Verification Date
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($claims as $claim): ?>

                        <tr>


                            <!-- CLAIM ID -->

                            <td
                                style="
                                    padding:12px;
                                "
                            >

                                <?php
                                echo htmlspecialchars(
                                    $claim['claim_id']
                                );
                                ?>

                            </td>


                            <!-- ITEM -->

                            <td
                                style="
                                    padding:12px;
                                "
                            >

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $claim['item_name']
                                    );
                                    ?>

                                </strong>

                            </td>


                            <!-- LOCATION -->

                            <td
                                style="
                                    padding:12px;
                                "
                            >

                                <?php

                                echo htmlspecialchars(
                                    $claim['location']
                                    ?? 'Not specified'
                                );

                                ?>

                            </td>


                            <!-- CLAIM DATE -->

                            <td
                                style="
                                    padding:12px;
                                "
                            >

                                <?php
                                echo htmlspecialchars(
                                    $claim['claimdate']
                                );
                                ?>

                            </td>


                            <!-- STATUS -->

                            <td
                                style="
                                    padding:12px;
                                "
                            >

                                <?php

                                $status =
                                    $claim['claimstatus'];

                                if ($status === 'Approved'):

                                ?>

                                    <span
                                        style="
                                            display:inline-block;
                                            padding:5px 10px;
                                            border-radius:20px;
                                            background:#dcfce7;
                                            color:#166534;
                                            font-weight:600;
                                        "
                                    >
                                        Approved
                                    </span>


                                <?php elseif ($status === 'Rejected'): ?>

                                    <span
                                        style="
                                            display:inline-block;
                                            padding:5px 10px;
                                            border-radius:20px;
                                            background:#fee2e2;
                                            color:#991b1b;
                                            font-weight:600;
                                        "
                                    >
                                        Rejected
                                    </span>


                                <?php else: ?>

                                    <span
                                        style="
                                            display:inline-block;
                                            padding:5px 10px;
                                            border-radius:20px;
                                            background:#fef3c7;
                                            color:#92400e;
                                            font-weight:600;
                                        "
                                    >
                                        Pending
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- VERIFICATION DATE -->

                            <td
                                style="
                                    padding:12px;
                                "
                            >

                                <?php

                                echo htmlspecialchars(
                                    $claim['Verification_Date']
                                    ?? 'Not yet verified'
                                );

                                ?>

                            </td>


                        </tr>

                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php endif; ?>


        <!-- BACK BUTTON -->

        <div
            style="
                margin-top:25px;
            "
        >

            <button
                type="button"
                onclick="window.location.href='dashboard.php'"
                style="
                    padding:10px 18px;
                    border:none;
                    border-radius:6px;
                    background:#1e3a8a;
                    color:white;
                    cursor:pointer;
                    font-family:Poppins,sans-serif;
                "
            >
                Back to Dashboard
            </button>

        </div>


    </section>


</main>

</body>

</html>