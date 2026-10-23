<?php

session_start();

if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION["user"];

$firstName = htmlspecialchars($user["first_name"] ?? "User", ENT_QUOTES, "UTF-8");
$lastName  = htmlspecialchars($user["last_name"] ?? "", ENT_QUOTES, "UTF-8");
$today     = date("Y-m-d");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Report Found Item - MU Lost &amp; Found</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

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

        * {
            box-sizing: border-box;
        }

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

        .nav-brand img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .user-name {
            font-size: 13px;
            font-weight: 500;
        }

        .nav-link {
            padding: 9px 18px;
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }

        .nav-link:hover {
            background: #ffffff;
            color: var(--navy);
        }

        /* ---------- PAGE ---------- */
        .container {
            max-width: 760px;
            margin: 32px auto 48px;
            padding: 0 20px;
            display: grid;
            gap: 22px;
        }

        .page-banner {
            position: relative;
            overflow: hidden;
            padding: 28px 30px;
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

        .page-banner > * {
            position: relative;
        }

        .banner-tag {
            display: inline-block;
            margin-bottom: 10px;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .page-banner h1 {
            margin: 0 0 6px;
            font-size: 26px;
        }

        .page-banner p {
            margin: 0;
            color: #dbe7ff;
            font-size: 14px;
            line-height: 1.6;
        }

        .card {
            padding: 30px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .card h2 {
            margin: 0 0 4px;
            color: var(--navy);
            font-size: 20px;
        }

        .card-sub {
            margin: 0 0 24px;
            color: var(--muted);
            font-size: 13px;
        }

        /* ---------- FORM ---------- */
        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
        }

        .required {
            color: #dc2626;
        }

        input[type="text"],
        input[type="date"],
        textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
            color: var(--text);
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--blue);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
        }

        textarea {
            min-height: 130px;
            resize: vertical;
            line-height: 1.6;
        }

        .field-hint {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        /* Photo upload */
        .upload-box {
            display: block;
            padding: 22px 16px;
            border: 2px dashed #bfdbfe;
            border-radius: 12px;
            background: var(--blue-soft);
            color: var(--navy);
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }

        .upload-box:hover {
            border-color: var(--blue);
            background: #e0ecff;
        }

        .upload-box small {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 400;
        }

        .upload-box input {
            display: none;
        }

        .photo-preview {
            display: none;
            margin-top: 14px;
            text-align: center;
        }

        .photo-preview img {
            max-width: 100%;
            max-height: 240px;
            border: 1px solid var(--border);
            border-radius: 10px;
            object-fit: contain;
            background: #f3f4f6;
        }

        .photo-preview button {
            display: block;
            width: auto;
            margin: 10px auto 0;
            padding: 7px 16px;
            border: 1px solid #fecaca;
            border-radius: 999px;
            background: #fff5f5;
            color: #b91c1c;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .photo-preview button:hover {
            background: #fee2e2;
        }

        /* Buttons */
        .form-actions {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            margin-top: 8px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--navy), var(--blue));
            color: #ffffff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 6px 14px rgba(30, 58, 138, 0.25);
            transition: transform 0.15s, box-shadow 0.2s, opacity 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(30, 58, 138, 0.32);
        }

        .btn-submit:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none;
        }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            padding: 14px 22px;
            border-radius: 10px;
            background: #f3f4f6;
            color: #4b5563;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-cancel:hover {
            background: #e5e7eb;
        }

        /* Messages */
        #message {
            display: none;
            margin-top: 18px;
            padding: 13px 16px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.6;
        }

        .success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        /* ---------- MOBILE ---------- */
        @media (max-width: 650px) {
            .navbar {
                padding: 12px 4%;
            }

            .nav-brand {
                font-size: 16px;
            }

            .user-name {
                display: none;
            }

            .container {
                margin-top: 20px;
                padding: 0 14px;
            }

            .page-banner {
                padding: 22px 20px;
            }

            .page-banner h1 {
                font-size: 22px;
            }

            .card {
                padding: 22px 18px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-actions {
                grid-template-columns: 1fr;
            }

            .btn-cancel {
                justify-content: center;
            }
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

            <span class="user-name">
                <?= $firstName . " " . $lastName ?>
            </span>

            <a href="dashboard.php" class="nav-link">
                Back to Dashboard
            </a>

        </div>

    </nav>


    <main class="container">

        <section class="page-banner">

            <span class="banner-tag">Found Item</span>

            <h1>Report a Found Item</h1>

            <p>
                Found something on campus? Share the details so the
                owner can find it. Registered users will be notified
                by email once it is posted.
            </p>

        </section>


        <section class="card">

            <h2>Item Details</h2>

            <p class="card-sub">
                Fields marked with <span class="required">*</span> are required.
            </p>


            <form
                id="foundItemForm"
                enctype="multipart/form-data"
            >

                <div class="form-group">

                    <label for="item_name">
                        Item Name <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="item_name"
                        name="item_name"
                        placeholder="Example: Black Wallet"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="description">
                        Description <span class="required">*</span>
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Describe the item, including color, brand, size, or other identifying details."
                        maxlength="3000"
                        required
                    ></textarea>

                    <p class="field-hint">
                        Do not include details that only the real owner would know.
                        Those can be used to verify the claim.
                    </p>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label for="location">
                            Location Found <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="location"
                            name="location"
                            placeholder="Example: University Library"
                            maxlength="255"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="date_reported">
                            Date Found <span class="required">*</span>
                        </label>

                        <input
                            type="date"
                            id="date_reported"
                            name="date_reported"
                            value="<?= $today ?>"
                            max="<?= $today ?>"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="photo">
                        Item Photo
                    </label>

                    <label class="upload-box" for="photo" id="uploadBox">
                        Click to choose a photo
                        <small>JPG, PNG or WEBP, up to 5 MB</small>

                        <input
                            type="file"
                            id="photo"
                            name="photo"
                            accept="image/jpeg,image/png,image/webp"
                        >
                    </label>

                    <div class="photo-preview" id="photoPreview">
                        <img id="previewImage" alt="Selected photo preview">
                        <button type="button" id="removePhoto">Remove photo</button>
                    </div>

                </div>


                <div class="form-actions">

                    <button type="submit" class="btn-submit" id="submitBtn">
                        Report Found Item
                    </button>

                    <a href="dashboard.php" class="btn-cancel">
                        Cancel
                    </a>

                </div>

                <div id="message" role="status"></div>

            </form>

        </section>

    </main>


    <script>

        const form =
            document.getElementById("foundItemForm");

        const message =
            document.getElementById("message");

        const submitBtn =
            document.getElementById("submitBtn");

        const photoInput =
            document.getElementById("photo");

        const photoPreview =
            document.getElementById("photoPreview");

        const previewImage =
            document.getElementById("previewImage");

        const uploadBox =
            document.getElementById("uploadBox");

        const MAX_BYTES = 5 * 1024 * 1024;


        function showMessage(text, type) {

            message.textContent = text;
            message.className = type;
            message.style.display = "block";
        }


        function clearPhoto() {

            photoInput.value = "";
            previewImage.removeAttribute("src");
            photoPreview.style.display = "none";
            uploadBox.style.display = "block";
        }


        photoInput.addEventListener("change", function () {

            const file = photoInput.files[0];

            if (!file) {
                clearPhoto();
                return;
            }

            if (file.size > MAX_BYTES) {
                clearPhoto();
                showMessage("Photo must not exceed 5 MB.", "error");
                return;
            }

            message.style.display = "none";

            previewImage.src = URL.createObjectURL(file);
            photoPreview.style.display = "block";
            uploadBox.style.display = "none";
        });


        document.getElementById("removePhoto")
            .addEventListener("click", clearPhoto);


        form.addEventListener(
            "submit",
            async function(event) {

                event.preventDefault();

                message.style.display = "none";

                submitBtn.disabled = true;
                submitBtn.textContent = "Submitting...";


                const formData =
                    new FormData(form);


                try {

                    const response =
                        await fetch(
                            "api/items/create-found.php",
                            {
                                method: "POST",
                                body: formData
                            }
                        );


                    const result =
                        await response.json();


                    if (result.success) {

                        showMessage(result.message, "success");

                        form.reset();
                        clearPhoto();

                        window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });

                    } else {

                        showMessage(result.message, "error");
                    }


                } catch (error) {

                    showMessage(
                        "An error occurred while submitting the found item.",
                        "error"
                    );

                } finally {

                    submitBtn.disabled = false;
                    submitBtn.textContent = "Report Found Item";
                }

            }
        );

    </script>

</body>

</html>