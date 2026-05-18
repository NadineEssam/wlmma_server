<?php

namespace App\Http\Requests;

use App\Models\Notification;
use Illuminate\Foundation\Http\FormRequest;

class PushNotificationRequest extends FormRequest
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
            'title_en' => 'required|string',
            'title_ar' => 'required|string',
            'body_en' => 'required|string',
            'body_ar' => 'required|string',
            'type' => 'required|in:' . implode(',', Notification::NOTIFICATIONS_ALLOWED_TYPES),
            'book_id' => 'nullable',
            'order_id' => 'nullable',
            'send_to_all' => 'required|boolean',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    protected function withValidator($validator)
    {
        $validator->sometimes('user_id', [
            'required',
            'exists:users,id'
        ], function ($input) {
            return $input->send_to_all === false;
        });
    }
}
