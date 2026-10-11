<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookshop Store Promotion & Price Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=IBM+Plex+Mono:wght@400;500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #f6f1e8;
            --white: #ffffff;
            --ink: #1e2b35;
            --ink-soft: #344552;
            --ink-70: rgba(30, 43, 53, 0.70);
            --ink-50: rgba(30, 43, 53, 0.50);

            --burgundy: #8f3b46;
            --forest: #365f4b;
            --brass: #b18442;

            --line: #ded7cc;
            --radius: 12px;
            --shadow: 0 12px 35px rgba(30, 43, 53, 0.10);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--paper);
            color: var(--ink);
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        nav {
            width: 100%;
            padding: 22px 7vw;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--paper);
            border-bottom: 1px solid var(--line);
            position: relative;
            z-index: 10;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-mark {
            width: 38px;
            height: 38px;
            background: var(--ink);
            color: var(--paper);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Fraunces', serif;
            font-size: 20px;
            font-weight: 600;
            border-radius: 4px;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .logo-text strong {
            font-family: 'Fraunces', serif;
            font-size: 18px;
            font-weight: 600;
        }

        .logo-text span {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 9px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ink-50);
            margin-top: 2px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            font-size: 13px;
            color: var(--ink-70);
        }

        .nav-links a {
            transition: color .2s ease;
        }

        .nav-links a:hover {
            color: var(--ink);
        }

        .nav-login {
            background: var(--ink);
            color: var(--paper) !important;
            padding: 11px 18px;
            border-radius: 4px;
            font-weight: 600;
        }

        .nav-login:hover {
            background: var(--ink-soft);
        }

        .hero {
            min-height: calc(100vh - 83px);
            display: grid;
            grid-template-columns: 58% 42%;
            position: relative;
            overflow: hidden;
        }

        .hero-left {
            background: var(--ink);
            color: var(--paper);
            padding: 9vw 8vw;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
        }

        .hero-left::before {
            content: "";
            position: absolute;
            width: 500px;
            height: 500px;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 50%;
            right: -180px;
            top: -180px;
        }

        .hero-left::after {
            content: "";
            position: absolute;
            width: 320px;
            height: 320px;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 50%;
            left: -140px;
            bottom: -180px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .eyebrow {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: rgba(255,255,255,.55);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .eyebrow::before {
            content: "";
            width: 35px;
            height: 1px;
            background: var(--brass);
        }

        .hero h1 {
            font-family: 'Fraunces', serif;
            font-size: clamp(42px, 5vw, 76px);
            line-height: 1.05;
            font-weight: 600;
            max-width: 700px;
            margin-bottom: 28px;
        }

        .hero h1 span {
            color: #d7b77a;
        }

        .hero-description {
            font-size: 16px;
            line-height: 1.8;
            color: rgba(255,255,255,.70);
            max-width: 570px;
            margin-bottom: 38px;
        }

        .hero-buttons {

            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .primary-btn {
            background: var(--paper);
            color: var(--ink);
            padding: 15px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            transition: all .2s ease;
        }

        .primary-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,.20);

        }

        .secondary-btn {
            border: 1px solid rgba(255,255,255,.25);
            color: var(--paper);
            padding: 15px 24px;
            border-radius: 4px;
            font-size: 14px;
            transition: all .2s ease;
        }

        .secondary-btn:hover {
            background: rgba(255,255,255,.08);
        }

        .hero-right {
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 4vw;
            position: relative;
        }

        .system-card {
            width: 100%;
            max-width: 470px;
        }

        .system-card-label {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--ink-50);
            margin-bottom: 15px;
        }

        .system-card h2 {
            font-family: 'Fraunces', serif;
            font-size: 32px;
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 12px;
        }

        .system-card > p {
            font-size: 14px;
            line-height: 1.7;
            color: var(--ink-70);
            margin-bottom: 28px;
        }

        .role-preview {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }

        .preview-role {
            background: var(--paper);
            border: 1px solid var(--line);
            padding: 18px 12px;
            border-radius: 8px;
            min-height: 135px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .preview-role .role-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
        }

        .manager-circle {
            background: var(--burgundy);
        }

        .retail-circle {
            background: var(--forest);
        }

        .cashier-circle {
            background: var(--brass);
        }


        .preview-role h3 {
            font-family: 'Fraunces', serif;
            font-size: 15px;
            margin-top: 15px;
            margin-bottom: 4px;
        }

        .preview-role p {
            font-size: 10px;
            line-height: 1.4;
            color: var(--ink-50);
        }

        .system-login-btn {
            display: block;
            width: 100%;
            background: var(--ink);
            color: var(--paper);
            padding: 16px;
            text-align: center;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            transition: background .2s ease;
        }

        .system-login-btn:hover {
            background: var(--ink-soft);
        }

        .secure-text {
            margin-top: 16px;
            text-align: center;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 9px;
            color: var(--ink-50);
            letter-spacing: .04em;
        }

        .features {
            padding: 100px 8vw;
            background: var(--paper);
        }

        .section-label {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10px;
            letter-spacing: .13em;
            text-transform: uppercase;
            color: var(--forest);
            margin-bottom: 14px;
        }

        .section-title {
            font-family: 'Fraunces', serif;
            font-size: clamp(32px, 4vw, 52px);
            font-weight: 600;
            max-width: 700px;
            line-height: 1.15;
            margin-bottom: 55px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .feature-card {
            background: var(--white);
            border: 1px solid var(--line);
            padding: 32px 28px;
            border-radius: var(--radius);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .feature-number {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            color: var(--brass);
            margin-bottom: 35px;
        }

        .feature-card h3 {
            font-family: 'Fraunces', serif;
            font-size: 22px;
            margin-bottom: 12px;
        }

        .feature-card p {
            font-size: 13px;
            line-height: 1.7;
            color: var(--ink-70);
        }

        .workflow {
            padding: 100px 8vw;
            background: var(--ink);
            color: var(--paper);
        }

        .workflow .section-label {
            color: #d7b77a;
        }

        .workflow-title {
            font-family: 'Fraunces', serif;
            font-size: clamp(32px, 4vw, 52px);
            font-weight: 600;
            max-width: 650px;
            margin-bottom: 60px;
        }

        .flow-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .flow-step {
            border-top: 1px solid rgba(255,255,255,.20);
            padding-top: 22px;
        }

        .flow-number {
            font-family: 'IBM Plex Mono', monospace;
            color: #d7b77a;
            font-size: 11px;
            margin-bottom: 18px;
        }

        .flow-step h3 {
            font-family: 'Fraunces', serif;
            font-size: 21px;
            margin-bottom: 10px;
        }

        .flow-step p {
            font-size: 13px;
            line-height: 1.7;
            color: rgba(255,255,255,.60);
        }

        .about {
            padding: 100px 8vw;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 100px;
            align-items: center;
            background: var(--white);
        }

        .about h2 {
            font-family: 'Fraunces', serif;
            font-size: clamp(35px, 4vw, 56px);
            line-height: 1.15;
            margin-bottom: 25px;
        }

        .about-description {
            font-size: 15px;
            line-height: 1.9;
            color: var(--ink-70);
        }

        .problem-box {
            background: var(--paper);
            border-left: 4px solid var(--burgundy);
            padding: 30px;
            border-radius: 0 8px 8px 0;
            margin-top: 28px;
        }

        .problem-box h4 {
            font-family: 'Fraunces', serif;
            font-size: 19px;
            margin-bottom: 10px;
        }

        .problem-box p {
            font-size: 13px;
            line-height: 1.7;
            color: var(--ink-70);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .stat-box {
            border: 1px solid var(--line);
            padding: 30px;
            border-radius: var(--radius);
        }

        .stat-box h3 {
            font-family: 'Fraunces', serif;
            font-size: 38px;
            color: var(--forest);
            margin-bottom: 7px;
        }

        .stat-box p {
            font-size: 12px;
            color: var(--ink-50);
        }

        .cta {
            padding: 90px 8vw;
            text-align: center;
            background: var(--brass);
            color: var(--white);
        }

        .cta h2 {
            font-family: 'Fraunces', serif;
            font-size: clamp(35px, 5vw, 60px);
            font-weight: 600;
            margin-bottom: 18px;
        }

        .cta p {
            max-width: 550px;
            margin: 0 auto 30px;
            font-size: 15px;
            line-height: 1.7;
            color: rgba(255,255,255,.85);
        }

        .cta a {
            display: inline-block;
            background: var(--ink);
            color: var(--paper);
            padding: 16px 30px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
            transition: transform .2s ease;
        }

        .cta a:hover {

            transform: translateY(-3px);

        }

        footer {
            background: #17222b;
            color: rgba(255,255,255,.55);
            padding: 28px 8vw;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
        }

        footer strong {
            color: rgba(255,255,255,.85);
            font-family: 'Fraunces', serif;
            font-size: 14px;
        }

        @media (max-width: 1000px) {
            .hero {
                grid-template-columns: 1fr;
            }
            .hero-left {
                min-height: 650px;
            }
            .hero-right {
                padding: 70px 8vw;
            }
            .system-card {
                max-width: 650px;
            }
            .feature-grid {
                grid-template-columns: 1fr;
            }
            .flow-container {
                grid-template-columns: repeat(2, 1fr);
            }
            .about {
                grid-template-columns: 1fr;
                gap: 50px;
            }
        }

        @media (max-width: 700px) {
            nav {
                padding: 18px 6vw;
            }
            .nav-links a:not(.nav-login) {
                display: none;
            }
            .hero-left {
                padding: 100px 7vw;
            }
            .hero h1 {
                font-size: 45px;
            }
            .role-preview {
                grid-template-columns: 1fr;
            }
            .preview-role {
                min-height: auto;
            }
            .flow-container {
                grid-template-columns: 1fr;
            }
            .features,
            .workflow,
            .about,
            .cta {
                padding: 75px 7vw;
            }
            footer {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }

    </style>
</head>
<body>

<nav>
    <a href="index.php" class="logo">
        <div class="logo-mark">B</div>
        <div class="logo-text">
            <strong>Bookshop System</strong>
            <span>Promotion & Price Management</span>
        </div>
    </a>

    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="#features">Features</a>
        <a href="#about">About System</a>
        <a href="login.php" class="nav-login">
            Login
        </a>
    </div>
</nav>

<section class="hero">
    <div class="hero-left">
        <div class="hero-content">
            <div class="eyebrow">
                Bookshop Management System
            </div>
            <h1>
                One System.<br>
                <span>One Accurate Price.</span>
            </h1>
            <p class="hero-description">
                A centralized Bookshop Store Promotion and Price Management
                System designed to synchronize product information, pricing
                and promotions between management, retail staff and cashiers.
            </p>

            <div class="hero-buttons">
                <a href="login.php" class="primary-btn">
                    Access System →
                </a>
                <a href="#features" class="secondary-btn">
                    Explore Features
                </a>
            </div>
        </div>
    </div>

    <div class="hero-right">
        <div class="system-card">
            <div class="system-card-label">
                System Access
            </div>
            <h2>
                Manage every price from one place.
            </h2>
            <p>
                Different departments access the same centralized
                information to reduce price mismatches and promotion errors.
            </p>

            <div class="role-preview">
                <div class="preview-role">
                    <div class="role-circle manager-circle">
                        M
                    </div>
                    <div>
                        <h3>Manager</h3>
                        <p>
                            Manage products, staff and promotions.
                        </p>
                    </div>
                </div>

                <div class="preview-role">
                    <div class="role-circle retail-circle">
                        R
                    </div>
                    <div>
                        <h3>Retail</h3>
                        <p>
                            View products and updated prices.
                        </p>
                    </div>
                </div>

                <div class="preview-role">
                    <div class="role-circle cashier-circle">
                        C
                    </div>
                    <div>
                        <h3>Cashier</h3>
                        <p>
                            Process sales using accurate prices.
                        </p>
                    </div>
                </div>
            </div>

            <a href="login.php" class="system-login-btn">
                Login to Your Account
            </a>
            <div class="secure-text">
                CENTRALIZED • SYNCHRONIZED • SECURE
            </div>
        </div>
    </div>
</section>


<section class="features" id="features">
    <div class="section-label">
        Core Features
    </div>
    <h2 class="section-title">
        Everything connected through
        one centralized system.
    </h2>

    <div class="feature-grid">
        <div class="feature-card">
            <div class="feature-number">
                01 / PRODUCT MANAGEMENT
            </div>
            <h3>
                Centralized Products
            </h3>
            <p>
                Manage product information, prices and stock from a
                centralized database accessible by authorized departments.
            </p>
        </div>

        <div class="feature-card">
            <div class="feature-number">
                02 / PROMOTION MANAGEMENT
            </div>
            <h3>
                Accurate Promotions
            </h3>
            <p>
                Create promotion prices with start and end dates to
                ensure expired promotions do not continue at checkout.
            </p>
        </div>

        <div class="feature-card">
            <div class="feature-number">
                03 / PRICE SYNCHRONIZATION
            </div>
            <h3>
                Real-Time Price Access
            </h3>
            <p>
                Retail staff and cashiers access the same updated
                product and promotion information from one system.
            </p>
        </div>
    </div>
</section>


<section class="workflow">
    <div class="section-label">
        System Workflow
    </div>
    <h2 class="workflow-title">
        From product setup to
        customer checkout.
    </h2>
    <div class="flow-container">
        <div class="flow-step">
            <div class="flow-number">
                STEP 01
            </div>
            <h3>
                Manager
            </h3>
            <p>
                Adds products, updates prices and creates promotions
                through the management dashboard.
            </p>
        </div>

        <div class="flow-step">
            <div class="flow-number">
                STEP 02
            </div>
            <h3>
                Database Sync
            </h3>
            <p>
                Updated information is stored in the centralized
                database for all authorized users.
            </p>
        </div>

        <div class="flow-step">
            <div class="flow-number">
                STEP 03
            </div>
            <h3>
                Retail Staff
            </h3>
            <p>
                Retail staff can check product details, current prices
                and active promotions.
            </p>
        </div>

        <div class="flow-step">
            <div class="flow-number">
                STEP 04
            </div>
            <h3>
                Cashier
            </h3>
            <p>
                The cashier processes transactions using the latest
                available product and promotion price.
            </p>
        </div>
    </div>
</section>


<section class="about" id="about">
    <div>
        <div class="section-label">
            About The System
        </div>
        <h2>
            Reducing price mismatch
            between departments.
        </h2>

        <p class="about-description">
            The Bookshop Store Promotion and Price Management System
            is developed to improve communication and data synchronization
            between the manager, retail staff and cashier.

            By using one centralized database, product prices and promotions
            can be managed consistently across different departments.
        </p>

        <div class="problem-box">
            <h4>
                The Problem
            </h4>
            <p>
                Manual price updates and disconnected department records
                can cause promotion errors, outdated prices and incorrect
                transaction amounts.
            </p>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box">
            <h3>3</h3>
            <p>
                Main User Roles
            </p>
        </div>

        <div class="stat-box">
            <h3>1</h3>
            <p>
                Centralized Database
            </p>
        </div>

        <div class="stat-box">
            <h3>24/7</h3>
            <p>
                System Data Access
            </p>
        </div>

        <div class="stat-box">
            <h3>100%</h3>
            <p>
                Centralized Price Information
            </p>
        </div>
    </div>
</section>

<section class="cta">
    <h2>
        Ready to access the system?
    </h2>
    <p>
        Login according to your assigned role and access
        your Bookshop Management System dashboard.
    </p>
    <a href="login.php">
        Login Now →
    </a>
</section>

<footer>
    <strong>
        Bookshop System
    </strong>
    <span>
        Bookshop Store Promotion & Price Management System
    </span>
    <span>
        © 2026
    </span>
</footer>
</body>
</html>