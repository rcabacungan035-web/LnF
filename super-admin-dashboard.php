<?php
session_start();
require __DIR__ . '/db.php'; // must define $pdo (PDO instance)

if (empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Verify the role against the database on every request
$me = $pdo->prepare('SELECT admin_id, Name, role, is_active FROM admin WHERE admin_id = ?');
$me->execute([$_SESSION['admin_id']]);
$current = $me->fetch(PDO::FETCH_ASSOC);

if (!$current || !(int) $current['is_active'] || $current['role'] !== 'super_admin') {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

// Safe counter: returns 0 if the table can't be read
function countRows(PDO $pdo, string $table): int
{
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    } catch (Throwable $ex) {
        return 0;
    }
}

$stats = [
    ['Items',          countRows($pdo, 'item'),    'admin-items.php'],
    ['Claims',         countRows($pdo, 'claim'),   'admin-claims.php'],
    ['Students',       countRows($pdo, 'student'), null],
    ['Admin accounts', countRows($pdo, 'admin'),   'super-admin.php'],
];

$activeAdmins = (int) $pdo->query("SELECT COUNT(*) FROM admin WHERE is_active = 1")->fetchColumn();
$superAdmins  = (int) $pdo->query("SELECT COUNT(*) FROM admin WHERE role = 'super_admin'")->fetchColumn();
$adminName    = $_SESSION['admin_name'] ?? $current['Name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - MU Lost &amp; Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            font-family: "Poppins", sans-serif;
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
            flex-wrap: wrap;
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

        .nav-brand img { width: 34px; height: 34px; object-fit: contain; }

        .nav-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        .user-name { font-size: 13px; font-weight: 500; margin-right: 6px; }

        .nav-link {
            padding: 9px 16px;
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }

        .nav-link:hover { background: #ffffff; color: var(--navy); }

        /* ---------- PAGE ---------- */
        .container {
            max-width: 1100px;
            margin: 32px auto 48px;
            padding: 0 20px;
            display: grid;
            gap: 22px;
        }

        .page-banner {
            position: relative;
            overflow: hidden;
            padding: 30px 32px;
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
            background: rgba(245, 158, 11, 0.22);
            border: 1px solid rgba(245, 158, 11, 0.55);
            color: #fde68a;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .page-banner h1 { margin: 0 0 6px; font-size: 26px; }

        .page-banner p { margin: 0; color: #dbe7ff; font-size: 14px; line-height: 1.6; }

        /* ---------- STATS ---------- */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .stat {
            display: block;
            padding: 22px 24px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-left: 4px solid var(--navy);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            color: inherit;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        a.stat:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }

        .stat .num { font-size: 32px; font-weight: 700; color: var(--navy); line-height: 1.1; }
        .stat .lbl { margin-top: 6px; color: var(--muted); font-size: 13px; }

        .stat:nth-child(2) { border-left-color: var(--blue); }
        .stat:nth-child(3) { border-left-color: #0ea5e9; }
        .stat:nth-child(4) { border-left-color: var(--gold); }

        /* ---------- CARDS ---------- */
        .card {
            padding: 28px 30px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .card h2 { margin: 0 0 4px; color: var(--navy); font-size: 20px; }
        .card-sub { margin: 0 0 22px; color: var(--muted); font-size: 13px; }

        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
        }

        .action {
            display: block;
            padding: 16px 18px;
            background: var(--blue-soft);
            border: 1px solid var(--blue-line);
            border-radius: 12px;
            color: var(--navy);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, transform 0.15s, border-color 0.2s;
        }

        .action small {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 400;
        }

        .action:hover { background: #e0ecff; border-color: #bfdbfe; transform: translateY(-2px); }

        .action.primary {
            background: linear-gradient(135deg, var(--navy), var(--blue));
            border-color: transparent;
            color: #ffffff;
            box-shadow: 0 6px 14px rgba(30, 58, 138, 0.25);
        }

        .action.primary small { color: #dbe7ff; }
        .action.primary:hover { box-shadow: 0 8px 18px rgba(30, 58, 138, 0.32); }

        .summary { display: flex; gap: 40px; flex-wrap: wrap; }
        .summary .k { color: var(--muted); font-size: 12px; }
        .summary .v { color: var(--navy); font-size: 22px; font-weight: 700; }

        /* ---------- MOBILE ---------- */
        @media (max-width: 900px) {
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 650px) {
            .navbar { padding: 12px 4%; }
            .nav-brand { font-size: 16px; }
            .user-name { display: none; }
            .container { margin-top: 20px; padding: 0 14px; }
            .page-banner { padding: 22px 20px; }
            .page-banner h1 { font-size: 22px; }
            .card { padding: 22px 18px; }
            .stats { grid-template-columns: 1fr; }
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
        <span class="user-name"><?= e((string) $adminName) ?></span>
        <a class="nav-link" href="super-admin.php">Manage Accounts</a>
        <a class="nav-link" href="admin-items.php">Items</a>
        <a class="nav-link" href="admin-claims.php">Claims</a>
        <a class="nav-link" href="admin-post-item.php">Post Item</a>
        <a class="nav-link" href="super-admin-logout.php">Logout</a>
    </div>
</nav>

<main class="container">

    <section class="page-banner">
        <span class="banner-tag">Super Admin</span>
        <h1>Welcome, <?= e((string) $current['Name']) ?></h1>
        <p>You have full control over accounts, items and claims in the MU Lost &amp; Found system.</p>
    </section>

    <section class="stats" aria-label="System statistics">
        <?php foreach ($stats as [$label, $count, $link]): ?>
            <?php if ($link): ?>
                <a class="stat" href="<?= e($link) ?>">
            <?php else: ?>
                <div class="stat">
            <?php endif; ?>
                <div class="num"><?= (int) $count ?></div>
                <div class="lbl"><?= e($label) ?></div>
            <?= $link ? '</a>' : '</div>' ?>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <h2>Quick Actions</h2>
        <p class="card-sub">Manage the system from one place.</p>
        <div class="actions">
            <a class="action primary" href="super-admin.php">Manage admin accounts
                <small>Add, disable or remove administrators</small></a>
            <a class="action" href="admin-post-item.php">Post a lost / found item
                <small>Notifies all registered users by email</small></a>
            <a class="action" href="admin-items.php">Manage items
                <small>Review and update item status</small></a>
            <a class="action" href="admin-claims.php">Review claims
                <small>Approve or reject student claims</small></a>
            <a class="action" href="admin-dashboard.php">Regular admin dashboard
                <small>View the standard admin overview</small></a>
        </div>
    </section>

    <section class="card">
        <h2>Account Summary</h2>
        <p class="card-sub">Logged in as <?= e((string) $adminName) ?>.</p>
        <div class="summary">
            <div><div class="k">Active admin accounts</div><div class="v"><?= $activeAdmins ?></div></div>
            <div><div class="k">Super admins</div><div class="v"><?= $superAdmins ?></div></div>
        </div>
    </section>

</main>

</body>
</html>