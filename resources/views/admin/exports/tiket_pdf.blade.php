<!-- Tiket Antrian -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tiket Antrian</title>
    <style>
        @page {
            size: 80mm 63mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
        }

        .container {
            width: 76mm; /* lebih kecil dari kertas 80mm */
            box-sizing: border-box;

            /* Padding lengkap semua sisi: top right bottom left */
            padding: 2mm 3mm 2mm 3mm; 
            /* artinya: padding-top:2mm; padding-right:3mm; padding-bottom:2mm; padding-left:3mm; */
        }

        .logo {
            width: 9mm; /* lebih kecil */
            height: auto;
        }

        .header-text h1 {
            font-size: 9pt;
            margin: 0;
            font-weight: bold;
        }

        .header-text p {
            font-size: 6pt;
            margin-top: 1px;
            line-height: 1.1;
        }

        .divider {
            height: 1px;
            background-color: black;
            margin: 5px 0;
        }

        .ticket-box {
            border: 1px solid black;
            margin-top: 2px;
            width: 100%;
            border-collapse: collapse;
        }

        .ticket-box td {
            padding: 1mm;
            vertical-align: middle;
        }

        .queue-number {
            font-size: 24pt; /* lebih kecil */
            font-weight: bold;
        }

        .qr-code {
            width: 16mm; /* lebih kecil */
            height: 16mm;
        }

        .footer-item strong {
            font-size: 7pt;
        }

        .footer-item div {
            margin-bottom: 0.5mm;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
<table width="100%" style="margin: 0 auto; text-align: center;">
    <tr>
        <td style="width:9mm; text-align:center;">
            <img src="file://{{ public_path('assets/images/kemenag.png') }}" class="logo" alt="Logo">
        </td>
        <td class="header-text" style="padding-left:2mm; text-align:left;">
            <h1>Kantor Wilayah Kementrian Agama<br>Provinsi Sulawesi Barat</h1>
            <p>
                Jl. H.A.M Jl. Abdul Malik Pattana Endeng No.46, Simboro,<br>
                Kec. Simboro Dan Kepulauan, Kab. Mamuju, Sulbar 91512
            </p>
        </td>
    </tr>
</table>

        <div class="divider"></div>

        <table class="ticket-box" width="100%" 
            style="padding: 3px 20px 3px 20px; border-collapse: separate;">
            <tr>
                <td style="width:58%; text-align:center;">
                    <div style="font-size:6pt; margin-bottom:1px;">NOMOR ANTRIAN</div>
                    <div class="queue-number">{{ $nomor }}</div>
                </td>
                <td style="width:0%;">
                    <img src="data:image/svg+xml;base64,{{ $qrCode }}" class="qr-code" alt="QR Code">
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <table width="100%" style="margin-top:1mm;">
            <tr>
                <td style="text-align:left;">
                    <div>Tanggal Pelayanan</div>
                    <strong>{{ $tanggal }}</strong>
                </td>
                <td style="text-align:right;">
                    <div>Bidang Layanan</div>
                    <strong>{{ $bidang }}</strong>
                    <div>Layanan</div>
                    <strong>{{ $layanan }}</strong>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
