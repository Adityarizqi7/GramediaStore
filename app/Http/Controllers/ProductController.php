<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {

            $per_page = 6;
            $books = $request->query('book');
            $search = $request->query('search');
            $sort_by = $request->query('sort_by', 'ASC');
            $order_by = $request->query('order_by', 'id');
            $query = Product::query();

            if ($search) {
                $query->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($search) . '%']);
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

            if ($books) {
                $booksArray = explode(',', $books);
                $query->whereHas('book', function ($query) use ($booksArray) {
                    $query->where('id', $booksArray);
                });
            }

            $products = $query;
            $pagination = $query->paginate($per_page);

            $data = [];
            foreach ($pagination->items() as $product) {
                if ($product->book) {
                    $bookData = [
                        'id' => $product->book->id,
                        'name' => $product->book->name,
                        'author' => $product->book->author,
                        'slug' => $product->book->slug,
                        'description' => $product->book->description,
                        'date_published' => $product->book->date_published,
                        'published_at' => $product->book->published_at
                    ];
                }
                $productData = [
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'language' => $product->language,
                    'total_pages' => $product->total_pages,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'sale_percentage' => $product->sale_percentage,
                    'stock' => $product->stock,
                    'status' => $product->status,
                    'type' => $product->type,
                    'is_available' => $product->is_available,
                ];
                $productData['book'] = $bookData;

                $data[] = $productData;
            }

            return response()->json([
                'data' => $data,
                'meta' => [
                    'pagination' => [
                        'total_all_items' => $pagination->total(),
                        'total_items_current_page' => $products->count(),
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
                'message' => 'Terjadi kesalahan saat mengambil data product.',
                'status' => 'error'
            ]);
        }
    }

    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:3'],
            'language' => ['in:idn,eng', 'min:3'],
            'total_pages' => ['required', 'numeric', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable'],
            'sale_percentage' => ['nullable'],
            'stock' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,empty,ordering'],
            'type' => ['required', 'in:book'],
            'is_available' => ['nullable', 'boolean'],
            'book_id' => ['required', 'numeric', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $slug = strtolower(preg_replace('/-+/', '-', preg_replace('/[^A-Za-z0-9\-]+/', '', str_replace(' ', '-', $request->title))));
        
        // Cek Product di Database
        $product_detail = Product::where('slug', 'like', '%' . $slug . '%')->get();
        if (!empty($product_detail)) {
            foreach($product_detail as $detail) {
                return response()->json([
                    'message' => "Buku '{$detail->title}' sudah tersedia. Harap coba lagi!.",
                    'status' => 'error'
                ], 404);
            }
        }

        $product = new Product();

        $book_id = $request->book_id;
        $existing_book_id = Book::find($book_id);

        // Cek Buku di database

        if (empty($existing_book_id)) {
            return response()->json([
                'message' => 'ID Book `' . $book_id . '` tidak ditemukan.',
                'status' => 'error'
            ], 400);

        } else {

            $product->title = $request->title;
            $product->slug = $slug;
            $product->language = $request->language;
            $product->total_pages = $request->total_pages;
            $product->price = $request->price;
            $product->sale_price = $request->sale_price;
            $product->sale_percentage = $request->sale_percentage;
            $product->stock = $request->stock;
            $product->status = $request->status;
            $product->type = $request->type;
            $product->is_available = $request->is_available;
            $product->book_id = $book_id;

            $product->save();
            
            return response()->json([
                'message' => 'Data produk berhasil disimpan',
                'status' => 'Success',
                'data' => [
                    $product->load('book'),
                ],
            ], 201);
        }
    }

    public function detail($id) {
        
        if (!is_numeric($id) || intval($id) < 0) {
            return response()->json([
                'message' => 'Path yang diberikan tidak sesuai.',
                'status' => 'error',
            ], 400);
        }
        $id = intval($id);
        
        if ($id) {

            $product_detail = Product::with('book:id,name,author,slug,description,date_published,is_active,is_promo,is_pre_order')->find($id);
            if (!$product_detail) {
                return response()->json([
                    'message' => 'Detail Product tidak ditemukan.',
                    'status' => 'error'
                ], 404);
            }
            return response()->json($product_detail);
        }
        return response()->json([
            'message' => 'id is required.',
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
            $product_detail = Product::with('book:id,name,author,slug,description,date_published,is_active,is_promo,is_pre_order')->find($id);
    
            if (empty($product_detail)) {
                return response()->json([
                    'message' => 'Detail Product tidak ditemukan.',
                    'status' => 'error'
                ], 404);
            }
    
            $validator = Validator::make($request->all(), [
                'title' => ['required', 'min:3'],
                'language' => ['in:idn,eng', 'min:3'],
                'total_pages' => ['required', 'numeric', 'min:1'],
                'price' => ['required', 'numeric', 'min:0'],
                'sale_price' => ['nullable'],
                'sale_percentage' => ['nullable'],
                'stock' => ['required', 'numeric', 'min:0'],
                'status' => ['required', 'in:available,empty,ordering'],
                'type' => ['required', 'in:book'],
                'is_available' => ['nullable', 'boolean'],
                'book_id' => ['required', 'numeric', 'min:1'],
            ]);
    
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            } 
            
            $slug = strtolower(preg_replace('/-+/', '-', preg_replace('/[^A-Za-z0-9\-]+/', '', str_replace(' ', '-', $request->title))));
    
            try {
    
                $slug = strtolower(preg_replace('/-+/', '-', preg_replace('/[^A-Za-z0-9\-]+/', '', str_replace(' ', '-', $request->title))));
    
                $book_id = $request->book_id;
                $existing_book_id = Book::find($book_id);
    
                // Cek Buku di database
                if (empty($existing_book_id)) {
                    return response()->json([
                        'message' => 'ID Book `' . $book_id . '` tidak ditemukan.',
                        'status' => 'error'
                    ], 400);
    
                } else {
    
                    // Update
                    $product_detail->update([
                        'title' => $request->title,
                        'slug' => $slug,
                        'language' => $request->language,
                        'total_pages' => $request->total_pages,
                        'price' => $request->price,
                        'sale_price' => $request->sale_price,
                        'sale_percentage' => $request->sale_percentage,
                        'stock' => $request->stock,
                        'status' => $request->status,
                        'type' => $request->type,
                        'is_available' => $request->is_available,
                        'book_id' => $book_id
                    ]);
                    
                    return response()->json([
                        'message' => 'Data produk berhasil di-Update',
                        'status' => 'Success',
                        'data' => $product_detail
                    ], 201);
                }            
    
            } catch (\Throwable $th) {
                return response()->json([
                    'message' => "Terjadi kesalahan saat mengubah data produk",
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

            $product_detail = Product::find($id);

            if (!$product_detail) {
                return response()->json([
                    'message' => 'Detail Product tidak ditemukan.',
                    'status' => 'error'
                ], 404);
            }

            try {

                $product_detail->delete();

                return response()->json([
                    'message' => 'Data product berhasil di-Delete',
                    'status' => 'Success',
                ], 200);


            } catch (\Throwable $th) {

                return response()->json([
                    'message' => "Terjadi kesalahan saat menghapus data product",
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