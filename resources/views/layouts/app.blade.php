<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SweetNest') - Order & Inventory Management System</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #e11d48;
            --primary-hover: #be123c;
            --primary-light: #ffe4e6;
            --secondary: #f43f5e;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --border-focus: #fda4af;
            --success: #10b981;
            --success-bg: #d1fae5;
            --warning: #f59e0b;
            --warning-bg: #fef3c7;
            --danger: #ef4444;
            --danger-bg: #fee2e2;
            --info: #0284c7;
            --info-bg: #e0f2fe;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
            --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.08), 0 4px 6px -4px rgb(0 0 0 / 0.04);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
        .navbar {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.05);
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary), #fb7185);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 20px;
            box-shadow: 0 4px 10px rgba(225, 29, 72, 0.3);
        }

        .brand-text h1 {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.1;
        }

        .brand-text span {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 6px;
            list-style: none;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.88rem;
            padding: 8px 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .nav-link:hover {
            color: var(--primary);
            background-color: var(--primary-light);
        }

        .nav-link.active {
            color: var(--primary);
            background-color: var(--primary-light);
            font-weight: 700;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .role-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .badge-owner { background-color: #fee2e2; color: #b91c1c; }
        .badge-staff { background-color: #dbeafe; color: #1d4ed8; }
        .badge-baker { background-color: #fef3c7; color: #b45309; }
        .badge-delivery { background-color: #d1fae5; color: #047857; }

        .btn-logout {
            background: none;
            border: 1px solid var(--border);
            padding: 7px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background-color: #fee2e2;
            border-color: #fca5a5;
            color: var(--primary);
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-main);
        }

        /* Container */
        .main-container {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            padding: 1.75rem 1.25rem;
            flex: 1;
        }

        /* Flash Alerts */
        .alert {
            padding: 12px 18px;
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: var(--success-bg);
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background-color: var(--danger-bg);
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Footer */
        .footer {
            background-color: var(--card-bg);
            border-top: 1px solid var(--border);
            padding: 1.25rem 0;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: auto;
        }

        @media (max-width: 900px) {
            .mobile-toggle {
                display: block;
            }

            .nav-menu {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 70px;
                left: 0;
                right: 0;
                background-color: var(--card-bg);
                border-bottom: 1px solid var(--border);
                padding: 1rem;
                box-shadow: var(--shadow-md);
            }

            .nav-menu.show {
                display: flex;
            }

            .nav-link {
                width: 100%;
                padding: 10px 14px;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    @auth
    <nav class="navbar">
        <div class="nav-container">
            <a href="{{ route('dashboard') }}" class="brand-logo">
                <div class="brand-icon">🍰</div>
                <div class="brand-text">
                    <h1>SweetNest</h1>
                    <span>Cakes & Pastries OIMS</span>
                </div>
            </a>

            <button class="mobile-toggle" onclick="toggleMenu()" aria-label="Toggle navigation">☰</button>

            <ul class="nav-menu" id="navMenu">
                <li>
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        Dashboard
                    </a>
                </li>

                @if(Auth::user()->isOwner() || Auth::user()->isStaff())
                <li>
                    <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                        Customers
                    </a>
                </li>
                <li>
                    <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                        Orders
                    </a>
                </li>
                <li>
                    <a href="{{ route('payments.index') }}" class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                        Payments
                    </a>
                </li>
                @endif

                @if(Auth::user()->isOwner() || Auth::user()->isBaker() || Auth::user()->isStaff())
                <li>
                    <a href="{{ route('productions.index') }}" class="nav-link {{ request()->routeIs('productions.*') ? 'active' : '' }}">
                        Production
                    </a>
                </li>
                @endif

                @if(Auth::user()->isOwner() || Auth::user()->isStaff() || Auth::user()->isBaker())
                <li>
                    <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') || request()->routeIs('audits.*') ? 'active' : '' }}">
                        Inventory
                    </a>
                </li>
                @endif

                @if(Auth::user()->isOwner() || Auth::user()->isDelivery())
                <li>
                    <a href="#" class="nav-link">
                        Delivery
                    </a>
                </li>
                @endif

                @if(Auth::user()->isOwner())
                <li>
                    <a href="#" class="nav-link">
                        Reports
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link">
                        Users
                    </a>
                </li>
                @endif
            </ul>

            <div class="user-section">
                <div style="text-align: right; display: none; sm:block;">
                    <div style="font-size: 0.85rem; font-weight: 700;">{{ Auth::user()->name }}</div>
                    <span class="role-badge badge-{{ Auth::user()->role }}">
                        {{ strtoupper(Auth::user()->role) }}
                    </span>
                </div>

                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn-logout">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </nav>
    @endauth

    <main class="main-container">
        @if(session('success'))
            <div class="alert alert-success">
                <span>✓</span> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                <span>✕</span> {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="footer">
        <p>© 2026 SweetNest Homemade Cakes and Pastries • Tugbok, Davao City • IT12/L Project</p>
    </footer>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('navMenu');
            if (menu) {
                menu.classList.toggle('show');
            }
        }
    </script>
    @yield('scripts')
</body>
</html>
