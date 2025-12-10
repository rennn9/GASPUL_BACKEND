<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>
        Daftar Konsultasi {{ ucfirst($status) }}
    </title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
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
        }
        /* ⭐ Logo di pojok kanan */
        .logo-container {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .logo-container img {
            width: 80px;
        }
    </style>
</head>
<body>

    <!-- ⭐ Logo absolut, pojok kanan -->
    <div class="logo-container">
        <img src="{{ public_path('assets/images/logo-gaspul.png') }}" alt="Logo GASPUL">
    </div>

    <h3>
        Daftar Konsultasi - {{ ucfirst($status) }}
    </h3>

    <div class="header-info">
        Total Data: <strong>{{ $konsultasis->count() }}</strong><br>
        Dicetak pada: {{ now()->translatedFormat('l, d F Y H:i') }}<br>
        Dicetak oleh: {{ auth()->user()->name ?? 'Sistem' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Pemohon</th>
                <th>No. HP / WA</th>
                <th>Email</th>
                <th>Alamat</th>
                <th>Asal Instansi</th>
                <th>Perihal</th>
                <th>Status</th>
                <th>Tanggal Konsultasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($konsultasis as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->nama_lengkap ?? '-' }}</td>
                    <td>{{ $item->no_hp_wa ?? '-' }}</td>
                    <td>{{ $item->email ?? '-' }}</td>
                    <td style="max-width: 120px; word-wrap: break-word;">
                        {{ $item->alamat ?? '-' }}
                    </td>
                    <td style="max-width: 120px; word-wrap: break-word;">
                        {{ $item->asal_instansi ?? '-' }}
                    </td>
                    <td style="max-width: 120px; word-wrap: break-word;">
                        {{ $item->perihal ?? '-' }}
                    </td>
                    <td>{{ ucfirst($item->status ?? '-') }}</td>
                    <td>
                        {{ optional($item->tanggal_layanan)->translatedFormat('l, d/m/Y H:i') ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;">Tidak ada data konsultasi</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
