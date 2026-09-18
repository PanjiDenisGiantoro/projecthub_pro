<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Flovig — Manajemen Project & Tim dalam Satu Platform</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1a5fe0;
            --primary-dark: #1547b8;
            --teal: #14d6b8;
            --gradient-brand: linear-gradient(135deg, #1a5fe0 0%, #1a8fdb 45%, #17c9c3 75%, #14d6b8 100%);
            --secondary: #0f172a;
            --text: #111827;
            --muted: #64748b;
            --bg: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
            --success: #22c55e;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: auto;
        }

        /* ── Navbar ── */
        header {
            position: fixed;
            top: 24px;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            justify-content: center;
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 28px;
            background: #ffffff;
            border-radius: 100px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
            width: 90%;
            max-width: 1100px;
        }

        .navbar::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -50px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, transparent 40%, rgba(20, 214, 184, 0.03) 41%, transparent 42%),
                radial-gradient(circle, transparent 50%, rgba(26, 95, 224, 0.03) 51%, transparent 52%);
            border-radius: 50%;
            pointer-events: none;
        }

        .logo {
            font-size: 22px;
            font-weight: 800;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: var(--gradient-brand);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-style: italic;
            font-weight: 800;
        }

        .nav-links {
            display: flex;
            gap: 32px;
            color: #1e293b;
            font-weight: 600;
            font-size: 14.5px;
            z-index: 1;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .nav-links a svg {
            width: 14px;
            height: 14px;
            color: #94a3b8;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-buttons {
            display: flex;
            align-items: center;
            z-index: 1;
        }

        .nav-buttons .log-in {
            font-weight: 600;
            font-size: 14.5px;
            color: #1e293b;
            margin-right: 24px;
            transition: color 0.2s;
        }

        .nav-buttons .log-in:hover {
            color: var(--primary);
        }

        .btn-nav-primary {
            background: var(--primary);
            color: white;
            border-radius: 100px;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14.5px;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(26, 95, 224, 0.3);
            transition: background-color 0.2s, transform 0.2s;
        }

        .btn-nav-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-nav-primary svg {
            width: 16px;
            height: 16px;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: background-color .15s ease, border-color .15s ease;
            display: inline-block;
            font-size: 15px;
        }

        .btn-outline {
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--text);
        }

        .btn-outline:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        /* ── Hero ── */
        .hero {
            padding: 140px 0 60px;
            position: relative;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 60px;
            align-items: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 8px 14px 8px 12px;
            border-radius: 999px;
            background: #eef4ff;
            border: 1px solid #dce7fd;
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 13.5px;
            margin-bottom: 16px;
            margin-top: 36px;
        }

        .badge-dot {
            position: relative;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--primary);
            flex-shrink: 0;
        }

        .badge-dot::after {
            content: "";
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 1.5px solid var(--primary);
            opacity: 0;
            animation: fl-ping 2.2s cubic-bezier(0, 0, .2, 1) infinite;
        }

        @keyframes fl-ping {
            0% {
                transform: scale(0.6);
                opacity: .6;
            }

            100% {
                transform: scale(1.8);
                opacity: 0;
            }
        }

        .hero h1 {
            font-size: 52px;
            line-height: 1.12;
            margin-bottom: 22px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #0f172a;
        }

        .hero h1 span {
            color: var(--primary);
        }

        .hero p {
            font-size: 17px;
            color: var(--muted);
            margin-bottom: 32px;
            max-width: 480px;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .hero-note {
            color: var(--muted);
            font-size: 14px;
        }

        /* ── Dashboard Card ── */
        .dashboard-card {
            background: var(--white);
            border-radius: 20px;
            padding: 26px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 16px 40px rgba(15, 23, 42, 0.07);
            border: 1px solid var(--border);
        }

        .dashboard-title {
            font-weight: 700;
            font-size: 15px;
            color: var(--muted);
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 26px;
        }

        .stat-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }

        .stat-box h3 {
            font-size: 24px;
            color: var(--secondary);
            margin-bottom: 2px;
            font-weight: 800;
        }

        .stat-box p {
            font-size: 12.5px;
            color: var(--muted);
        }

        .progress-item {
            margin-bottom: 16px;
        }

        .progress-item:last-child {
            margin-bottom: 0;
        }

        .progress-head {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13.5px;
            font-weight: 600;
        }

        .progress-head span:last-child {
            color: var(--muted);
            font-weight: 500;
        }

        .progress-bar {
            height: 6px;
            border-radius: 999px;
            background: #eef1f5;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            border-radius: inherit;
        }

        /* ── Brands ── */
        .brands {
            padding: 46px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .brands>.container>p {
            color: var(--muted);
            margin-bottom: 28px;
            font-weight: 600;
            font-size: 13px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .brand-grid {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
        }

        .brand-item {
            font-weight: 700;
            color: #94a3b8;
            font-size: 17px;
            letter-spacing: -.01em;
        }

        /* ── Section ── */
        section {
            padding: 90px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .section-title h2 {
            font-size: 36px;
            margin-bottom: 14px;
            color: var(--secondary);
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .section-title p {
            max-width: 620px;
            margin: auto;
            color: var(--muted);
            font-size: 17px;
        }

        /* ── Features ── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px 32px;
            margin-top: 56px;
        }

        .feature-card {
            transition: transform .15s ease;
        }

        .feature-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: #eef4ff;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }

        .feature-icon svg {
            width: 22px;
            height: 22px;
        }

        .feature-card h3 {
            margin-bottom: 8px;
            font-size: 17px;
            font-weight: 700;
        }

        .feature-card p {
            color: var(--muted);
            font-size: 14.5px;
            line-height: 1.65;
        }

        /* ── Integration ── */
        .integration {
            background: #f8fafc;
        }

        .integration-box {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 50px;
            align-items: center;
            margin-top: 56px;
        }

        .team-list {
            background: white;
            border-radius: 18px;
            padding: 26px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 28px rgba(15, 23, 42, 0.06);
        }

        .team-member {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .team-member:last-child {
            border-bottom: none;
        }

        .member-info {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
        }

        .member-role {
            font-size: 13px;
            color: var(--muted);
        }

        .status-badge {
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
        }

        .status-cuti {
            background: rgba(251, 191, 36, 0.1);
            color: #d97706;
        }

        .integration-content {
            padding: 20px 0;
        }

        .integration-content h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 16px;
            line-height: 1.3;
        }

        .integration-content p {
            color: var(--muted);
            font-size: 16px;
            margin-bottom: 24px;
        }

        .integration-content ul {
            list-style: none;
        }

        .integration-content li {
            margin-bottom: 16px;
            padding-left: 30px;
            position: relative;
            color: var(--muted);
            font-size: 16px;
        }

        .integration-content li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: 700;
        }

        /* ── Stats Row ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            margin-top: 50px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .big-stat {
            text-align: center;
            background: white;
            padding: 28px 20px;
        }

        .big-stat h3 {
            font-size: 32px;
            color: var(--secondary);
            margin-bottom: 6px;
            font-weight: 800;
            letter-spacing: -.01em;
        }

        .big-stat p {
            color: var(--muted);
            font-weight: 500;
            font-size: 14px;
        }

        /* ── Pricing ── */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-top: 56px;
            align-items: start;
        }

        .pricing-card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            border: 1px solid var(--border);
            position: relative;
        }

        .pricing-card.popular {
            border: 2px solid var(--primary);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 16px 32px rgba(26, 95, 224, 0.1);
        }

        .popular-badge {
            position: absolute;
            top: -13px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: white;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .pricing-card h3 {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .pricing-card>p {
            color: var(--muted);
            font-size: 14px;
        }

        .price {
            font-size: 36px;
            font-weight: 800;
            margin: 20px 0 4px;
            color: var(--secondary);
            letter-spacing: -.01em;
        }

        .price-note {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 24px;
        }

        .pricing-card ul {
            list-style: none;
            margin: 24px 0;
        }

        .pricing-card li {
            margin-bottom: 12px;
            color: var(--muted);
            font-size: 14.5px;
        }

        .pricing-card .btn {
            width: 100%;
            text-align: center;
        }

        /* ── CTA ── */
        .cta {
            padding: 100px 0;
        }

        .cta-box {
            background: var(--secondary);
            border-radius: 24px;
            padding: 64px 40px;
            text-align: center;
            color: white;
        }

        .cta-box h2 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -.02em;
        }

        .cta-box p {
            max-width: 560px;
            margin: 0 auto 28px;
            color: #cbd5e1;
            font-size: 16px;
        }

        .btn-white {
            background: white;
            color: var(--secondary);
            font-weight: 700;
        }

        .btn-white:hover {
            background: #e2e8f0;
        }

        /* ── Footer ── */
        footer {
            background: #061224;
            /* deep blue */
            color: #cbd5e1;
            padding: 80px 0 30px;
            position: relative;
            overflow: hidden;
            margin-top: 60px;
            border-radius: 24px 24px 0 0;
            margin-left: 20px;
            margin-right: 20px;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle at top right, rgba(26, 95, 224, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        .footer-container {
            width: 90%;
            max-width: 1200px;
            margin: auto;
            position: relative;
            z-index: 10;
        }

        .footer-top {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 40px;
        }

        .footer-brand {
            max-width: 320px;
        }

        .footer-brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 26px;
            font-weight: 800;
            color: white;
            margin-bottom: 20px;
        }

        .footer-brand-logo .icon {
            width: 34px;
            height: 34px;
            background: var(--gradient-brand);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-style: italic;
        }

        .footer-brand h4 {
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .footer-brand p {
            font-size: 13.5px;
            line-height: 1.6;
            color: #94a3b8;
            margin-bottom: 24px;
        }

        .social-icons {
            display: flex;
            gap: 12px;
        }

        .social-icons a {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            transition: all 0.2s;
        }

        .social-icons a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .social-icons svg {
            width: 15px;
            height: 15px;
        }

        .footer-links-group {
            display: flex;
            gap: 70px;
        }

        .footer-col h5 {
            color: white;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .footer-col ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-col ul li {
            margin-bottom: 14px;
        }

        .footer-col ul li a {
            color: #94a3b8;
            font-size: 14px;
            transition: color 0.2s;
        }

        .footer-col ul li a:hover {
            color: white;
        }

        .footer-subscribe {
            max-width: 300px;
        }

        .footer-subscribe h5 {
            color: white;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .footer-subscribe p {
            font-size: 13.5px;
            color: #94a3b8;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .subscribe-form {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 100px;
            padding: 4px;
        }

        .subscribe-form input {
            background: transparent;
            border: none;
            outline: none;
            color: white;
            padding: 8px 16px;
            font-size: 14px;
            width: 100%;
        }

        .subscribe-form input::placeholder {
            color: #64748b;
        }

        .subscribe-form button {
            background: var(--primary);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
            flex-shrink: 0;
        }

        .subscribe-form button:hover {
            background: var(--primary-dark);
        }

        .subscribe-form button svg {
            width: 16px;
            height: 16px;
        }

        .footer-waves {
            position: relative;
            height: 180px;
            margin-top: 40px;
            background-image:
                url("data:image/svg+xml,%3Csvg width='1200' height='180' viewBox='0 0 1200 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 90 Q 200 30, 400 90 T 800 90 T 1200 90' fill='none' stroke='rgba(26, 95, 224, 0.25)' stroke-width='1.5'/%3E%3Cpath d='M0 110 Q 300 180, 600 110 T 1200 110' fill='none' stroke='rgba(26, 95, 224, 0.15)' stroke-width='1.5'/%3E%3Cpath d='M0 70 Q 250 10, 500 70 T 1000 70 T 1200 70' fill='none' stroke='rgba(255, 255, 255, 0.05)' stroke-width='1'/%3E%3C/svg%3E");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .wave-tag {
            position: absolute;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 12.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(4px);
        }

        .wave-tag.plan {
            top: 30%;
            left: 15%;
        }

        .wave-tag.collaborate {
            top: 65%;
            left: 38%;
        }

        .wave-tag.execute {
            top: 75%;
            left: 63%;
        }

        .wave-tag.grow {
            top: 25%;
            left: 82%;
        }

        .wave-tag svg {
            width: 14px;
            height: 14px;
            color: var(--primary);
        }

        .wave-tag.execute svg {
            color: var(--primary-dark);
        }

        .wave-point {
            position: absolute;
            width: 6px;
            height: 6px;
            background: var(--primary);
            border-radius: 50%;
            box-shadow: 0 0 10px 2px rgba(26, 95, 224, 0.5);
        }

        .point-1 {
            top: 46%;
            left: 20%;
        }

        .point-2 {
            top: 46%;
            left: 30%;
            background: var(--primary-dark);
            box-shadow: 0 0 10px 2px rgba(21, 71, 184, 0.5);
        }

        .point-3 {
            top: 70%;
            left: 56%;
        }

        .point-4 {
            top: 60%;
            left: 83%;
            background: var(--primary-dark);
            box-shadow: 0 0 10px 2px rgba(21, 71, 184, 0.5);
        }

        .footer-bottom-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 13px;
            color: #64748b;
        }

        .built-text {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .built-line {
            width: 60px;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        @media(max-width: 1024px) {
            .footer-links-group {
                gap: 40px;
                flex-wrap: wrap;
            }
        }

        @media(max-width: 768px) {
            .footer-top {
                flex-direction: column;
            }

            .footer-bottom-bar {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
        }

        /* ── Responsive ── */
        @media(max-width: 1024px) {

            .hero-grid,
            .integration-box,
            .features-grid,
            .pricing-grid,
            .stats-row,
            .brand-grid {
                grid-template-columns: 1fr 1fr;
            }

            .hero h1 {
                font-size: 44px;
            }
        }

        @media(max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero-grid,
            .integration-box,
            .features-grid,
            .pricing-grid,
            .stats-row,
            .brand-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 36px;
            }

            .section-title h2,
            .cta-box h2 {
                font-size: 32px;
            }

            .pricing-card.popular {
                transform: none;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

    {{-- ── Navbar ── --}}
    <header>
        <div class="navbar">
            <a href="/" class="logo">
                <img src="{{ asset('flovig_logo.png') }}" alt="Flovig"
                    style="height:32px;width:auto;object-fit:contain;">
            </a>

            <nav class="nav-links">
                <a href="#fitur">Fitur</a>
                <a href="#modul">Modul</a>
                <a href="#harga">Harga</a>
                <a href="#kontak">Kontak</a>
            </nav>

            <div class="nav-buttons">
                <a href="{{ route('login') }}" class="log-in">Masuk</a>
                <a href="{{ route('register') }}" class="btn-nav-primary">Coba Gratis</a>
            </div>
        </div>
    </header>

    {{-- ── Hero ── --}}
    <section class="hero">
        <div class="container hero-grid">

            <div>
                <div class="badge"><span class="badge-dot"></span> Dipakai tim project & operasional di seluruh
                    Indonesia</div>

                <h1>Satu platform untuk <span>project</span> & tim Anda.</h1>

                <p>
                    Flovig menyatukan manajemen project, CRM, invoicing, bug tracker,
                    dan komunikasi tim dalam satu workspace.
                    Lebih sederhana, lebih cepat, lebih hemat.
                </p>

                <div class="hero-buttons">
                    <a href="{{ route('register') }}" class="btn btn-primary">Mulai Gratis 14 Hari</a>
                    <a href="{{ route('login') }}" class="btn btn-outline">Lihat Demo</a>
                </div>

                <div class="hero-note">Tanpa kartu kredit &bull; Setup kurang dari 5 menit</div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-title">Dashboard Project</div>

                <div class="stats-grid">
                    <div class="stat-box">
                        <h3>37</h3>
                        <p>Project Aktif</p>
                    </div>
                    <div class="stat-box">
                        <h3>248</h3>
                        <p>Anggota Tim</p>
                    </div>
                    <div class="stat-box">
                        <h3>94%</h3>
                        <p>Task Selesai</p>
                    </div>
                </div>

                <div class="progress-item">
                    <div class="progress-head">
                        <span>Website Redesign</span>
                        <span>82%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:82%"></div>
                    </div>
                </div>

                <div class="progress-item">
                    <div class="progress-head">
                        <span>Mobile App v2</span>
                        <span>64%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:64%"></div>
                    </div>
                </div>

                <div class="progress-item">
                    <div class="progress-head">
                        <span>API Integration</span>
                        <span>35%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:35%"></div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- ── Brands ── --}}
    <section class="brands">
        <div class="container">
            <p>Dipercaya oleh tim di seluruh Indonesia</p>
            <div class="brand-grid">
                <div class="brand-item">Acme Corp</div>
                <div class="brand-item">Nusantara</div>
                <div class="brand-item">Skyline</div>
                <div class="brand-item">Bumi Tech</div>
                <div class="brand-item">Kiranatama</div>
                <div class="brand-item">Lentera</div>
            </div>
        </div>
    </section>

    {{-- ── Fitur ── --}}
    <section id="fitur">
        <div class="container">
            <div class="section-title">
                <h2>Semua yang tim Anda butuhkan</h2>
                <p>Berhenti bayar banyak software berbeda. Flovig menggantikan seluruh stack manajemen proyek Anda.</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="5" height="16" rx="1.5" />
                            <rect x="9.5" y="4" width="5" height="10" rx="1.5" />
                            <rect x="16" y="4" width="5" height="13" rx="1.5" />
                        </svg>
                    </div>
                    <h3>Manajemen Project</h3>
                    <p>Kanban board, sprint planning, milestone tracking, dan timeline dalam satu tampilan intuitif.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3.5" y="3.5" width="17" height="17" rx="4" />
                            <path d="M8 12.5l2.5 2.5L16 9.5" />
                        </svg>
                    </div>
                    <h3>Task Management</h3>
                    <p>Assign task, set deadline, track progress, dan kelola dependensi antar task dengan mudah.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="8" r="3" />
                            <path d="M3.5 20c0-3.3 2.5-6 5.5-6s5.5 2.7 5.5 6" />
                            <circle cx="17.5" cy="9.5" r="2.5" />
                            <path d="M15.5 14.2c2.4.3 4.5 2.5 4.5 5.8" />
                        </svg>
                    </div>
                    <h3>CRM & Leads</h3>
                    <p>Kelola kontak, pipeline penjualan, dan kampanye marketing dari satu dashboard terintegrasi.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3.5" y="4.5" width="17" height="12" rx="3.5" />
                            <path d="M8 20l2.5-3.5" />
                            <path d="M8 9.5h8M8 12.5h5" />
                        </svg>
                    </div>
                    <h3>Chat Real-time</h3>
                    <p>Diskusi per project tanpa keluar dari platform. Kirim file, reaksi, dan mention anggota tim.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 3.5h8l4 4v13a1 1 0 01-1 1H6a1 1 0 01-1-1v-16a1 1 0 011-1z" />
                            <path d="M14 3.5v4h4" />
                            <path d="M8.5 10h7M8.5 13.5h7M8.5 17h4" />
                        </svg>
                    </div>
                    <h3>Invoice & Budget</h3>
                    <p>Buat invoice profesional, track pembayaran, dan pantau anggaran project secara real-time.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3l7 3v6c0 5-3 8.5-7 9.5-4-1-7-4.5-7-9.5V6l7-3z" />
                            <path d="M9.5 12l2 2 3.5-3.5" />
                        </svg>
                    </div>
                    <h3>Keamanan Enterprise</h3>
                    <p>Enkripsi data, role-based access, dan audit log lengkap untuk kepatuhan perusahaan Anda.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Integration / Modul ── --}}
    <section class="integration" id="modul">
        <div class="container">
            <div class="section-title">
                <h2>Project dan Tim, akhirnya bicara satu bahasa.</h2>
                <p>Data anggota tim, beban kerja, dan progres project hidup di satu workspace terpadu.</p>
            </div>

            <div class="integration-box">
                <div class="team-list">
                    <div class="team-member">
                        <div class="member-info">
                            <div class="avatar">S</div>
                            <div>
                                <strong>Sari Wijaya</strong>
                                <div class="member-role">Lead Designer</div>
                            </div>
                        </div>
                        <div class="status-badge">Aktif</div>
                    </div>
                    <div class="team-member">
                        <div class="member-info">
                            <div class="avatar">B</div>
                            <div>
                                <strong>Budi Santoso</strong>
                                <div class="member-role">Backend Engineer</div>
                            </div>
                        </div>
                        <div class="status-badge status-cuti">On Leave</div>
                    </div>
                    <div class="team-member">
                        <div class="member-info">
                            <div class="avatar">M</div>
                            <div>
                                <strong>Maya Putri</strong>
                                <div class="member-role">Project Manager</div>
                            </div>
                        </div>
                        <div class="status-badge">Aktif</div>
                    </div>
                </div>

                <div class="integration-content">
                    <h3>Kolaborasi tanpa batas antar divisi</h3>
                    <p>Semua informasi tim tersedia real-time sehingga keputusan lebih cepat dan tepat.</p>
                    <ul>
                        <li>Assign task langsung ke anggota tim yang tepat</li>
                        <li>Workload otomatis terpantau per orang</li>
                        <li>Timesheet project terhubung ke laporan kinerja</li>
                        <li>Satu login, satu billing, satu support</li>
                    </ul>
                </div>
            </div>

            <div class="stats-row">
                <div class="big-stat">
                    <h3>500+</h3>
                    <p>Perusahaan</p>
                </div>
                <div class="big-stat">
                    <h3>50K+</h3>
                    <p>Task diselesaikan</p>
                </div>
                <div class="big-stat">
                    <h3>99.9%</h3>
                    <p>Uptime</p>
                </div>
                <div class="big-stat">
                    <h3>4.9/5</h3>
                    <p>Rating pengguna</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Harga ── --}}
    <section id="harga">
        <div class="container">
            <div class="section-title">
                <h2>Sederhana, transparan, terjangkau.</h2>
                <p>Pilih paket sesuai ukuran tim Anda. Ganti kapan saja.</p>
            </div>

            <div class="pricing-grid">
                @foreach($pricingTiers as $tier)
                    <div class="pricing-card @if($tier->is_popular) popular @endif">
                        @if($tier->is_popular)
                            <div class="popular-badge">Paling Populer</div>
                        @endif
                        <h3>{{ $tier->name }}</h3>
                        <p>{{ $tier->tagline }}</p>
                        <div class="price">{{ $tier->priceDisplay() }}</div>
                        <div class="price-note">{{ $tier->price_period }}</div>
                        <a href="{{ $tier->cta_type === 'contact' ? 'mailto:sales@projecthubpro.id' : route('register', ['plan' => $tier->slug]) }}"
                            class="btn {{ $tier->is_popular ? 'btn-primary' : 'btn-outline' }}">{{ $tier->cta_label }}</a>
                        <ul>
                            @foreach($tier->features as $feature)
                                <li>✓ {{ $feature->label }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── CTA ── --}}
    <section class="cta">
        <div class="container">
            <div class="cta-box">
                <h2>Siap menyederhanakan operasi tim Anda?</h2>
                <p>Coba Flovig gratis 14 hari. Tanpa kartu kredit, tanpa komitmen.</p>
                <a href="{{ route('register') }}" class="btn btn-white">Mulai Sekarang</a>
            </div>
        </div>
    </section>

    {{-- ── Footer ── --}}
    <footer id="kontak">
        <div class="footer-container">
            <div class="footer-top">
                <div class="footer-brand">
                    <a href="/" class="logo"
                        style="margin-bottom: 20px; display: inline-block; background: white; padding: 10px 16px; border-radius: 12px;">
                        <img src="{{ asset('flovig_logo.png') }}" alt="Flovig"
                            style="height:32px;width:auto;object-fit:contain;">
                    </a>
                    <h4>Modern Workflow Intelligence<br>for a More Productive Tomorrow.</h4>
                    <p>Flovig helps teams plan, collaborate, and get things done — with smarter workflows and AI-powered
                        insights.</p>
                    <div class="social-icons">
                        <a href="#"><svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                            </svg></a>
                        <a href="#"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                            </svg></a>
                        <a href="#"><svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.5 12 3.5 12 3.5s-7.505 0-9.377.55a3.015 3.015 0 0 0-2.122 2.136C0 8.07 0 12 0 12s0 3.93.501 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.55 9.377.55 9.377.55s7.505 0 9.377-.55a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                            </svg></a>
                        <a href="#"><svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                            </svg></a>
                    </div>
                </div>

                <div class="footer-links-group">
                    <div class="footer-col">
                        <h5>Produk</h5>
                        <ul>
                            <li><a href="#fitur">Fitur</a></li>
                            <li><a href="#modul">Modul</a></li>
                            <li><a href="#harga">Harga</a></li>
                            <li><a href="#"
                                    onclick="alert('Fitur masih tahap development'); return false;">Integrasi</a></li>
                            <li><a href="#" onclick="alert('Fitur masih tahap development'); return false;">Roadmap</a>
                            </li>
                            <li><a href="#"
                                    onclick="alert('Fitur masih tahap development'); return false;">Changelog</a></li>
                        </ul>
                    </div>

                    <div class="footer-col">
                        <h5>Perusahaan</h5>
                        <ul>
                            <li><a href="#" onclick="alert('Fitur masih tahap development'); return false;">Tentang
                                    Kami</a></li>
                            <li><a href="#" onclick="alert('Fitur masih tahap development'); return false;">Karir</a>
                            </li>
                            <li><a href="#" onclick="alert('Fitur masih tahap development'); return false;">Blog</a>
                            </li>
                            <li><a href="#" onclick="alert('Fitur masih tahap development'); return false;">Partner</a>
                            </li>
                            <li><a href="mailto:support@projecthubpro.id">Kontak</a></li>
                        </ul>
                    </div>

                    <div class="footer-col">
                        <h5>Sumber Daya</h5>
                        <ul>
                            <li><a href="#" onclick="alert('Fitur masih tahap development'); return false;">Pusat
                                    Bantuan</a></li>
                            <li><a href="#"
                                    onclick="alert('Fitur masih tahap development'); return false;">Dokumentasi</a></li>
                            <li><a href="#" onclick="alert('Fitur masih tahap development'); return false;">Panduan</a>
                            </li>
                            <li><a href="#" onclick="alert('Fitur masih tahap development'); return false;">Webinar</a>
                            </li>
                            <li><a href="#"
                                    onclick="alert('Fitur masih tahap development'); return false;">Komunitas</a></li>
                        </ul>
                    </div>

                    <div class="footer-col">
                        <h5>Legal</h5>
                        <ul>
                            <li><a href="#" onclick="alert('Fitur masih tahap development'); return false;">Syarat
                                    Ketentuan</a></li>
                            <li><a href="#" onclick="alert('Fitur masih tahap development'); return false;">Kebijakan
                                    Privasi</a></li>
                            <li><a href="#" onclick="alert('Fitur masih tahap development'); return false;">Pengaturan
                                    Cookie</a></li>
                        </ul>
                    </div>
                </div>

                <div class="footer-subscribe">
                    <h5>Stay Updated</h5>
                    <p>Get the latest updates, tips and product news from Flovig.</p>
                    <div class="subscribe-form">
                        <input type="email" placeholder="Enter your email address">
                        <button type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg></button>
                    </div>
                </div>
            </div>

            <div class="footer-waves">
                <div class="wave-tag plan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg> Plan</div>
                <div class="wave-tag collaborate"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg> Collaborate</div>
                <div class="wave-tag execute"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg> Execute</div>
                <div class="wave-tag grow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg> Grow</div>

                <div class="wave-point point-1"></div>
                <div class="wave-point point-2"></div>
                <div class="wave-point point-3"></div>
                <div class="wave-point point-4"></div>
            </div>

            <div class="footer-bottom-bar">
                <div class="copyright">
                    © 2025 Flovig. All rights reserved.
                </div>
                <div class="built-text">
                    Built for better work. <div class="built-line"></div>
                </div>
            </div>
        </div>
    </footer>

</body>

</html>