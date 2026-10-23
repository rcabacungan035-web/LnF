
<?php
$pageTitle = "Privacy Policy | MU Lost & Found";
$lastUpdated = date("F j, Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
          content="Privacy Policy for the MU Lost & Found System.">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    <style>
        :root {
            --primary: #1e3a8a;
            --primary-light: #eff6ff;
            --text: #1f2937;
            --muted: #64748b;
            --border: #e2e8f0;
            --background: #f4f7fb;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 24px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.8;
        }

        a {
            color: var(--primary);
        }

        .site-header {
            background: var(--primary);
            color: var(--white);
            padding: 22px 20px;
        }

        .header-inner {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .brand {
            font-size: 21px;
            font-weight: 700;
        }

        .brand span {
            color: #bfdbfe;
        }

        .back-link {
            color: var(--white);
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        .hero {
            background: var(--primary);
            color: var(--white);
            padding: 35px 20px 65px;
            text-align: center;
        }

        .hero h1 {
            font-size: clamp(27px, 5vw, 38px);
            margin-bottom: 10px;
        }

        .hero p {
            max-width: 680px;
            margin: 0 auto;
            color: #dbeafe;
            font-size: 14px;
        }

        .container {
            width: min(920px, calc(100% - 32px));
            margin: -30px auto 45px;
            position: relative;
        }

        .policy-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: clamp(22px, 5vw, 48px);
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
        }

        .updated {
            display: inline-block;
            background: var(--primary-light);
            color: var(--primary);
            padding: 7px 12px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .intro {
            font-size: 15px;
            margin-bottom: 28px;
        }

        .notice {
            border-left: 4px solid var(--primary);
            background: var(--primary-light);
            padding: 16px 18px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 30px;
            font-size: 13px;
        }

        .notice strong {
            display: block;
            margin-bottom: 5px;
            color: var(--primary);
        }

        .policy-section {
            padding: 23px 0;
            border-bottom: 1px solid var(--border);
        }

        .policy-section:last-of-type {
            border-bottom: none;
        }

        h2 {
            color: var(--primary);
            font-size: 19px;
            margin-bottom: 12px;
        }

        p {
            margin-bottom: 12px;
            font-size: 14px;
        }

        p:last-child {
            margin-bottom: 0;
        }

        ul {
            padding-left: 23px;
            margin: 10px 0 14px;
        }

        li {
            font-size: 14px;
            margin-bottom: 8px;
        }

        .contact-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            margin-top: 24px;
        }

        .contact-box h2 {
            margin-bottom: 8px;
        }

        .site-footer {
            text-align: center;
            padding: 24px 16px;
            color: var(--muted);
            font-size: 12px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .footer-links a {
            text-decoration: none;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .header-inner {
                align-items: flex-start;
            }

            .brand {
                font-size: 18px;
            }

            .hero {
                padding-bottom: 55px;
            }

            .policy-card {
                border-radius: 12px;
            }
        }

        @media print {
            body {
                background: white;
            }

            .site-header,
            .back-link,
            .site-footer {
                display: none;
            }

            .hero {
                background: white;
                color: var(--text);
                padding: 20px 0;
            }

            .hero p {
                color: var(--muted);
            }

            .container {
                width: 100%;
                margin: 0;
            }

            .policy-card {
                box-shadow: none;
                border: none;
                padding: 0;
            }
        }
    </style>
</head>

<body>

<header class="site-header">
    <div class="header-inner">
        <div class="brand">MU <span>Lost &amp; Found</span></div>

        <!-- Change this path if your login page has a different filename. -->
        <a class="back-link" href="index.php">
            &larr; Back to Home
        </a>
    </div>
</header>

<section class="hero">
    <h1>Privacy Policy</h1>
    <p>
        Learn how the MU Lost &amp; Found System handles information
        submitted when reporting, finding, and claiming lost belongings.
    </p>
</section>

<main class="container">
    <article class="policy-card">

        <div class="updated">
            Last updated: <?= htmlspecialchars($lastUpdated, ENT_QUOTES, 'UTF-8') ?>
        </div>

        <p class="intro">
            Welcome to MU Lost &amp; Found. This Privacy Policy explains
            how the system may collect, use, store, and protect information
            associated with its lost-and-found services. By using the system,
            you acknowledge that you have read this policy.
        </p>

        <div class="notice">
            <strong>Academic Project Notice</strong>
            MU Lost &amp; Found is a student-developed project. Unless
            formally authorized and deployed by an institution, it should
            not be represented as an official university service.
            Use sample information for demonstrations and testing.
        </div>

        <section class="policy-section">
            <h2>1. Information We Collect</h2>

            <p>Depending on the features you use, the system may process:</p>

            <ul>
                <li>
                    <strong>Account information:</strong>
                    your name, email address, and available profile
                    information used for sign-in.
                </li>
                <li>
                    <strong>Google sign-in information:</strong>
                    information made available through Google authentication,
                    such as your name, email address, and profile picture,
                    subject to the permissions requested by the application.
                </li>
                <li>
                    <strong>Lost and found reports:</strong>
                    item names, descriptions, categories, dates, locations
                    where items were lost or found, and item photographs.
                </li>
                <li>
                    <strong>Claim information:</strong>
                    claim descriptions, submitted evidence, claim status,
                    and communications needed to verify ownership.
                </li>
                <li>
                    <strong>System records:</strong>
                    information necessary to maintain sessions, process
                    requests, protect accounts, and troubleshoot errors,
                    where such records are enabled.
                </li>
            </ul>

            <p>
                Please do not submit passwords, government identification
                numbers, financial information, or other sensitive details
                unless a specifically authorized process requires them.
            </p>
        </section>

        <section class="policy-section">
            <h2>2. How We Use Your Information</h2>

            <p>Information may be used to:</p>

            <ul>
                <li>Create or identify your account during sign-in.</li>
                <li>Publish and manage lost or found item reports.</li>
                <li>Help users identify items and submit ownership claims.</li>
                <li>Track claim statuses and provide relevant notifications.</li>
                <li>Allow authorized administrators to review reports and claims.</li>
                <li>Maintain system security and investigate technical problems.</li>
                <li>Improve the system as part of its authorized development and testing.</li>
            </ul>

            <p>
                Information should only be used for purposes related to
                operating, maintaining, securing, or improving the system,
                subject to applicable law and any required consent.
            </p>
        </section>

        <section class="policy-section">
            <h2>3. Google Authentication</h2>

            <p>
                If Google Sign-In is enabled, Google may authenticate your
                identity and share the profile information permitted by
                the sign-in configuration.
            </p>

            <p>
                The system should use this information only for the disclosed
                authentication and account-related purposes. It does not
                need your Google password to authenticate you through the
                normal Google OAuth process.
            </p>

            <p>
                Google services are subject to Google's own privacy policies
                and terms. Your use of Google Sign-In may involve Google's
                processing of information under those policies.
            </p>

            <p>
                Do not assume that all Google account information is collected.
                The actual information processed depends on the OAuth scopes
                requested and the fields your backend stores.
            </p>
        </section>

        <section class="policy-section">
            <h2>4. Lost Item Photos and Public Information</h2>

            <p>
                Photographs and descriptions submitted in reports may help
                other users identify lost belongings. Information displayed
                to other users should be limited to what is necessary for
                item recovery.
            </p>

            <p>
                Avoid including faces, identification cards, addresses,
                phone numbers, passwords, or other private information
                in uploaded images or item descriptions.
            </p>

            <p>
                If the system makes reports visible to other users, information
                displayed in those reports may be seen by those users.
                Do not assume that every report is private unless the
                system explicitly restricts access.
            </p>
        </section>

        <section class="policy-section">
            <h2>5. Data Sharing and Disclosure</h2>

            <p>
                Personal information should not be sold or disclosed for
                unrelated commercial purposes. Information may be accessed
                or disclosed when reasonably necessary for the following:
            </p>

            <ul>
                <li>
                    Authorized administrators reviewing reports, accounts,
                    or claims within their assigned responsibilities.
                </li>
                <li>
                    Google or other service providers involved in features
                    you choose to use, subject to their respective roles
                    and privacy policies.
                </li>
                <li>
                    Responding to valid legal requirements or protecting
                    the rights, safety, and security of users and the system.
                </li>
            </ul>

            <p>
                Access should be limited to authorized persons and to the
                information required for the relevant purpose.
            </p>
        </section>

        <section class="policy-section">
            <h2>6. Data Storage and Security</h2>

            <p>
                The system uses a PHP backend and MySQL database for the
                information required by its implemented features. Uploaded
                files may also be stored in a server directory.
            </p>

            <p>
                Appropriate safeguards should include access controls,
                secure session handling, input validation, protected
                database credentials, secure file-upload validation,
                and HTTPS when deployed online.
            </p>

            <p>
                No website or storage method can guarantee absolute security.
                Users should report suspected unauthorized access or
                disclosure to the project administrator.
            </p>
        </section>

        <section class="policy-section">
            <h2>7. Data Retention and Deletion</h2>

            <p>
                Information should be retained only for as long as reasonably
                necessary for item recovery, claim resolution, security,
                legitimate project requirements, and applicable legal
                obligations.
            </p>

            <p>
                When information is no longer needed, it should be deleted,
                anonymized, or securely disposed of when appropriate,
                subject to applicable retention requirements.
            </p>

            <p>
                Deleting an account or report may not automatically remove
                every backup, log, or related record. Any deletion request
                will be handled according to the system's actual capabilities
                and applicable requirements.
            </p>
        </section>

        <section class="policy-section">
            <h2>8. Your Privacy Rights</h2>

            <p>
                Subject to applicable law, including the Philippine Data
                Privacy Act of 2012 (Republic Act No. 10173), you may have
                rights to:
            </p>

            <ul>
                <li>Be informed about the processing of your personal data.</li>
                <li>Request access to personal information held about you.</li>
                <li>Request correction of inaccurate or incomplete information.</li>
                <li>
                    Request blocking, deletion, or removal when legally
                    applicable and subject to valid retention requirements.
                </li>
                <li>Object to certain types of processing where the law allows.</li>
                <li>
                    Raise a concern or lodge a complaint with the appropriate
                    authority when your rights have been violated.
                </li>
            </ul>

            <p>
                To make a request, contact the designated project
                administrator using the contact method officially provided
                by the project team. Identity verification may be required
                before disclosing or changing account information.
            </p>
        </section>

        <section class="policy-section">
            <h2>9. Cookies and Session Data</h2>

            <p>
                The PHP application may use session cookies to maintain
                authentication status and support protected features.
                These cookies should be used only for necessary system
                functions and configured securely.
            </p>

            <p>
                If additional analytics, tracking, or third-party services
                are introduced, this policy should be updated to explain
                what they collect and how users can manage applicable
                choices.
            </p>
        </section>

        <section class="policy-section">
            <h2>10. Third-Party Services and External Links</h2>

            <p>
                The system may connect to Google for authentication or
                other services if these integrations are implemented.
                External websites and services operate under their own
                policies. Review those policies before providing information
                to them.
            </p>

            <p>
                The project team should review third-party integrations
                before deployment and disclose any additional data
                collection that they introduce.
            </p>
        </section>

        <section class="policy-section">
            <h2>11. Children's Privacy</h2>

            <p>
                The system is intended for its defined user community.
                It should not knowingly collect children's personal data
                without the appropriate legal basis, safeguards, and
                permissions where required by law.
            </p>
        </section>

        <section class="policy-section">
            <h2>12. Changes to This Policy</h2>

            <p>
                This policy may be revised when the system's features,
                data practices, or applicable legal requirements change.
                The latest version and its revision date should be made
                available on this page.
            </p>

            <p>
                If a material change requires notice or consent, the
                project team should provide it as required by applicable law.
            </p>
        </section>

        <section class="policy-section">
            <h2>13. Contact and Privacy Concerns</h2>

            <div class="contact-box">
                <p>
                    For questions, correction requests, or privacy concerns,
                    contact the designated MU Lost &amp; Found project
                    administrator through the contact channel officially
                    provided by your project team.
                </p>

                <p>
                    <strong>System:</strong> MU Lost &amp; Found<br>
                    <strong>Purpose:</strong> Lost and found item reporting
                    and claim management<br>
                    <strong>Contact:</strong> Use the verified project
                    administrator contact details supplied by your team.
                </p>
            </div>
        </section>

        <div class="notice" style="margin-top: 28px; margin-bottom: 0;">
            <strong>Academic Project Disclaimer</strong>
            This policy is a project template and must be checked against
            the actual implementation before publication. It does not,
            by itself, establish legal compliance or guarantee that every
            listed privacy safeguard has been implemented.
        </div>

    </article>
</main>

<footer class="site-footer">
    <nav class="footer-links" aria-label="Footer navigation">
        <a href="index.php">Home</a>
        <a href="privacy-policy.php" aria-current="page">Privacy Policy</a>
    </nav>

    <p>&copy; <?= date("Y") ?> MU Lost &amp; Found. Academic project.</p>
</footer>

</body>
</html>