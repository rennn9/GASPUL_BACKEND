{{-- Navigasi Tab Statistik --}}
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ request()->is('admin/statistik/pelayanan') ? 'active' : '' }}"
            href="{{ route('admin.statistik.pelayanan') }}">
            Statistik Pelayanan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('admin/statistik/konsultasi') ? 'active' : '' }}"
            href="{{ route('admin.statistik.konsultasi') }}">
            Statistik Konsultasi
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('admin/statistik/survey') ? 'active' : '' }}"
            href="{{ route('admin.statistik.survey') }}">
            Statistik Survey (IKM)
        </a>
    </li>
</ul>
