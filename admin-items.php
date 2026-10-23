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
   GET ALL ITEMS
========================= */

$stmt = $pdo->query("
    SELECT
        i.item_id,
        i.item_name,
        i.Description,
        i.location,
        i.Date_reported,
        i.Photo,
        i.Item_type,
        i.status,
        s.fname,
        s.lname,
        s.email
    FROM item i
    INNER JOIN student s
        ON i.student_id = s.student_id
    ORDER BY i.item_id DESC
");

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Counts for the hero band */
$countLost     = 0;
$countFound    = 0;
$countReturned = 0;

foreach ($items as $row) {
    $t = trim((string) ($row['Item_type'] ?? ''));
    $s = trim((string) ($row['status'] ?? ''));

    if ($t === 'Lost')  { $countLost++; }
    if ($t === 'Found') { $countFound++; }
    if ($s === 'Claimed / Returned') { $countReturned++; }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin - Items | MU Lost &amp; Found</title>

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

            --green:     #166534;
            --green-bg:  #dcfce7;
            --amber:     #92400e;
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
        button:focus-visible,
        input:focus-visible,
        select:focus-visible {
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
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
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

        /* ---------- Filters ---------- */
        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filters input,
        .filters select {
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            background: #fff;
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
        }

        .filters input { min-width: 220px; }

        /* ---------- Table ---------- */
        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        thead th {
            padding: 12px 18px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            background: #f8fafc;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        tbody td {
            padding: 16px 18px;
            vertical-align: top;
            border-bottom: 1px solid var(--line);
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #fafcff; }

        .id-tag {
            font-weight: 600;
            color: var(--ink);
        }

        .item-cell {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            min-width: 240px;
        }

        .item-thumb {
            flex: 0 0 60px;
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            background: var(--blue-soft);
            cursor: zoom-in;
        }

        .item-thumb.empty {
            display: grid;
            place-items: center;
            color: var(--blue);
            cursor: default;
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
            max-width: 320px;
        }

        .sub {
            display: block;
            margin-top: 2px;
            font-size: 12.5px;
            color: var(--muted);
        }

        /* ---------- Badges ---------- */
        .item-status,
        .type-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            background: #e2e8f0;
            color: var(--muted);
        }

        .status-available { background: #dbeafe; color: #1e40af; }
        .status-returned  { background: var(--green-bg); color: var(--green); }
        .status-lost      { background: var(--amber-bg); color: var(--amber); }

        .type-pill.type-lost  { background: var(--amber-bg); color: var(--amber); }
        .type-pill.type-found { background: var(--blue-soft); color: var(--blue); }

        /* ---------- Buttons ---------- */
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .item-action-button {
            padding: 8px 14px;
            border: none;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            transition: filter 0.15s, transform 0.1s;
        }

        .item-action-button:hover { filter: brightness(1.1); }
        .item-action-button:active { transform: scale(0.97); }

        .mark-returned  { background: #1e3a8a; }
        .mark-available { background: #6b7280; }

        .item-action-button.view {
            background: #fff;
            color: var(--ink);
            border: 1px solid #cbd5e1;
        }

        .item-action-button.view:hover { background: var(--blue-soft); filter: none; }

        .no-action {
            font-size: 13px;
            color: var(--muted);
            align-self: center;
        }

        /* ---------- Empty state ---------- */
        .empty-message {
            margin: 0;
            padding: 40px 24px;
            text-align: center;
            font-size: 14px;
            color: var(--muted);
        }

        #noMatches { display: none; }

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

        /* ---------- Item viewer ---------- */
        .admin-item-viewer {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            padding: 20px;
            background: rgba(8, 20, 50, 0.82);
            align-items: center;
            justify-content: center;
            overflow: auto;
        }

        .admin-item-viewer.open { display: flex; }

        .admin-item-viewer-panel {
            position: relative;
            width: min(900px, 100%);
            max-height: 94vh;
            overflow: auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);
        }

        #closeAdminViewer {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 2;
            width: 38px;
            height: 38px;
            border: none;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.92);
            color: #1e3a8a;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        #closeAdminViewer:hover { background: #fff; }

        #adminViewerPhoto {
            display: block;
            width: 100%;
            max-height: 58vh;
            object-fit: contain;
            background: #0f172a;
        }

        .viewer-body { padding: 24px 28px 28px; }

        .viewer-body h2 {
            margin: 0 0 14px;
            font-size: 22px;
            font-weight: 700;
            color: var(--ink);
        }

        .viewer-desc {
            margin: 0 0 18px;
            font-size: 14px;
            line-height: 1.7;
            color: var(--text);
        }

        .viewer-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 24px;
            margin: 0;
        }

        .viewer-grid div {
            padding: 12px 14px;
            background: var(--paper);
            border-radius: 10px;
        }

        .viewer-grid dt {
            margin: 0 0 2px;
            font-size: 12px;
            font-weight: 500;
            color: var(--muted);
        }

        .viewer-grid dd {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            overflow-wrap: anywhere;
        }

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

            .filters { width: 100%; }
            .filters input { flex: 1; min-width: 0; }
        }

        @media (max-width: 520px) {
            .ad-hero h1 { font-size: 22px; }
            .ad-hero-inner { padding: 26px 0 70px; }
            .panel-head { padding: 18px 16px 14px; }
            .viewer-grid { grid-template-columns: 1fr; }
            .viewer-body { padding: 20px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .ad-nav a, .item-action-button, .bottom-actions a { transition: none; }
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
            <a href="admin-claims.php">Claims</a>
            <a href="admin-items.php" aria-current="page">Items</a>
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
            <h1>Item Management</h1>
            <p>Manage lost and found items reported by students.</p>
        </div>

        <div class="ad-hero-stats">
            <div class="hero-chip">
                <strong><?= $countLost ?></strong>
                Lost items
            </div>
            <div class="hero-chip">
                <strong><?= $countFound ?></strong>
                Found items
            </div>
            <div class="hero-chip">
                <strong><?= $countReturned ?></strong>
                Returned
            </div>
        </div>

    </div>
</section>

<!-- MAIN -->

<main class="dashboard-container">

    <section class="panel">

        <div class="panel-head">

            <div>
                <h2>
                    All Items
                    <?php if (!empty($items)): ?>
                        <span class="count-pill" id="itemCount"><?= count($items) ?></span>
                    <?php endif; ?>
                </h2>
                <p>Click a photo or View Details to see the full item.</p>
            </div>

            <?php if (!empty($items)): ?>
                <div class="filters">
                    <input type="search" id="itemSearch" placeholder="Search items, location, student"
                           aria-label="Search items">
                    <select id="typeFilter" aria-label="Filter by type">
                        <option value="">All types</option>
                        <option value="Lost">Lost</option>
                        <option value="Found">Found</option>
                    </select>
                </div>
            <?php endif; ?>

        </div>

        <?php if (empty($items)): ?>

            <p class="empty-message">
                No items have been reported yet.
            </p>

        <?php else: ?>

            <div class="table-wrap">

                <table id="itemsTable">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Reported By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($items as $item): ?>

                        <?php
                        $status = trim((string) ($item['status'] ?? ''));
                        $type = trim((string) ($item['Item_type'] ?? ''));

                        $isAvailable = ($status === 'Available');
                        $isReturned = ($status === 'Claimed / Returned');

                        if ($isReturned) {
                            $statusLabel = 'Claimed / Returned';
                            $statusClass = 'status-returned';
                        } elseif ($isAvailable && $type === 'Found') {
                            $statusLabel = 'Available';
                            $statusClass = 'status-available';
                        } elseif ($type === 'Lost' || $status === 'Lost') {
                            $statusLabel = 'Lost';
                            $statusClass = 'status-lost';
                        } else {
                            $statusLabel = $status !== '' ? $status : 'Unknown';
                            $statusClass = '';
                        }

                        $photoPath = !empty($item['Photo'])
                            ? 'uploads/' . basename($item['Photo'])
                            : '';

                        $typeClass = $type === 'Lost' ? 'type-lost' : ($type === 'Found' ? 'type-found' : '');

                        $searchText = strtolower(
                            ($item['item_name'] ?? '') . ' ' .
                            ($item['Description'] ?? '') . ' ' .
                            ($item['location'] ?? '') . ' ' .
                            ($item['fname'] ?? '') . ' ' .
                            ($item['lname'] ?? '') . ' ' .
                            ($item['email'] ?? '')
                        );
                        ?>

                        <tr data-type="<?= e($type) ?>" data-search="<?= e($searchText) ?>">

                            <!-- ID -->

                            <td data-label="ID">
                                <span class="id-tag">#<?= e($item['item_id']) ?></span>
                            </td>

                            <!-- ITEM (photo + name + description) -->

                            <td class="cell-item" data-label="Item">

                                <div class="item-cell">

                                    <?php if ($photoPath !== ''): ?>

                                        <img class="item-thumb admin-item-photo"
                                            src="<?= e($photoPath) ?>"
                                            alt="Photo of <?= e($item['item_name']) ?>"
                                            data-photo="<?= e($photoPath) ?>"
                                            data-name="<?= e($item['item_name']) ?>"
                                            data-type="<?= e($type) ?>"
                                            data-description="<?= e($item['Description']) ?>"
                                            data-location="<?= e($item['location']) ?>"
                                            data-date="<?= e($item['Date_reported']) ?>"
                                            data-status="<?= e($status) ?>"
                                        >

                                    <?php else: ?>

                                        <span class="item-thumb empty" aria-hidden="true" title="No photo">
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

                            <!-- TYPE -->

                            <td data-label="Type">
                                <span class="type-pill <?= $typeClass ?>"><?= e($type !== '' ? $type : 'Unknown') ?></span>
                            </td>

                            <!-- LOCATION -->

                            <td data-label="Location">
                                <?= e(!empty($item['location']) ? $item['location'] : 'Not specified') ?>
                            </td>

                            <!-- REPORTED BY -->

                            <td data-label="Reported By">
                                <?= e(($item['fname'] ?? '') . ' ' . ($item['lname'] ?? '')) ?>
                                <span class="sub"><?= e($item['email']) ?></span>
                            </td>

                            <!-- DATE -->

                            <td data-label="Date">
                                <?= e(!empty($item['Date_reported']) ? $item['Date_reported'] : 'Not specified') ?>
                            </td>

                            <!-- STATUS -->

                            <td data-label="Status">
                                <span class="item-status <?= $statusClass ?>">
                                    <?= e($statusLabel) ?>
                                </span>
                            </td>

                            <!-- ACTION -->

                            <td data-label="Action">

                                <div class="actions">

                                    <button type="button" class="item-action-button view admin-view-details"
                                        data-photo="<?= e($photoPath) ?>"
                                        data-name="<?= e($item['item_name']) ?>"
                                        data-type="<?= e($type) ?>"
                                        data-description="<?= e($item['Description']) ?>"
                                        data-location="<?= e($item['location']) ?>"
                                        data-date="<?= e($item['Date_reported']) ?>"
                                        data-status="<?= e($status) ?>">View Details</button>

                                    <?php if ($type === 'Found' && $isAvailable): ?>

                                        <button
                                            type="button"
                                            class="item-action-button mark-returned"
                                            onclick="updateItemStatus(<?= (int) $item['item_id'] ?>, 'Claimed / Returned')"
                                        >Mark Returned</button>

                                    <?php elseif ($type === 'Found' && $isReturned): ?>

                                        <button
                                            type="button"
                                            class="item-action-button mark-available"
                                            onclick="updateItemStatus(<?= (int) $item['item_id'] ?>, 'Available')"
                                        >Mark Available</button>

                                    <?php else: ?>

                                        <span class="no-action">No action</span>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <p class="empty-message" id="noMatches">No items match your search.</p>

        <?php endif; ?>

    </section>

    <!-- BOTTOM NAVIGATION -->

    <div class="bottom-actions">
        <a href="admin-dashboard.php">Return to Dashboard</a>
        <a class="secondary" href="admin-claims.php">Back to Claims</a>
    </div>

</main>

<!-- ITEM VIEWER -->

<div id="adminItemViewer" class="admin-item-viewer" aria-hidden="true">
    <div class="admin-item-viewer-panel" role="dialog" aria-modal="true" aria-labelledby="adminViewerTitle">

        <button type="button" id="closeAdminViewer" aria-label="Close">&times;</button>

        <img id="adminViewerPhoto" alt="Full-size item">

        <div class="viewer-body">
            <h2 id="adminViewerTitle"></h2>
            <p class="viewer-desc" id="adminViewerDescription"></p>

            <dl class="viewer-grid">
                <div><dt>Type</dt><dd id="adminViewerType"></dd></div>
                <div><dt>Status</dt><dd id="adminViewerStatus"></dd></div>
                <div><dt>Location</dt><dd id="adminViewerLocation"></dd></div>
                <div><dt>Date</dt><dd id="adminViewerDate"></dd></div>
            </dl>
        </div>

    </div>
</div>

<script>
/* ---------- Item viewer ---------- */
const adminViewer = document.getElementById('adminItemViewer');

function setText(id, value) {
    document.getElementById(id).textContent = value && value.trim() ? value : 'Not specified';
}

document.querySelectorAll('.admin-item-photo, .admin-view-details').forEach(function (el) {
    el.addEventListener('click', function () {
        const photo   = el.dataset.photo || '';
        const photoEl = document.getElementById('adminViewerPhoto');

        if (photo) {
            photoEl.src = photo;
            photoEl.style.display = 'block';
        } else {
            photoEl.removeAttribute('src');
            photoEl.style.display = 'none';
        }

        document.getElementById('adminViewerTitle').textContent = el.dataset.name || '';
        setText('adminViewerDescription', el.dataset.description || '');
        setText('adminViewerType', el.dataset.type || '');
        setText('adminViewerStatus', el.dataset.status || '');
        setText('adminViewerLocation', el.dataset.location || '');
        setText('adminViewerDate', el.dataset.date || '');

        adminViewer.classList.add('open');
        adminViewer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        document.getElementById('closeAdminViewer').focus();
    });
});

function closeAdminViewer() {
    adminViewer.classList.remove('open');
    adminViewer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

document.getElementById('closeAdminViewer').addEventListener('click', closeAdminViewer);

adminViewer.addEventListener('click', function (e) {
    if (e.target === adminViewer) {
        closeAdminViewer();
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeAdminViewer();
    }
});

/* ---------- Search and type filter ---------- */
(function () {
    const search = document.getElementById('itemSearch');
    const type   = document.getElementById('typeFilter');
    const table  = document.getElementById('itemsTable');

    if (!search || !type || !table) {
        return;
    }

    const rows      = table.querySelectorAll('tbody tr');
    const noMatches = document.getElementById('noMatches');
    const counter   = document.getElementById('itemCount');

    function applyFilters() {
        const q = search.value.trim().toLowerCase();
        const t = type.value;
        let visible = 0;

        rows.forEach(function (row) {
            const matchesText = q === '' || row.dataset.search.indexOf(q) !== -1;
            const matchesType = t === '' || row.dataset.type === t;
            const show = matchesText && matchesType;

            row.style.display = show ? '' : 'none';

            if (show) {
                visible++;
            }
        });

        noMatches.style.display = visible === 0 ? 'block' : 'none';

        if (counter) {
            counter.textContent = visible;
        }
    }

    search.addEventListener('input', applyFilters);
    type.addEventListener('change', applyFilters);
})();

/* ---------- Update item status ---------- */
async function updateItemStatus(itemId, status) {

    const confirmation = confirm(
        "Are you sure you want to change this item's status to " +
        status +
        "?"
    );

    if (!confirmation) {
        return;
    }

    const formData = new FormData();
    formData.append("item_id", itemId);
    formData.append("status", status);

    try {
        const response = await fetch("api/items/update-status.php", {
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
            alert("Update failed:\n\n" + (data.message || "Unknown error."));
            return;
        }

        alert(data.message || "Item status updated successfully.");
        window.location.reload();

    } catch (error) {
        console.error("FETCH ERROR:", error);
        alert("An error occurred:\n\n" + error.message);
    }
}
</script>

</body>
</html>