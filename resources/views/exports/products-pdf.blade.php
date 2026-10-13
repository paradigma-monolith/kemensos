<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Produk - Hybrid Laravel</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #1e1b4b;
        }
        .subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Hybrid Laravel &bull; Laporan Inventaris Produk</div>
        <div class="subtitle">Dicetak pada: {{ $generatedAt->format('d F Y, H:i:s') }} | Engine: Laravel DomPDF</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">ID</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Stok</th>
                <th>Status</th>
                <th>Channel Dibuat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td>#{{ $product->id }}</td>
                <td><strong>{{ $product->name }}</strong></td>
                <td>{{ $product->category }}</td>
                <td class="text-right">${{ number_format($product->price, 2) }}</td>
                <td class="text-right">{{ $product->stock }} unit</td>
                <td>{{ ucfirst($product->status) }}</td>
                <td>{{ $product->created_by_channel }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Laporan resmi di-generate secara otomatis oleh Starter Kit Hybrid Laravel. Total Produk: {{ $products->count() }} item.
    </div>
</body>
</html>
