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


/* =========================
   GET CURRENT STUDENT
========================= */

$studentId = intval(
    $_SESSION['user']['student_id'] ?? 0
);

if ($studentId <= 0) {

    $email = $_SESSION['user']['email'] ?? '';

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

    if ($student) {
        $studentId = intval($student['student_id']);
    }
}

if ($studentId <= 0) {
    exit("Student record not found. Please log in again.");
}

$firstName = e($_SESSION['user']['first_name'] ?? 'User');
$lastName  = e($_SESSION['user']['last_name'] ?? '');


/* =========================
   SEARCH AND FILTER
========================= */

$search = trim($_GET['search'] ?? '');
$location = trim($_GET['location'] ?? '');


/* =========================
   GET AVAILABLE FOUND ITEMS
========================= */

$sql = "
    SELECT
        i.item_id,
        i.student_id,
        i.item_name,
        i.Description,
        i.location,
        i.Date_reported,
        i.Photo,
        i.Item_type,
        i.status,
        s.fname,
        s.lname

    FROM item i

    INNER JOIN student s
        ON i.student_id = s.student_id

    WHERE i.Item_type = 'Found'
      AND i.status = 'Available'
";

$params = [];


/* =========================
   SEARCH ITEM NAME OR DESCRIPTION
========================= */

if ($search !== '') {

    $sql .= "
        AND (
            i.item_name LIKE :search_name
            OR i.Description LIKE :search_description
        )
    ";

    $params[':search_name'] = '%' . $search . '%';
    $params[':search_description'] = '%' . $search . '%';
}


/* =========================
   LOCATION FILTER
========================= */

if ($location !== '') {

    $sql .= "
        AND i.location LIKE :location
    ";

    $params[':location'] = '%' . $location . '%';
}


$sql .= " ORDER BY i.item_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   CHECK EXISTING CLAIMS
========================= */

