<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return new CategoryResource(true, 'List Data Category', $categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $categories = Category::create([
            'name' => $request->name,
        ]);

        return new CategoryResource(true, 'Data berhasil ditambahkan', $categories);
    }

    public function update(Request $request, $id)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required',  // Pastikan nama kategori wajib diisi
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
            ], 404);
        }

        // Update kategori
        $category->name = $request->name;
        $category->save();  // Simpan perubahan

        return new CategoryResource(true, 'Data berhasil diperbarui', $category);
    }

    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $category->delete();

        return new CategoryResource(true, 'Data Berhasil Dihapus!', null);
    }
}
