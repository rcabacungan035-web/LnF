<?php
session_start();

/* Admin or super admin signed in through Google */
if (!empty($_SESSION['admin_id'])) {
    header(
        ($_SESSION['admin_role'] ?? '') === 'super_admin'
            ? "Location: super-admin-dashboard.php"
            : "Location: admin-dashboard.php"
    );
    exit;
}

/* Regular student */
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MU Lost &amp; Found</title>

    <link rel="stylesheet" href="style.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>
        * { box-sizing: border-box; }

        body.mu-login {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: 'Poppins', sans-serif;
            color: #1f2937;
            background:
                radial-gradient(circle at 15% 20%, rgba(30, 94, 255, 0.35), transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(245, 158, 11, 0.18), transparent 40%),
                #06152e;
        }

        /* ---------- Card shell ---------- */
        .mu-shell {
            width: 100%;
            max-width: 980px;
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);
            animation: muRise 0.6s ease both;
        }

        @keyframes muRise {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ---------- Left hero ---------- */
        .mu-hero {
            position: relative;
            padding: 52px 44px;
            color: #ffffff;
            background: linear-gradient(145deg, #0b2a6b 0%, #1e3a8a 55%, #1d4ed8 100%);
            overflow: hidden;
        }

        .mu-hero::before,
        .mu-hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
        }

        .mu-hero::before { width: 260px; height: 260px; top: -90px; right: -80px; }
        .mu-hero::after  { width: 200px; height: 200px; bottom: -70px; left: -60px; }

        .mu-hero > * { position: relative; z-index: 1; }

        .mu-logo-wrap {
            width: 110px;
            height: 110px;
            margin-bottom: 26px;
        }

        .mu-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .mu-hero h1 {
            margin: 0 0 8px;
            font-size: 30px;
            font-weight: 700;
            line-height: 1.2;
        }

        .mu-hero .mu-tagline {
            margin: 0 0 30px;
            color: #bfdbfe;
            font-size: 14px;
        }

        .mu-features {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 14px;
        }

        .mu-features li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 13px;
            line-height: 1.5;
            color: #e0ecff;
        }

        .mu-check {
            flex: 0 0 24px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.16);
            color: #fcd34d;
            font-size: 13px;
            font-weight: 700;
        }

        /* ---------- Right panel ---------- */
        .mu-panel {
            padding: 52px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .mu-panel h2 {
            margin: 0 0 8px;
            font-size: 26px;
            font-weight: 700;
            color: #08265c;
        }

        .mu-panel .mu-sub {
            margin: 0 0 28px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        /* ---------- Google button ---------- */
        #googleLoginBtn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 14px 18px;
            background: #ffffff;
            color: #3c4043;
            border: 1.5px solid #dadce0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: box-shadow 0.2s, border-color 0.2s, transform 0.1s;
        }

        #googleLoginBtn:hover {
            border-color: #1e5eff;
            box-shadow: 0 6px 18px rgba(30, 94, 255, 0.18);
        }

        #googleLoginBtn:active { transform: scale(0.99); }

        #googleLoginBtn:focus-visible {
            outline: 3px solid rgba(30, 94, 255, 0.35);
            outline-offset: 2px;
        }

        #googleLoginBtn:disabled { opacity: 0.6; cursor: not-allowed; }

        #googleLoginBtn svg { flex: 0 0 20px; }

        /* Message area used by script.js */
        #loginMessage {
            margin: 16px 0 0;
            padding: 11px 14px;
            background: #fee2e2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #b91c1c;
            font-size: 13px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        #loginMessage:empty { display: none; }

        #loginMessage.success {
            background: #dcfce7;
            border-color: #bbf7d0;
            color: #166534;
        }

        .mu-footer {
            margin-top: 28px;
            font-size: 12px;
            line-height: 1.7;
            color: #94a3b8;
        }

        .mu-footer p { margin: 0 0 8px; }

        .mu-footer a {
            color: #1049b8;
            text-decoration: none;
            font-weight: 500;
        }

        .mu-footer a:hover { text-decoration: underline; }

        .mu-secure {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
        }

        .login-disclaimer {
            margin-top: 18px;
            padding: 12px 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .login-disclaimer strong { color: #334155; }

        /* ---------- Responsive ---------- */
        @media (max-width: 820px) {
            .mu-shell { grid-template-columns: 1fr; max-width: 460px; }
            .mu-hero  { padding: 32px 28px; text-align: center; }
            .mu-logo-wrap { width: 84px; height: 84px; margin: 0 auto 18px; }
            .mu-hero h1 { font-size: 24px; }
            .mu-hero .mu-tagline { margin-bottom: 0; }
            .mu-features { display: none; }
            .mu-panel { padding: 30px 26px 34px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .mu-shell { animation: none; }
            #googleLoginBtn { transition: none; }
        }
    </style>
</head>

<body class="mu-login">

    <main class="mu-shell">

        <!-- LEFT: BRANDING -->
        <section class="mu-hero">

            <div class="mu-logo-wrap">
                <img src="mu_logo.png" alt="MU Logo">
            </div>

            <h1>MU Lost &amp; Found</h1>
            <p class="mu-tagline">Campus Lost and Found System</p>

            <ul class="mu-features">
                <li>
                    <span class="mu-check">&#10003;</span>
                    <span>Report a lost item in a minute, with a photo and details.</span>
                </li>
                <li>
                    <span class="mu-check">&#10003;</span>
                    <span>Browse found items posted by the campus community.</span>
                </li>
                <li>
                    <span class="mu-check">&#10003;</span>
                    <span>Get an email whenever a new lost or found item is posted.</span>
                </li>
            </ul>

        </section>

        <!-- RIGHT: SIGN IN -->
        <section class="mu-panel">

            <h2>Welcome</h2>

            <p class="mu-sub">
                Sign in using your personal Google account
                to access the system.
            </p>

            <button
                type="button"
                id="googleLoginBtn"
                class="google-btn"
            >
                <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                <span>Continue with Google</span>
            </button>

            <p id="loginMessage" class="login-message"></p>

            <div class="mu-footer">

                <p class="mu-secure">
                    Secure authentication powered by Google
                </p>

                <p>
                    By signing in, you agree to our
                    <a
                        href="terms.php"
                        target="_blank"
                        rel="noopener"
                    >Terms and Conditions</a>
                    and
                    <a
                        href="privacy-policy.php"
                        target="_blank"
                        rel="noopener"
                    >Privacy Policy</a>.
                </p>

                <p>
                    &copy; <?= date('Y') ?> MU Lost &amp; Found.
                    All rights reserved.
                </p>

            </div>

            <!-- ACADEMIC DISCLAIMER -->
            <div class="login-disclaimer">
                <strong>Disclaimer:</strong>
           
            </div>

        </section>

    </main>

    <script src="script.js"></script>

</body>
</html>