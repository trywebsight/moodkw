<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Support\CheckoutAddressRules;
use App\Support\CheckoutValidation;
use App\Support\KuwaitPhone;
use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => KuwaitPhone::rules(),
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'governorate_id' => ['required', 'integer', Rule::exists('governorates', 'id')->where('is_active', true)],
            'area_id' => [
                'required',
                'integer',
                Rule::exists('areas', 'id')->where(function ($query) {
                    $query->where('is_active', true)
                        ->where('governorate_id', $this->input('governorate_id'));
                }),
            ],
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'payment_token' => [
                'nullable',
                'string',
                'max:4096',
                Rule::requiredIf(fn (): bool => in_array(
                    $this->input('payment_method'),
                    [PaymentMethod::Card->value, PaymentMethod::ApplePay->value],
                    true,
                )),
            ],
        ], CheckoutAddressRules::rules($this->input('address_type')));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $methodValue = $this->input('payment_method');

            if (! is_string($methodValue) || $methodValue === '') {
                return;
            }

            $method = PaymentMethod::tryFrom($methodValue);

            if ($method === null) {
                return;
            }

            if (! app(SettingsService::class)->isPaymentMethodEnabled($method)) {
                $validator->errors()->add('payment_method', __('checkout.payment_method_disabled'));
            }
        });
    }

    public function messages(): array
    {
        return CheckoutValidation::messages();
    }

    public function attributes(): array
    {
        return CheckoutValidation::attributes();
    }
}
