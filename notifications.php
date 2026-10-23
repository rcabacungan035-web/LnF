<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

require_once "api/config/database.php";

/* Small helper */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$firstName = e($_SESSION['user']['first_name'] ?? 'User');
$lastName  = e($_SESSION['user']['last_name'] ?? '');


/* =========================
   GET STUDENT ID
========================= */

$studentId =
    intval($_SESSION['user']['student_id'] ?? $_SESSION['user']['id'] ?? 0);


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
   GET NOTIFICATIONS
========================= */

$stmt = $pdo->prepare("
    SELECT
        n.notification_id,
        n.student_id,
        n.item_id,
        n.Message,
        n.DateSent,
        n.IsRead,
        i.item_name

    FROM email_notification n

    LEFT JOIN item i
        ON n.item_id = i.item_id

    WHERE n.student_id = :student_id

    ORDER BY n.DateSent DESC
");

$stmt->execute([
    ':student_id' => $studentId
]);

$notifications =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/* Count unread BEFORE marking everything as read */
$unreadCount = 0;

foreach ($notifications as $n) {
    if (empty($n['IsRead'])) {
        $unreadCount++;
    }
}


/* =========================
   MARK NOTIFICATIONS AS READ
========================= */

if (!empty($notifications)) {

    $stmt = $pdo->prepare("
        UPDATE email_notification
        SET IsRead = 1
        WHERE student_id = :student_id
    ");

    $stmt->execute([
        ':student_id' => $studentId
    ]);

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Notifications - MU Lost &amp; Found</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --navy:      #1e3a8a;
            --navy-dark: #0b2a6b;
            --blue:      #1d4ed8;
            --blue-soft: #eff6ff;
            --blue-line: #dbeafe;
            --bg:        #f5f7fb;
            --border:    #e5e7eb;
            --text:      #1f2937;
            --muted:     #6b7280;
            --radius:    14px;
            --shadow:    0 2px 10px rgba(15, 23, 42, 0.05);
            --shadow-lg: 0 10px 28px rgba(30, 58, 138, 0.14);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        /* ---------- NAVBAR ---------- */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 13px 5%;
            background: linear-gradient(90deg, var(--navy-dark), var(--navy));
            color: #ffffff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.18);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 19px;
            font-weight: 700;
        }

        .nav-brand img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .user-name {
            font-size: 13px;
            font-weight: 500;
        }

        .nav-link {
            padding: 9px 18px;
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }

        .nav-link:hover {
            background: #ffffff;
            color: var(--navy);
        }

        /* ---------- PAGE ---------- */
        .container {
            max-width: 820px;
            margin: 30px auto 50px;
            padding: 0 20px;
            display: grid;
            gap: 22px;
        }

        .page-banner {
            position: relative;
            overflow: hidden;
            padding: 28px 30px;
            color: #ffffff;
            border-radius: var(--radius);
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy) 55%, var(--blue) 100%);
            box-shadow: var(--shadow-lg);
        }

        .page-banner::before {
            content: "";
            position: absolute;
            top: -90px;
            right: -60px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
        }

        .page-banner > * { position: relative; }

        .banner-tag {
            display: inline-block;
            margin-bottom: 10px;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .page-banner h1 {
            margin: 0 0 6px;
            font-size: 26px;
        }

        .page-banner p {
            max-width: 560px;
            margin: 0;
            color: #dbe7ff;
            font-size: 14px;
            line-height: 1.6;
        }

        .card {
            padding: 26px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .card-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 10px;
        }

        .card h2 {
            margin: 0 0 4px;
            color: var(--navy);
            font-size: 20px;
        }

        .card-sub {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .count-pills {
            display: flex;
            gap: 8px;
        }

        .count-pill {
            padding: 5px 14px;
            border-radius: 999px;
            background: var(--blue-line);
            color: #1e40af;
            font-size: 12px;
            font-weight: 600;
        }

        .count-pill.new {
            background: #fef3c7;
            color: #92400e;
        }

        /* ---------- NOTIFICATION LIST ---------- */
        .notification-list {
            display: grid;
            gap: 14px;
            margin-top: 22px;
        }

        .notification {
            padding: 16px 18px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-left: 5px solid #d1d5db;
            border-radius: 12px;
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .notification:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .notification.unread {
            border-left-color: var(--blue);
            background: var(--blue-soft);
        }

        .notification-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .notification h3 {
            margin: 0;
            color: var(--navy);
            font-size: 16px;
            overflow-wrap: anywhere;
        }

        .new-tag {
            flex: 0 0 auto;
            padding: 3px 11px;
            border-radius: 999px;
            background: var(--blue);
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
        }

        .notification p {
            margin: 8px 0 10px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .notification small {
            color: #9ca3af;
            font-size: 12px;
        }

        .empty-message {
            margin: 22px 0 0;
            padding: 34px 20px;
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            background: #f9fafb;
            color: var(--muted);
            text-align: center;
            font-size: 13px;
            line-height: 1.7;
        }

        /* ---------- MOBILE ---------- */
        @media (max-width: 650px) {
            .navbar { padding: 12px 4%; }
            .nav-brand { font-size: 16px; }
            .user-name { display: none; }

            .container { margin-top: 20px; padding: 0 14px; }

            .page-banner { padding: 22px 20px; }
            .page-banner h1 { font-size: 22px; }

            .card { padding: 20px 16px; }
        }
    </style>

</head>

<body>

<nav class="navbar">

    <div class="nav-brand">
        <img src="mu_logo.png" alt="" onerror="this.style.display='none'">
        MU Lost &amp; Found
    </div>

    <div class="nav-right">

        <span class="user-name">
            <?= $firstName . ' ' . $lastName ?>
        </span>

        <a href="dashboard.php" class="nav-link">
            Back to Dashboard
        </a>

    </div>

</nav>


<main class="container">

    <section class="page-banner">

        <span class="banner-tag">Updates</span>

        <h1>Notifications</h1>

        <p>
            View updates about your claims and lost and found items.
        </p>

    </section>


    <section class="card">

        <div class="card-head">

            <div>
                <h2>Your Notifications</h2>
                <p class="card-sub">Newest updates appear first.</p>
            </div>

            <div class="count-pills">

                <span class="count-pill">
                    <?= count($notifications) ?> total
                </span>

                <?php if ($unreadCount > 0): ?>
                    <span class="count-pill new">
                        <?= $unreadCount ?> new
                    </span>
                <?php endif; ?>

            </div>

        </div>


        <?php if (empty($notifications)): ?>

            <p class="empty-message">
                You have no notifications yet.
            </p>

        <?php else: ?>

            <div class="notification-list">

                <?php foreach ($notifications as $notification): ?>

                    <?php
                    $isNew = empty($notification['IsRead']);

                    $ts = !empty($notification['DateSent'])
                        ? strtotime($notification['DateSent'])
                        : false;

                    $dateText = $ts
                        ? date('M j, Y g:i A', $ts)
                        : ($notification['DateSent'] ?? '');
                    ?>

                    <article class="notification<?= $isNew ? ' unread' : '' ?>">

                        <div class="notification-top">

                            <h3>
                                <?= e($notification['item_name'] ?? 'Item') ?>
                            </h3>

                            <?php if ($isNew): ?>
                                <span class="new-tag">New</span>
                            <?php endif; ?>

                        </div>

                        <p>
                            <?= e($notification['Message'] ?? '') ?>
                        </p>

                        <small>
                            <?= e($dateText) ?>
                        </small>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>