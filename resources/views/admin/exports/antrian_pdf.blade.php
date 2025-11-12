<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>
        Daftar Antrian {{ \Carbon\Carbon::parse($date ?? now())->translatedFormat('d F Y') }}
    </title>
    <style>
        @page {
            size: A4 landscape; /* 🔄 Orientasi lanskap */
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
    </style>
</head>
<body>
    <h3>
        Daftar Antrian
        {{ \Carbon\Carbon::parse($date ?? now())->translatedFormat('l, d F Y') }}
    </h3>
    <div class="header-info">
        Dicetak pada: {{ now()->translatedFormat('l, d F Y H:i') }}
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
                    <td style="max-width: 100px; word-wrap: break-word;">
                        {{ $item->alamat ?? '-' }}
                    </td>
                    <td>{{ $item->bidang_layanan ?? '-' }}</td>
                    <td style="max-width: 90px; word-wrap: break-word;">
                        {{ $item->layanan ?? '-' }}
                    </td>
                    <td>
                        {{ optional($item->tanggal_layanan)->translatedFormat('l, d/m/Y') ?? '-' }}
                    </td>
                    <td style="max-width: 90px; word-wrap: break-word;">
                        {{ $item->keterangan ?? '-' }}
                    </td>
                    <td>{{ $item->status ?? '-' }}</td>
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
