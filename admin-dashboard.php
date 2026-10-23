<?php

session_start();

if (empty($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$adminName = $_SESSION['admin_name'] ?? 'System Administrator';
$isSuper   = ($_SESSION['admin_role'] ?? '') === 'super_admin';

/* Initial for the avatar circle */
$initial = strtoupper(mb_substr(trim($adminName), 0, 1, 'UTF-8')) ?: 'A';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | MU Lost &amp; Found</title>

    <link rel="stylesheet" href="style.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --ink:       #1e3a8a;
            --navy:      #1e3a8a;
            --blue:      #1d4ed8;
            --blue-soft: #e8efff;
            --amber:     #ffffff;
            --paper:     #f4f7fb;
            --card:      #ffffff;
            --line:      #e5e7eb;
            --text:      #1f2937;
            --muted:     #64748b;

            --lost:    #2563eb;
            --found:   #16a34a;
            --claims:  #d97706;
            --history: #7c3aed;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--paper);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; }

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
            color: #93b4f5;
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
            background: var(--amber);
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

        .ad-hero::before,
        .ad-hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
        }

        .ad-hero::before { width: 300px; height: 300px; top: -140px; right: -60px; }
        .ad-hero::after  { width: 200px; height: 200px; bottom: -120px; left: 30%; }

        .ad-hero-inner {
            position: relative;
            z-index: 1;
            width: 92%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 38px 0 84px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            flex-wrap: wrap;
        }

        .ad-hero h1 {
            margin: 0 0 6px;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.25;
        }

        .ad-hero p {
            margin: 0;
            color: #bfdbfe;
            font-size: 14px;
            max-width: 52ch;
            line-height: 1.6;
        }

        .ad-role {
            display: inline-block;
            margin-bottom: 12px;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
        }

        .ad-refresh-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .refresh-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: #fff;
            color: var(--navy);
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
        }

        .refresh-button:hover { background: var(--blue-soft); }
        .refresh-button:active { transform: scale(0.98); }
        .refresh-button:disabled { opacity: 0.65; cursor: wait; }
        .refresh-button svg { width: 16px; height: 16px; }
        .refresh-button:disabled svg { animation: adSpin 0.9s linear infinite; }

        @keyframes adSpin { to { transform: rotate(360deg); } }

        #statusMessage {
            margin: 0;
            font-size: 13px;
            color: #bfdbfe;
        }

        #statusMessage.message {
            color: #fecaca;
            background: rgba(185, 28, 28, 0.35);
            padding: 8px 12px;
            border-radius: 8px;
            max-width: 420px;
        }

        /* ---------- Content ---------- */
        .dashboard-container {
            width: 92%;
            max-width: 1200px;
            margin: -56px auto 48px;
            position: relative;
            z-index: 2;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .stat-card {
            position: relative;
            background: var(--card);
            border-radius: 16px;
            padding: 22px 22px 20px;
            box-shadow: 0 8px 24px rgba(30, 58, 138, 0.12);
            overflow: hidden;
        }

        .stat-card::before {
            content: "";
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 5px;
            background: var(--accent);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 14px;
            font-weight: 500;
            color: var(--muted);
        }

        .stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            background: color-mix(in srgb, var(--accent) 13%, white);
            color: var(--accent);
        }

        .stat-icon svg { width: 20px; height: 20px; }

        .stat-number {
            margin: 0;
            font-size: 36px;
            font-weight: 700;
            line-height: 1;
            color: var(--text);
            font-variant-numeric: tabular-nums;
        }

        .stat-note {
            margin: 8px 0 0;
            font-size: 12px;
            color: var(--muted);
        }

        .stat-card.lost    { --accent: var(--lost); }
        .stat-card.found   { --accent: var(--found); }
        .stat-card.claims  { --accent: var(--claims); }
        .stat-card.history { --accent: var(--history); }

        .stat-number.is-loading {
            color: transparent;
            width: 70px;
            border-radius: 8px;
            background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
            background-size: 200% 100%;
            animation: adShimmer 1.2s ease-in-out infinite;
        }

        @keyframes adShimmer {
            from { background-position: 200% 0; }
            to   { background-position: -200% 0; }
        }

        /* ---------- Management ---------- */
        .management-section { margin-top: 40px; }

        .management-section h2 {
            margin: 0 0 4px;
            font-size: 20px;
            font-weight: 700;
            color: var(--ink);
        }

        .section-sub {
            margin: 0 0 18px;
            font-size: 14px;
            color: var(--muted);
        }

        .management-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .management-card {
            display: flex;
            flex-direction: column;
            gap: 6px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 22px;
            text-decoration: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .management-card:hover {
            border-color: var(--blue);
            box-shadow: 0 8px 24px rgba(29, 78, 216, 0.12);
        }

        .mc-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            margin-bottom: 8px;
            background: var(--blue-soft);
            color: var(--blue);
        }

        .mc-icon svg { width: 22px; height: 22px; }

        .management-card h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--ink);
        }

        .management-card p {
            margin: 0;
            flex: 1;
            font-size: 13px;
            line-height: 1.65;
            color: var(--muted);
        }

        .action-link {
            margin-top: 12px;
            font-size: 14px;
            font-weight: 600;
            color: var(--blue);
        }

        .management-card.primary {
            background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
            border-color: transparent;
        }

        .management-card.primary h3 { color: #fff; }
        .management-card.primary p { color: #cfe0ff; }
        .management-card.primary .action-link { color: #ffffff; }
        .management-card.primary .mc-icon { background: rgba(255, 255, 255, 0.16); color: #fff; }

        .management-card.primary:hover {
            border-color: transparent;
            box-shadow: 0 10px 28px rgba(30, 58, 138, 0.4);
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 980px) {
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .management-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 760px) {
            .ad-topbar-inner {
                flex-wrap: wrap;
                gap: 6px 16px;
                padding: 10px 0;
            }
            .ad-nav { order: 3; flex-basis: 100%; }
            .ad-user { margin-left: auto; }
            .ad-user-name { display: none; }
        }

        @media (max-width: 560px) {
            .stats-grid,
            .management-grid { grid-template-columns: 1fr; }

            .ad-hero h1 { font-size: 23px; }
            .ad-hero-inner { padding: 28px 0 76px; }
            .stat-number { font-size: 32px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .stat-number.is-loading,
            .refresh-button:disabled svg { animation: none; }
            .ad-nav a, .management-card, .refresh-button { transition: none; }
        }
    </style>
</head>

<body>

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
            <a href="admin-dashboard.php" aria-current="page">Dashboard</a>
            <a href="admin-claims.php">Claims</a>
            <a href="admin-items.php">Items</a>
            <a href="admin-post-item.php">Post Lost/Found</a>
            <?php if ($isSuper): ?>
                <a href="super-admin-dashboard.php">Super Admin</a>
            <?php endif; ?>
        </nav>

        <div class="ad-user">
            <span class="ad-avatar" aria-hidden="true"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="ad-user-name"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></span>
            <a class="ad-logout" href="api/auth/admin-logout.php">Logout</a>
        </div>

    </div>
</header>

<section class="ad-hero">
    <div class="ad-hero-inner">

        <div>
            <?php if ($isSuper): ?>
                <span class="ad-role">Super Admin</span>
            <?php else: ?>
                <span class="ad-role">Admin</span>
            <?php endif; ?>

            <h1>Welcome back, <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Here is where lost items, found items, and student claims stand right now.</p>
        </div>

        <div class="ad-refresh-wrap">
            <p id="statusMessage" class="loading" role="status">Loading statistics...</p>

            <button class="refresh-button" id="refreshButton" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/>
                </svg>
                Refresh
            </button>
        </div>

    </div>
</section>

<main class="dashboard-container">

    <section class="stats-grid" aria-label="System statistics">

        <article class="stat-card lost">
            <div class="stat-top">
                <h3>Lost items</h3>
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M9 11h4"/></svg>
                </span>
            </div>
            <p class="stat-number is-loading" id="lostItems">0</p>
            <p class="stat-note">Reported by students</p>
        </article>

        <article class="stat-card found">
            <div class="stat-top">
                <h3>Found items</h3>
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5"/><path d="M12 13v8"/></svg>
                </span>
            </div>
            <p class="stat-number is-loading" id="foundItems">0</p>
            <p class="stat-note">Waiting for their owners</p>
        </article>

        <article class="stat-card claims">
            <div class="stat-top">
                <h3>Pending claims</h3>
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
            </div>
            <p class="stat-number is-loading" id="pendingClaims">0</p>
            <p class="stat-note">Need your review</p>
        </article>

        <article class="stat-card history">
            <div class="stat-top">
                <h3>History records</h3>
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/><path d="M12 8v4l3 2"/></svg>
                </span>
            </div>
            <p class="stat-number is-loading" id="historyRecords">0</p>
            <p class="stat-note">Past item activity</p>
        </article>

    </section>

    <section class="management-section">
        <h2>What do you want to do?</h2>
        <p class="section-sub">Jump straight to the task you need.</p>

        <div class="management-grid">

            <a class="management-card primary" href="admin-post-item.php">
                <span class="mc-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                </span>
                <h3>Post an item</h3>
                <p>Add a lost or found item with a photo so students can see it right away.</p>
                <span class="action-link">Post Lost/Found Item</span>
            </a>

            <a class="management-card" href="admin-items.php">
                <span class="mc-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="M3 10h18M9 4v16"/></svg>
                </span>
                <h3>Item management</h3>
                <p>Review reported lost and found items and update their availability status.</p>
                <span class="action-link">Manage Items</span>
            </a>

            <a class="management-card" href="admin-claims.php">
                <span class="mc-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6z"/></svg>
                </span>
                <h3>Claim management</h3>
                <p>Review student claim requests and approve or reject pending claims.</p>
                <span class="action-link">Manage Claims</span>
            </a>

        </div>
    </section>

</main>

<script>
const statusMessage = document.getElementById('statusMessage');

const statIds = ['lostItems', 'foundItems', 'pendingClaims', 'historyRecords'];

function setLoading(isLoading) {
    statIds.forEach(function (id) {
        const el = document.getElementById(id);
        if (isLoading) {
            el.classList.add('is-loading');
        } else {
            el.classList.remove('is-loading');
        }
    });
}

async function loadStatistics() {
    const refreshButton = document.getElementById('refreshButton');

    refreshButton.disabled = true;
    setLoading(true);
    statusMessage.textContent = 'Loading statistics...';
    statusMessage.className = 'loading';

    try {
        const response = await fetch(
            'api/admin/statistics.php',
            {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || 'Unable to load statistics.'
            );
        }

        document.getElementById('lostItems').textContent =
            data.statistics.lost_items;

        document.getElementById('foundItems').textContent =
            data.statistics.found_items;

        document.getElementById('pendingClaims').textContent =
            data.statistics.pending_claims;

        document.getElementById('historyRecords').textContent =
            data.statistics.history_records;

        setLoading(false);

        statusMessage.textContent =
            'Updated at ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        statusMessage.className = '';

    } catch (error) {
        statIds.forEach(function (id) {
            const el = document.getElementById(id);
            el.textContent = '\u2014';
        });
        setLoading(false);

        statusMessage.textContent =
            error.message + ' Please log in again if your admin session expired.';

        statusMessage.className = 'message';

    } finally {
        refreshButton.disabled = false;
    }
}

document.getElementById('refreshButton').addEventListener(
    'click',
    loadStatistics
);

loadStatistics();
</script>

</body>
</html>