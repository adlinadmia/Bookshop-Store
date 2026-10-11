<?php

session_start();

require_once "db.php";

$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $selectedRole = $_POST["role"] ?? "";


    if (
        $username === "" ||
        $password === "" ||
        $selectedRole === ""
    ) {

        $error = "Please enter your username, password and select a role.";

    } else {

        $sql = "
            SELECT id, username, password, role
            FROM users
            WHERE username = ?
            LIMIT 1
        ";


        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            $error = "Database error. Please try again.";

        } else {

            $stmt->bind_param(
                "s",
                $username
            );


            $stmt->execute();


            $result = $stmt->get_result();


            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();


                /*
                Supports both:

                1. Plain text password
                2. password_hash password
                */

                $passwordValid =
                    password_verify(
                        $password,
                        $user["password"]
                    )
                    ||
                    $password === $user["password"];


                if (!$passwordValid) {

                    $error = "Invalid username or password.";

                } elseif ($user["role"] !== $selectedRole) {

                    $error = "The selected role does not match this account.";

                } else {

                    /*
                    LOGIN SUCCESS
                    */

                    $_SESSION["user_id"] = $user["id"];

                    $_SESSION["username"] =
                        $user["username"];

                    $_SESSION["role"] =
                        $user["role"];


                    /*
                    REDIRECT BASED ON ROLE
                    */

                    if ($user["role"] === "manager") {

                        header(
                            "Location: manager/dashboard.php"
                        );

                        exit();


                    } elseif ($user["role"] === "retail") {

                        header(
                            "Location: retail/dashboard.php"
                        );

                        exit();


                    } elseif ($user["role"] === "cashier") {

                        header(
                            "Location: cashier/dashboard.php"
                        );

                        exit();


                    } else {

                        $error = "Invalid user role.";

                    }

                }

            } else {

                $error = "Invalid username or password.";

            }


            $stmt->close();

        }

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Login | Bookshop Price & Promotion System
</title>


<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>


<link
    href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap"
    rel="stylesheet"
>


<style>

:root {

    --ink: #1B2A3A;

    --ink-soft: #2C3F54;

    --paper: #F4EEDF;

    --burgundy: #8A3324;

    --forest: #3F5D4A;

    --brass: #A9762F;

    --white: #FFFEFB;

    --line:
        rgba(27,42,58,0.14);

    --ink-70:
        rgba(27,42,58,0.7);

    --ink-50:
        rgba(27,42,58,0.5);

    --radius: 8px;

    --shadow:
        0 18px 40px -20px
        rgba(27,42,58,0.35);

}


* {

    box-sizing: border-box;

}


html,
body {

    margin: 0;

    padding: 0;

}


body {

    font-family:
        'Inter',
        sans-serif;

    background:
        var(--white);

    color:
        var(--ink);

    min-height:
        100vh;

}


/* =========================
   MAIN SCREEN
========================= */

.screen {

    display:
        grid;

    /*
    LEFT = 60%
    RIGHT = 40%
    */

    grid-template-columns:
        60% 40%;

    min-height:
        100vh;

    width:
        100%;

    position:
        relative;

    overflow:
        hidden;

}


/* =========================
   LEFT BRAND PANEL
========================= */

.brand {

    position:
        relative;

    background:
        var(--ink);

    color:
        var(--paper);

    display:
        flex;

    flex-direction:
        column;

    justify-content:
        flex-start;

    /*
    Keep original spacing,
    slightly adjusted so the
    content does not feel too spread out.
    */

    padding:
        56px 60px 64px 52px;

    overflow:
        hidden;

}


/* =========================
   FINE NAVY BACKGROUND LINES
========================= */

.brand::before {

    /*
    Original fine vertical
    book-spine lines.
    */

    content:
        "";

    position:
        absolute;

    inset:
        0;

    background-image:
        repeating-linear-gradient(
            90deg,
            rgba(244,238,223,0.045) 0px,
            rgba(244,238,223,0.045) 1px,
            transparent 1px,
            transparent 46px
        );

    pointer-events:
        none;

    z-index:
        0;

}


