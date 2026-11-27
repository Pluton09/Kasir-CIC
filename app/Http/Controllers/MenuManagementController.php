<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMenuPriceRequest;
use App\Models\Menu;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuManagementController extends Controller
{
    /**
     * Daftar menu per tenant dengan isian harga inline.
     */
    public function index(Request $request): View
    {
        $onlyUnpriced = $request->boolean('kosong');

        $tenants = Tenant::query()
            ->with([
                'categories' => fn ($query) => $query->orderBy('id'),
                'categories.menus' => function ($query) use ($onlyUnpriced) {
                    $query->orderBy('name');

                    if ($onlyUnpriced) {
                        $query->whereNull('price');
                    }
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->each(function (Tenant $tenant) {
                $tenant->categories = $tenant->categories
                    ->filter(fn ($category) => $category->menus->isNotEmpty())
                    ->values();
            })
            ->filter(fn (Tenant $tenant) => $tenant->categories->isNotEmpty())
            ->values();

        return view('menus.index', [
            'tenants' => $tenants,
            'onlyUnpriced' => $onlyUnpriced,
            'totalMenus' => Menu::count(),
            'unpricedCount' => Menu::whereNull('price')->count(),
        ]);
    }

    public function updatePrice(UpdateMenuPriceRequest $request, Menu $menu): RedirectResponse
    {
        $menu->update(['price' => $request->integer('price')]);

        return back()->with('success', 'Harga "'.$menu->name.'" disimpan.');
    }
}
