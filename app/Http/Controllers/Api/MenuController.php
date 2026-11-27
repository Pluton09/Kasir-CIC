<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    /**
     * Tenant aktif beserta kategori aktif dan menu aktif miliknya.
     */
    public function index(): JsonResponse
    {
        $tenants = Tenant::query()
            ->active()
            ->with([
                'categories' => fn ($category) => $category
                    ->where('is_active', true)
                    ->orderBy('id'),
                'categories.menus' => fn ($menu) => $menu
                    ->where('is_active', true)
                    ->orderBy('name'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'tenants' => $tenants->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'categories' => $tenant->categories->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'menus' => $category->menus->map(fn ($menu) => [
                        'id' => $menu->id,
                        'category_id' => $menu->category_id,
                        'name' => $menu->name,
                        'price' => $menu->price,
                    ])->values(),
                ])->values(),
            ])->values(),
        ]);
    }
}
