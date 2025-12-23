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
    overflow: hidden;
}

.layout-wrapper {
    display: flex;
    height: 100vh;
    width: 100vw;
    overflow: hidden;
}

/* Sidebar */
.sidebar {
    width: 220px;
    background-color: #017787;
    color: white;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 1000;
    transition: transform 0.3s ease;
}

.sidebar.hidden {
    transform: translateX(-100%);
}

/* Sidebar Header - Sticky */
.sidebar-header {
    position: sticky;
    top: 0;
    background-color: #017787;
    z-index: 10;
    padding: 14px;
    padding-bottom: 0;
}

.sidebar .logo {
    max-width: 90px;
}

.sidebar h5 {
    font-size: 1rem;
    margin-bottom: 4px !important;
}

.sidebar .badge {
    font-size: 0.7rem;
    padding: 4px 8px;
}

.sidebar-divider {
    border-top: 1px solid rgba(255,255,255,0.25);
    margin: 0.8rem 0;
}

/* Sidebar Menu Container - Scrollable */
.sidebar-menu {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0 14px;
    /* Hide scrollbar */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

.sidebar-menu::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

/* Scroll Indicators */
.scroll-indicator {
    position: absolute;
    left: 0;
    right: 0;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(to bottom, rgba(1, 119, 135, 0.9), rgba(1, 119, 135, 0));
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 5;
}

.scroll-indicator.show {
    opacity: 1;
}

.scroll-indicator-top {
    top: 120px; /* After header */
    background: linear-gradient(to bottom, rgba(1, 119, 135, 0.95), rgba(1, 119, 135, 0));
}

.scroll-indicator-bottom {
    bottom: 50px; /* Before footer */
    background: linear-gradient(to top, rgba(1, 119, 135, 0.95), rgba(1, 119, 135, 0));
}

.scroll-indicator i {
    color: white;
    font-size: 1.2rem;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-5px);
    }
    60% {
        transform: translateY(-3px);
    }
}

.scroll-indicator-bottom i {
    animation: bounceDown 2s infinite;
}

@keyframes bounceDown {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(5px);
    }
    60% {
        transform: translateY(3px);
    }
}

