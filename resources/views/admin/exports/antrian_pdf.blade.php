<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>
        Daftar Antrian {{ \Carbon\Carbon::parse($date ?? now())->translatedFormat('d F Y') }}
    </title>

    <style>
        @page {
            size: A4 landscape;
            margin: 15px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            position: relative;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
        }
        h3 {
            margin: 0;
            text-align: center;
        }
        .header-info {
            text-align: center;
            font-size: 10px;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        /* 🔥 LOGO FIXED TOP RIGHT TANPA MENGGESER TITLE */
        .logo-gaspul {
            position: absolute;
            top: 5px;
            right: 20px;
            width: 90px;
        }
    </style>
</head>
<body>

{{-- LOGO GASPUL --}}
<img src="{{ public_path('assets/images/logo-gaspul.png') }}" class="logo-gaspul" alt="Logo Gaspul">

@php
    use Carbon\Carbon;
    $filter = request()->get('filter', 'today');
    $date   = request()->get('date');

    switch ($filter) {
        case 'today':
            $titleDate = 'Hari Ini (' . now()->translatedFormat('l, d F Y') . ')';
            break;
        case 'tomorrow':
            $titleDate = 'Besok (' . now()->addDay()->translatedFormat('l, d F Y') . ')';
            break;
        case 'custom':
            $titleDate = $date ? Carbon::parse($date)->translatedFormat('l, d F Y') : 'Tanggal Tidak Diketahui';
            break;
        default:
            $titleDate = 'Semua Tanggal';
    }
@endphp

<h3>
    Daftar Antrian — {{ $titleDate }}
</h3>



    <div class="header-info">
        Dicetak pada: <b>{{ now()->translatedFormat('l, d F Y H:i') }}</b><br>
        Total Data: <b>{{ $antrians->count() }}</b><br>

        @if(isset($date))
            Periode Layanan: 
            <b>{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</b><br>
        @endif

        {{-- opsional jika ingin menampilkan nama user --}}
        @if(Auth::check())
            Dicetak oleh: <b>{{ Auth::user()->name }}</b>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Antrian</th>
                <th>Nama Lengkap</th>
                <th>Email</th>
                <th>No. HP / WA</th>
                <th>Alamat</th>
                <th>Bidang Layanan</th>
                <th>Layanan</th>
                <th>Tanggal Layanan</th>
                <th>Keterangan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($antrians as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->nomor_antrian ?? '-' }}</td>
                    <td>{{ $item->nama_lengkap ?? '-' }}</td>
                    <td>{{ $item->email ?? '-' }}</td>
                    <td>{{ $item->no_hp_wa ?? '-' }}</td>

                    <td style="max-width: 120px; word-wrap: break-word;">
                        {{ $item->alamat ?? '-' }}
                    </td>

                    <td>{{ $item->bidang_layanan ?? '-' }}</td>

                    <td style="max-width: 100px; word-wrap: break-word;">
                        {{ $item->layanan ?? '-' }}
                    </td>

                    <td>
                        @if($item->tanggal_layanan)
                            {{ \Carbon\Carbon::parse($item->tanggal_layanan)->translatedFormat('l, d F Y') }}
                        @else
                            -
                        @endif
                    </td>

                    <td style="max-width: 120px; word-wrap: break-word;">
                        {{ $item->keterangan ?? '-' }}
                    </td>

                    <td>{{ ucfirst($item->status) ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center;">Tidak ada data antrian</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
