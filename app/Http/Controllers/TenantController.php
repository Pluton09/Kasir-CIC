<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::query()
            ->withCount(['categories', 'menus'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('tenants.index', [
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:tenants,name'],
        ], $this->messages());

        Tenant::create([
            'name' => $data['name'],
            'sort_order' => (int) Tenant::max('sort_order') + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Tenant "'.$data['name'].'" ditambahkan.');
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tenants', 'name')->ignore($tenant->id),
            ],
        ], $this->messages());

        $tenant->update(['name' => $data['name']]);

        return back()->with('success', 'Nama tenant diubah menjadi "'.$data['name'].'".');
    }

    public function toggle(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => ! $tenant->is_active]);

        $status = $tenant->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', 'Tenant "'.$tenant->name.'" '.$status.'.');
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'name.required' => 'Nama tenant wajib diisi.',
            'name.string' => 'Nama tenant harus berupa teks.',
            'name.max' => 'Nama tenant maksimal 100 karakter.',
            'name.unique' => 'Nama tenant sudah dipakai.',
        ];
    }
}
