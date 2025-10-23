<!-- Download Daftar Antrian Admin -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Antrian</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h3>Daftar Antrian {{ ucfirst($filter) }} {{ $date ? '- '.Carbon\Carbon::parse($date)->translatedFormat('d/m/Y') : '' }}</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Antrian</th>
                <th>Nama</th>
                <th>No HP</th>
                <th>Alamat</th>
                <th>Bidang Layanan</th>
                <th>Layanan</th>
                <th>Tanggal Daftar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($antrians as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->nomor_antrian }}</td>
                <td>{{ $item->nama }}</td>
                <td>{{ $item->no_hp }}</td>
                <td>{{ $item->alamat }}</td>
                <td>{{ $item->bidang_layanan }}</td>
                <td>{{ $item->layanan }}</td>
                <td>{{ \Carbon\Carbon::parse($item->tanggal_daftar)->translatedFormat('l, d/m/Y') }}</td>
                <td>{{ $item->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
