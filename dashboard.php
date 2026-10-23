<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

require_once "api/config/database.php";

$user = $_SESSION['user'];

$firstName      = htmlspecialchars($user['first_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$lastName       = htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES, 'UTF-8');
$email          = htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8');
$profilePicture = htmlspecialchars($user['profile_picture'] ?? '', ENT_QUOTES, 'UTF-8');
$initial        = strtoupper(mb_substr((string) ($user['first_name'] ?? 'U'), 0, 1));
$studentId      = (int) ($user['student_id'] ?? 0);

/* Small helper */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* Status badge colour */
function statusClass(string $status): string
{
    $s = strtolower(trim($status));
    if (in_array($s, ['available', 'found', 'returned', 'resolved'], true)) return 'st-green';
    if (in_array($s, ['lost', 'missing'], true))                            return 'st-amber';
    if (in_array($s, ['claimed', 'pending', 'under review'], true))        return 'st-blue';
    return 'st-gray';
}

/* STATS */
$totalItems = $lostItems = $foundItems = $myItems = 0;

try {
    $row = $pdo->query("
        SELECT
            COUNT(*)                       AS total,
            SUM(Item_type = 'Lost')        AS lost,
            SUM(Item_type = 'Found')       AS found
        FROM item
    ")->fetch(PDO::FETCH_ASSOC);

    $totalItems = (int) ($row['total'] ?? 0);
    $lostItems  = (int) ($row['lost'] ?? 0);
    $foundItems = (int) ($row['found'] ?? 0);

    if ($studentId > 0) {
        $mine = $pdo->prepare("SELECT COUNT(*) FROM item WHERE student_id = ?");
        $mine->execute([$studentId]);
        $myItems = (int) $mine->fetchColumn();
    }
} catch (Throwable $ex) {
    error_log('Dashboard stats error: ' . $ex->getMessage());
}

/* GET RECENT ITEMS */
$stmt = $pdo->query("
    SELECT
        item_id,
        item_name,
        Description,
        location,
        Date_reported,
        Photo,
        Item_type,
        status
    FROM item
    ORDER BY item_id DESC
    LIMIT 6
");

$recentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Greeting by Philippine time */
$hour = (int) (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MU Dashboard</title>

    <link rel="stylesheet" href="style.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --navy:        #1e3a8a;
            --navy-dark:   #0b2a6b;
            --blue:        #1d4ed8;
            --blue-soft:   #eff6ff;
            --blue-line:   #dbeafe;
            --bg:          #f5f7fb;
            --card:        #ffffff;
            --border:      #e5e7eb;
            --text:        #1f2937;
            --muted:       #6b7280;
            --faint:       #9ca3af;
            --gold:        #f59e0b;
            --radius:      14px;
            --shadow:      0 2px 10px rgba(15, 23, 42, 0.05);
            --shadow-lg:   0 10px 28px rgba(30, 58, 138, 0.14);
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body.dashboard-body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        /* ---------- TOP NAVBAR ---------- */
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
            letter-spacing: 0.2px;
        }

        .nav-brand img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .nav-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }

        .avatar,
        .nav-right img.avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.85);
        }

        .avatar-initial {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #ffffff;
            color: var(--navy);
            font-weight: 700;
            font-size: 15px;
        }

        .user-name {
            font-size: 13px;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .logout-btn {
            padding: 9px 18px;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }

        .logout-btn:hover {
            background: #ffffff;
            color: var(--navy);
        }

        /* ---------- LAYOUT ---------- */
        .dashboard-layout {
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
            gap: 26px;
            max-width: 1600px;
            margin: 0 auto;
            padding: 28px 5%;
            align-items: start;
        }

        /* ---------- SIDEBAR ---------- */
        .dashboard-sidebar {
            position: sticky;
            top: 82px;
            padding: 22px 14px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .sidebar-brand {
            margin: 0 0 6px;
            padding: 0 10px;
            color: var(--navy);
            font-size: 18px;
            font-weight: 700;
        }

        .sidebar-heading {
            margin: 22px 10px 10px;
            color: var(--faint);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 11px;
            width: 100%;
            padding: 11px 12px;
            border-radius: 10px;
            color: #4b5563;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, transform 0.15s;
        }

        .sidebar-menu a:hover {
            background: var(--blue-soft);
            color: var(--navy);
            transform: translateX(2px);
        }

        .sidebar-menu a.active {
            background: linear-gradient(135deg, var(--navy), var(--blue));
            color: #ffffff;
            box-shadow: 0 6px 14px rgba(30, 58, 138, 0.25);
        }

        .sidebar-divider {
            margin: 20px 0;
            border: none;
            border-top: 1px solid var(--border);
        }

        /* ---------- MAIN ---------- */
        .dashboard-main {
            min-width: 0;
            display: grid;
            gap: 24px;
        }

        /* Hero */
        .hero {
            position: relative;
            overflow: hidden;
            padding: 32px;
            color: #ffffff;
            border-radius: var(--radius);
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy) 55%, var(--blue) 100%);
            box-shadow: var(--shadow-lg);
        }

        .hero::before,
        .hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
        }

        .hero::before { width: 280px; height: 280px; top: -110px; right: -70px; }
        .hero::after  { width: 180px; height: 180px; bottom: -80px; right: 190px; }

        .hero > * { position: relative; z-index: 1; }

        .hero-tag {
            display: inline-block;
            margin-bottom: 12px;
            padding: 4px 12px;
            background: rgba(255, 255, 255, 0.16);
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .hero h1 {
            margin: 0 0 8px;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.25;
        }

        .hero p {
            max-width: 520px;
            margin: 0 0 22px;
            color: #dbe7ff;
            font-size: 14px;
            line-height: 1.6;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.15s, box-shadow 0.2s, background 0.2s;
        }

        .btn:hover { transform: translateY(-2px); }

        .btn-light {
            background: #ffffff;
            color: var(--navy);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.18);
        }

        .btn-gold {
            background: var(--gold);
            color: #3b2a02;
            box-shadow: 0 6px 14px rgba(245, 158, 11, 0.35);
        }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .stat:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .stat { border-left: 4px solid var(--navy); }
        .stat:nth-child(2) { border-left-color: var(--gold); }
        .stat:nth-child(3) { border-left-color: #16a34a; }
        .stat:nth-child(4) { border-left-color: var(--blue); }

        .stat-num {
            display: block;
            color: var(--navy);
            font-size: 24px;
            font-weight: 700;
            line-height: 1.1;
        }

        .stat-label {
            display: block;
            margin-top: 2px;
            color: var(--muted);
            font-size: 12px;
        }

        /* Section cards */
        .recent-section {
            padding: 26px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            scroll-margin-top: 90px;
        }

        .section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 14px;
        }

        .recent-section h2 {
            margin: 0 0 4px;
            color: var(--navy);
            font-size: 20px;
        }

        .section-description {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .view-all {
            color: var(--blue);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .view-all:hover { text-decoration: underline; }

        /* Filter bar */
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 20px;
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chip {
            padding: 7px 16px;
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid transparent;
            border-radius: 999px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }

        .chip:hover { background: var(--blue-line); color: var(--navy); }

        .chip.active {
            background: var(--navy);
            color: #ffffff;
        }

        .search-box {
            width: 100%;
            max-width: 260px;
            padding: 9px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
            font-family: inherit;
            font-size: 13px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--blue);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
        }

        /* Items grid */
        .featured-items-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-top: 22px;
        }

        .featured-item-card {
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;
        }

        .featured-item-card:hover {
            border-color: var(--blue-line);
            box-shadow: var(--shadow-lg);
            transform: translateY(-3px);
        }

        .card-photo {
            position: relative;
        }

        .featured-item-photo,
        .featured-item-no-photo {
            display: block;
            width: 100%;
            height: 170px;
        }

        .featured-item-photo {
            object-fit: cover;
            background: #f3f4f6;
        }

        .featured-item-no-photo {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            color: var(--faint);
            font-size: 12px;
        }

        .item-type-label {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .type-lost  { background: #fef3c7; color: #92400e; }
        .type-found { background: #dcfce7; color: #166534; }
        .type-other { background: var(--blue-line); color: #1e40af; }

        .card-body {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 16px;
        }

        .featured-item-card h3 {
            margin: 0 0 10px;
            color: var(--text);
            font-size: 16px;
            overflow-wrap: anywhere;
        }

        .item-info {
            display: flex;
            gap: 6px;
            margin: 5px 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .item-info strong { color: #374151; }

        .status-pill {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 11px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            align-self: flex-start;
        }

        .st-green { background: #dcfce7; color: #166534; }
        .st-amber { background: #fef3c7; color: #92400e; }
        .st-blue  { background: var(--blue-line); color: #1e40af; }
        .st-gray  { background: #f3f4f6; color: #4b5563; }

        .view-item-fullscreen {
            width: 100%;
            margin-top: auto;
            padding: 11px;
            border: none;
            border-radius: 9px;
            background: var(--navy);
            color: #ffffff;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .card-body .status-pill + .view-item-fullscreen { margin-top: 14px; }

        .view-item-fullscreen:hover { background: var(--blue); }

        .empty-message,
        .no-results {
            margin-top: 20px;
            padding: 30px 20px;
            background: #f9fafb;
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            color: var(--muted);
            text-align: center;
            font-size: 13px;
            line-height: 1.7;
        }

        .no-results { display: none; }

        /* Account */
        .account-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: center;
            gap: 18px;
            margin-top: 18px;
        }

        .account-avatar img,
        .account-avatar .avatar-initial {
            width: 64px;
            height: 64px;
            font-size: 24px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--blue-line);
        }

        .account-avatar .avatar-initial { background: var(--blue-soft); }

        .account-detail {
            margin: 4px 0;
            color: var(--muted);
            font-size: 13px;
            overflow-wrap: anywhere;
        }

        .account-detail strong {
            display: inline-block;
            min-width: 62px;
            color: #374151;
        }

        /* ---------- FULLSCREEN ITEM VIEWER ---------- */
        .item-viewer {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 22px;
            overflow-y: auto;
            background: rgba(6, 21, 46, 0.82);
            backdrop-filter: blur(3px);
        }

        .item-viewer.open { display: flex; }

        .item-viewer-panel {
            position: relative;
            width: min(780px, 100%);
            max-height: 95vh;
            overflow-y: auto;
            padding: 28px;
            background: #ffffff;
            color: var(--text);
            border-radius: 18px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
        }

        .viewer-close {
            position: sticky;
            top: 0;
            float: right;
            padding: 9px 18px;
            border: none;
            border-radius: 999px;
            background: var(--navy);
            color: #ffffff;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .viewer-close:hover { background: var(--blue); }

        #viewerPhoto {
            display: none;
            clear: both;
            width: 100%;
            max-height: 55vh;
            margin: 15px 0;
            object-fit: contain;
            background: #f3f4f6;
            border-radius: 10px;
        }

        #viewerTitle {
            clear: both;
            margin: 0 0 10px;
            padding-top: 10px;
            color: var(--navy);
            overflow-wrap: anywhere;
        }

        .viewer-detail {
            margin: 8px 0;
            padding: 10px 14px;
            background: #f9fafb;
            border-left: 4px solid var(--blue-line);
            border-radius: 6px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        /* ---------- TABLET ---------- */
        @media (max-width: 1100px) {
            .featured-items-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 900px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
                gap: 18px;
                padding: 20px 4%;
            }

            .dashboard-sidebar {
                position: static;
                padding: 16px 12px;
            }

            .sidebar-menu {
                flex-direction: row;
                gap: 8px;
                overflow-x: auto;
                padding-bottom: 4px;
            }

            .sidebar-menu a {
                width: auto;
                white-space: nowrap;
                padding: 10px 14px;
                background: #f9fafb;
            }

            .sidebar-menu a:hover { transform: none; }
        }

        /* ---------- MOBILE ---------- */
        @media (max-width: 650px) {
            .navbar { padding: 12px 4%; flex-wrap: wrap; }
            .nav-brand { font-size: 16px; }
            .nav-right { gap: 8px; flex-wrap: wrap; }
            .user-name { display: none; }
            .logout-btn { padding: 8px 13px; font-size: 12px; }

            .hero { padding: 24px 20px; }
            .hero h1 { font-size: 22px; }
            .btn { padding: 10px 16px; }

            .stats { gap: 12px; }
            .stat { padding: 14px; gap: 10px; }
            .stat-num { font-size: 20px; }

            .recent-section { padding: 18px; }
            .featured-items-grid { grid-template-columns: 1fr; }
            .search-box { max-width: none; }

            .item-viewer { padding: 10px; }
            .item-viewer-panel { padding: 17px; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            * { transition: none !important; }
        }
    </style>
</head>

<body class="dashboard-body">

<!-- TOP NAVBAR -->
<nav class="navbar">

    <div class="nav-brand">
        <img src="mu_logo.png" alt="" onerror="this.style.display='none'">
        MU Lost &amp; Found
    </div>

    <div class="nav-right">

        <?php if (!empty($profilePicture)): ?>
            <img
                src="<?php echo $profilePicture; ?>"
                alt="Profile"
                class="avatar"
                referrerpolicy="no-referrer"
            >
        <?php else: ?>
            <span class="avatar-initial"><?php echo e($initial); ?></span>
        <?php endif; ?>

        <span class="user-name">
            <?php echo $firstName . " " . $lastName; ?>
        </span>

        <button
            type="button"
            id="logoutBtn"
            class="logout-btn"
        >
            Logout
        </button>

    </div>

</nav>

<!-- SIDEBAR AND DASHBOARD CONTENT -->
<div class="dashboard-layout">

    <!-- LEFT SIDEBAR -->
    <aside class="dashboard-sidebar">

        <h2 class="sidebar-brand">MU Menu</h2>

        <p class="sidebar-heading">Main Menu</p>

        <nav class="sidebar-menu" aria-label="Main menu">

            <a href="dashboard.php" class="active">
                Dashboard
            </a>

            <a href="found-item.php">
                Found Item
            </a>

            <a href="lost-item.php">
                Lost Item
            </a>

            <a href="found-items.php">
                Browse Found Items
            </a>

            <a href="claims.php">
                Claims
            </a>

            <a href="notifications.php">
                Notifications
            </a>

        </nav>

        <hr class="sidebar-divider">

        <p class="sidebar-heading">My Account</p>

        <nav class="sidebar-menu" aria-label="Account menu">

            <a href="#account-information">
                Account Information
            </a>

        </nav>

    </aside>

    <!-- RIGHT MAIN CONTENT -->
    <main class="dashboard-main">

        <!-- HERO -->
        <section class="hero">

            <span class="hero-tag">Student Dashboard</span>

            <h1><?php echo e($greeting); ?>, <?php echo $firstName; ?>!</h1>

            <p>
                Lost something on campus, or found something that belongs to someone else?
                Manage your lost and found items here.
            </p>

            <div class="hero-actions">
                <a href="lost-item.php" class="btn btn-gold">Report Lost Item</a>
                <a href="found-item.php" class="btn btn-light">Report Found Item</a>
            </div>

        </section>

        <!-- STATS -->
        <section class="stats" aria-label="Summary">

            <div class="stat">
                <div>
                    <span class="stat-num"><?php echo $totalItems; ?></span>
                    <span class="stat-label">Total items</span>
                </div>
            </div>

            <div class="stat">
                <div>
                    <span class="stat-num"><?php echo $lostItems; ?></span>
                    <span class="stat-label">Lost items</span>
                </div>
            </div>

            <div class="stat">
                <div>
                    <span class="stat-num"><?php echo $foundItems; ?></span>
                    <span class="stat-label">Found items</span>
                </div>
            </div>

            <div class="stat">
                <div>
                    <span class="stat-num"><?php echo $myItems; ?></span>
                    <span class="stat-label">My reports</span>
                </div>
            </div>

        </section>

        <!-- FEATURED ITEMS -->
        <section class="recent-section" id="featured-items">

            <div class="section-head">
                <div>
                    <h2>Featured Items</h2>
                    <p class="section-description">
                        Recently posted lost and found items.
                        Select an item to view its complete details.
                    </p>
                </div>

                <a href="found-items.php" class="view-all">Browse all found items</a>
            </div>

            <?php if (empty($recentItems)): ?>

                <p class="empty-message">
                                        No items have been posted yet.
                    Recent lost and found items will appear here
                    once the system is being used.
                </p>

            <?php else: ?>

                <div class="toolbar">

                    <div class="chips" role="group" aria-label="Filter items">
                        <button type="button" class="chip active" data-filter="all">All</button>
                        <button type="button" class="chip" data-filter="lost">Lost</button>
                        <button type="button" class="chip" data-filter="found">Found</button>
                    </div>

                    <input
                        type="search"
                        id="itemSearch"
                        class="search-box"
                        placeholder="Search items..."
                        aria-label="Search items"
                    >

                </div>

                <div class="featured-items-grid" id="itemsGrid">

                    <?php foreach ($recentItems as $item): ?>

                        <?php
                        $itemName        = $item['item_name'] ?? '';
                        $itemType        = $item['Item_type'] ?? '';
                        $itemLocation    = $item['location'] ?? 'Not specified';
                        $itemDateRaw     = $item['Date_reported'] ?? '';
                        $itemStatus      = $item['status'] ?? 'Not specified';
                        $itemDescription = $item['Description'] ?? '';

                        $ts       = $itemDateRaw !== '' ? strtotime($itemDateRaw) : false;
                        $itemDate = $ts ? date('M j, Y', $ts) : ($itemDateRaw !== '' ? $itemDateRaw : 'Not specified');

                        $itemPhoto = !empty($item['Photo'])
                            ? 'uploads/' . basename($item['Photo'])
                            : '';

                        $typeKey   = strtolower(trim($itemType));
                        $typeClass = $typeKey === 'lost' ? 'type-lost' : ($typeKey === 'found' ? 'type-found' : 'type-other');
                        $searchKey = strtolower($itemName . ' ' . $itemLocation . ' ' . $itemType . ' ' . $itemDescription);
                        ?>

                        <article
                            class="featured-item-card"
                            data-type="<?php echo e($typeKey); ?>"
                            data-search="<?php echo e($searchKey); ?>"
                        >

                            <div class="card-photo">

                                <?php if ($itemPhoto !== ''): ?>

                                    <img
                                        src="<?php echo e($itemPhoto); ?>"
                                        alt="<?php echo e($itemName); ?>"
                                        class="featured-item-photo"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <div class="featured-item-no-photo">
                                                                                No Photo Available
                                    </div>

                                <?php endif; ?>

                                <span class="item-type-label <?php echo $typeClass; ?>">
                                    <?php echo e($itemType); ?>
                                </span>

                            </div>

                            <div class="card-body">

                                <h3><?php echo e($itemName); ?></h3>

                                <p class="item-info">
                                    <strong>Location:</strong>
                                    <span><?php echo e($itemLocation); ?></span>
                                </p>

                                <p class="item-info">
                                    <strong>Date:</strong>
                                    <span><?php echo e($itemDate); ?></span>
                                </p>

                                <span class="status-pill <?php echo statusClass((string) $itemStatus); ?>">
                                    <?php echo e($itemStatus); ?>
                                </span>

                                <button
                                    type="button"
                                    class="view-item-fullscreen"
                                    data-name="<?php echo e($itemName); ?>"
                                    data-type="<?php echo e($itemType); ?>"
                                    data-location="<?php echo e($itemLocation); ?>"
                                    data-date="<?php echo e($itemDate); ?>"
                                    data-status="<?php echo e($itemStatus); ?>"
                                    data-description="<?php echo e($itemDescription); ?>"
                                    data-photo="<?php echo e($itemPhoto); ?>"
                                >
                                    View Item
                                </button>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <p class="no-results" id="noResults">
                                        No items match your search.
                </p>

            <?php endif; ?>

        </section>

        <!-- ACCOUNT INFORMATION -->
        <section
            class="recent-section"
            id="account-information"
        >

            <h2>Account Information</h2>

            <div class="account-grid">

                <div class="account-avatar">
                    <?php if (!empty($profilePicture)): ?>
                        <img src="<?php echo $profilePicture; ?>" alt="Profile" referrerpolicy="no-referrer">
                    <?php else: ?>
                        <span class="avatar-initial"><?php echo e($initial); ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <p class="account-detail">
                        <strong>Name:</strong>
                        <?php echo $firstName . " " . $lastName; ?>
                    </p>

                    <p class="account-detail">
                        <strong>Email:</strong>
                        <?php echo $email; ?>
                    </p>
                </div>

            </div>

        </section>

    </main>

</div>

<!-- FULLSCREEN ITEM VIEWER -->
<div
    id="itemViewer"
    class="item-viewer"
    aria-hidden="true"
>
    <div
        class="item-viewer-panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="viewerTitle"
    >

        <button
            type="button"
            id="closeItemViewer"
            class="viewer-close"
            aria-label="Close item details"
        >Close</button>

        <img id="viewerPhoto" alt="Item photo">

        <h2 id="viewerTitle"></h2>

        <p id="viewerType" class="viewer-detail"></p>

        <p id="viewerDescription" class="viewer-detail"></p>

        <p id="viewerLocation" class="viewer-detail"></p>

        <p id="viewerDate" class="viewer-detail"></p>

        <p id="viewerStatus" class="viewer-detail"></p>

    </div>
</div>

<script>
/* FULLSCREEN ITEM VIEWER */
const itemViewer = document.getElementById('itemViewer');

const viewerTitle = document.getElementById('viewerTitle');
const viewerType = document.getElementById('viewerType');
const viewerDescription = document.getElementById('viewerDescription');
const viewerLocation = document.getElementById('viewerLocation');
const viewerDate = document.getElementById('viewerDate');
const viewerStatus = document.getElementById('viewerStatus');
const viewerPhoto = document.getElementById('viewerPhoto');

document.querySelectorAll('.view-item-fullscreen').forEach(button => {

    button.addEventListener('click', () => {

        viewerTitle.textContent = button.dataset.name || 'Item Details';

        viewerType.textContent =
            'Type: ' + (button.dataset.type || 'Not specified');

        viewerDescription.textContent =
            'Description: ' +
            (button.dataset.description || 'No description available.');

        viewerLocation.textContent =
            'Location: ' + (button.dataset.location || 'Not specified');

        viewerDate.textContent =
            'Date reported: ' + (button.dataset.date || 'Not specified');

        viewerStatus.textContent =
            'Status: ' + (button.dataset.status || 'Not specified');

        if (button.dataset.photo) {
            viewerPhoto.src = button.dataset.photo;
            viewerPhoto.style.display = 'block';
        } else {
            viewerPhoto.removeAttribute('src');
            viewerPhoto.style.display = 'none';
        }

        itemViewer.classList.add('open');
        itemViewer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        document.getElementById('closeItemViewer').focus();
    });

});

/* CLOSE ITEM VIEWER */
function closeViewer() {
    itemViewer.classList.remove('open');
    itemViewer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

document.getElementById('closeItemViewer')
    .addEventListener('click', closeViewer);

itemViewer.addEventListener('click', event => {
    if (event.target === itemViewer) {
        closeViewer();
    }
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && itemViewer.classList.contains('open')) {
        closeViewer();
    }
});

/* FILTER + SEARCH */
(function () {
    const cards     = Array.from(document.querySelectorAll('#itemsGrid .featured-item-card'));
    const chips     = Array.from(document.querySelectorAll('.chip'));
    const searchBox = document.getElementById('itemSearch');
    const noResults = document.getElementById('noResults');

    if (!cards.length) return;

    let activeFilter = 'all';

    function applyFilters() {
        const term = (searchBox ? searchBox.value : '').trim().toLowerCase();
        let visible = 0;

        cards.forEach(card => {
            const typeOk   = activeFilter === 'all' || card.dataset.type === activeFilter;
            const searchOk = term === '' || (card.dataset.search || '').includes(term);
            const show     = typeOk && searchOk;

            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (noResults) {
            noResults.style.display = visible === 0 ? 'block' : 'none';
        }
    }

    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            chips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            activeFilter = chip.dataset.filter;
            applyFilters();
        });
    });

    if (searchBox) {
        searchBox.addEventListener('input', applyFilters);
    }
})();
</script>

<!-- EXISTING LOGOUT AND OTHER DASHBOARD FUNCTIONS -->
<script src="script.js"></script>

</body>
</html>
