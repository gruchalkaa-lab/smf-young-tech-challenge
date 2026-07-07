<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
    public function messages(): array
    {
        return [
            'invoice_file.required' => 'Plik faktury jest wymagany.',
            'invoice_file.mimes' => 'Dozwolone formaty: PDF, JPG, JPEG, PNG.',
            'invoice_file.max' => 'Plik nie może przekraczać 10 MB.',
        ];
    }
}
