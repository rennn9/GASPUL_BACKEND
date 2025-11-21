<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin GASPUL</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden; /* ❗ agar scroll hanya di area konten */
        }

        /* Layout dasar */
        .layout-wrapper {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: #017787;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: #fff;
            font-weight: 500;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #026b72;
            border-radius: 6px;
        }
        .sidebar .logo {
            max-width: 120px;
        }
        .sidebar-divider {
            border-top: 1px solid rgba(255,255,255,0.3);
            margin: 1rem 0;
        }
        .sidebar .badge {
            font-size: 0.95rem;
            padding: 0.5em 0.75em;
            border-radius: 0.5rem;
            font-weight: 600;
        }
        .copyright {
            font-size: 0.85rem;
            text-align: center;
            margin-top: 10px;
            color: rgba(255,255,255,0.8);
        }

        /* App Bar */
        .app-bar {
            height: 60px;
            background-color: #017787;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            z-index: 900;
        }
        .app-bar button {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.4rem;
        }
        .app-bar .user-logout-container {
            display: flex;
            align-items: center;
            background-color: #dc3545;
            color: #fff;
            padding: 0 12px;
            height: 38px;
            border-radius: 50px;
            font-size: 0.9rem;
            gap: 6px;
        }
        .app-bar .user-logout-container button {
            background: none;
            border: none;
            color: #fff;
            padding: 0;
            cursor: pointer;
        }

        /* Konten */
        .content {
            position: absolute;
            top: 60px;
            left: 260px;
            right: 0;
            bottom: 0;
            background: #f8f9fa;
            padding: 20px;
            overflow: auto;
        }

        /* Sidebar hidden */
        .sidebar.hidden {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .content.full-width {
            left: 0;
            transition: left 0.3s ease;
        }

        /* Scrollbar rapi */
        ::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.2);
            border-radius: 5px;
        }
    </style>
</head>

<body>
<div class="layout-wrapper">
    <!-- Sidebar -->
    <div class="sidebar p-4 text-white" id="sidebar">
        <div>
            <div class="text-center mb-3">
                <img src="{{ asset('assets/images/logo-gaspul.png') }}" alt="Logo Gaspul" class="logo mb-2">
                <h5 class="fw-bold mb-1">Admin GASPUL</h5>
                @auth
                <span class="badge
                    @if(Auth::user()->role === 'superadmin') bg-danger
                    @elseif(Auth::user()->role === 'admin') bg-success
                    @elseif(Auth::user()->role === 'operator') bg-info
                    @else bg-secondary @endif">
                    {{ ucfirst(Auth::user()->role) }}
                </span>
                @endauth
            </div>
            <div class="sidebar-divider"></div>

            <p class="fw-bold mt-4">Menu</p>
            <ul class="nav flex-column ms-2">
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->is('admin/statistik*') ? 'active' : '' }}"
                       href="{{ route('admin.statistik') }}">
                        <i class="bi bi-bar-chart-line me-2"></i> Statistik
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->is('admin/dashboard*') ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-card-checklist me-2"></i> Daftar Antrian
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->is('admin/konsultasi*') ? 'active' : '' }}"
                       href="{{ route('admin.konsultasi') }}">
                        <i class="bi bi-chat-dots me-2"></i> Layanan Konsultasi
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->is('admin/survey*') ? 'active' : '' }}"
                       href="{{ route('admin.survey.index') }}">
                        <i class="bi bi-ui-checks-grid me-2"></i> Survey Kepuasan
                    </a>
                </li>

                @if(in_array(Auth::user()->role, ['superadmin', 'admin', 'operator']))
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->is('admin/monitor*') ? 'active' : '' }}"
                       href="{{ route('admin.monitor') }}">
                        <i class="bi bi-display me-2"></i> Monitor Antrian
                    </a>
                </li>
                @endif

<li class="nav-item mb-2">
    <a class="nav-link {{ request()->is('admin/monitor/settings') ? 'active' : '' }}"
       href="{{ route('admin.monitor.settings') }}">
        <i class="bi bi-gear me-2"></i> Pengaturan Monitor
    </a>
</li>


                @if(Auth::user()->role === 'superadmin')
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}"
                       href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people me-2"></i> User Management
                    </a>
                </li>
                @endif
            </ul>
        </div>

        <div class="copyright mt-4">© Sistem Informasi dan Data</div>
    </div>

    <!-- App Bar -->
    <div class="app-bar">
        <div class="left-section">
            <button id="toggleSidebar" title="Toggle Sidebar">
                <i class="bi bi-list"></i>
            </button>
            <span>Dashboard Admin</span>
        </div>

        <div class="d-flex align-items-center gap-2">
            @auth
            <div class="d-flex align-items-center bg-white text-dark px-3 py-1 rounded-pill shadow-sm">
                <i class="bi bi-person-fill me-2"></i>
                <span class="fw-semibold">{{ Auth::user()->name }} ({{ Auth::user()->nip }})</span>
            </div>

            <form action="{{ route('logout') }}" method="POST" class="mb-0">
                @csrf
                <div class="user-logout-container">
                    <i class="bi bi-box-arrow-right"></i>
                    <button type="submit">Logout</button>
                </div>
            </form>
            @endauth
        </div>
    </div>

    <!-- Content -->
    <div class="content" id="mainContent">
        @yield('content')
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('mainContent');
    const appBar = document.querySelector('.app-bar');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('hidden');
        content.classList.toggle('full-width');
        appBar.style.left = sidebar.classList.contains('hidden') ? '0' : '260px';
    });
</script>
</body>
</html>
