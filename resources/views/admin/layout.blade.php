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
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background-color: #017787;
            border-top-right-radius: 16px;
            border-bottom-right-radius: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
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
        .content {
            flex-grow: 1;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        .copyright {
            font-size: 0.85rem;
            text-align: center;
            margin-top: 10px;
            color: rgba(255,255,255,0.8);
        }
    </style>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar p-4 text-white">
        <div>
            <!-- Logo & Header -->
            <div class="text-center mb-3">
                <img src="{{ asset('assets/images/logo-gaspul.png') }}" alt="Logo Gaspul" class="logo mb-2">
                <h5 class="fw-bold">Admin GASPUL</h5>
            </div>
            <div class="sidebar-divider"></div>

            <!-- Navigation -->
            <p class="fw-bold">Pengaduan</p>
            <ul class="nav flex-column ms-2">
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->is('admin/dashboard') ? 'active' : '' }}" 
                       href="{{ url('admin/dashboard#masyarakat') }}">
                        <i class="bi bi-people me-2"></i> Pengaduan Masyarakat
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/dashboard#pelayanan') ? 'active' : '' }}" 
                       href="{{ url('admin/dashboard#pelayanan') }}">
                        <i class="bi bi-building me-2"></i> Pengaduan Pelayanan
                    </a>
                </li>
            </ul>
        </div>

        <div>
            <!-- Logout -->
            <form action="{{ route('logout') }}" method="POST" class="mt-4">
                @csrf
                <button type="submit" class="btn btn-danger w-100">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
            <div class="copyright">
                © Sistem Informasi dan Data
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="content">
        @yield('content')
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function setActiveSidebar() {
        const hash = window.location.hash || '#masyarakat'; // default masyarakat
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').includes(hash)) {
                link.classList.add('active');
            }
        });

        // buka tab sesuai hash
        const tabTrigger = document.querySelector(`[data-bs-toggle="tab"][href="${hash}"]`);
        if (tabTrigger) {
            new bootstrap.Tab(tabTrigger).show();
        }
    }

    window.addEventListener('DOMContentLoaded', setActiveSidebar);
    window.addEventListener('hashchange', setActiveSidebar);
</script>

</body>
</html>
