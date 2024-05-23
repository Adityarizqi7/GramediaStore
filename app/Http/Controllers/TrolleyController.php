<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductTrolley;
use App\Models\Trolley;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrolleyController extends Controller
{
    public function index(Request $request)
    {
        try {

            $per_page = 6;
            $query = Trolley::orderBy('id', 'desc');

            $trolleys = $query;
            $pagination = $query->with('products')->paginate($per_page);

            return response()->json([
                'data' => $pagination->items(),
                'meta' => [
                    'pagination' => [
                        'total_all_items' => $pagination->total(),
                        'total_items_current_page' => $trolleys->count(),
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
            dd($th->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data Product di keranjang.',
                'status' => 'error'
            ]);
        }
    }

    public function store($id, Request $request) {

        if (!is_numeric($id) || intval($id) < 0) {
            return response()->json([
                'message' => 'Path yang diberikan tidak sesuai.',
                'status' => 'error',
            ], 400);
        }
        $id = intval($id);

        if ($id) {

            $validator = Validator::make($request->all(), [
                'is_at_trolley' => ['nullable', 'boolean'],
                'quantity_purchased' => ['required', 'numeric', 'min:1'],
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }
            
            // Cek Product di Database
            $product = Product::find($id);
            if (!$product) {
                return response()->json([
                    'message' => "Product '{$product->title}' tidak ditemukan.",
                    'status' => 'error'
                ], 404);
            }

            $product_at_trolley = ProductTrolley::where('product_id', $id)->first();
            if ($product_at_trolley) {
                return response()->json([
                    'message' => "Product '{$product->title}' sudah dimasukkan di Keranjang.",
                    'status' => 'error'
                ], 404);
            }

            $trolley = new Trolley();
            $trolley->is_at_trolley = true;
            $trolley->quantity_purchased = $request->quantity_purchased;

            $trolley->save();
            $trolley->products()->attach($id);

            return response()->json([
                'message' => 'Data Product berhasil disimpan dalam keranjang',
                'status' => 'Success',
                'data' => [
                    $trolley->load('products'),
                ],
            ], 201);
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

        // Cek Product di Database
        $product = Product::find($id);

        if ($product === null) {
            return response()->json([
                'message' => "Product tidak ditemukan.",
                'status' => 'error'
            ], 404);
        }
        
        $product_at_trolley = ProductTrolley::where('product_id', $id)->first();

        if ($product_at_trolley->trolley_id) {

            $validator = Validator::make($request->all(), [
                'is_at_trolley' => ['nullable', 'boolean'],
                'quantity_purchased' => ['required', 'numeric', 'min:1'],
                'trolley_id' => ['required', 'numeric', 'min:1'],
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            if ($product_at_trolley) {
                if ($request->trolley_id === $product_at_trolley->trolley_id) {

                    if (!is_numeric($product_at_trolley->trolley_id) || intval($product_at_trolley->trolley_id) < 0) {
                        return response()->json([
                            'message' => 'Path yang diberikan tidak sesuai.',
                            'status' => 'error',
                        ], 400);
                    }

                    $trolley_id_update = intval($product_at_trolley->trolley_id);

                    if ($trolley_id_update) {
                        $trolley = Trolley::find($product_at_trolley->trolley_id);
                        // Update
                        $trolley->update([
                            'is_at_trolley' => true,
                            'quantity_purchased' => $request->quantity_purchased,
                        ]);
        
                        $trolley->products()->sync($product);
        
                        return response()->json([
                            'message' => 'Data Product dalam keranjang berhasil di-Update',
                            'status' => 'Success',
                            'data' => [
                                $trolley->load('products'),
                            ],
                        ], 201);
                    } else {
                        // Error bukan id
                        return response()->json([
                            'message' => 'id trolley is required.',
                            'status' => 'error'
                        ], 404);
                    }
                }
            } else {
                return response()->json([
                    'message' => "Product '{$product->title}' tidak ada di Keranjang.",
                    'status' => 'error'
                ], 404);
            }

        }

        return response()->json([
            'message' => 'Id Trolley is false.',
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

        // Cek Product di Database
        $product = Product::find($id);

        if ($product === null) {
            return response()->json([
                'message' => "Product tidak ditemukan.",
                'status' => 'error'
            ], 404);
        }
        
        $product_at_trolley = ProductTrolley::where('product_id', $id)->first();

        if ($product_at_trolley->trolley_id) {
            if ($product_at_trolley) {
                if ($product_at_trolley->trolley_id) {

                    if (!is_numeric($product_at_trolley->trolley_id) || intval($product_at_trolley->trolley_id) < 0) {
                        return response()->json([
                            'message' => 'Path yang diberikan tidak sesuai.',
                            'status' => 'error',
                        ], 400);
                    }

                    $trolley_id_update = intval($product_at_trolley->trolley_id);

                    if ($trolley_id_update) {

                        $trolley = Trolley::find($product_at_trolley->trolley_id);

                        $trolley->delete();
                        $trolley->products()->detach();
                        
                        return response()->json([
                            'message' => 'Data Product dalam keranjang berhasil dihapus',
                            'status' => 'Success'
                        ], 201);
                    } else {
                        // Error bukan id
                        return response()->json([
                            'message' => 'Id Trolley is required.',
                            'status' => 'error'
                        ], 404);
                    }
                }
            } else {
                return response()->json([
                    'message' => "Product '{$product->title}' tidak ada di Keranjang.",
                    'status' => 'error'
                ], 404);
            }

        } else {
            return response()->json([
                'message' => 'Id Trolley is false.',
                'status' => 'error'
            ], 404);
        }
    }
}