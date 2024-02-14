<?php

namespace DigitalClaim\AzureQueue;

use Illuminate\Foundation\Http\FormRequest;

class JobRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required|string',
            'message' => 'required|array',
            'meta' => 'required|array',
            'meta.dequeueCount' => 'required|numeric',
            'meta.expirationTime' => 'required|string',
            'meta.insertionTime' => 'required|string',
            'meta.nextVisibleTime' => 'required|string',
            'meta.popReceipt' => 'required|string',
        ];
    }
}