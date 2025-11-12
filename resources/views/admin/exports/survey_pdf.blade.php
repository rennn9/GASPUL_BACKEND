<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>
        Daftar Survei {{ $dateText }}
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
        Daftar Survei - {{ $dateText }}
    </h3>
    <div class="header-info">
        Dicetak pada: {{ now()->locale('id')->translatedFormat('l, d/m/Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Responden</th>
                <th>Bidang</th>
                <th>Pekerjaan</th>
                <th>Tanggal</th>
                <th>Usia</th>
                <th>Jenis Kelamin</th>
                <th>Saran / Masukan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($surveys as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->nama_responden ?? '-' }}</td>
                    <td>{{ $item->bidang ?? '-' }}</td>
                    <td>{{ $item->pekerjaan ?? '-' }}</td>
                    <td>
                        {{ optional($item->tanggal)
                            ? \Carbon\Carbon::parse($item->tanggal)
                                ->locale('id')
                                ->translatedFormat('l, d/m/Y')
                            : '-' }}
                    </td>
                    <td>{{ $item->usia ?? '-' }}</td>
                    <td>{{ $item->jenis_kelamin ?? '-' }}</td>
                    <td style="max-width: 200px; word-wrap: break-word;">
                        {{ $item->saran ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;">Tidak ada data survei</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
