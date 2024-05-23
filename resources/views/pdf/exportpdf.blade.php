<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Export PDF</title>

    <style lang="css">
        .title-wrapper > h1 {
            font-size: 2rem;
            margin-top: 2rem;
            font-weight: 700;
            text-align: center;
        }
        .table-wrapper {
            overflow-x: auto;
            margin-top: 2rem;
            position: relative;
            margin-inline: 2rem;
        }
        table {
            width: 100%;
            text-align: center;
            border-collapse: collapse;
        }
        thead {
            font-size: 1rem;
            text-transform: uppercase;
        }
        th, td {
            line-height: 1.5;
            font-size: 0.85rem;
            padding: 1rem 0.5rem;
            border: 1px solid rgb(187, 187, 187);
        }
    </style>
</head>
<body>
    <section>
        <div class="title-wrapper">
            <h1>DAFTAR BUKU GRAMEDIA STORE</h1>
        </div>
        
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th> No. </th>
                        <th> Nama </th>
                        <th> Harga </th>
                        <th> Total Halaman </th>
                        <th> Stok </th>
                        <th> Status Barang </th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $nomor = 1;
                    @endphp
                    @foreach ($books as $book)
                        @foreach ($book->product as $book_with_product)
                            <tr>
                                <td>
                                    {{ $nomor++ }}.
                                </td>
                                <td>
                                    {{ $book->name }} ({{ $book_with_product->title }})
                                </td>
                                <td>
                                    <div>
                                        @if ($book_with_product->sale_percentage > 0)
                                            <span>
                                                Rp. {{ formatNumber($book_with_product->sale_price) }}
                                            </span>
                                        @endif
                                        <div>
                                            <span style="
                                                font-size: 12px;
                                                color: rgb(182, 182, 182);
                                                @if ($book_with_product->sale_percentage > 0)
                                                    text-decoration: line-through;
                                                @endif
                                            ">
                                                Rp. {{ formatNumber($book_with_product->price) }}
                                            </span>
                                            <span style="color: red; font-size: 12px">
                                                {{ $book_with_product->sale_percentage }}%
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td style="width: 30px;">
                                    {{ $book_with_product->total_pages }}
                                </td>
                                <td>
                                    {{ formatNumber($book_with_product->stock) }}
                                </td>
                                <td style="text-transform: capitalize; font-weight: 600; color: 
                                    @if($book_with_product->status === 'available') green; 
                                    @elseif($book_with_product->status === 'empty') red; 
                                    @elseif($book_with_product->status === 'ordering') blue; 
                                    @endif">
                                    {{ $book_with_product->status }}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

    </section>
</body>
</html>