$stmt = $pdo->prepare("
    SELECT item_id
    FROM claim
    WHERE student_id = :student_id
");

$stmt->execute([
    ':student_id' => $studentId
]);

$claimedItems = array_map(
    'intval',
    $stmt->fetchAll(PDO::FETCH_COLUMN)
);

$isFiltered = ($search !== '' || $location !== '');

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Browse Found Items - MU Lost &amp; Found</title>

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
            --gold:      #f59e0b;
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
            max-width: 1200px;
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

        /* ---------- SEARCH ---------- */
        .search-form {
            display: grid;
            grid-template-columns: 1.4fr 1fr auto auto;
            gap: 12px;
            margin-top: 18px;
        }

        .search-form input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
            color: var(--text);
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .search-form input:focus {
            outline: none;
            border-color: var(--blue);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
        }

        .action-button {
            padding: 12px 22px;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.2s, background 0.2s;
        }

        .primary-button {
            background: linear-gradient(135deg, var(--navy), var(--blue));
            color: #ffffff;
            box-shadow: 0 6px 14px rgba(30, 58, 138, 0.22);
        }

        .primary-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(30, 58, 138, 0.3);
        }

        .reset-button {
            background: #f3f4f6;
            color: #4b5563;
        }

        .reset-button:hover { background: #e5e7eb; }

        /* ---------- RESULTS ---------- */
        .results-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 10px;
        }

        .count-pill {
            padding: 5px 14px;
            border-radius: 999px;
            background: var(--blue-line);
            color: #1e40af;
            font-size: 12px;
            font-weight: 600;
        }

        .item-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-top: 22px;
        }

        .item-card {
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow-wrap: anywhere;
            transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;
        }

        .item-card:hover {
            border-color: var(--blue-line);
            box-shadow: var(--shadow-lg);
            transform: translateY(-3px);
        }

        .card-photo { position: relative; }

        .item-photo,
        .no-photo {
            display: block;
            width: 100%;
            height: 180px;
        }

        .item-photo {
            object-fit: cover;
            background: #f3f4f6;
        }

        .no-photo {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            color: #9ca3af;
            font-size: 12px;
        }

        .item-type-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 12px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 11px;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .card-body {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 16px;
        }

        .item-card h3 {
            margin: 0 0 8px;
            color: var(--text);
            font-size: 16px;
        }

        .item-description {
            margin: 0 0 10px;
            color: #4b5563;
            font-size: 13px;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .item-detail {
            margin: 4px 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .item-detail strong { color: #374151; }

        .claim-button {
            width: 100%;
            margin-top: auto;
            padding: 11px;
            border: none;
            border-radius: 9px;
            background: var(--navy);
            color: #ffffff;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .card-body .item-detail:last-of-type { margin-bottom: 14px; }

        .claim-button:hover:not(:disabled) { background: var(--blue); }

        .claim-button:disabled {
            cursor: not-allowed;
            background: #e5e7eb;
            color: #6b7280;
        }

        .already-claimed:disabled {
            background: #fef3c7;
            color: #92400e;
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

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 1000px) {
            .item-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .search-form { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 650px) {
            .navbar { padding: 12px 4%; }
            .nav-brand { font-size: 16px; }
            .user-name { display: none; }

            .container { margin-top: 20px; padding: 0 14px; }

            .page-banner { padding: 22px 20px; }
            .page-banner h1 { font-size: 22px; }

            .card { padding: 20px 16px; }

            .search-form { grid-template-columns: 1fr; }
            .item-grid { grid-template-columns: 1fr; }
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

        <span class="banner-tag">Found Items</span>

        <h1>Browse Found Items</h1>

        <p>
            Search for items reported as found and submit
            a claim if an item belongs to you.
        </p>

    </section>


    <!-- SEARCH AND FILTER -->

    <section class="card">

        <h2>Search Items</h2>

        <p class="card-sub">
            Search by item name or description, and narrow the results by location.
        </p>

        <form method="GET" class="search-form">

            <input
                type="text"
                name="search"
                placeholder="Search item name or description"
                aria-label="Search item name or description"
                value="<?= e($search) ?>"
            >

            <input
                type="text"
                name="location"
                placeholder="Filter by location"
                aria-label="Filter by location"
                value="<?= e($location) ?>"
            >

            <button
                type="submit"
                class="action-button primary-button"
            >
                Search
            </button>

            <button
                type="button"
                class="action-button reset-button"
                onclick="window.location.href='found-items.php'"
            >
                Reset
            </button>

        </form>

    </section>


    <!-- AVAILABLE ITEMS -->

    <section class="card">

        <div class="results-head">

            <div>
                <h2>Available Found Items</h2>
                <p class="card-sub">
                    <?= $isFiltered ? 'Showing results for your search.' : 'Items waiting to be claimed by their owners.' ?>
                </p>
            </div>

            <span class="count-pill">
                <?= count($items) ?> <?= count($items) === 1 ? 'item' : 'items' ?>
            </span>

        </div>

        <?php if (empty($items)): ?>

            <p class="empty-message">

                <?php if ($isFiltered): ?>

                    No available found items match your search.

                <?php else: ?>

                    No available found items have been posted yet.

                <?php endif; ?>

            </p>

        <?php else: ?>

            <div class="item-grid">

                <?php foreach ($items as $item): ?>

                    <?php

                    $itemId = intval($item['item_id']);

                    $isOwner =
                        $studentId === intval($item['student_id']);

                    $hasClaimed =
                        in_array($itemId, $claimedItems, true);

                    $ts = !empty($item['Date_reported'])
                        ? strtotime($item['Date_reported'])
                        : false;

                    $dateText = $ts
                        ? date('M j, Y', $ts)
                        : ($item['Date_reported'] ?? 'Not specified');

                    ?>

                    <article class="item-card">

                        <!-- PHOTO -->

                        <div class="card-photo">

                            <?php if (!empty($item['Photo'])): ?>

                                <img
                                    src="uploads/<?= e(basename($item['Photo'])) ?>"
                                    alt="Photo of <?= e($item['item_name']) ?>"
                                    class="item-photo"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="no-photo">
                                    No Photo
                                </div>

                            <?php endif; ?>

                            <span class="item-type-badge">
                                Found - Available
                            </span>

                        </div>


                        <div class="card-body">

                            <h3><?= e($item['item_name']) ?></h3>

                            <p class="item-description">
                                <?= e($item['Description'] ?? 'No description') ?>
                            </p>

                            <p class="item-detail">
                                <strong>Location:</strong>
                                <?= e($item['location'] ?? 'Not specified') ?>
                            </p>

                            <p class="item-detail">
                                <strong>Date Reported:</strong>
                                <?= e($dateText) ?>
                            </p>

                            <p class="item-detail">
                                <strong>Reported by:</strong>
                                <?= e(($item['fname'] ?? '') . ' ' . ($item['lname'] ?? '')) ?>
                            </p>


                            <!-- CLAIM ACTION -->

                            <?php if ($isOwner): ?>

                                <button
                                    type="button"
                                    class="claim-button"
                                    disabled
                                >
                                    Your Found Item
                                </button>

                            <?php elseif ($hasClaimed): ?>

                                <button
                                    type="button"
                                    class="claim-button already-claimed"
                                    disabled
                                >
                                    Claim Already Submitted
                                </button>

                            <?php else: ?>

                                <button
                                    type="button"
                                    class="claim-button"
                                    onclick="submitClaim(<?= $itemId ?>, this)"
                                >
                                    Claim This Item
                                </button>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>


<script>

async function submitClaim(itemId, button) {

    if (!confirm("Are you sure you want to claim this item?")) {
        return;
    }

    button.disabled = true;
    button.textContent = "Submitting...";

    const formData = new FormData();
    formData.append("item_id", itemId);

    try {

        const response = await fetch(
            "api/claims/create.php",
            {
                method: "POST",
                body: formData
            }
        );

        const text = await response.text();

        let data;

        try {
            data = JSON.parse(text);
        } catch (error) {
            throw new Error(
                "The server returned an invalid response. Check the PHP error log."
            );
        }

        alert(data.message || "No response message received.");

        if (response.ok && data.success) {
            window.location.reload();
            return;
        }

    } catch (error) {

        console.error("Claim submission error:", error);
        alert(error.message || "Unable to submit the claim.");

    }

    button.disabled = false;
    button.textContent = "Claim This Item";
}

</script>

</body>
</html>
