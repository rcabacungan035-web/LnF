<?php

session_start();

require_once __DIR__ . "/../config/google.php";
require_once __DIR__ . "/../config/database.php";

/*
 * Check whether Google returned an authorization code.
 */
if (!isset($_GET["code"])) {
    die("Google authorization code was not provided.");
}

/*
 * Validate OAuth state.
 */
if (
    !isset($_GET["state"]) ||
    !isset($_SESSION["oauth_state"]) ||
    !hash_equals($_SESSION["oauth_state"], $_GET["state"])
) {
    die("Invalid OAuth state. Please start the Google login again.");
}

/*
 * State is no longer needed.
 */
unset($_SESSION["oauth_state"]);

$code = $_GET["code"];

try {

    /*
     * Exchange authorization code
     * for an access token.
     */
    $token = $client->fetchAccessTokenWithAuthCode($code);

    /*
     * Check for Google OAuth errors.
     */
    if (isset($token["error"])) {

        $error = $token["error"];
        $description =
            $token["error_description"] ??
            "No description provided.";

        die(
            "Google authentication failed.<br><br>" .
            "Error: " .
            htmlspecialchars($error) .
            "<br>" .
            "Description: " .
            htmlspecialchars($description)
        );
    }

    /*
     * Make sure Google returned an access token.
     */
    if (!isset($token["access_token"])) {
        die("Google did not return an access token.");
    }

    $client->setAccessToken($token);

    /*
     * Get Google user information.
     */
    $oauth2 = new Google\Service\Oauth2($client);

    $googleUser = $oauth2->userinfo->get();

    $googleId =
        $googleUser->getId();

    $firstName =
        $googleUser->getGivenName();

    $lastName =
        $googleUser->getFamilyName();

    $email =
        $googleUser->getEmail();

    $profilePicture =
        $googleUser->getPicture();


    /*
     * --------------------------------------------------
     * SUPER ADMIN LOGIN
     * --------------------------------------------------
     *
     * If the Google account is the Super Admin email set in
     * api/config/super-admin.php, skip the student flow and
     * go straight to the Super Admin dashboard.
     */
    $superAdminEmail = "";
    $superConfigFile = __DIR__ . "/../config/super-admin.php";

    if (is_file($superConfigFile)) {
        $superConfig = require $superConfigFile;
        $superAdminEmail = strtolower(trim((string) ($superConfig["email"] ?? "")));
    }

    if (
        $superAdminEmail !== "" &&
        strpos($superAdminEmail, "replace_with_") !== 0 &&
        strtolower(trim((string) $email)) === $superAdminEmail
    ) {

        /*
         * Only trust the email if Google says it is verified.
         */
        if (!$googleUser->getVerifiedEmail()) {
            die("This Google account's email is not verified.");
        }

        $stmt = $pdo->prepare(
            "SELECT admin_id, Name
             FROM admin
             WHERE Email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        $adminRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$adminRow) {

            /*
             * First time: create the Super Admin record.
             * The password column is required, so store a random
             * unusable hash. This account signs in through Google.
             */
            $stmt = $pdo->prepare(
                "INSERT INTO admin (Name, Email, password, role, is_active)
                 VALUES (?, ?, ?, 'super_admin', 1)"
            );

            $stmt->execute([
                trim($firstName . " " . $lastName) ?: "Super Admin",
                $email,
                password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)
            ]);

            $adminId   = (int) $pdo->lastInsertId();
            $adminName = trim($firstName . " " . $lastName) ?: "Super Admin";

        } else {

            /*
             * The email in the config file is the authority,
             * so make sure this record is an active super admin.
             */
            $stmt = $pdo->prepare(
                "UPDATE admin
                 SET role = 'super_admin', is_active = 1
                 WHERE admin_id = ?"
            );

            $stmt->execute([$adminRow["admin_id"]]);

            $adminId   = (int) $adminRow["admin_id"];
            $adminName = $adminRow["Name"];
        }

        /*
         * Prevent session fixation, then sign in as super admin only.
         */
        session_regenerate_id(true);

        unset($_SESSION["user"]);

        $_SESSION["admin_id"]   = $adminId;
        $_SESSION["admin_name"] = $adminName;
        $_SESSION["admin_role"] = "super_admin";
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

        /* Sign-in alert. Email failure must not block login. */
        try {
            require_once __DIR__ . "/../config/mailer.php";
            muSendLoginNotification(
                (string) $email,
                (string) $adminName,
                "Google (Super Admin)"
            );
        } catch (Throwable $mailError) {
            error_log("Super admin login alert failed: " . $mailError->getMessage());
        }

        header("Location: ../../super-admin-dashboard.php");
        exit;
    }


    /*
     * --------------------------------------------------
     * REGULAR ADMIN LOGIN
     * --------------------------------------------------
     *
     * If the Google email exists in the admin table,
     * sign in as admin and skip the student flow.
     */
    $stmt = $pdo->prepare(
        "SELECT admin_id, Name, role, is_active
         FROM admin
         WHERE Email = ?
         LIMIT 1"
    );

    $stmt->execute([$email]);

    $adminAccount = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($adminAccount) {

        if (!$googleUser->getVerifiedEmail()) {
            die("This Google account's email is not verified.");
        }

        if ((int) $adminAccount["is_active"] !== 1) {
            die("This admin account is deactivated.");
        }

        $adminRole = $adminAccount["role"] === "super_admin"
            ? "super_admin"
            : "admin";

        session_regenerate_id(true);

        unset($_SESSION["user"]);

        $_SESSION["admin_id"]   = (int) $adminAccount["admin_id"];
        $_SESSION["admin_name"] = $adminAccount["Name"];
        $_SESSION["admin_role"] = $adminRole;
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

        /* Sign-in alert. Email failure must not block login. */
        try {
            require_once __DIR__ . "/../config/mailer.php";
            muSendLoginNotification(
                (string) $email,
                (string) $adminAccount["Name"],
                "Google (Admin)"
            );
        } catch (Throwable $mailError) {
            error_log("Admin login alert failed: " . $mailError->getMessage());
        }

        header(
            $adminRole === "super_admin"
                ? "Location: ../../super-admin-dashboard.php"
                : "Location: ../../admin-dashboard.php"
        );
        exit;
    }


    /*
     * --------------------------------------------------
     * STEP 1: Find Google account in users table
     * --------------------------------------------------
     */
    $stmt = $pdo->prepare(
        "SELECT *
         FROM users
         WHERE google_id = ?
         LIMIT 1"
    );

    $stmt->execute([$googleId]);

    $user = $stmt->fetch();


    /*
     * --------------------------------------------------
     * STEP 2: Create users record if necessary
     * --------------------------------------------------
     */
    if (!$user) {

        $stmt = $pdo->prepare(
            "INSERT INTO users
            (
                google_id,
                first_name,
                last_name,
                email,
                profile_picture,
                role
            )
            VALUES (?, ?, ?, ?, ?, 'student')"
        );

        $stmt->execute([
            $googleId,
            $firstName,
            $lastName,
            $email,
            $profilePicture
        ]);

        $userId = $pdo->lastInsertId();

        /*
         * Get the newly created user.
         */
        $stmt = $pdo->prepare(
            "SELECT *
             FROM users
             WHERE id = ?
             LIMIT 1"
        );

        $stmt->execute([$userId]);

        $user = $stmt->fetch();
    }


    /*
     * --------------------------------------------------
     * STEP 3: Find corresponding student record
     * --------------------------------------------------
     *
     * The item table uses student_id.
     * Therefore every Google student must have
     * a corresponding record in the student table.
     */

    $stmt = $pdo->prepare(
        "SELECT *
         FROM student
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([
        $user["email"]
    ]);

    $student = $stmt->fetch();


    /*
     * --------------------------------------------------
     * STEP 4: Create student record if necessary
     * --------------------------------------------------
     */
    if (!$student) {

        /*
         * The student.password column is required.
         *
         * Google users don't need a normal password,
         * so generate a random unusable password hash.
         */
        $randomPassword =
            bin2hex(random_bytes(32));

        $passwordHash =
            password_hash(
                $randomPassword,
                PASSWORD_DEFAULT
            );

        $stmt = $pdo->prepare(
            "INSERT INTO student
            (
                fname,
                lname,
                email,
                password
            )
            VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $user["first_name"],
            $user["last_name"],
            $user["email"],
            $passwordHash
        ]);

        $studentId =
            $pdo->lastInsertId();

        /*
         * Get the newly created student.
         */
        $stmt = $pdo->prepare(
            "SELECT *
             FROM student
             WHERE student_id = ?
             LIMIT 1"
        );

        $stmt->execute([
            $studentId
        ]);

        $student = $stmt->fetch();

    } else {

        /*
         * Student already exists.
         */
        $studentId =
            $student["student_id"];
    }


    /*
     * --------------------------------------------------
     * STEP 5: Store user + student information
     * in the PHP session.
     * --------------------------------------------------
     */
    $_SESSION["user"] = [

        "id" =>
            $user["id"],

        "google_id" =>
            $user["google_id"],

        "student_id" =>
            $studentId,

        "first_name" =>
            $user["first_name"],

        "last_name" =>
            $user["last_name"],

        "email" =>
            $user["email"],

        "profile_picture" =>
            $user["profile_picture"],

        "role" =>
            $user["role"]
    ];


    /*
     * Prevent session fixation.
     */
    session_regenerate_id(true);


    /* Send sign-in alert to the account that just signed in. Email delivery failure must not block login. */
    require_once __DIR__ . '/../config/mailer.php';
    muSendLoginNotification(
        (string)$user['email'],
        trim((string)$user['first_name'] . ' ' . (string)$user['last_name']),
        'Google'
    );

    /*
     * Login successful.
     */
    header(
        "Location: ../../dashboard.php"
    );

    exit;


} catch (Exception $e) {

    die(
        "Google authentication error:<br><br>" .
        htmlspecialchars(
            $e->getMessage()
        )
    );
}