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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            height: 100vh; /* full viewport height */
            background-color: #017787;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: sticky;
            top: 0;
            transition: all 0.3s;
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
            transition: all 0.3s;
        }
        .sidebar-divider {
            border-top: 1px solid rgba(255,255,255,0.3);
            margin: 1rem 0;
        }
        .sidebar-scrollable {
            overflow-y: auto;
            flex-grow: 1;
        }

        /* Perbesar Role Badge di sidebar */
        .sidebar .badge {
            font-size: 0.95rem; /* lebih besar dari default */
            padding: 0.5em 0.75em; /* lebih lega */
            border-radius: 0.5rem; /* rounded lebih jelas */
            font-weight: 600; /* lebih tegas */
        }


        /* App Bar */
        .app-bar {
            height: 60px;
            background-color: #017787; /* sama dengan sidebar */
            color: #fff;
            display: flex;
            align-items: center;
            padding: 0 20px;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .app-bar .left-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .app-bar button {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.4rem;
        }

        /* Pastikan tombol logout selalu merah */
        .app-bar .btn-danger {
            background-color: #dc3545; /* merah Bootstrap default */
            border-color: #dc3545;
            color: #fff;
        }

        .app-bar .btn-danger:hover,
        .app-bar .btn-danger:focus,
        .app-bar .btn-danger:active {
            background-color: #c82333; /* versi lebih gelap saat hover/active */
            border-color: #bd2130;
            color: #fff;
        }

        /* Samakan tinggi dan style tombol logout dengan info user */
        .app-bar .user-logout-container {
            display: flex;
            align-items: center;
            background-color: #dc3545; /* merah tetap muncul */
            color: #fff;
            padding: 0 12px; /* sama seperti info user */
            height: 38px; /* tinggi kontainer info user */
            border-radius: 50px; /* rounded pill */
            font-size: 0.9rem; /* sama dengan info user */
            gap: 6px;
        }

        .app-bar .user-logout-container i {
            font-size: 1rem; /* ukuran icon seragam */
            line-height: 1;
        }

        .app-bar .user-logout-container button {
            background: none;
            border: none;
            color: #fff;
            padding: 0;
            margin: 0;
            font-size: inherit;
            cursor: pointer;
        }

        .app-bar .user-logout-container button:focus {
            outline: none;
        }


        /* Content area */
        .content {
            flex-grow: 1;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
            overflow-x: auto;
            transition: margin-left 0.3s;
        }

        /* Copyright */
        .copyright {
            font-size: 0.85rem;
            text-align: center;
            margin-top: 10px;
            color: rgba(255,255,255,0.8);
        }

        /* Sidebar hidden */
        .sidebar.hidden {
            display: none;
        }
        .content.full-width {
            margin-left: 0;
        }
    </style>
</head>

<body>
<div class="d-flex">

    <!-- Sidebar -->
    <div class="sidebar p-4 text-white" id="sidebar">
        <div class="sidebar-scrollable">
            <!-- Logo & Header -->
            <div class="text-center mb-3">
                <img src="{{ asset('assets/images/logo-gaspul.png') }}" alt="Logo Gaspul" class="logo mb-2">
                <h5 class="fw-bold mb-1">Admin GASPUL</h5>
                @auth
                    <span class="badge
                        @if(Auth::user()->role === 'superadmin') bg-danger
                        @elseif(Auth::user()->role === 'admin') bg-success
                        @elseif(Auth::user()->role === 'operator') bg-info
                        @else bg-secondary @endif
                    ">
                        {{ ucfirst(Auth::user()->role) }}
                    </span>
                @endauth
            </div>
            <div class="sidebar-divider"></div>

            <!-- Sidebar Navigation Menu -->
            <p class="fw-bold mt-4">Menu</p>
            <ul class="nav flex-column ms-2">
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->is('admin/statistik*') ? 'active' : '' }}"
                       href="{{ route('admin.statistik') }}">
                        <i class="bi bi-bar-chart-line me-2"></i> <span>Statistik</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->is('admin/dashboard*') ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-card-checklist me-2"></i> <span>Daftar Antrian</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->is('admin/konsultasi*') ? 'active' : '' }}"
                       href="{{ route('admin.konsultasi') }}">
                        <i class="bi bi-chat-dots me-2"></i> <span>Layanan Konsultasi</span>
                    </a>
                </li>

                {{-- Monitor Antrian: Accessible by Superadmin, Admin, and Operator --}}
                @if(in_array(Auth::user()->role, ['superadmin', 'admin', 'operator']))
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->is('admin/monitor*') ? 'active' : '' }}"
                    href="{{ route('admin.monitor') }}">
                        <i class="bi bi-display me-2"></i> <span>Monitor Antrian</span>
                    </a>
                </li>
                @endif

                {{-- User Management: Only Superadmin --}}
                @if(Auth::user()->role === 'superadmin')
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}"
                       href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people me-2"></i> <span>User Management</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>

        <div class="copyright mt-4">
            © Sistem Informasi dan Data
        </div>
    </div>

    <!-- Main content wrapper -->
    <div class="d-flex flex-column flex-grow-1">
        <!-- App Bar -->
        <div class="app-bar">
            <div class="left-section">
                <button id="toggleSidebar" title="Toggle Sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <span>Dashboard Admin</span>
            </div>

<!-- User info + Logout kanan -->
<div class="d-flex align-items-center gap-2">
    @auth
    <div class="d-flex align-items-center bg-white text-dark px-3 py-1 rounded-pill shadow-sm">
        <i class="bi bi-person-fill me-2"></i>
        <span class="fw-semibold">{{ Auth::user()->name }} ({{ Auth::user()->nip }})</span>
    </div>

    <!-- Logout button versi kontainer sama ukuran dengan info user -->
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
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Toggle sidebar
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('mainContent');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('hidden');
        content.classList.toggle('full-width');
    });
</script>
</body>
</html>
