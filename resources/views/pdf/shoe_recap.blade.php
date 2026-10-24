<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Kebutuhan Sepatu</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0 0 5px 0;
            color: #0f172a;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 12px;
            margin: 0;
            color: #475569;
            font-weight: normal;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .meta-table td {
            padding: 3px 0;
            font-size: 11px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th {
            background-color: #3b82f6;
            color: #ffffff;
            font-weight: bold;
            padding: 8px 6px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }
        table.data-table td {
            padding: 6px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        tr.total-row {
            background-color: #e2e8f0 !important;
            font-weight: bold;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
        }
        .signature-table td {
            text-align: center;
            vertical-align: top;
            width: 50%;
        }
        .signature-space {
            height: 60px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REKAPITULASI KEBUTUHAN PENGADAAN SEPATU</h1>
        <h2>{{ $school ? $school->nama_sekolah : 'SEMUA SEKOLAH RAKYAT' }} &mdash; Dicetak pada {{ $date }}</h2>
    </div>

    @if($school)
    <table class="meta-table">
        <tr>
            <td width="15%"><strong>Nama Sekolah:</strong></td>
            <td width="40%">{{ $school->nama_sekolah }}</td>
            <td width="18%"><strong>PIC / Petugas Ukur:</strong></td>
            <td>{{ $school->pic_nama ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Kota / Wilayah:</strong></td>
            <td>{{ $school->kota ?? '-' }}</td>
            <td><strong>Kepala Sekolah:</strong></td>
            <td>{{ $school->kepala_sekolah_nama ?? '-' }} {{ $school->kepala_sekolah_nip ? '(' . $school->kepala_sekolah_nip . ')' : '' }}</td>
        </tr>
    </table>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Ukuran Sepatu</th>
                <th width="12%">Siswa (L)</th>
                <th width="12%">Siswa (P)</th>
                <th width="14%">Guru/Tendik (L)</th>
                <th width="14%">Guru/Tendik (P)</th>
                <th width="14%">Total Pria</th>
                <th width="14%">TOTAL PASANG</th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
                $totSiswaL = 0; $totSiswaP = 0; $totStaffL = 0; $totStaffP = 0;
                $totPria = 0; $grandTotal = 0;
            @endphp
            @foreach($matrix as $row)
                @php
                    $totSiswaL += $row->siswa_l;
                    $totSiswaP += $row->siswa_p;
                    $totStaffL += $row->staff_l;
                    $totStaffP += $row->staff_p;
                    $totPria += $row->total_pria;
                    $grandTotal += $row->total;
                @endphp
                <tr>
                    <td>{{ $no++ }}</td>
                    <td><strong>Size {{ $row->ukuran_sepatu }}</strong></td>
                    <td>{{ $row->siswa_l }}</td>
                    <td>{{ $row->siswa_p }}</td>
                    <td>{{ $row->staff_l }}</td>
                    <td>{{ $row->staff_p }}</td>
                    <td>{{ $row->total_pria }}</td>
                    <td><strong>{{ $row->total }}</strong></td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">TOTAL</td>
                <td>{{ $totSiswaL }}</td>
                <td>{{ $totSiswaP }}</td>
                <td>{{ $totStaffL }}</td>
                <td>{{ $totStaffP }}</td>
                <td>{{ $totPria }}</td>
                <td>{{ $grandTotal }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Kepala Sekolah</strong>
                <div class="signature-space"></div>
                <u>{{ $school?->kepala_sekolah_nama ?? '( .............................................. )' }}</u><br>
                {{ $school?->kepala_sekolah_nip ?? '' }}
            </td>
            <td>
                {{ $school?->kota ?? 'Cimahi' }}, {{ $date }}<br>
                <strong>Petugas Ukur / PIC</strong>
                <div class="signature-space"></div>
                <u>{{ $school?->pic_nama ?? '( .............................................. )' }}</u>
            </td>
        </tr>
    </table>
</body>
</html>
