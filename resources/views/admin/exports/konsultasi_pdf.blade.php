<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Konsultasi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Daftar Konsultasi - {{ ucfirst($status) }}</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Pemohon</th>
                <th>No HP/WA</th>
                <th>Email</th>
                <th>Perihal</th>
                <th>Isi Konsultasi</th>
                <th>Status</th>
                <th>Tanggal Konsultasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($konsultasis as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->nama_lengkap }}</td>
                <td>{{ $item->no_hp }}</td>
                <td>{{ $item->email ?? '-' }}</td>
                <td>{{ $item->perihal }}</td>
                <td>{{ Str::limit($item->isi_konsultasi, 50) }}</td>
                <td>{{ ucfirst($item->status) }}</td>
                <td>{{ \Carbon\Carbon::parse($item->tanggal_konsultasi)->translatedFormat('d/m/Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