/* =========================
   SLANTED DIVIDER
========================= */

.brand::after {

    content:
        "";

    position:
        absolute;

    top:
        0;

    right:
        -1px;

    /*
    Divider is only a narrow area
    at the edge of the 60% panel.
    */

    width:
        110px;

    height:
        100%;

    /*
    IMPORTANT:
    This must be background,
    NOT background-image.
    */

    background:
        var(--paper);

    /*
    The upper and lower ends
    are kept around the middle
    of the divider.

    This creates a subtle
    slanted centre line without
    cutting the entire navy panel.
    */

    clip-path:
        polygon(
            55% 0%,
            100% 0%,
            100% 100%,
            45% 100%
        );

    pointer-events:
        none;

    z-index:
        2;

}


/* Keep all left content above
   the fine line pattern */

.brand > * {

    position:
        relative;

    z-index:
        4;

}


/* =========================
   BRAND MARK
========================= */

.brand-mark {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

}


/* K CIRCLE */

.glyph {

    width:
        36px;

    height:
        36px;

    border:
        1.5px solid
        rgba(244,238,223,0.6);

    border-radius:
        50%;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-family:
        'Fraunces',
        serif;

    font-size:
        17px;

}


/* KIM BOOKSHOP */

.wordmark {

    font-family:
        'IBM Plex Mono',
        monospace;

    font-size:
        13px;
 letter-spacing:
        .08em;

}


/* =========================
   BRAND HEADLINE
========================= */

.brand-headline {

 margin-top:
        140px;
    padding-top:
        30px;

}


/* HEADLINE */

.brand-headline h1 {

    font-family:
        'Fraunces',
        serif;

    font-size:
        clamp(32px, 3.5vw, 46px);

    line-height:
        1.15;

    font-weight:
        600;

    /*
    Small bottom spacing.
    */

    margin:
        0 0 14px;

    max-width:
        380px;

}


.brand-headline h1 em {

    font-weight:
        400;

    color:
        #D9C9A3;

}


/* DESCRIPTION */

.brand-headline p {

    font-size:
        14px;

    line-height:
        1.7;

    color:
        rgba(244,238,223,0.65);

    max-width:
        350px;

    margin:
        0;

}


/* =========================
   BARCODE
========================= */

.ticket-row {

    margin-top:
        35px;

}


.barcode {

    width:
        240px;

    height:
        30px;

    background-image:

        repeating-linear-gradient(

            90deg,

            rgba(244,238,223,0.9) 0px,

            rgba(244,238,223,0.9) 2px,

            transparent 2px,

            transparent 5px,

            rgba(244,238,223,0.9) 5px,

            rgba(244,238,223,0.9) 7px,

            transparent 7px,

            transparent 11px

        );

    opacity:
        .65;

}


/* =========================
   RIGHT LOGIN PANEL
========================= */

.stage {

    display:
        flex;

    justify-content:
        center;

    align-items:
        center;

    padding:
        60px 70px;

    background:
        var(--paper);

}


.stage-inner {

    width:
        100%;

    max-width:
        560px;

    margin:
        0 auto;

}


/* =========================
   ROLE BUTTONS
========================= */

.roles {

    display:
        grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap:
        15px;

    margin-bottom:
        30px;

}


.role-tag {

    --accent:
        var(--forest);

    position:
        relative;

    background:
        var(--white);

    border:
        1px solid var(--line);

    border-radius:
        var(--radius);

    padding:
        18px;

    cursor:
        pointer;

    text-align:
        left;

    font-family:
        'Inter',
        sans-serif;

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        border-color .2s ease;

}


.role-tag:hover {

    transform:
        translateY(-3px);

    box-shadow:
        var(--shadow);

}


