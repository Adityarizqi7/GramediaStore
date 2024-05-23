<?php

namespace App\Http\Controllers;

use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublisherController extends Controller
{
    public function index(Request $request)
    {
        try {

            $per_page = 6;
            $book = $request->query('book');
            $search = $request->query('search');
            $sort_by = $request->query('sort_by', 'ASC');
            $order_by = $request->query('order_by', 'id');
            $query = Publisher::query();

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

            if ($book) {
                $bookArray = explode(',', $book);
                $query->whereHas('genres', function ($query) use ($bookArray) {
                    $query->where('id', $bookArray);
                });
            }

            $publishers = $query;
            $pagination = $query->paginate($per_page);

            return response()->json([
                'data' => $pagination->items(),
                'meta' => [
                    'pagination' => [
                        'total_all_items' => $pagination->total(),
                        'total_items_current_page' => $publishers->count(),
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
                'message' => 'Terjadi kesalahan saat mengambil data Publisher.',
                'status' => 'error'
            ]);
        }
    }

    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'min:3'],
            'description' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $slug = strtolower(preg_replace('/-+/', '-', preg_replace('/[^A-Za-z0-9\-]+/', '', str_replace(' ', '-', $request->name))));
        
        // Cek Product di Database
        $publisher_detail = Publisher::where('slug', 'like', '%' . $slug . '%')->get();
        if (!empty($publisher_detail)) {
            foreach($publisher_detail as $detail) {
                return response()->json([
                    'message' => "Publisher '{$detail->name}' sudah tersedia. Harap coba lagi!.",
                    'status' => 'error'
                ], 404);
            }
        }

        $publisher = new Publisher();
        $publisher->name = $request->name;
        $publisher->slug = $slug;
        $publisher->description = $request->description;

        $publisher->save();
        
        return response()->json([
            'message' => 'Data publisher berhasil disimpan',
            'status' => 'Success',
            'data' => $publisher
        ], 201);
    }

    public function detail($slug) {

        if ($slug) {
            $publisher_detail = Publisher::whereRaw('LOWER(slug) = ?', [strtolower($slug)])->get();
    
            if ($publisher_detail->isEmpty()) {
                return response()->json([
                    'message' => 'Detail Publisher tidak ditemukan.',
                    'status' => 'error'
                ], 404);
            }
            return response()->json($publisher_detail);
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
            $publisher_detail = Publisher::where('id', $id)->first();
    
            if (empty($publisher_detail)) {
                return response()->json([
                    'message' => 'Detail Publisher tidak ditemukan.',
                    'status' => 'error'
                ], 404);
            }
    
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'min:3'],
                'description' => ['nullable'],
            ]);
    
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            } 
            
            $slug = strtolower(preg_replace('/-+/', '-', preg_replace('/[^A-Za-z0-9\-]+/', '', str_replace(' ', '-', $request->name))));
    
            try {
                $publisher_detail->update([
                    'name' => $request->name,
                    'slug' => $slug,
                    'description' => $request->description
                ]);
    
                return response()->json([
                    'message' => 'Data Publisher berhasil di-Update',
                    'status' => 'Success',
                    'data' => $publisher_detail
                ], 200);
    
            } catch (\Throwable $th) {
                return response()->json([
                    'message' => "Terjadi kesalahan saat mengubah data pubsliher.",
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
            $publisher_detail = Publisher::where('id', $id)->first();
    
            if (empty($publisher_detail)) {
                return response()->json([
                    'message' => 'Detail Publisher tidak ditemukan.',
                    'status' => 'error'
                ], 404);
            }
    
            try {
    
                $publisher_detail->delete();
                return response()->json([
                    'message' => 'Data Publisher berhasil di-Delete.',
                    'status' => 'Success',
                ], 200);
    
            } catch (\Throwable $th) {
                
                return response()->json([
                    'message' => "Terjadi kesalahan saat menghapus data Publisher.",
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
