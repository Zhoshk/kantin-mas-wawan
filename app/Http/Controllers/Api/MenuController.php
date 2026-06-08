<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    // GET /api/menu — semua menu aktif (untuk pelanggan)
    public function index(): JsonResponse
    {
        $menu = MenuItem::active()->orderBy('category')->orderBy('name')->get();
        return response()->json(['data' => $menu]);
    }

    // GET /api/admin/menu — semua menu (untuk admin, termasuk nonaktif)
    public function adminIndex(): JsonResponse
    {
        $menu = MenuItem::orderBy('category')->orderBy('name')->get();
        return response()->json(['data' => $menu]);
    }

    // POST /api/admin/menu — tambah menu baru
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'price'       => 'required|integer|min:0',
            'emoji'       => 'nullable|string|max:10',
            'image'       => 'nullable|string',
            'stock'       => 'nullable|integer|min:0',
            'category'    => 'required|in:burger,rice,snack,drink',
            'is_hot'      => 'boolean',
            'is_active'   => 'boolean',
            'variants'    => 'nullable|array',
            'variants.*.name'  => 'required|string',
            'variants.*.price' => 'required|integer|min:0',
            'variants.*.emoji' => 'nullable|string',
        ]);

        $item = MenuItem::create($validated);
        return response()->json(['message' => 'Menu berhasil ditambahkan', 'data' => $item], 201);
    }

    // GET /api/admin/menu/{id} — detail satu menu
    public function show(MenuItem $menuItem): JsonResponse
    {
        return response()->json(['data' => $menuItem]);
    }

    // PUT /api/admin/menu/{id} — edit menu
    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:255',
            'price'       => 'sometimes|integer|min:0',
            'emoji'       => 'nullable|string|max:10',
            'image'       => 'nullable|string',
            'stock'       => 'nullable|integer|min:0',
            'category'    => 'sometimes|in:burger,rice,snack,drink',
            'is_hot'      => 'boolean',
            'is_active'   => 'boolean',
            'variants'    => 'nullable|array',
            'variants.*.name'  => 'required|string',
            'variants.*.price' => 'required|integer|min:0',
            'variants.*.emoji' => 'nullable|string',
        ]);

        $menuItem->update($validated);
        return response()->json(['message' => 'Menu berhasil diupdate', 'data' => $menuItem]);
    }

    // DELETE /api/admin/menu/{id} — hapus menu
    public function destroy(MenuItem $menuItem): JsonResponse
    {
        $menuItem->delete();
        return response()->json(['message' => 'Menu berhasil dihapus']);
    }

    // PATCH /api/admin/menu/{id}/toggle — aktif/nonaktif
    public function toggle(MenuItem $menuItem): JsonResponse
    {
        $menuItem->update(['is_active' => !$menuItem->is_active]);
        $status = $menuItem->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return response()->json(['message' => "Menu berhasil $status", 'data' => $menuItem]);
    }

    // GET /api/menu/{id}/stock — cek stok real-time (publik, tanpa auth)
    // Dipakai frontend untuk validasi sebelum tambah item ke keranjang
    public function stock(MenuItem $menuItem): JsonResponse
    {
        return response()->json([
            'id'        => $menuItem->id,
            'name'      => $menuItem->name,
            'stock'     => $menuItem->stock,      // null = tidak terbatas
            'is_active' => $menuItem->is_active,
        ]);
    }
}