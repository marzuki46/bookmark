<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:bookmark,note,prompt,snippet,file,secret',
            'title' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:2048',
            'content' => 'nullable|string',
            'metadata' => 'nullable|json',
            'folder_id' => 'nullable|exists:folders,id',
            'favorite' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ];
    }
}