.role-tag[data-role="manager"] {

    --accent:
        var(--burgundy);

}


.role-tag[data-role="retail"] {

    --accent:
        var(--forest);

}


.role-tag[data-role="cashier"] {

    --accent:
        var(--brass);

}


.role-tag.active {

    border:
        2px solid var(--accent);

    box-shadow:
        var(--shadow);

}


.role-icon {

    width:
        32px;

    height:
        32px;

    border-radius:
        50%;

    background:
        var(--accent);

    color:
        white;

    display:
        flex;

    justify-content:
        center;

    align-items:
        center;

    font-family:
        'IBM Plex Mono',
        monospace;

    margin-bottom:
        12px;

}


.role-name {

    margin:
        0;

    font-family:
        'Fraunces',
        serif;

    font-size:
        16px;

    font-weight:
        600;

}


/* =========================
   LOGIN FORM
========================= */

form {

    background:
        var(--white);

    border:
        1px solid var(--line);

    border-radius:
        var(--radius);

    padding:
        30px;

}


.form-role-flag {

    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    font-family:
        'IBM Plex Mono',
        monospace;

    font-size:
        11px;

    margin-bottom:
        20px;

    color:
        var(--forest);

}


.dot {

    width:
        8px;

    height:
        8px;

    border-radius:
        50%;

    background:
        currentColor;

}


.field {

    margin-bottom:
        18px;

}


.field label {

    display:
        block;

    font-size:
        13px;

    font-weight:
        600;

    margin-bottom:
        7px;

}


.field input {

    width:
        100%;

    padding:
        13px;

    border:
        1px solid var(--line);

    border-radius:
        4px;

    background:
        var(--paper);

    color:
        var(--ink);

    font-family:
        'Inter',
        sans-serif;

    font-size:
        14px;

}


.field input:focus {

    outline:
        none;

    border-color:
        var(--forest);

    background:
        white;

}


/* =========================
   REMEMBER / FORGOT
========================= */

.row-between {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    margin-bottom:
        22px;

    font-size:
        12px;

}


.row-between label {

    display:
        flex;

    align-items:
        center;

    gap:
        7px;

    color:
        var(--ink-70);

}


.row-between a {

    color:
        var(--ink-soft);

    text-decoration:
        none;

}


.row-between a:hover {

    text-decoration:
        underline;

}


/* =========================
   SUBMIT BUTTON
========================= */

.submit {

    width:
        100%;

    padding:
        14px;

    border:
        none;

    border-radius:
        4px;

    background:
        var(--ink);

    color:
        var(--paper);

    font-family:
        'Inter',
        sans-serif;

    font-size:
        14px;

    font-weight:
        600;

    cursor:
        pointer;

    transition:
        0.2s;

}


.submit:hover {

    background:
        var(--ink-soft);

}


/* =========================
   ERROR MESSAGE
========================= */

.login-error {

    background:
        #fdeaea;

    border:
        1px solid #e7baba;

    color:
        #9b2626;

    padding:
        13px;

    border-radius:
        4px;

    margin-bottom:
        18px;

    font-size:
        13px;

}


/* =========================
   MOBILE
========================= */

@media (max-width: 880px) {

    .screen {

        grid-template-columns:
            1fr;

    }


    .brand {

        padding:
            45px 30px;

        min-height:
            500px;

    }


    /*
    Hide the desktop diagonal
    on mobile so the layout
    remains clean.
    */

    .brand::after {

        display:
            none;

    }


    .stage {

        padding:
            40px 25px;

    }

}


@media (max-width: 600px) {

    .roles {

        grid-template-columns:
            1fr;

    }

}

</style>

</head>


<body>


<div class="screen">


<!-- =====================
     LEFT BRAND PANEL
===================== -->

