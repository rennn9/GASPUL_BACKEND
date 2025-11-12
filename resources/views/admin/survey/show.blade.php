@extends('admin.layout')

@section('content')
<div class="container mt-4">
    <h2 class="fw-bold mb-4">Detail Survei Pengguna</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-borderless">
                <tr>
                    <th width="200">Nomor Antrian</th>
                    <td>{{ $survey->nomor_antrian ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Nama Responden</th>
                    <td>{{ $survey->nama_responden }}</td>
                </tr>
                <tr>
                    <th>Nomor HP / WA</th>
                    <td>{{ $survey->no_hp_wa ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Bidang</th>
                    <td>{{ $survey->bidang }}</td>
                </tr>
                <tr>
                    <th>Pekerjaan</th>
                    <td>{{ $survey->pekerjaan }}</td>
                </tr>
                <tr>
                    <th>Tanggal</th>
                    <td>
                        {{ optional($survey->tanggal)
                            ? \Carbon\Carbon::parse($survey->tanggal)
                                ->locale('id')
                                ->translatedFormat('l, d/m/Y')
                            : '-' }}
                    </td>
                </tr>
                <tr>
                    <th>Usia</th>
                    <td>{{ $survey->usia ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Jenis Kelamin</th>
                    <td>{{ $survey->jenis_kelamin ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Saran / Masukan</th>
                    <td>{{ $survey->saran ?? '-' }}</td>
                </tr>
            </table>

            @if(!empty($jawaban))
                <div class="mt-4">
                    <h5>Jawaban Survei</h5>
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Pertanyaan</th>
                                <th>Jawaban</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jawaban as $pertanyaan => $jawab)
                                <tr>
                                    <td>{{ $pertanyaan }}</td>
                                    <td>{{ $jawab }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="mt-4">
                <a href="{{ route('admin.survey.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
