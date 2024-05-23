<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use App\Imports\ProductImport;
use App\Models\Product;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    public function index(Request $request)
    {
        try {
            $per_page = 6;
            $search = $request->query('search');
            $genres = $request->query('genres');
            $publishers = $request->query('publishers');
            $sort_by = $request->query('sort_by', 'ASC');
            $order_by = $request->query('order_by', 'id');
            $query = Book::query();

            if ($search) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
            }
            
            // Cek Ordering Param
            if (!empty($order_by)) {
                if ($order_by === 'id') {
                    // Cek Sorting Param
                    if (!empty($sort_by)) {
                        if ($sort_by === 'ASC') {
                            $query->orderBy('id', 'asc');
                        } else if ($sort_by === 'DESC') {
                            $query->orderBy('id', 'desc');
                        }
                        $query->orderBy('id', 'asc');
                    } else {
                        $query->orderBy('id', 'asc');
                    }
                } else if ($order_by === 'name') {
                    // Cek Sorting Param
                    if (!empty($sort_by)) {
                        if ($sort_by === 'ASC') {
                            $query->orderBy('name', 'asc');
                        } else if ($sort_by === 'DESC') {
                            $query->orderBy('name', 'desc');
                        }
                        $query->orderBy('name', 'asc');
                    } else {
                        $query->orderBy('name', 'asc');
                    }
                } else {
                    $query->orderBy('id', 'asc');
                }
            } else {
                $query->orderBy('id', 'asc');
            }

            if ($genres) {
                $genresArray = explode(',', $genres);
                $query->whereHas('genres', function ($query) use ($genresArray) {
                    $query->where('slug', $genresArray);
                });
            }

            if ($publishers) {
                $publishersArray = explode(',', $publishers);
                $query->whereHas('publishers', function ($query) use ($publishersArray) {
                    $query->where('slug', $publishersArray);
                });
            }

            $books = $query;
            $pagination = $query->with([
                'publisher:id,name,slug',
                'genres:id,name,slug',
                'product'
                // 'product:id,title,slug,language,total_pages,price,sale_price,sale_percentage,stock,status,type,is_available'
            ])->paginate($per_page);
            
            // Can be use for future
            // $data = [];
            // foreach ($pagination->items() as $book) {
            //     // $bookData = [
            //     //     'id' => $book->id,
            //     //     'name' => $book->name,
            //     //     'author' => $book->author,
            //     //     'slug' => $book->slug,
            //     //     'description' => $book->description,
            //     //     'total_pages' => $book->total_pages,
            //     //     'date_published' => $book->date_published,
            //     //     'created_at' => $book->created_at,
            //     //     'published_at' => $book->published_at
            //     // ];

            //     // if ($book->product) {
            //     //     $productData = [
            //     //         'price' => $book->product->price,
            //     //         'sale_price' => $book->product->sale_price,
            //     //         'sale_percentage' => $book->product->sale_percentage,
            //     //         'stock' => $book->product->stock,
            //     //         'status' => $book->product->status,
            //     //         'type' => $book->product->type
            //     //     ];
            //     //     $bookData['product'] = $productData;
            //     // }
            //     $data[] = $bookData;
            // }

            // Can be use for future
            // $result = $pagination->getCollection()->transform(function ($book) {
            //     return [
            //         'id' => $book->id,
            //         'name' => $book->name,
            //         'author' => $book->author,
            //         'slug' => $book->slug,
            //         'description' => $book->description,
            //         'date_published' => $book->date_published,
            //         'is_active' => $book->is_active,
            //         'is_promo' => $book->is_promo,
            //         'is_pre_order' => $book->is_pre_order,
            //         'genres' =>  $book->product ? [
            //             'id' => $book->product->id,
            //             'name' => $book->product->name,
            //             'slug' => $book->product->slug
            //         ] : null,
            //         'product' => $book->product ? [
            //             'id' => $book->product->id,
            //             'title' => $book->product->title,
            //             'slug' => $book->product->slug,
            //             'language' => $book->product->language,
            //             'total_pages' => $book->product->total_pages,
            //             'price' => $book->product->price,
            //             'sale_price' => $book->product->sale_price,
            //             'sale_percentage' => $book->product->sale_percentage,
            //             'stock' => $book->product->stock,
            //             'status' => $book->product->status,
            //             'type' => $book->product->type,
            //             'is_available' => $book->product->is_available,
            //         ] : null,
            //     ];
            // });

            return response()->json([
                'data' => $pagination->items(),
                'meta' => [
                    'pagination' => [
                        'total_all_items' => $pagination->total(),
                        'total_items_current_page' => $books->count(),
                        'per_page' => $pagination->perpage(),
                        'page' => $pagination->currentPage(),
                        'total_pages' => $pagination->lastPage(),
                        'links' => [
                            'last_page' => $pagination->url($pagination->lastPage()),
                            'next_page' => $pagination->nextPageUrl(),
                            'previous_page' => $pagination->previousPageUrl(),
                        ]
                    ]
                ]
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data buku.',
                'status' => 'error'
            ]);
        }
    }

    public function exportToPdf(Request $request) {

        try {
            $status = $request->query('status');
            $sort_by = $request->query('sort_by');
            $order_by = $request->query('order_by', 'id');
            $query = Book::query()->with('product');
    
            // Cek Ordering Param
            if (!empty($order_by)) {
                if ($order_by === 'id') {
                    // Cek Sorting Param
                    if (!empty($sort_by)) {
                        if ($sort_by === 'ASC') {
                            $query->orderBy('id', 'asc');
                        } else if ($sort_by === 'DESC') {
                            $query->orderBy('id', 'desc');
                        }
                        $query->orderBy('id', 'asc');
                    } else {
                        $query->orderBy('id', 'asc');
                    }
                } else if ($order_by === 'name') {
                    // Cek Sorting Param
                    if (!empty($sort_by)) {
                        if ($sort_by === 'ASC') {
                            $query->orderBy('name', 'asc');
                        } else if ($sort_by === 'DESC') {
                            $query->orderBy('name', 'desc');
                        }
                        $query->orderBy('name', 'asc');
                    } else {
                        $query->orderBy('name', 'asc');
                    }
                } else {
                    $query->orderBy('id', 'asc');
                }
            } else {
                $query->orderBy('id', 'asc');
            }

            $pdf = PDF::loadView('pdf.exportpdf', ['books' => $query->get()])->setPaper('a4', 'portrait');
            return $pdf->stream('Daftar Buku Gramedia Store.pdf'); 
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil export daftar buku.',
                'status' => 'error'
            ]);
        }
    }

    public function importFromExcel(Request $request) {

        try {
            $request->validate([
                'file_excel_book' => 'required|mimes:xlsx,xls'
            ]);
    
            $file = $request->file('file_excel_book');
            $nama_file = now()->format('Ymd') . '-' . uniqid() . '-' . $file->getClientOriginalName();
            $file->move('data/book', $nama_file);

            try {
                
                $import = new ProductImport();
                $data = Excel::toCollection($import, public_path('data/book/' . $nama_file))->first();
                
                // Cek Buku di Database
                foreach ($data as $row) {
                    $existingBook = Book::where('name', $row[0])->first();
                    if ($existingBook) {
                        throw new \Exception('Data buku dengan nama "' . $row[0] . '" sudah ada di database.');
                    }
                }

                Excel::import(new ProductImport, public_path('data/book/' . $nama_file));
                return response()->json([
                    'message' => 'Successfully imported book data from Excel.'
                ], 201);

            } catch (\Exception $e) {
                if (stripos($e->getMessage(), 'data buku dengan nama') !== false) {
                    return response()->json([
                        'message' => $e->getMessage(),
                        'status' => 'error'
                    ], 422);
                } else {
                    dd($e->getMessage());
                    return response()->json([
                        'message' => 'Terjadi kesalahan saat menyimpan data import buku ke database. Format Data didalam Excel kurang tepat',
                        'status' => 'error'
                    ], 500);
                }
            }

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengimport buku dari excel.',
                'status' => 'error'
            ]);
        }
    }

    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'min:3'],
            'author' => ['required', 'min:3'],
            'description' => ['nullable'],
            'date_published' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'is_promo' => ['nullable', 'boolean'],
            'is_pre_order' => ['nullable', 'boolean'],
            'publisher_id' => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $slug = strtolower(preg_replace('/-+/', '-', preg_replace('/[^A-Za-z0-9\-]+/', '', str_replace(' ', '-', $request->name))));
        
        // Cek Buku di Database
        $book_detail = Book::where('slug', 'like', '%' . $slug . '%')->get();
        if (!empty($book_detail)) {
            foreach($book_detail as $detail) {
                return response()->json([
                    'message' => "Buku '{$detail->name}' sudah tersedia. Harap coba lagi!.",
                    'status' => 'error'
                ], 404);
            }
        }

        // Cek Publisher di Database
        $publisher = Publisher::find($request->publisher_id);
        if (!$publisher) {
            return response()->json([
                'message' => "Publisher dengan ID '{$request->publisher_id}' tidak ditemukan.",
                'status' => 'error'
            ], 404);
        }

        $book = new Book;

        if ($request->has('genre_id')) {
            $genre_id = $request->genre_id;
    
            $existing_genre_id = Genre::whereIn('id', $genre_id)->pluck('id')->toArray();
            $non_existing_genre_id = array_diff($genre_id, $existing_genre_id);

            if (!empty($non_existing_genre_id)) {

                return response()->json([
                    'message' => 'ID genre ' . implode(', ', $non_existing_genre_id) . ' tidak valid',
                    'status' => 'error'
                ], 400);

            } else {

                $book->name = $request->name;
                $book->slug = $slug;
                $book->author = strtoupper($request->author);
                $book->description = $request->description;
                $book->date_published = $request->date_published;
                $book->is_active = $request->is_active;
                $book->is_promo = $request->is_promo;
                $book->is_pre_order = $request->is_pre_order;
                $book->publisher_id = $request->publisher_id;

                $book->save();
                $book->genres()->attach($existing_genre_id);
                
                return response()->json([
                    'message' => 'Data buku berhasil disimpan',
                    'status' => 'Success',
                    'data' => [
                        $book->load('genres'),
                    ],
                ], 201);
            }
        }

        $book->name = $request->name;
        $book->slug = $slug;
        $book->author = strtoupper($request->author);
        $book->description = $request->description;
        $book->date_published = $request->date_published;
        $book->is_active = $request->is_active;
        $book->is_promo = $request->is_promo;
        $book->is_pre_order = $request->is_pre_order;
        $book->publisher_id = $request->publisher_id;

        $book->save();

        return response()->json([
            'message' => 'Data buku berhasil disimpan',
            'status' => 'Success',
            'data' => [
                $book->load('genres'),
            ],
        ], 201);
    }

    public function detail($slug) {

        if ($slug) {
            $book_detail = Book::whereRaw('LOWER(slug) = ?', [strtolower($slug)])
                                ->with('product')
                                ->get();
    
            if ($book_detail->isEmpty()) {
                return response()->json([
                    'message' => 'Detail Buku tidak ditemukan.',
                    'status' => 'error'
                ], 404);
            }
            return response()->json($book_detail);
        }
        return response()->json([
            'message' => 'Slug is required.',
            'status' => 'error'
        ], 404);
    }

    public function update($id, Request $request) {

        if (!is_numeric($id) || intval($id) < 0) {
            return response()->json([
                'message' => 'Path yang diberikan tidak sesuai.',
                'status' => 'error',
            ], 400);
        }
        $id = intval($id);

        if ($id) {

            $book_detail = Book::where('id', $id)->first();
    
            if (empty($book_detail)) {
                return response()->json([
                    'message' => 'Detail Buku tidak ditemukan.',
                    'status' => 'error'
                ], 404);
            }
    
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'min:3'],
                'author' => ['required', 'min:3'],
                'description' => ['nullable'],
                'date_published' => ['nullable', 'date'],
                'is_active' => ['nullable', 'boolean'],
                'is_promo' => ['nullable', 'boolean'],
                'is_pre_order' => ['nullable', 'boolean'],
            ]);
    
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            } 
            
            $slug = strtolower(preg_replace('/-+/', '-', preg_replace('/[^A-Za-z0-9\-]+/', '', str_replace(' ', '-', $request->name))));
    
            try {
    
                if ($request->has('genre_id')) {
                    $genre_id = $request->genre_id;
            
                    $existing_genre_id = Genre::whereIn('id', $genre_id)->pluck('id')->toArray();
                    $non_existing_genre_id = array_diff($genre_id, $existing_genre_id);
        
                    if (!empty($non_existing_genre_id)) {
        
                        return response()->json([
                            'message' => 'ID genre ' . implode(', ', $non_existing_genre_id) . ' tidak valid',
                            'status' => 'error'
                        ], 400);
        
                    } else {
    
                        // Update
                        $book_detail->update([
                            'name' => $request->name,
                            'slug' => $slug,
                            'author' => strtoupper($request->author),
                            'description' => $request->description,
                            'date_published' => $request->date_published,
                            'is_active' => $request->is_active,
                            'is_promo' => $request->is_promo,
                            'is_pre_order' => $request->is_pre_order,
                            'publisher_id' => $request->publisher_id,
                        ]);
    
                        $book_detail->genres()->sync($existing_genre_id);
    
                        return response()->json([
                            'message' => 'Data buku berhasil di-Update',
                            'status' => 'Success',
                            'data' => $book_detail->load('genres')
                        ], 200);
                    }
                } 
    
                // Update
                $book_detail->update([
                    'name' => $request->name,
                    'slug' => $slug,
                    'author' => strtoupper($request->author),
                    'description' => $request->description,
                    'date_published' => $request->date_published,
                    'is_active' => $request->is_active,
                    'is_promo' => $request->is_promo,
                    'is_pre_order' => $request->is_pre_order,
                    'publisher_id' => $request->publisher_id
                ]);
    
                return response()->json([
                    'message' => 'Data buku berhasil di-Update',
                    'status' => 'Success',
                    'data' => $book_detail
                ], 200);
    
            } catch (\Throwable $th) {
                return response()->json([
                    'message' => "Terjadi kesalahan saat mengubah data buku",
                    'status' => 'error'
                ], 422);
            }
        }

        return response()->json([
            'message' => 'id is required.',
            'status' => 'error'
        ], 404);
    }

    public function destroy($id) {

        if (!is_numeric($id) || intval($id) < 0) {
            return response()->json([
                'message' => 'Path yang diberikan tidak sesuai.',
                'status' => 'error',
            ], 400);
        }
        $id = intval($id);

        if ($id) {

            $book_detail = Book::where('id', $id)->first();
    
            if (empty($book_detail)) {
                return response()->json([
                    'message' => 'Detail Buku tidak ditemukan.',
                    'status' => 'error'
                ], 404);
            }
    
            try {
    
                $book_detail->product()->delete();
                $book_detail->delete();
                $book_detail->genres()->detach();
    
                return response()->json([
                    'message' => 'Data buku berhasil di-Delete',
                    'status' => 'Success',
                ], 200);
            } catch (\Throwable $th) {
                return response()->json([
                    'message' => "Terjadi kesalahan saat menghapus data buku",
                    'status' => 'error'
                ], 422);
            }
        }

        return response()->json([
            'message' => 'id is required.',
            'status' => 'error'
        ], 404);
    }
}