<div class="brand">
    <div class="brand-mark">

        <div class="glyph">

            K

        </div>
        <span class="wordmark">
            KIM BOOKSHOP
        </span>
    </div>

    <div class="brand-headline">
        <h1>
            One shelf price.

            <br>

            <em>
                Everywhere at once.
            </em>

        </h1>


        <p>

            Centralised product, price and promotion data
            for the counter, the floor and the back office
            synced the moment it changes.

        </p>


        <div class="ticket-row">

            <div class="barcode"></div>

        </div>


    </div>


</div>



<!-- =====================
     RIGHT LOGIN PANEL
===================== -->

<div class="stage">


<div class="stage-inner">


<!-- ROLE SELECTION -->

<div class="roles">


<button
    type="button"
    class="role-tag active"
    data-role="manager"
>

    <div class="role-icon">

        M

    </div>

    <p class="role-name">

        Manager

    </p>

</button>



<button
    type="button"
    class="role-tag"
    data-role="retail"
>

    <div class="role-icon">

        R

    </div>

    <p class="role-name">

        Retail Staff

    </p>

</button>



<button
    type="button"
    class="role-tag"
    data-role="cashier"
>

    <div class="role-icon">

        C

    </div>

    <p class="role-name">

        Cashier

    </p>

</button>


</div>



<!-- LOGIN FORM -->

<form
    id="loginForm"
    method="POST"
    action="login.php"
>


<input
    type="hidden"
    name="role"
    id="selectedRole"
    value="manager"
>



<div
    class="form-role-flag"
    id="roleFlag"
>

    <span class="dot"></span>

    <span id="roleFlagText">

        Signing in as Manager

    </span>

</div>



<?php if ($error !== ""): ?>


<div class="login-error">

    <?php echo htmlspecialchars($error); ?>

</div>


<?php endif; ?>



<div class="field">

<label for="username">

    Username

</label>


<input
    type="text"
    id="username"
    name="username"
    placeholder="e.g. manager1"
    autocomplete="username"
    required
>

</div>



<div class="field">


<label for="password">

    Password

</label>


<input
    type="password"
    id="password"
    name="password"
    placeholder="Enter password"
    autocomplete="current-password"
    required
>

</div>



<div class="row-between">


<label>

    <input
        type="checkbox"
    >

    Remember this device

</label>


</div>



<button
    type="submit"
    class="submit"
>

    Sign in

</button>


</form>


</div>


</div>


</div>



<script>


const accents = {

    manager: '#8A3324',

    retail: '#3F5D4A',

    cashier: '#A9762F'

};


const labels = {

    manager:
        'Signing in as Manager',

    retail:
        'Signing in as Retail Staff',

    cashier:
        'Signing in as Cashier'

};


const placeholders = {

    manager:
        'e.g. manager1',

    retail:
        'e.g. retail1',

    cashier:
        'e.g. cashier1'

};


const roleButtons =
    document.querySelectorAll('.role-tag');


const selectedRoleInput =
    document.getElementById('selectedRole');


const roleFlagText =
    document.getElementById('roleFlagText');


const usernameInput =
    document.getElementById('username');


const loginForm =
    document.getElementById('loginForm');



roleButtons.forEach(function(button) {


    button.addEventListener(
        'click',
        function() {


            roleButtons.forEach(
                function(btn) {

                    btn.classList.remove(
                        'active'
                    );

                }
            );


            button.classList.add(
                'active'
            );


            const selectedRole =
                button.dataset.role;


            /*
            UPDATE HIDDEN INPUT
            */

            selectedRoleInput.value =
                selectedRole;


            /*
            UPDATE LABEL
            */

            roleFlagText.textContent =
                labels[selectedRole];


            /*
            UPDATE PLACEHOLDER
            */

            usernameInput.placeholder =
                placeholders[selectedRole];


            /*
            UPDATE FORM COLOR
            */

            loginForm.style.setProperty(
                '--accent-current',
                accents[selectedRole]
            );


        }

    );


});


</script>


</body>

</html>