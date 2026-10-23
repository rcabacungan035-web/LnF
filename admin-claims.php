<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . "/api/config/database.php";

$adminName = $_SESSION['admin_name'] ?? 'System Administrator';
$isSuper   = ($_SESSION['admin_role'] ?? '') === 'super_admin';
$initial   = strtoupper(mb_substr(trim($adminName), 0, 1, 'UTF-8')) ?: 'A';

/* Small helper for safe output */
if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/* =========================
   GET ALL CLAIMS
========================= */

$stmt = $pdo->query("
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
        s.student_id,
        s.fname,
        s.lname,
        s.email
    FROM claim c
    INNER JOIN item i
        ON c.item_id = i.item_id
    INNER JOIN student s
        ON c.student_id = s.student_id
    ORDER BY c.claim_id DESC
");

$claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT
        i.item_id,
        i.item_name,
        i.Description,
        i.location,
        i.Date_reported,
        i.Photo,
        s.fname,
        s.lname,
        s.email
    FROM item i
    INNER JOIN student s
        ON i.student_id = s.student_id
    WHERE i.Item_type = 'Found'
      AND i.status = 'Found'
    ORDER BY i.item_id DESC
");

$pendingFoundItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pendingClaimCount = 0;
foreach ($claims as $c) {
    if (($c['claimstatus'] ?? '') === 'Pending') {
        $pendingClaimCount++;
    }
}

/* Maps a claim status to a badge class */
function statusClass(string $status): string
{
    switch ($status) {
        case 'Approved': return 'badge-approved';
        case 'Rejected': return 'badge-rejected';
        case 'Pending':  return 'badge-pending';
        default:         return 'badge-neutral';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin - Claims | MU Lost &amp; Found</title>

    <link rel="stylesheet" href="style.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --ink:       #1e3a8a;
            --blue:      #1d4ed8;
            --blue-soft: #e8efff;
            --paper:     #f4f7fb;
            --card:      #ffffff;
            --line:      #e5e7eb;
            --text:      #1f2937;
            --muted:     #64748b;

            --green:     #15803d;
            --green-bg:  #dcfce7;
            --red:       #b91c1c;
            --red-bg:    #fee2e2;
            --amber:     #b45309;
            --amber-bg:  #fef3c7;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--paper);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        a:focus-visible,
        button:focus-visible {
            outline: 3px solid rgba(29, 78, 216, 0.4);
            outline-offset: 2px;
        }

        /* ---------- Top bar ---------- */
        .ad-topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #1e3a8a;
            color: #fff;
        }

        .ad-topbar-inner {
            width: 92%;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 24px;
            min-height: 64px;
        }

        .ad-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 16px;
            white-space: nowrap;
            text-decoration: none;
            color: #fff;
        }

        .ad-brand-mark {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: rgba(255, 255, 255, 0.16);
            display: grid;
            place-items: center;
        }

        .ad-brand-mark svg { width: 18px; height: 18px; }

        .ad-brand small {
            font-weight: 500;
            color: #bfdbfe;
            margin-left: 2px;
        }

        .ad-nav {
            display: flex;
            gap: 4px;
            flex: 1;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .ad-nav::-webkit-scrollbar { display: none; }

        .ad-nav a {
            padding: 8px 14px;
            border-radius: 8px;
            color: #c7d7f8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            transition: background 0.15s, color 0.15s;
        }

        .ad-nav a:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }

        .ad-nav a[aria-current="page"] {
            background: rgba(255, 255, 255, 0.14);
            color: #fff;
        }

        .ad-user {
            display: flex;
            align-items: center;
            gap: 12px;
            white-space: nowrap;
        }

        .ad-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #fff;
            color: #1e3a8a;
            font-weight: 700;
            font-size: 14px;
            display: grid;
            place-items: center;
        }

        .ad-user-name {
            font-size: 13px;
            font-weight: 500;
            max-width: 160px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ad-logout {
            padding: 7px 14px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 8px;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.15s;
        }

        .ad-logout:hover { background: rgba(255, 255, 255, 0.12); }

        /* ---------- Hero band ---------- */
        .ad-hero {
            position: relative;
            overflow: hidden;
            color: #fff;
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
        }

        .ad-hero::before {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            top: -140px;
            right: -50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
        }

        .ad-hero-inner {
            position: relative;
            z-index: 1;
            width: 92%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 0 76px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            flex-wrap: wrap;
        }

        .ad-hero h1 {
            margin: 0 0 6px;
            font-size: 27px;
            font-weight: 700;
            line-height: 1.25;
        }

        .ad-hero p {
            margin: 0;
            color: #bfdbfe;
            font-size: 14px;
            line-height: 1.6;
            max-width: 52ch;
        }

        .ad-hero-stats {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .hero-chip {
            padding: 10px 16px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.14);
            font-size: 13px;
            color: #e0ecff;
            line-height: 1.3;
        }

        .hero-chip strong {
            display: block;
            font-size: 22px;
            font-weight: 700;
            color: #fff;
        }

        /* ---------- Content ---------- */
        .dashboard-container {
            width: 92%;
            max-width: 1200px;
            margin: -48px auto 48px;
            position: relative;
            z-index: 2;
            display: grid;
            gap: 22px;
        }

        .panel {
            background: var(--card);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(30, 58, 138, 0.1);
            overflow: hidden;
        }

        .panel-head {
            padding: 22px 24px 18px;
            border-bottom: 1px solid var(--line);
        }

        .panel-head h2 {
            margin: 0 0 4px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: var(--ink);
        }

        .panel-head p {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
        }

        .count-pill {
            padding: 2px 10px;
            border-radius: 999px;
            background: var(--blue-soft);
            color: var(--blue);
            font-size: 12px;
            font-weight: 600;
        }

        .count-pill.warn {
            background: var(--amber-bg);
            color: var(--amber);
        }

        /* ---------- Tables ---------- */
        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        thead th {
            padding: 12px 20px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            background: #f8fafc;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        tbody td {
            padding: 16px 20px;
            vertical-align: top;
            border-bottom: 1px solid var(--line);
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #fafcff; }

        .item-cell {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            min-width: 220px;
        }

        .item-thumb {
            flex: 0 0 56px;
            width: 56px;
            height: 56px;
            border-radius: 10px;
            object-fit: cover;
            background: var(--blue-soft);
        }

        .item-thumb.empty {
            display: grid;
            place-items: center;
            color: var(--blue);
        }

        .item-thumb.empty svg { width: 24px; height: 24px; }

        .item-name {
            display: block;
            font-weight: 600;
            color: var(--text);
        }

        .item-desc {
            display: block;
            margin-top: 2px;
            font-size: 12.5px;
            line-height: 1.5;
            color: var(--muted);
            max-width: 340px;
        }

        .sub {
            display: block;
            margin-top: 2px;
            font-size: 12.5px;
            color: var(--muted);
        }

        .id-tag {
            font-weight: 600;
            color: var(--ink);
        }

        /* ---------- Badges ---------- */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pending  { background: var(--amber-bg); color: var(--amber); }
        .badge-approved { background: var(--green-bg); color: var(--green); }
        .badge-rejected { background: var(--red-bg);   color: var(--red); }
        .badge-neutral  { background: #e2e8f0;         color: var(--muted); }

        /* ---------- Buttons ---------- */
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .claim-action {
            padding: 8px 14px;
            border: none;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            transition: filter 0.15s, transform 0.1s;
        }

        .claim-action:hover { filter: brightness(1.1); }
        .claim-action:active { transform: scale(0.97); }
        .claim-action:disabled { opacity: 0.6; cursor: wait; }

        .approve-button { background: #15803d; }
        .reject-button  { background: #b91c1c; }

        .done-text {
            font-size: 13px;
            color: var(--muted);
        }

        /* ---------- Empty state ---------- */
        .empty-message {
            margin: 0;
            padding: 40px 24px;
            text-align: center;
            font-size: 14px;
            color: var(--muted);
        }

        /* ---------- Bottom links ---------- */
        .bottom-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .bottom-actions a {
            display: inline-block;
            padding: 11px 20px;
            border-radius: 10px;
            background: #1e3a8a;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.15s;
        }

        .bottom-actions a:hover { background: #1d4ed8; }

        .bottom-actions a.secondary {
            background: #fff;
            color: var(--ink);
            border: 1px solid #cbd5e1;
        }

        .bottom-actions a.secondary:hover { background: var(--blue-soft); }

        /* ---------- Responsive ---------- */
        @media (max-width: 760px) {
            .ad-topbar-inner {
                flex-wrap: wrap;
                gap: 6px 16px;
                padding: 10px 0;
            }
            .ad-nav { order: 3; flex-basis: 100%; }
            .ad-user { margin-left: auto; }
            .ad-user-name { display: none; }

            /* Tables become stacked cards */
            thead { display: none; }

            table, tbody, tr, td { display: block; width: 100%; }

            tbody tr {
                padding: 14px 16px;
                border-bottom: 1px solid var(--line);
            }

            tbody tr:last-child { border-bottom: none; }

            tbody td {
                display: flex;
                justify-content: space-between;
                gap: 16px;
                padding: 6px 0;
                border: none;
                text-align: right;
            }

            tbody td::before {
                content: attr(data-label);
                flex: 0 0 auto;
                font-size: 12.5px;
                font-weight: 600;
                color: var(--muted);
                text-align: left;
            }

            tbody td.cell-item { display: block; text-align: left; }
            tbody td.cell-item::before { display: none; }
            tbody td.cell-item .item-cell { min-width: 0; }

            .actions { justify-content: flex-end; }
        }

        @media (max-width: 520px) {
            .ad-hero h1 { font-size: 22px; }
            .ad-hero-inner { padding: 26px 0 70px; }
            .panel-head { padding: 18px 16px 14px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .ad-nav a, .claim-action, .bottom-actions a { transition: none; }
        }
    </style>
</head>

<body>

<!-- NAVIGATION -->

<header class="ad-topbar">
    <div class="ad-topbar-inner">

        <a class="ad-brand" href="admin-dashboard.php">
            <span class="ad-brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                </svg>
            </span>
            <span>MU Lost &amp; Found <small>Admin</small></span>
        </a>

        <nav class="ad-nav" aria-label="Admin navigation">
            <a href="admin-dashboard.php">Dashboard</a>
            <a href="admin-claims.php" aria-current="page">Claims</a>
            <a href="admin-items.php">Items</a>
            <a href="admin-post-item.php">Post Lost/Found</a>
            <?php if ($isSuper): ?>
                <a href="super-admin-dashboard.php">Super Admin</a>
            <?php endif; ?>
        </nav>

        <div class="ad-user">
            <span class="ad-avatar" aria-hidden="true"><?= e($initial) ?></span>
            <span class="ad-user-name"><?= e($adminName) ?></span>
            <a class="ad-logout" href="api/auth/admin-logout.php">Logout</a>
        </div>

    </div>
</header>

<!-- HERO -->

<section class="ad-hero">
    <div class="ad-hero-inner">

        <div>
            <h1>Claim Management</h1>
            <p>Review found-item submissions and student claims.</p>
        </div>

        <div class="ad-hero-stats">
            <div class="hero-chip">
                <strong><?= count($pendingFoundItems) ?></strong>
                Submissions to review
            </div>
            <div class="hero-chip">
                <strong><?= $pendingClaimCount ?></strong>
                Pending claims
            </div>
        </div>

    </div>
</section>

<!-- MAIN -->

<main class="dashboard-container">

    <!-- FOUND ITEM SUBMISSIONS -->

    <section class="panel">

        <div class="panel-head">
            <h2>
                Found Item Submissions
                <?php if (!empty($pendingFoundItems)): ?>
                    <span class="count-pill warn"><?= count($pendingFoundItems) ?> waiting</span>
                <?php endif; ?>
            </h2>
            <p>Approve a submission to make it available for students to claim.</p>
        </div>

        <?php if (empty($pendingFoundItems)): ?>

            <p class="empty-message">
                No found-item submissions are waiting for review.
            </p>

        <?php else: ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Location</th>
                            <th>Reported By</th>
                            <th>Date Found</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pendingFoundItems as $item): ?>
                        <tr>
                            <td class="cell-item" data-label="Item">
                                <div class="item-cell">
                                    <?php if (!empty($item['Photo'])): ?>
                                        <img
                                            class="item-thumb"
                                            src="uploads/<?= e(basename($item['Photo'])) ?>"
                                            alt="Photo of <?= e($item['item_name']) ?>"
                                        >
                                    <?php else: ?>
                                        <span class="item-thumb empty" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5"/><path d="M12 13v8"/></svg>
                                        </span>
                                    <?php endif; ?>
                                    <div>
                                        <span class="item-name"><?= e($item['item_name']) ?></span>
                                        <?php if (!empty($item['Description'])): ?>
                                            <span class="item-desc"><?= e($item['Description']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Location"><?= e($item['location']) ?></td>
                            <td data-label="Reported By">
                                <?= e(($item['fname'] ?? '') . ' ' . ($item['lname'] ?? '')) ?>
                                <span class="sub"><?= e($item['email']) ?></span>
                            </td>
                            <td data-label="Date Found"><?= e($item['Date_reported']) ?></td>
                            <td data-label="Action">
                                <div class="actions">
                                    <button
                                        type="button"
                                        class="claim-action approve-button"
                                        onclick="reviewFoundItem(<?= (int) $item['item_id'] ?>, 'Available')"
                                    >Approve</button>
                                    <button
                                        type="button"
                                        class="claim-action reject-button"
                                        onclick="reviewFoundItem(<?= (int) $item['item_id'] ?>, 'Rejected')"
                                    >Reject</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>

    <!-- STUDENT CLAIMS -->

    <section class="panel">

        <div class="panel-head">
            <h2>
                Student Claims
                <?php if ($pendingClaimCount > 0): ?>
                    <span class="count-pill warn"><?= $pendingClaimCount ?> pending</span>
                <?php elseif (!empty($claims)): ?>
                    <span class="count-pill"><?= count($claims) ?> total</span>
                <?php endif; ?>
            </h2>
            <p>Approve or reject claims submitted by students.</p>
        </div>

        <?php if (empty($claims)): ?>

            <p class="empty-message">
                No claims have been submitted yet.
            </p>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>
                        <tr>
                            <th>Claim ID</th>
                            <th>Item</th>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Claim Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($claims as $claim): ?>

                        <tr>

                            <td data-label="Claim ID">
                                <span class="id-tag">#<?= (int) $claim['claim_id'] ?></span>
                            </td>

                            <td class="cell-item" data-label="Item">
                                <span class="item-name"><?= e($claim['item_name']) ?></span>
                                <?php if (!empty($claim['Description'])): ?>
                                    <span class="item-desc"><?= e($claim['Description']) ?></span>
                                <?php endif; ?>
                            </td>

                            <td data-label="Student">
                                <?= e(($claim['fname'] ?? '') . ' ' . ($claim['lname'] ?? '')) ?>
                            </td>

                            <td data-label="Email">
                                <?= e($claim['email']) ?>
                            </td>

                            <td data-label="Claim Date">
                                <?= e($claim['claimdate']) ?>
                            </td>

                            <td data-label="Status">
                                <span class="badge <?= statusClass((string) ($claim['claimstatus'] ?? '')) ?>">
                                    <?= e($claim['claimstatus']) ?>
                                </span>
                            </td>

                            <td data-label="Action">

                                <?php if ($claim['claimstatus'] === 'Pending'): ?>

                                    <div class="actions">
                                        <button
                                            type="button"
                                            class="claim-action approve-button"
                                            onclick="updateClaim(<?= (int) $claim['claim_id'] ?>, 'Approved')"
                                        >Approve</button>

                                        <button
                                            type="button"
                                            class="claim-action reject-button"
                                            onclick="updateClaim(<?= (int) $claim['claim_id'] ?>, 'Rejected')"
                                        >Reject</button>
                                    </div>

                                <?php else: ?>

                                    <span class="done-text">Completed</span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

    <!-- BOTTOM NAVIGATION -->

    <div class="bottom-actions">
        <a href="admin-dashboard.php">Return to Dashboard</a>
        <a class="secondary" href="admin-items.php">Manage Items</a>
    </div>

</main>

<script>
async function reviewFoundItem(itemId, status) {
    const action = status === 'Available' ? 'approve' : 'reject';

    if (!confirm(`Are you sure you want to ${action} this found item?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('status', status);

    try {
        const response = await fetch('api/items/update-status.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            alert(data.message || 'Unable to review the found item.');
            return;
        }

        alert(data.message || 'Found item reviewed successfully.');
        window.location.reload();
    } catch (error) {
        alert('An error occurred while reviewing the found item.');
    }
}

async function updateClaim(claimId, status) {

    const confirmation = confirm(
        "Are you sure you want to mark this claim as " +
        status +
        "?"
    );

    if (!confirmation) {
        return;
    }

    const formData = new FormData();
    formData.append("claim_id", claimId);
    formData.append("status", status);

    try {
        const response = await fetch("api/claims/update-status.php", {
            method: "POST",
            body: formData,
            credentials: "same-origin"
        });

        const text = await response.text();

        if (!text.trim()) {
            alert("The server returned an empty response.");
            return;
        }

        let data;

        try {
            data = JSON.parse(text);
        } catch (error) {
            alert("Server returned an invalid response:\n\n" + text);
            return;
        }

        if (!response.ok || !data.success) {
            alert(
                "Update failed:\n\n" +
                (data.message || "Unable to update the claim.")
            );
            return;
        }

        alert(data.message || "Claim updated successfully.");
        window.location.reload();

    } catch (error) {
        console.error("FETCH ERROR:", error);
        alert("An error occurred:\n\n" + error.message);
    }
}
</script>

</body>
</html>