<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_no' => ['nullable', 'string', 'max:20'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', Rule::in(['Tunai', 'QRIS', 'Transfer'])],
            'paid_amount' => [
                Rule::requiredIf($this->input('payment_method') === 'Tunai'),
                'nullable',
                'integer',
                'min:0',
                'max:100000000',
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function messages(): array
    {
        return [
            'table_no.max' => 'Nomor meja maksimal 20 karakter.',
            'customer_name.max' => 'Nama pelanggan maksimal 100 karakter.',
            'note.max' => 'Catatan maksimal 255 karakter.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'paid_amount.required' => 'Uang diterima wajib diisi untuk pembayaran tunai.',
            'paid_amount.integer' => 'Uang diterima harus berupa angka.',
            'paid_amount.min' => 'Uang diterima tidak boleh negatif.',
            'paid_amount.max' => 'Uang diterima terlalu besar.',
            'items.required' => 'Pesanan minimal berisi satu menu.',
            'items.array' => 'Format item pesanan tidak valid.',
            'items.min' => 'Pesanan minimal berisi satu menu.',
            'items.*.menu_id.required' => 'Menu wajib dipilih.',
            'items.*.menu_id.integer' => 'Menu tidak valid.',
            'items.*.menu_id.exists' => 'Menu tidak ditemukan.',
            'items.*.qty.required' => 'Jumlah menu wajib diisi.',
            'items.*.qty.integer' => 'Jumlah menu harus berupa angka.',
            'items.*.qty.min' => 'Jumlah menu minimal 1.',
            'items.*.qty.max' => 'Jumlah menu maksimal 99.',
        ];
    }
}
