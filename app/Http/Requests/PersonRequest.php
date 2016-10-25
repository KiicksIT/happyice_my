<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class PersonRequest extends Request
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
        $person = $this->route('person');

        return [
            'cust_id' => 'required|unique:people,cust_id,'.$person,
            'name'=>'min:3',
            'roc_no' => 'unique:people,roc_no,'.$person,
            'email'=>'email|unique:people,email,'.$person,
            'contact'=>array('regex:/^([0-9\s\-\+\(\)]*)$/'),
            'alt_contact'=>array('regex:/^([0-9\s\-\+\(\)]*)$/'),
            'postcode' => 'numeric',
            'cost_rate' => 'integer',
        ];
    }

    public function messages()
    {
        return [
            'cust_id.required' => 'Please fill in the ID',
            'cust_id.unique' => 'The ID has been taken',
            'name.min' => 'Attn To must more than 3 words',
            'roc_no.unique' => 'The ROC No has been taken',
            'email.unique' => 'The Email has been taken',
            'email.email' => 'The email format is incorrect',
            'contact.regex' => 'The contact number only accepts 0-9, +, -',
            'alt_contact.regex' => 'The Alt contact number only accepts 0-9, +, -',
            'postcode.numeric' => 'The postcode must be in numbers',
            'cost_rate.integer' => 'Cost rate need to be in whole number',
        ];
    }
}
