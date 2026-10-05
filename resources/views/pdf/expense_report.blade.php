<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 32px 34px;
        }

        body {
            color: #202820;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        .letterhead {
            border-bottom: 3px solid #168344;
            margin-bottom: 18px;
            padding-bottom: 10px;
            text-align: center;
        }

        .brand {
            color: #087b43;
            font-size: 21px;
            font-weight: bold;
        }

        .organization {
            color: #45544a;
            font-size: 9px;
            margin-top: 2px;
        }

        h1 {
            font-size: 15px;
            margin: 18px 0 5px;
            text-align: center;
        }

        .period {
            color: #526057;
            margin: 0 0 14px;
            text-align: center;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #cbd5cd;
            padding: 7px 6px;
        }

        th {
            background: #e8f3eb;
            color: #164b2b;
            font-size: 9px;
            text-align: left;
        }

        td.amount,
        th.amount {
            text-align: right;
            white-space: nowrap;
        }

        td.total {
            font-weight: bold;
        }

        .empty {
            color: #657168;
            padding: 18px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header class="letterhead">
        <div class="brand">PLN</div>
        <div class="organization">LAPORAN PENGELUARAN ARMADA</div>
    </header>

    <h1>Rekapitulasi Biaya Kendaraan</h1>
    <p class="period">Periode {{ sprintf('%02d', $bulan) }}/{{ $tahun }}</p>

    <table>
        <thead>
            <tr>
                <th>Plat Nomor</th>
                <th>Merk/Tipe</th>
                <th>Pengelola</th>
                <th class="amount">Biaya Servis</th>
                <th class="amount">Biaya Perbaikan</th>
                <th class="amount">Grand Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekap as $baris)
                <tr>
                    <td>{{ $baris['plat_nomor'] }}</td>
                    <td>{{ $baris['merk_tipe'] }}</td>
                    <td>{{ $baris['pengelola'] }}</td>
                    <td class="amount">Rp {{ number_format($baris['total_biaya_servis'], 2, ',', '.') }}</td>
                    <td class="amount">Rp {{ number_format($baris['total_biaya_perbaikan'], 2, ',', '.') }}</td>
                    <td class="amount total">Rp {{ number_format($baris['grand_total_pengeluaran'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="6">Belum ada data kendaraan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>