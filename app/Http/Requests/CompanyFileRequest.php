<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id') ?? $this->id;
        $uniqueFileNumber = Rule::unique('company_files', 'file_number');

        if (!empty($id)) {
            $uniqueFileNumber->ignore($id);
        }

        return [
            'file_number' => ['required', 'string', 'max:100', $uniqueFileNumber],
            'file_name' => ['required', 'string', 'max:255'],
            'issuing_authority' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'file_number.required' => 'رقم الملف مطلوب.',
            'file_number.unique' => 'رقم الملف مستخدم بالفعل.',
            'file_number.max' => 'رقم الملف يجب ألا يتجاوز 100 حرف.',
            'file_name.required' => 'اسم الملف مطلوب.',
            'file_name.max' => 'اسم الملف يجب ألا يتجاوز 255 حرف.',
            'issuing_authority.max' => 'جهة صدوره يجب ألا تتجاوز 255 حرف.',
            'issue_date.date' => 'تاريخ اصداره غير صالح.',
            'expiry_date.date' => 'تاريخ انتهاء المستند غير صالح.',
            'expiry_date.after_or_equal' => 'تاريخ انتهاء المستند يجب أن يكون بعد أو يساوي تاريخ اصداره.',
            'is_active.required' => 'حالة السريان مطلوبة.',
            'is_active.boolean' => 'حالة السريان يجب أن تكون صحيحة.',
        ];
    }
}
