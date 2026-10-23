<?php
session_start();
require __DIR__ . '/db.php'; // Must define $pdo as a PDO instance.

if (empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Verify the current account and role from the database.
$me = $pdo->prepare('SELECT admin_id, Name AS full_name, role, is_active FROM admin WHERE admin_id = ?');
$me->execute([$_SESSION['admin_id']]);
$currentAdmin = $me->fetch(PDO::FETCH_ASSOC);

if (!$currentAdmin || !(int) $currentAdmin['is_active'] || $currentAdmin['role'] !== 'super_admin') {
    http_response_code(403);
    exit('Access denied. Super admin only.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash(string $msg, string $type = 'success'): void
{
    $_SESSION['sa_message'] = $msg;
    $_SESSION['sa_type'] = $type;
    header('Location: super-admin.php');
    exit;
}

/* ---------------- Handle account actions ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(400);
        exit('Invalid CSRF token. Please refresh the page and try again.');
    }

    $action = (string) ($_POST['action'] ?? '');
    $targetId = (int) ($_POST['admin_id'] ?? 0);

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (($_POST['role'] ?? 'admin') === 'super_admin') ? 'super_admin' : 'admin';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Please provide a valid name and email.', 'error');
        }
        if (strlen($password) < 8) {
            flash('Password must be at least 8 characters.', 'error');
        }

        $dup = $pdo->prepare('SELECT 1 FROM admin WHERE Email = ? LIMIT 1');
        $dup->execute([$email]);
        if ($dup->fetchColumn()) {
            flash('That email is already used by another admin.', 'error');
        }

        $ins = $pdo->prepare('INSERT INTO admin (Name, Email, password, role, is_active) VALUES (?, ?, ?, ?, 1)');
        $ins->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        flash("Account '{$email}' created successfully.");
    }

    if ($targetId < 1 && in_array($action, ['toggle', 'reset_password', 'delete'], true)) {
        flash('Please select a valid admin account.', 'error');
    }

    if ($targetId === (int) $currentAdmin['admin_id'] && in_array($action, ['toggle', 'delete'], true)) {
        flash('You cannot deactivate or delete your own account.', 'error');
    }

    if ($action === 'toggle') {
        $stmt = $pdo->prepare('UPDATE admin SET is_active = 1 - is_active WHERE admin_id = ?');
        $stmt->execute([$targetId]);
        flash('Account status updated successfully.');
    }

    if ($action === 'reset_password') {
        $newPass = (string) ($_POST['new_password'] ?? '');
        if (strlen($newPass) < 8) {
            flash('New password must be at least 8 characters.', 'error');
        }
        $stmt = $pdo->prepare('UPDATE admin SET password = ? WHERE admin_id = ?');
        $stmt->execute([password_hash($newPass, PASSWORD_DEFAULT), $targetId]);
        flash('Password reset successfully.');
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM admin WHERE admin_id = ?');
        $stmt->execute([$targetId]);
        flash('Account deleted successfully.');
    }

    flash('Unknown action.', 'error');
}

$message = $_SESSION['sa_message'] ?? '';
$type = $_SESSION['sa_type'] ?? 'success';
unset($_SESSION['sa_message'], $_SESSION['sa_type']);

$admins = $pdo->query(
    'SELECT admin_id, Name AS full_name, Email AS email, role, is_active
     FROM admin ORDER BY role DESC, admin_id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$totalAdmins = count($admins);
$activeAdmins = count(array_filter($admins, static fn($a) => (int) $a['is_active'] === 1));
$disabledAdmins = $totalAdmins - $activeAdmins;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin | MU Lost &amp; Found</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #1e3a8a;
            --navy-dark: #0b2a6b;
            --blue: #1d4ed8;
            --blue-soft: #eff6ff;
            --blue-line: #dbeafe;
            --bg: #f5f7fb;
            --border: #e5e7eb;
            --text: #1f2937;
            --muted: #6b7280;
            --green: #15803d;
            --red: #b91c1c;
            --gold: #b45309;
            --radius: 14px;
            --shadow: 0 2px 10px rgba(15, 23, 42, .05);
            --shadow-lg: 0 10px 28px rgba(30, 58, 138, .14);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); }
        button, input, select { font: inherit; }

        /* Navbar - matching found-item.php */
        .navbar {
            position: sticky; top: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between; gap: 15px;
            padding: 13px 5%; color: #fff;
            background: linear-gradient(90deg, var(--navy-dark), var(--navy));
            box-shadow: 0 2px 12px rgba(0,0,0,.18);
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; font-size: 19px; font-weight: 700; }
        .nav-brand img { width: 34px; height: 34px; object-fit: contain; }
        .nav-right { display: flex; align-items: center; justify-content: flex-end; gap: 10px; flex-wrap: wrap; }
        .nav-link {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 9px 15px; border: 1px solid rgba(255,255,255,.55); border-radius: 8px;
            background: rgba(255,255,255,.12); color: #fff; font-size: 13px; font-weight: 600;
            text-decoration: none; transition: background .2s, color .2s;
        }
        .nav-link:hover, .nav-link.active { background: #fff; color: var(--navy); }

        .container { width: min(1200px, 100%); margin: 32px auto 48px; padding: 0 20px; display: grid; gap: 22px; }
        .page-banner {
            position: relative; overflow: hidden; padding: 28px 30px; color: #fff; border-radius: var(--radius);
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy) 55%, var(--blue) 100%);
            box-shadow: var(--shadow-lg);
        }
        .page-banner::before { content: ''; position: absolute; top: -90px; right: -60px; width: 240px; height: 240px; border-radius: 50%; background: rgba(255,255,255,.07); }
        .page-banner::after { content: ''; position: absolute; right: 100px; bottom: -100px; width: 180px; height: 180px; border-radius: 50%; background: rgba(255,255,255,.05); }
        .page-banner > * { position: relative; z-index: 1; }
        .banner-tag { display: inline-block; margin-bottom: 10px; padding: 4px 12px; border-radius: 999px; background: rgba(255,255,255,.16); font-size: 11px; font-weight: 600; letter-spacing: .6px; text-transform: uppercase; }
        .page-banner h1 { margin: 0 0 6px; font-size: 27px; }
        .page-banner p { max-width: 760px; margin: 0; color: #dbe7ff; font-size: 14px; line-height: 1.7; }

        .stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .stat-card { display: flex; align-items: center; gap: 15px; padding: 20px; background: #fff; border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); }
        .stat-icon { display: grid; place-items: center; flex: 0 0 48px; width: 48px; height: 48px; border-radius: 12px; background: var(--blue-soft); color: var(--blue); font-size: 22px; font-weight: 700; }
        .stat-icon.green { background: #dcfce7; color: var(--green); }
        .stat-icon.gold { background: #fef3c7; color: var(--gold); }
        .stat-label { margin: 0 0 2px; color: var(--muted); font-size: 12px; }
        .stat-value { margin: 0; color: var(--navy); font-size: 25px; font-weight: 700; line-height: 1.3; }

        .card { min-width: 0; padding: 28px; background: #fff; border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); }
        .card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .card h2 { margin: 0 0 4px; color: var(--navy); font-size: 20px; }
        .card-sub { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.6; }
        .count-pill { display: inline-flex; align-items: center; white-space: nowrap; padding: 5px 12px; border-radius: 999px; background: var(--blue-soft); color: var(--navy); font-size: 12px; font-weight: 700; }

        .create-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .form-group { min-width: 0; }
        label { display: block; margin-bottom: 7px; color: #374151; font-size: 13px; font-weight: 600; }
        .required { color: #dc2626; }
        .form-control { width: 100%; min-height: 46px; padding: 11px 13px; border: 1px solid #d1d5db; border-radius: 10px; background: #f9fafb; color: var(--text); font-size: 13px; transition: border-color .2s, box-shadow .2s, background .2s; }
        .form-control:focus { outline: none; border-color: var(--blue); background: #fff; box-shadow: 0 0 0 3px rgba(29,78,216,.12); }
        .form-hint { margin: 6px 0 0; color: var(--muted); font-size: 11px; }
        .create-actions { display: flex; align-items: flex-end; justify-content: flex-end; grid-column: 1 / -1; gap: 10px; padding-top: 2px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 38px; padding: 9px 14px; border: 0; border-radius: 9px; color: #fff; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; transition: transform .15s, box-shadow .2s, opacity .2s; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: linear-gradient(135deg, var(--navy), var(--blue)); box-shadow: 0 4px 10px rgba(30,58,138,.18); }
        .btn-primary:hover { box-shadow: 0 6px 14px rgba(30,58,138,.25); }
        .btn-warn { background: #b45309; }
        .btn-danger { background: #b91c1c; }
        .btn-light { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
        .btn-small { min-height: 34px; padding: 7px 10px; }

        .note { margin: 0; padding: 13px 16px; border-radius: 10px; font-size: 13px; line-height: 1.6; }
        .note.success { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
        .note.error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .table-tools { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
        .search-wrap { position: relative; width: min(340px, 100%); }
        .search-wrap input { width: 100%; min-height: 42px; padding: 10px 13px 10px 38px; border: 1px solid #d1d5db; border-radius: 10px; background: #f9fafb; font-size: 12px; }
        .search-wrap input:focus { outline: none; border-color: var(--blue); background: #fff; box-shadow: 0 0 0 3px rgba(29,78,216,.1); }
        .search-symbol { position: absolute; left: 13px; top: 9px; color: var(--muted); font-size: 17px; }
        .table-scroll { overflow-x: auto; border: 1px solid var(--border); border-radius: 12px; }
        table { width: 100%; min-width: 950px; border-collapse: collapse; }
        th { padding: 13px 14px; background: #f8fafc; color: #64748b; font-size: 11px; font-weight: 700; letter-spacing: .4px; text-align: left; text-transform: uppercase; white-space: nowrap; }
        td { padding: 14px; border-top: 1px solid #edf0f5; color: #374151; font-size: 12px; vertical-align: middle; }
        tbody tr:hover { background: #fbfdff; }
        .account-name { color: #1f2937; font-weight: 600; }
        .account-you { display: inline-block; margin-left: 5px; padding: 2px 7px; border-radius: 999px; background: var(--blue-soft); color: var(--blue); font-size: 10px; font-weight: 700; }
        .email-text { color: #64748b; overflow-wrap: anywhere; }
        .badge { display: inline-flex; align-items: center; padding: 5px 10px; border-radius: 999px; background: #e2e8f0; color: #475569; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .badge.super { background: #fef3c7; color: #92400e; }
        .badge.active { background: #dcfce7; color: #166534; }
        .badge.off { background: #fee2e2; color: #991b1b; }
        .actions { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
        .actions form { display: inline-flex; align-items: center; gap: 6px; margin: 0; }
        .actions input[type=password] { width: 135px; min-height: 34px; padding: 7px 9px; border: 1px solid #d1d5db; border-radius: 8px; background: #f9fafb; font-size: 11px; }
        .actions input[type=password]:focus { outline: none; border-color: var(--blue); background: #fff; }
        .empty-state { padding: 30px 16px; color: var(--muted); text-align: center; font-size: 13px; }
        .table-footer { display: flex; justify-content: space-between; gap: 12px; margin-top: 12px; color: var(--muted); font-size: 11px; }

        @media (max-width: 760px) {
            .navbar { padding: 12px 4%; align-items: flex-start; }
            .nav-brand { font-size: 15px; }
            .nav-brand img { width: 30px; height: 30px; }
            .nav-right { gap: 6px; }
            .nav-link { padding: 8px 10px; font-size: 11px; }
            .container { margin-top: 20px; padding: 0 14px; }
            .page-banner { padding: 23px 21px; }
            .page-banner h1 { font-size: 23px; }
            .stats { gap: 10px; }
            .stat-card { padding: 14px; gap: 10px; }
            .stat-icon { flex-basis: 38px; width: 38px; height: 38px; font-size: 18px; }
            .stat-value { font-size: 21px; }
            .card { padding: 22px 18px; }
        }
        @media (max-width: 560px) {
            .navbar { flex-direction: column; }
            .nav-right { width: 100%; justify-content: flex-start; }
            .stats { grid-template-columns: 1fr; }
            .stat-card { padding: 13px 16px; }
            .create-grid { grid-template-columns: 1fr; gap: 14px; }
            .create-actions { grid-column: auto; }
            .create-actions .btn { width: 100%; }
            .card-head, .table-tools { flex-direction: column; align-items: stretch; }
            .count-pill { align-self: flex-start; }
            .search-wrap { width: 100%; }
            .table-footer { flex-direction: column; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">
        <img src="mu_logo.png" alt="" onerror="this.style.display='none'">
        <span>MU Lost &amp; Found <span style="font-weight:400;opacity:.8">/ Super Admin</span></span>
    </div>
    <div class="nav-right">
        <a href="admin-dashboard.php" class="nav-link">Dashboard</a>
        <a href="admin-items.php" class="nav-link">Manage Items</a>
    </div>
</nav>

<main class="container">
    <section class="page-banner">
        <span class="banner-tag">Administration</span>
        <h1>Super Admin Dashboard</h1>
        <p>Manage administrator accounts, assign roles, reset passwords, and control account access for the MU Lost &amp; Found system.</p>
    </section>

    <?php if ($message !== ''): ?>
        <p class="note <?= e($type === 'error' ? 'error' : 'success') ?>" role="status"><?= e($message) ?></p>
    <?php endif; ?>

    <section class="stats" aria-label="Account summary">
        <div class="stat-card">
            <div class="stat-icon">♙</div>
            <div><p class="stat-label">Total Accounts</p><p class="stat-value"><?= $totalAdmins ?></p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✓</div>
            <div><p class="stat-label">Active Accounts</p><p class="stat-value"><?= $activeAdmins ?></p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon gold">Ⅱ</div>
            <div><p class="stat-label">Disabled Accounts</p><p class="stat-value"><?= $disabledAdmins ?></p></div>
        </div>
    </section>

    <section class="card">
        <div class="card-head">
            <div>
                <h2>Create Admin Account</h2>
                <p class="card-sub">Add a new administrator to help manage the system.</p>
            </div>
        </div>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="create">
            <div class="create-grid">
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input class="form-control" id="name" name="name" maxlength="100" placeholder="Enter full name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input class="form-control" id="email" type="email" name="email" maxlength="100" placeholder="admin@example.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input class="form-control" id="password" type="password" name="password" minlength="8" placeholder="At least 8 characters" required>
                    <p class="form-hint">Use at least 8 characters.</p>
                </div>
                <div class="form-group">
                    <label for="role">Account Role <span class="required">*</span></label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                <div class="create-actions">
                    <button class="btn btn-primary" type="submit">＋ Create Account</button>
                </div>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="card-head">
            <div>
                <h2>Admin Accounts</h2>
                <p class="card-sub">View accounts and manage their access and passwords.</p>
            </div>
            <span class="count-pill"><?= $totalAdmins ?> account<?= $totalAdmins === 1 ? '' : 's' ?></span>
        </div>

        <div class="table-tools">
            <div class="search-wrap">
                <span class="search-symbol" aria-hidden="true">⌕</span>
                <input type="search" id="accountSearch" placeholder="Search name, email, role, or status..." aria-label="Search admin accounts">
            </div>
            <span class="card-sub">Use the action buttons to manage each account.</span>
        </div>

        <div class="table-scroll">
            <table id="accountsTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email Address</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Account Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($admins as $a): $isMe = (int) $a['admin_id'] === (int) $currentAdmin['admin_id']; ?>
                    <tr>
                        <td>
                            <span class="account-name"><?= e($a['full_name']) ?></span>
                            <?php if ($isMe): ?><span class="account-you">YOU</span><?php endif; ?>
                        </td>
                        <td class="email-text"><?= e($a['email']) ?></td>
                        <td><span class="badge <?= $a['role'] === 'super_admin' ? 'super' : '' ?>"><?= $a['role'] === 'super_admin' ? 'Super Admin' : 'Admin' ?></span></td>
                        <td><span class="badge <?= (int) $a['is_active'] ? 'active' : 'off' ?>"><?= (int) $a['is_active'] ? 'Active' : 'Disabled' ?></span></td>
                        <td>
                            <div class="actions">
                                <form method="post" autocomplete="off">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="admin_id" value="<?= (int) $a['admin_id'] ?>">
                                    <input type="password" name="new_password" placeholder="New password" minlength="8" aria-label="New password for <?= e($a['full_name']) ?>" required>
                                    <button class="btn btn-primary btn-small" type="submit">Reset</button>
                                </form>
                                <?php if (!$isMe): ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="admin_id" value="<?= (int) $a['admin_id'] ?>">
                                        <button class="btn btn-warn btn-small" type="submit"><?= (int) $a['is_active'] ? 'Disable' : 'Enable' ?></button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Delete this account permanently? This action cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="admin_id" value="<?= (int) $a['admin_id'] ?>">
                                        <button class="btn btn-danger btn-small" type="submit">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$admins): ?>
                    <tr><td colspan="5" class="empty-state">No admin accounts found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span id="resultCount">Showing <?= $totalAdmins ?> account<?= $totalAdmins === 1 ? '' : 's' ?></span>
            <span>MU Lost &amp; Found · Super Admin</span>
        </div>
    </section>
</main>

<script>
    const accountSearch = document.getElementById('accountSearch');
    const accountRows = Array.from(document.querySelectorAll('#accountsTable tbody tr'));
    const resultCount = document.getElementById('resultCount');

    accountSearch.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();
        let visible = 0;

        accountRows.forEach(function (row) {
            const matches = row.innerText.toLowerCase().includes(term);
            row.style.display = matches ? '' : 'none';
            if (matches && row.querySelector('td[colspan]') === null) visible++;
        });

        resultCount.textContent = 'Showing ' + visible + ' account' + (visible === 1 ? '' : 's');
    });
</script>
</body>
</html>