.sidebar-section-title {
    text-transform: uppercase;
    font-size: 0.7rem;
    opacity: 0.7;
    margin-top: 8px;
    margin-bottom: 2px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Sidebar Footer - Sticky */
.sidebar-footer {
    position: sticky;
    bottom: 0;
    background-color: #017787;
    z-index: 10;
    padding: 10px 14px;
}

/* Nav Link - More Compact */
.nav-link {
    color: #ffffff;
    padding: 6px 10px !important;
    font-size: 0.8rem;
    border-radius: 5px;
    transition: all 0.2s ease;
    line-height: 1.3;
}

.nav-link i {
    font-size: 0.9rem;
}

.nav-item {
    margin-bottom: 0 !important;
}

/* Tab default */
.nav-tabs .nav-link {
    color: #017787;          /* teks biru tua, kontras dengan putih */
    background-color: #ffffff; /* background putih */
    border: 1px solid #dee2e6;
    border-bottom-color: transparent;
    transition: all 0.2s ease;
}

/* Tab aktif */
.nav-tabs .nav-link.active {
    color: #ffffff;          /* teks putih saat aktif */
    background-color: #017787; /* background biru hijau */
    border-color: #017787 #017787 #ffffff;
}

.nav-link:hover,
.nav-link.active {
    background-color: #00a3af;
    color: #ffffff;
    font-weight: 500;
}

/* App Bar */
.app-bar {
    height: 55px;
    background-color: #017787;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    position: fixed;
    top: 0;
    left: 0; /* akan diatur JS */
    right: 0;
    z-index: 900;
    font-size: 0.95rem;
    transition: left 0.3s ease;
}

.app-bar button {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.2rem;
}

/* User Logout */
.user-logout-container {
    display: flex;
    align-items: center;
    background-color: #c82333;
    color: #fff;
    padding: 0 12px;
    height: 36px;
    border-radius: 50px;
    font-size: 0.85rem;
    gap: 6px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.user-logout-container:hover {
    background-color: #a71d2a;
}

.user-logout-container button {
    background: none;
    border: none;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    margin: 0;
}

/* Konten */
.content {
    position: absolute;
    top: 55px;
    left: 220px; /* akan diatur JS */
    right: 0;
    bottom: 0;
    background: #f8f9fa;
    padding: 16px;
    overflow: auto;
    transition: left 0.3s ease;
}

.content.full-width {
    left: 0 !important;
}

/* Tabel Responsif */
.responsive-table {
    width: 100%;
    table-layout: auto;
    border-collapse: collapse;
}

.responsive-table th {
    white-space: nowrap;
    padding: 6px 8px;
    font-size: 0.875rem;
    text-align: center;
}

.responsive-table td {
    padding: 6px 8px;
    font-size: 0.875rem;
    white-space: normal;
    word-break: break-word;
}

.responsive-table td select.status-dropdown {
    min-width: 120px;
    max-width: 180px;
    width: 100%;
}

.responsive-table td:nth-child(11) {
    white-space: nowrap;
    width: 1%;
}

.responsive-table td .btn {
    font-size: 0.8rem;
    padding: 2px 6px;
}

/* Responsive untuk layar kecil */
@media (max-width: 1200px) {
    .responsive-table th,
    .responsive-table td {
        font-size: 0.8rem;
        padding: 4px 6px;
    }
}

@media (max-width: 992px) {
    .responsive-table th,
    .responsive-table td {
        font-size: 0.75rem;
        padding: 3px 5px;
    }
}
</style>
</head>

<body>
<div class="layout-wrapper">

    <!-- Sidebar -->
    <div class="sidebar text-white" id="sidebar">
        <!-- Sticky Header -->
        <div class="sidebar-header">
            <div class="text-center mb-3">
                <img src="{{ asset('assets/images/logo-gaspul.png') }}" class="logo mb-2">
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
        </div>

        <!-- Scroll Indicator Top -->
        <div class="scroll-indicator scroll-indicator-top" id="scrollIndicatorTop">
            <i class="bi bi-chevron-up"></i>
        </div>

        <!-- Scrollable Menu -->
        <div class="sidebar-menu" id="sidebarMenu">
            @php $role = Auth::user()->role; @endphp

            {{-- Sidebar Links --}}
            @if(in_array($role, ['superadmin','admin','operator']))
            <p class="sidebar-section-title">Dashboard</p>
            <ul class="nav flex-column ms-2 mb-1">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/statistik*') ? 'active' : '' }}" href="{{ route('admin.statistik') }}">
                        <i class="bi bi-bar-chart-line me-2"></i> Statistik
                    </a>
                </li>
            </ul>
            @endif

            <p class="sidebar-section-title">Layanan</p>
            <ul class="nav flex-column ms-2 mb-1">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/layanan-publik*') ? 'active' : '' }}" href="{{ route('admin.layanan.index') }}">
                        <i class="bi bi-file-earmark-text me-2"></i> Layanan Publik
                    </a>
                </li>
                @if(in_array($role, ['superadmin','admin','operator']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/konsultasi*') ? 'active' : '' }}" href="{{ route('admin.konsultasi') }}">
                        <i class="bi bi-chat-dots me-2"></i> Konsultasi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/survey*') && !request()->is('admin/survey-templates*') && !request()->is('admin/survey-questions*') ? 'active' : '' }}" href="{{ route('admin.survey.index') }}">
                        <i class="bi bi-ui-checks-grid me-2"></i> Survey Kepuasan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/standar-pelayanan*') ? 'active' : '' }}" href="{{ route('admin.standar-pelayanan.index') }}">
                        <i class="bi bi-journal-check me-2"></i> Standar Pelayanan
                    </a>
                </li>
                @endif
            </ul>

            @if(in_array($role, ['superadmin','admin','operator']))
            <p class="sidebar-section-title">Antrian</p>
            <ul class="nav flex-column ms-2 mb-1">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/dashboard*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-card-checklist me-2"></i> Daftar Antrian
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/monitor*') ? 'active' : '' }}" href="{{ route('admin.monitor') }}">
                        <i class="bi bi-display me-2"></i> Monitor Antrian
                    </a>
                </li>
            </ul>
            @endif

            @if(in_array($role, ['superadmin','admin','operator']))
            <p class="sidebar-section-title">Pengaturan</p>
            <ul class="nav flex-column ms-2 mb-1">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/monitor/settings') ? 'active' : '' }}" href="{{ route('admin.monitor.settings') }}">
                        <i class="bi bi-gear me-2"></i> Pengaturan Monitor
                    </a>
                </li>
            </ul>
            @endif

            @if(in_array($role, ['superadmin','admin']))
            <p class="sidebar-section-title">Manajemen</p>
            <ul class="nav flex-column ms-2 mb-1">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people me-2"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/survey-templates*') ? 'active' : '' }}" href="{{ route('admin.survey-templates.index') }}">
                        <i class="bi bi-file-earmark-text me-2"></i> Template Survey
                    </a>
                </li>
            </ul>
            @endif
        </div>

        <!-- Scroll Indicator Bottom -->
        <div class="scroll-indicator scroll-indicator-bottom" id="scrollIndicatorBottom">
            <i class="bi bi-chevron-down"></i>
        </div>

        <!-- Sticky Footer -->
        <div class="sidebar-footer">
            <div class="text-center">
                <small>© Sistem Informasi dan Data</small>
            </div>
        </div>
    </div>

    <!-- App Bar -->
    <div class="app-bar">
        <div class="left-section">
            <button id="toggleSidebar"><i class="bi bi-list"></i></button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const toggleSidebarBtn = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebar');
const content = document.getElementById('mainContent');
const appBar = document.querySelector('.app-bar');
const sidebarMenu = document.getElementById('sidebarMenu');
const scrollIndicatorTop = document.getElementById('scrollIndicatorTop');
const scrollIndicatorBottom = document.getElementById('scrollIndicatorBottom');

// Toggle Sidebar
toggleSidebarBtn.onclick = () => {
    sidebar.classList.toggle('hidden');
    content.classList.toggle('full-width');

    const sidebarWidth = sidebar.classList.contains('hidden') ? 0 : sidebar.offsetWidth;
    appBar.style.left = sidebarWidth + 'px';
    content.style.left = sidebarWidth + 'px';
};

// Update Scroll Indicators
function updateScrollIndicators() {
    const scrollTop = sidebarMenu.scrollTop;
    const scrollHeight = sidebarMenu.scrollHeight;
    const clientHeight = sidebarMenu.clientHeight;
    const scrollBottom = scrollHeight - scrollTop - clientHeight;

    // Show top indicator if not at top
    if (scrollTop > 10) {
        scrollIndicatorTop.classList.add('show');
    } else {
        scrollIndicatorTop.classList.remove('show');
    }

    // Show bottom indicator if not at bottom
    if (scrollBottom > 10) {
        scrollIndicatorBottom.classList.add('show');
    } else {
        scrollIndicatorBottom.classList.remove('show');
    }
}

// Listen to scroll events on sidebar menu
sidebarMenu.addEventListener('scroll', updateScrollIndicators);

// Initial setup untuk memastikan posisi pas saat load
window.addEventListener('load', () => {
    const sidebarWidth = sidebar.classList.contains('hidden') ? 0 : sidebar.offsetWidth;
    appBar.style.left = sidebarWidth + 'px';
    content.style.left = sidebarWidth + 'px';

    // Check scroll indicators on load
    updateScrollIndicators();
});

// Update indicators on window resize
window.addEventListener('resize', updateScrollIndicators);
</script>

<!-- Lottie Web for animations -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>

</body>
</html>
