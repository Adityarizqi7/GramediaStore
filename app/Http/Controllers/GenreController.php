<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GenreController extends Controller
{
    public function index(Request $request) {
        try {
            $per_page = 6;
            $search = $request->query('search');
            $sort_by = $request->query('sort_by', 'asc');
            $order_by = $request->query('order_by', 'id');
            $query = Genre::query();

            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
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

            $genres = $query;
            $pagination = $query->paginate($per_page);

            return response()->json([
                'data' => $pagination->items(),
                'meta' => [
                    'pagination' => [
                        'total_all_items' => $pagination->total(),
                        'total_items_current_page' => $genres->count(),
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
                'message' => 'Terjadi kesalahan saat mengambil data genre.',
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
        
        // Cek Buku di Database
        $genre_detail = Genre::where('slug', 'like', '%' . $slug . '%')->get();
        if (!empty($genre_detail)) {
            foreach($genre_detail as $detail) {
                return response()->json([
                    'message' => "Genre '{$detail->name}' sudah tersedia. Harap coba lagi!",
                    'status' => 'error'
                ], 404);
            }
        }

        $genres = new Genre;
        $genres->name = $request->name;
        $genres->slug = $slug;

        $genres->save();

        return response()->json([
            'message' => 'Data genre berhasil disimpan.',
            'status' => 'Success',
            'data' => $genres
        ], 201);
    }

    public function detail($slug) {

        $genre_detail = Genre::whereRaw('LOWER(slug) = ?', [strtolower($slug)])->get();

        if (empty($genre_detail)) {
            return response()->json([
                'message' => 'Detail Genre tidak ditemukan.',
                'status' => 'error'
            ], 404);
        }

        return response()->json($genre_detail);
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
            $genre_detail = Genre::where('id', $id)->first();
    
            if (empty($genre_detail)) {
                return response()->json([
                    'message' => 'Detail Genre tidak ditemukan.',
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
                $genre_detail->update([
                    'name' => $request->name,
                    'slug' => $slug,
                    'description' => $request->description
                ]);
    
                return response()->json([
                    'message' => 'Data Genre berhasil di-Update',
                    'status' => 'Success',
                    'data' => $genre_detail
                ], 200);
    
            } catch (\Throwable $th) {
                return response()->json([
                    'message' => "Terjadi kesalahan saat mengubah data genre.",
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
            $genre_detail = Genre::where('id', $id)->first();
    
            if (empty($genre_detail)) {
                return response()->json([
                    'message' => 'Detail genre tidak ditemukan.',
                    'status' => 'error'
                ], 404);
            }
    
            try {
                $genre_detail->delete();
                return response()->json([
                    'message' => 'Data genre berhasil di-Delete.',
                    'status' => 'Success',
                ], 200);
            } catch (\Throwable $th) {
                return response()->json([
                    'message' => "Terjadi kesalahan saat menghapus data genre.",
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
