<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SweetNest') - Order &amp; Inventory Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #e11d48;
            --primary-hover: #be123c;
            --primary-light: #fff1f2;
            --primary-mid: #fecdd3;
            --secondary: #fb7185;
            --bg: #f5f5f7;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-sub: #1e293b;
            --text-muted: #64748b;
            --text-faint: #94a3b8;
            --border: #e2e8f0;
            --border-hover: #cbd5e1;
            --success: #10b981;
            --success-bg: #d1fae5;
            --success-border: #a7f3d0;
            --warning: #f59e0b;
            --warning-bg: #fef3c7;
            --warning-border: #fde68a;
            --danger: #ef4444;
            --danger-bg: #fee2e2;
            --danger-border: #fecaca;
            --info: #0284c7;
            --info-bg: #e0f2fe;
            --info-border: #bae6fd;
            --purple: #7c3aed;
            --purple-bg: #ede9fe;
            --radius-sm: 8px;
            --radius: 14px;
            --radius-lg: 20px;
            --radius-xl: 28px;
            --shadow-xs: 0 1px 2px 0 rgb(0 0 0 / 0.04);
            --shadow-sm: 0 2px 6px -1px rgb(0 0 0 / 0.06), 0 1px 4px -2px rgb(0 0 0 / 0.04);
            --shadow: 0 4px 16px -2px rgb(0 0 0 / 0.08), 0 2px 8px -2px rgb(0 0 0 / 0.04);
            --shadow-md: 0 10px 24px -4px rgb(0 0 0 / 0.1), 0 4px 10px -4px rgb(0 0 0 / 0.05);
            --shadow-lg: 0 20px 40px -8px rgb(0 0 0 / 0.12), 0 8px 20px -6px rgb(0 0 0 / 0.06);
            --nav-height: 66px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        /* === NAVBAR === */
        .navbar {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(226,232,240,0.8);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 0 rgba(0,0,0,0.04);
        }
        .nav-container {
            max-width: 1360px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: var(--nav-height);
            gap: 1rem;
        }
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            flex-shrink: 0;
        }
        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #e11d48, #fb923c);
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(225,29,72,0.35);
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        .brand-logo:hover .brand-icon { transform: rotate(-6deg) scale(1.05); }
        .brand-name {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.02em;
            display: block;
            line-height: 1.1;
        }
        .brand-sub {
            font-size: 0.67rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            display: block;
        }
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2px;
            list-style: none;
            flex: 1;
            justify-content: center;
        }
        .nav-link {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.855rem;
            padding: 7px 12px;
            border-radius: 9px;
            transition: all 0.18s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            position: relative;
            white-space: nowrap;
        }
        .nav-link:hover { color: var(--primary); background-color: var(--primary-light); }
        .nav-link.active {
            color: var(--primary);
            background-color: var(--primary-light);
            font-weight: 700;
        }
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 2.5px;
            background: var(--primary);
            border-radius: 999px;
        }
        .user-section { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .user-info-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px 5px 5px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 999px;
        }
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e11d48, #fb923c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.72rem;
            font-weight: 800;
            flex-shrink: 0;
        }
        .user-name-text {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-main);
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .role-badge {
            font-size: 0.65rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-owner { background: linear-gradient(135deg,#fee2e2,#fecdd3); color: #9f1239; border: 1px solid #fca5a5; }
        .badge-staff { background: linear-gradient(135deg,#dbeafe,#bfdbfe); color: #1e40af; border: 1px solid #93c5fd; }
        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: none;
            border: 1px solid var(--border);
            padding: 7px 13px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            border-radius: 9px;
            cursor: pointer;
            transition: all 0.18s;
            font-family: inherit;
        }
        .btn-logout:hover { background: #fee2e2; border-color: #fca5a5; color: var(--primary); }
        .mobile-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            background: none;
            border: 1px solid var(--border);
            font-size: 18px;
            cursor: pointer;
            color: var(--text-main);
            padding: 7px 10px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .mobile-toggle:hover { background: var(--primary-light); border-color: var(--primary-mid); color: var(--primary); }

        /* === LAYOUT === */
        .main-container {
            max-width: 1360px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            flex: 1;
            animation: pageFadeIn 0.22s ease-out;
        }
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.75rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .page-title {
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.03em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-subtitle { font-size: 0.875rem; color: var(--text-muted); margin-top: 3px; font-weight: 500; }

        /* === ALERTS === */
        .alert {
            padding: 13px 18px;
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
            animation: alertSlideDown 0.3s ease-out;
        }
        @keyframes alertSlideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .alert-icon { font-size: 1.05rem; flex-shrink: 0; }
        .alert-success { background: var(--success-bg); color: #064e3b; border-color: var(--success-border); }
        .alert-error   { background: var(--danger-bg);  color: #7f1d1d; border-color: var(--danger-border); }
        .alert-warning { background: var(--warning-bg); color: #78350f; border-color: var(--warning-border); }
        .alert-info    { background: var(--info-bg);    color: #0c4a6e; border-color: var(--info-border); }

        /* === CARDS === */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: box-shadow 0.2s ease;
        }
        .card:hover { box-shadow: var(--shadow-md); }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .card-title { font-size: 1rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px; }

        /* === STAT CARDS === */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--stat-accent, linear-gradient(90deg, #e11d48, #fb923c));
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: var(--stat-icon-bg, var(--primary-light));
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        .stat-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 900;
            color: var(--text-main);
            letter-spacing: -0.04em;
            line-height: 1;
        }
        .stat-change { font-size: 0.72rem; font-weight: 600; margin-top: 5px; color: var(--text-muted); }

        /* === BUTTONS === */
        .btn-primary, .btn-secondary, .btn-danger, .btn-success, .btn-warning, .btn-info {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-family: inherit;
            font-size: 0.86rem;
            font-weight: 700;
            padding: 9px 18px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.18s ease;
            line-height: 1.4;
            white-space: nowrap;
        }
        .btn-primary {
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: #fff;
            box-shadow: 0 4px 14px rgba(225,29,72,0.28);
        }
        .btn-primary:hover { background: linear-gradient(135deg,#be123c,#9f1239); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(225,29,72,0.38); color:#fff; }
        .btn-primary:active { transform: translateY(0); }
        .btn-secondary { background: #fff; color: #334155; border: 1.5px solid #cbd5e1; box-shadow: var(--shadow-xs); }
        .btn-secondary:hover { background: #f8fafc; border-color: #94a3b8; color: #0f172a; transform: translateY(-1px); }
        .btn-danger { background: linear-gradient(135deg,#ef4444,#dc2626); color: #fff; box-shadow: 0 4px 14px rgba(239,68,68,0.25); }
        .btn-danger:hover { background: linear-gradient(135deg,#dc2626,#b91c1c); transform: translateY(-1px); color:#fff; }
        .btn-success { background: linear-gradient(135deg,#10b981,#059669); color: #fff; box-shadow: 0 4px 14px rgba(16,185,129,0.25); }
        .btn-success:hover { background: linear-gradient(135deg,#059669,#047857); transform: translateY(-1px); color:#fff; }
        .btn-warning { background: linear-gradient(135deg,#f59e0b,#d97706); color: #fff; box-shadow: 0 4px 14px rgba(245,158,11,0.25); }
        .btn-warning:hover { background: linear-gradient(135deg,#d97706,#b45309); transform: translateY(-1px); color:#fff; }
        .btn-info { background: linear-gradient(135deg,#0284c7,#0369a1); color: #fff; box-shadow: 0 4px 14px rgba(2,132,199,0.22); }
        .btn-info:hover { background: linear-gradient(135deg,#0369a1,#075985); transform: translateY(-1px); color:#fff; }
        .btn-sm { padding: 6px 12px; font-size: 0.78rem; border-radius: 8px; }
        .btn-lg { padding: 12px 24px; font-size: 0.95rem; border-radius: 12px; }

        /* === FORMS === */
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.795rem; font-weight: 700; color: var(--text-sub); margin-bottom: 6px; }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1.5px solid #d1d5db;
            background: #fff;
            font-family: inherit;
            font-size: 0.875rem;
            color: var(--text-main);
            transition: all 0.18s ease;
            outline: none;
            appearance: none;
        }
        .form-input::placeholder, .form-textarea::placeholder { color: var(--text-faint); }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(225,29,72,0.12);
        }
        .form-input:hover:not(:focus), .form-select:hover:not(:focus), .form-textarea:hover:not(:focus) { border-color: #9ca3af; }
        .form-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z' clip-rule='evenodd'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
            cursor: pointer;
        }
        .input-error { border-color: #ef4444 !important; background: #fff5f5; }
        .input-error:focus { box-shadow: 0 0 0 3px rgba(239,68,68,0.12) !important; }
        .error-text { color: #dc2626; font-size: 0.775rem; font-weight: 600; margin-top: 5px; }

        /* === TABLES === */
        .table-responsive { overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch; }
        .table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .table th {
            text-align: left;
            padding: 11px 14px;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1.5px solid var(--border);
            background: #f9fafb;
            white-space: nowrap;
        }
        .table td { padding: 13px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .table tbody tr { transition: background-color 0.12s ease; }
        .table tbody tr:hover { background: #fff5f5; }
        .table tbody tr:last-child td { border-bottom: none; }

        /* === BADGES === */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            line-height: 1.3;
            white-space: nowrap;
        }
        .badge-success  { background: #d1fae5; color: #065f46; }
        .badge-danger   { background: #fee2e2; color: #991b1b; }
        .badge-warning  { background: #fef3c7; color: #92400e; }
        .badge-info     { background: #e0f2fe; color: #0369a1; }
        .badge-purple   { background: #ede9fe; color: #5b21b6; }
        .badge-neutral  { background: #f1f5f9; color: #475569; }
        .status-pending           { background: #fef9c3; color: #713f12; border: 1px solid #fde68a; }
        .status-confirmed         { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .status-in_production     { background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; }
        .status-ready_for_release { background: #d1fae5; color: #064e3b; border: 1px solid #a7f3d0; }
        .status-out_for_delivery  { background: #ffedd5; color: #9a3412; border: 1px solid #fdba74; }
        .status-completed         { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .status-cancelled         { background: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }

        /* === MODAL === */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.5);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }
        .modal-overlay.show { display: flex; }
        .modal-content {
            background: #fff;
            border-radius: var(--radius-lg);
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            animation: modalPop 0.22s cubic-bezier(0.34,1.56,0.64,1);
        }
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.92) translateY(12px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-header { font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.75rem; }

        /* === EMPTY STATE === */
        .empty-state { text-align: center; padding: 4rem 2rem; color: var(--text-muted); }
        .empty-state-icon { font-size: 3.5rem; margin-bottom: 1rem; opacity: 0.5; }
        .empty-state h3 { font-size: 1.05rem; font-weight: 700; color: var(--text-sub); margin-bottom: 0.4rem; }
        .empty-state p { font-size: 0.875rem; max-width: 320px; margin: 0 auto 1.25rem; }

        /* === TOAST === */
        #toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
        .toast {
            display: flex; align-items: center; gap: 10px;
            padding: 13px 18px;
            border-radius: var(--radius);
            background: #1e293b;
            color: #f8fafc;
            font-size: 0.86rem;
            font-weight: 600;
            box-shadow: var(--shadow-lg);
            min-width: 280px;
            max-width: 380px;
            animation: toastSlide 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }
        .toast.toast-success { background: #064e3b; border-left: 3px solid #10b981; }
        .toast.toast-error   { background: #7f1d1d; border-left: 3px solid #ef4444; }
        .toast.toast-info    { background: #0c4a6e; border-left: 3px solid #0284c7; }
        @keyframes toastSlide { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }

        /* === PAGINATION === */
        nav[role="navigation"] { margin-top: 1.5rem; }
        nav[role="navigation"] .flex { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
        nav[role="navigation"] p { font-size: 0.82rem; color: var(--text-muted); }
        nav[role="navigation"] span[aria-current="page"] span,
        nav[role="navigation"] a {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 36px; height: 36px; padding: 0 10px;
            font-size: 0.82rem; font-weight: 600;
            border-radius: 8px; text-decoration: none; transition: all 0.15s ease;
        }
        nav[role="navigation"] a { color: var(--text-muted); border: 1px solid var(--border); }
        nav[role="navigation"] a:hover { color: var(--primary); border-color: var(--primary-mid); background: var(--primary-light); }
        nav[role="navigation"] span[aria-current="page"] span { background: var(--primary); color: #fff; font-weight: 700; }
        nav[role="navigation"] span:not([aria-current="page"]) span { color: var(--text-faint); font-size: 0.82rem; }

        /* === BREADCRUMB === */
        .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.5rem; flex-wrap: wrap; }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; font-weight: 500; }
        .breadcrumb a:hover { color: var(--primary); }
        .breadcrumb .sep { color: var(--border-hover); }
        .breadcrumb .current { color: var(--text-main); font-weight: 700; }

        /* === DIVIDER === */
        .divider { height: 1px; background: var(--border); margin: 1.5rem 0; }

        /* === FOOTER === */
        .footer { background: var(--card-bg); border-top: 1px solid var(--border); padding: 1.1rem 0; text-align: center; font-size: 0.78rem; color: var(--text-faint); margin-top: auto; }
        .footer span { color: var(--primary); font-weight: 700; }

        /* === RESPONSIVE === */
        @media (max-width: 1024px) { .user-name-text { display: none; } }
        @media (max-width: 900px) {
            .mobile-toggle { display: flex; }
            .nav-menu {
                display: none; flex-direction: column;
                position: absolute; top: var(--nav-height); left: 0; right: 0;
                background: rgba(255,255,255,0.97); backdrop-filter: blur(20px);
                border-bottom: 1px solid var(--border);
                padding: 0.75rem 1rem; box-shadow: var(--shadow-md); gap: 2px;
            }
            .nav-menu.show { display: flex; }
            .nav-link { width: 100%; padding: 10px 14px; border-radius: 10px; }
            .nav-link.active::after { display: none; }
            .user-info-chip { display: none; }
        }
        @media (max-width: 640px) { .main-container { padding: 1.25rem 1rem; } .page-title { font-size: 1.3rem; } }

        /* === UTILITIES === */
        .text-primary { color: var(--primary); }
        .text-muted   { color: var(--text-muted); }
        .text-success { color: var(--success); }
        .text-danger  { color: var(--danger); }
        .text-warning { color: var(--warning); }
        .font-bold { font-weight: 700; }
        .font-black { font-weight: 900; }
        .text-sm { font-size: 0.82rem; }
        .text-xs { font-size: 0.72rem; }
        .no-print { }
        @media print { .no-print { display: none !important; } }
    </style>
    @yield('styles')
</head>
<body>
    @auth
    <nav class="navbar no-print">
        <div class="nav-container">
            <a href="{{ route('dashboard') }}" class="brand-logo">
                <div class="brand-icon">🍰</div>
                <div>
                    <span class="brand-name">SweetNest</span>
                    <span class="brand-sub">Cakes &amp; Pastries OIMS</span>
                </div>
            </a>

            <button class="mobile-toggle" onclick="toggleMobileMenu()" id="mobileToggle" aria-label="Menu">☰</button>

            <ul class="nav-menu" id="navMenu">
                <li><a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">⊞ Dashboard</a></li>
                @if(Auth::user()->isOwner() || Auth::user()->isStaff())
                <li><a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">👥 Customers</a></li>
                <li><a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">🎂 Orders</a></li>
                <li><a href="{{ route('payments.index') }}" class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">💳 Payments</a></li>
                <li><a href="{{ route('productions.index') }}" class="nav-link {{ request()->routeIs('productions.*') ? 'active' : '' }}">👩‍🍳 Production</a></li>
                <li><a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') || request()->routeIs('audits.*') ? 'active' : '' }}">📦 Inventory</a></li>
                @endif
                @if(Auth::user()->isOwner())
                <li><a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">📊 Reports</a></li>
                <li><a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">🔑 Users</a></li>
                @endif
            </ul>

            <div class="user-section">
                <div class="user-info-chip">
                    <div class="user-avatar">{{ strtoupper(substr(Auth::user()->first_name ?? 'U', 0, 1)) }}</div>
                    <span class="user-name-text">{{ Auth::user()->first_name ?? Auth::user()->name }}</span>
                    <span class="role-badge badge-{{ Auth::user()->role }}">{{ strtoupper(Auth::user()->role) }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn-logout">↩ Log Out</button>
                </form>
            </div>
        </div>
    </nav>
    @endauth

    <main class="main-container">
        @if(session('success'))
            <div class="alert alert-success" role="alert"><span class="alert-icon">✓</span><span>{{ session('success') }}</span></div>
        @endif
        @if(session('error'))
            <div class="alert alert-error" role="alert"><span class="alert-icon">✕</span><span>{{ session('error') }}</span></div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning" role="alert"><span class="alert-icon">⚠</span><span>{{ session('warning') }}</span></div>
        @endif
        @yield('content')
    </main>

    <footer class="footer no-print">
        <p>© 2026 <span>SweetNest</span> Homemade Cakes and Pastries &nbsp;•&nbsp; Tugbok, Davao City &nbsp;•&nbsp; IT12/L Academic Project</p>
    </footer>

    <div id="toast-container"></div>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('navMenu');
            const toggle = document.getElementById('mobileToggle');
            const isOpen = menu.classList.toggle('show');
            toggle.textContent = isOpen ? '✕' : '☰';
        }
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('navMenu');
            const toggle = document.getElementById('mobileToggle');
            if (menu && !menu.contains(e.target) && toggle && !toggle.contains(e.target)) {
                menu.classList.remove('show');
                toggle.textContent = '☰';
            }
        });
        function showToast(message, type, duration) {
            type = type || 'info'; duration = duration || 4000;
            var container = document.getElementById('toast-container');
            var toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            var icons = { success: '✓', error: '✕', info: 'ℹ' };
            toast.innerHTML = '<span>' + (icons[type] || 'ℹ') + '</span><span>' + message + '</span>';
            container.appendChild(toast);
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(20px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(function() { toast.remove(); }, 300);
            }, duration);
        }
        document.querySelectorAll('.alert').forEach(function(alert) {
            setTimeout(function() {
                alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-4px)';
                setTimeout(function() { if(alert.parentNode) alert.remove(); }, 400);
            }, 5000);
        });
    </script>
    @yield('scripts')
</body>
</html>
