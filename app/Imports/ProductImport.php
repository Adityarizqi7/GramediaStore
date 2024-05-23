<?php

namespace App\Imports;

use App\Models\Book;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;

class ProductImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $book = new Book([
            'id' => Book::max('id') + 1,
            'name' => $row[0],
            'slug' => strtolower(preg_replace('/-+/', '-', preg_replace('/[^A-Za-z0-9\-]+/', '', str_replace(' ', '-', $row[0])))),
            'author' => $row[1],
            'description' => $row[2],
            'date_published' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[3]),
        ]);
        $book->save();

        $product = new Product([
            'id' => Product::max('id') + 1,
            'title' => $row[4],
            'price' => $row[5],
            'slug' => strtolower(preg_replace('/-+/', '-', preg_replace('/[^A-Za-z0-9\-]+/', '', str_replace(' ', '-', $row[4])))),
            'stock' => $row[6],
            'status' => $row[7],
            'sale_price' => $row[8],
            'sale_percentage' => $row[9],
            'type' => $row[10],
            'language' => $row[11],
            'total_pages' => $row[12],
            'book_id' => $book->id
        ]);
        $product->save();

    
        return $book;
    }
}
