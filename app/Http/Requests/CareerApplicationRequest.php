<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class CareerApplicationRequest extends FormRequest
{
    /** @var list<string> */
    public const CV_EXTENSIONS = ['odt', 'doc', 'docx', 'txt', 'pdf', 'jpg', 'jpeg', 'png'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:400'],
            'email' => ['required', 'email:rfc', 'max:400'],
            'phone' => ['required', 'string', 'max:50', 'regex:/^[0-9+()\-\s]{6,50}$/'],
            'job_title' => ['required', 'string', 'max:400'],
            'cv' => ['required', File::types(self::CV_EXTENSIONS)->max(5 * 1024)],
            'message' => ['nullable', 'string', 'max:2000'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('site.form.name'),
            'email' => __('site.form.email'),
            'phone' => __('site.form.phone'),
            'job_title' => __('site.form.job_title'),
            'cv' => __('site.form.cv'),
            'message' => __('site.form.message'),
        ];
    }
}
