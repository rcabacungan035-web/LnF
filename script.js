document.addEventListener("DOMContentLoaded", function () {

    const googleLoginBtn =
        document.getElementById("googleLoginBtn");

    const loginMessage =
        document.getElementById("loginMessage");

    if (googleLoginBtn) {

        googleLoginBtn.addEventListener("click", function () {

            loginMessage.textContent =
                "Connecting to Google...";

            /*
             * Google OAuth will be connected here.
             *
             * Example final flow:
             *
             * window.location.href =
             * "api/auth/google-login.php";
             */

            window.location.href =
                "api/auth/google-login.php";
        });
    }


    /* =========================
       DASHBOARD LOGOUT
    ========================= */

    const logoutBtn =
        document.getElementById("logoutBtn");

    if (logoutBtn) {

        logoutBtn.addEventListener("click", function () {

            const confirmLogout =
                confirm("Are you sure you want to logout?");

            if (confirmLogout) {

                window.location.href =
                    "api/auth/logout.php";
            }

        });
    }


    /* =========================
       DASHBOARD CARDS
    ========================= */

    const dashboardCards =
        document.querySelectorAll(".dashboard-card");

    dashboardCards.forEach(function (card) {

        card.addEventListener("click", function () {

            const target =
                card.getAttribute("data-target");

            if (target) {
                window.location.href = target;
            }

        });

    });

});