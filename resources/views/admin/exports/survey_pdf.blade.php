<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Survey</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background: #eee; }
        .empty { text-align: center; font-style: italic; padding: 20px; }
    </style>
</head>
<body>
    <h2>Daftar Survey - {{ $dateText }}</h2>

    @if($surveys->isEmpty())
        <div class="empty">Tidak ada daftar survey</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Bidang</th>
                    <th>Pekerjaan</th>
                    <th>Tanggal & Jam</th>
                    <th>Usia</th>
                    <th>Jenis Kelamin</th>
                    <th>Saran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($surveys as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->nama_responden }}</td>
                        <td>{{ $item->bidang }}</td>
                        <td>{{ $item->pekerjaan }}</td>
                        <td>{{ $item->tanggal?->format('d/m/Y H:i') }}</td>
                        <td>{{ $item->usia ?? '-' }}</td>
                        <td>{{ $item->jenis_kelamin ?? '-' }}</td>
                        <td>{{ $item->saran ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
