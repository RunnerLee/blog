<?php

namespace App\Http\Requests;

use App\Models\Category;
use Auth;

class StoreArticleRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'category_id'       => 'required|int|exists:categories,id',
            'title'             => 'required|min:2|max:100',
            'original_content'  => 'required|min:2',
        ];
    }
}
