<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message     = $_SESSION['post_message'] ?? '';
$messageType = $_SESSION['post_message_type'] ?? 'info';
unset($_SESSION['post_message'], $_SESSION['post_message_type']);

// Allow only known message types (used as CSS class)
if (!in_array($messageType, ['info', 'success', 'error'], true)) {
    $messageType = 'info';
}

$adminName = $_SESSION['admin_name'] ?? 'System Administrator';
$isSuper   = ($_SESSION['admin_role'] ?? '') === 'super_admin';
$initial   = strtoupper(mb_substr(trim($adminName), 0, 1, 'UTF-8')) ?: 'A';

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Post Item | MU Lost &amp; Found</title>

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
        select:focus-visible,
        textarea:focus-visible {
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
            max-width: 56ch;
        }

        /* ---------- Form panel ---------- */
        .post-wrap {
            width: 92%;
            max-width: 780px;
            margin: -48px auto 48px;
            position: relative;
            z-index: 2;
            background: var(--card);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(30, 58, 138, 0.1);
            padding: 30px 32px 32px;
        }

        .note {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin: 0 0 22px;
            padding: 12px 16px;
            border-radius: 10px;
            background: var(--blue-soft);
            color: var(--ink);
            font-size: 14px;
            line-height: 1.5;
        }

        .note.success { background: #dcfce7; color: #166534; }
        .note.error   { background: #fee2e2; color: #991b1b; }

        .post-form { display: grid; gap: 20px; }

        .field-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .post-form label,
        .post-form .group-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--ink);
        }

        .post-form input[type="text"],
        .post-form input[type="date"],
        .post-form textarea {
            display: block;
            width: 100%;
            margin-top: 6px;
            padding: 12px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            font: inherit;
            font-weight: 400;
            font-size: 14px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .post-form textarea { resize: vertical; min-height: 110px; line-height: 1.6; }

        .post-form input:focus,
        .post-form textarea:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.15);
        }

        .hint {
            font-weight: 400;
            font-size: 12.5px;
            color: var(--muted);
        }

        /* ---------- Type picker ---------- */
        .type-picker {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 8px;
        }

        .type-option { position: relative; display: block; }

        .type-option input {
            position: absolute;
            opacity: 0;
            inset: 0;
            cursor: pointer;
        }

        .type-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            background: #fff;
            transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
        }

        .type-icon {
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            background: var(--blue-soft);
            color: var(--blue);
        }

        .type-icon svg { width: 20px; height: 20px; }

        .type-title {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
        }

        .type-sub {
            display: block;
            font-size: 12px;
            font-weight: 400;
            color: var(--muted);
        }

        .type-option input:checked + .type-card {
            border-color: var(--blue);
            background: var(--blue-soft);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.15);
        }

        .type-option input:focus-visible + .type-card {
            outline: 3px solid rgba(29, 78, 216, 0.4);
            outline-offset: 2px;
        }

        /* ---------- Photo upload ---------- */
        .upload-box {
            position: relative;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 8px;
            padding: 16px;
            border: 2px dashed #b6c6ea;
            border-radius: 12px;
            background: #f8faff;
            transition: border-color 0.15s, background 0.15s;
        }

        .upload-box:hover,
        .upload-box.has-file {
            border-color: var(--blue);
            background: var(--blue-soft);
        }

        .upload-box input[type="file"] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            opacity: 0;
            cursor: pointer;
            border: 0;
        }

        .upload-preview {
            flex: 0 0 72px;
            width: 72px;
            height: 72px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid var(--line);
            display: grid;
            place-items: center;
            overflow: hidden;
            color: var(--blue);
        }

        .upload-preview svg { width: 28px; height: 28px; }

        .upload-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .upload-text strong {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--ink);
        }

        .upload-text span {
            font-size: 12.5px;
            font-weight: 400;
            color: var(--muted);
            overflow-wrap: anywhere;
        }

        .file-error {
            display: block;
            margin-top: 6px;
            color: #b91c1c;
            font-size: 13px;
            font-weight: 500;
        }

        .file-error:empty { display: none; }

        /* ---------- Actions ---------- */
        .form-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            padding-top: 6px;
        }

        .post-form button {
            padding: 13px 26px;
            background: #1e3a8a;
            color: #fff;
            border: 0;
            border-radius: 10px;
            font: inherit;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
        }

        .post-form button:hover { background: #1d4ed8; }
        .post-form button:active { transform: scale(0.98); }
        .post-form button:disabled { opacity: .6; cursor: not-allowed; }

        .back-btn {
            display: inline-block;
            padding: 13px 22px;
            background: #fff;
            color: var(--ink);
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            transition: background 0.15s;
        }

        .back-btn:hover { background: var(--blue-soft); }

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
        }

        @media (max-width: 560px) {
            .ad-hero h1 { font-size: 22px; }
            .ad-hero-inner { padding: 26px 0 70px; }
            .post-wrap { padding: 22px 18px 24px; }
            .field-row,
            .type-picker { grid-template-columns: 1fr; }
            .form-actions > * { flex: 1 1 100%; text-align: center; }
        }

        @media (prefers-reduced-motion: reduce) {
            .ad-nav a, .post-form button, .back-btn,
            .type-card, .upload-box { transition: none; }
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
            <a href="admin-dashboard.php">Dashboard</a>
            <a href="admin-claims.php">Claims</a>
            <a href="admin-items.php">Items</a>
            <a href="admin-post-item.php" aria-current="page">Post Lost/Found</a>
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

<section class="ad-hero">
    <div class="ad-hero-inner">
        <h1>Post a Lost or Found Item</h1>
        <p>Items posted here will appear in the student dashboard and item listings.</p>
    </div>
</section>

<main class="post-wrap">

    <?php if ($message): ?>
        <p class="note <?= e($messageType) ?>" role="status"><?= e($message) ?></p>
    <?php endif; ?>

    <form class="post-form" id="postForm" action="api/items/admin-create.php"
          method="post" enctype="multipart/form-data">

        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

        <!-- ITEM TYPE -->
        <div role="radiogroup" aria-labelledby="typeLabel">
            <span class="group-label" id="typeLabel">Item type</span>

            <div class="type-picker">

                <label class="type-option">
                    <input type="radio" name="item_type" value="Found" checked required>
                    <span class="type-card">
                        <span class="type-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5"/><path d="M12 13v8"/></svg>
                        </span>
                        <span>
                            <span class="type-title">Found</span>
                            <span class="type-sub">Someone turned in an item</span>
                        </span>
                    </span>
                </label>

                <label class="type-option">
                    <input type="radio" name="item_type" value="Lost" required>
                    <span class="type-card">
                        <span class="type-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M9 11h4"/></svg>
                        </span>
                        <span>
                            <span class="type-title">Lost</span>
                            <span class="type-sub">Someone is looking for an item</span>
                        </span>
                    </span>
                </label>

            </div>
        </div>

        <label>Item name
            <input type="text" name="item_name" maxlength="150" required
                   placeholder="e.g. Black umbrella">
        </label>

        <label>Description
            <textarea name="description" rows="4" maxlength="3000" required
                      placeholder="Color, brand, marks, or anything that helps identify the item"></textarea>
        </label>

        <div class="field-row">

            <label>Location
                <input type="text" name="location" maxlength="255" required
                       placeholder="e.g. Library, 2nd floor">
            </label>

            <label>Date reported
                <input type="date" name="date_reported"
                       value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
            </label>

        </div>

        <div>
            <span class="group-label">Item photo <span class="hint">(optional; JPG, PNG, WEBP; max 5 MB)</span></span>

            <div class="upload-box" id="uploadBox">
                <input type="file" name="photo" id="photo"
                       accept="image/jpeg,image/png,image/webp"
                       aria-label="Item photo">

                <span class="upload-preview" aria-hidden="true">
                    <svg id="uploadIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-8 8"/></svg>
                    <img id="photoPreview" alt="">
                </span>

                <span class="upload-text">
                    <strong id="uploadTitle">Choose a photo</strong>
                    <span id="uploadName">Click here to browse your files</span>
                </span>
            </div>

            <span class="file-error" id="photoError" role="alert"></span>
        </div>

        <div class="form-actions">
            <button type="submit" id="submitBtn">Publish Item</button>
            <a class="back-btn" href="admin-dashboard.php">Cancel</a>
        </div>
    </form>
</main>

<script>
(function () {
    var MAX_BYTES = 5 * 1024 * 1024;
    var photo   = document.getElementById('photo');
    var error   = document.getElementById('photoError');
    var form    = document.getElementById('postForm');
    var button  = document.getElementById('submitBtn');
    var box     = document.getElementById('uploadBox');
    var preview = document.getElementById('photoPreview');
    var icon    = document.getElementById('uploadIcon');
    var title   = document.getElementById('uploadTitle');
    var name    = document.getElementById('uploadName');

    function resetUpload() {
        box.classList.remove('has-file');
        preview.removeAttribute('src');
        preview.style.display = 'none';
        icon.style.display = '';
        title.textContent = 'Choose a photo';
        name.textContent = 'Click here to browse your files';
    }

    photo.addEventListener('change', function () {
        error.textContent = '';
        var file = photo.files[0];

        if (!file) {
            resetUpload();
            return;
        }

        if (file.size > MAX_BYTES) {
            error.textContent = 'Photo is too large. Maximum is 5 MB.';
            photo.value = '';
            resetUpload();
            return;
        }

        // Show a small preview of the chosen photo
        var reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            icon.style.display = 'none';
        };
        reader.readAsDataURL(file);

        box.classList.add('has-file');
        title.textContent = 'Photo selected';
        name.textContent = file.name;
    });

    // Prevent double submit
    form.addEventListener('submit', function () {
        button.disabled = true;
        button.textContent = 'Publishing...';
    });
})();
</script>

</body>
</html>