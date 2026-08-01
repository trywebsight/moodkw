<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class RespondService
{
    private const DEFAULT_BASE_URL = 'https://api.respond.io/v2';

    private const DEFAULT_LANGUAGE = 'ar';

    private const MEDIA_HEADER_FORMATS = ['DOCUMENT', 'IMAGE', 'VIDEO'];

    private ?array $normalizedTemplates = null;

    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->apiToken()) && $this->channelId() !== null;
    }

    public function isEnabled(): bool
    {
        $setting = $this->settingsService->get();

        return (bool) ($setting->respond_whatsapp_enabled ?? false) && $this->isConfigured();
    }

    public function sendPaymentLink(Order $order): array
    {
        $setting = $this->settingsService->get();
        $templateName = (string) ($setting->respond_payment_template_name
            ?: config('services.respond.payment_template', ''));

        if (blank($templateName)) {
            return [
                'success' => false,
                'message' => 'Payment WhatsApp template is not configured.',
                'data' => [],
            ];
        }

        $language = (string) ($setting->respond_payment_template_language
            ?: config('services.respond.payment_template_language', self::DEFAULT_LANGUAGE));

        $variables = $this->orderVariables($order);
        $fields = $this->paymentTemplateFields($templateName);
        $components = $this->buildMappedTemplateComponents($templateName, $fields, $variables);

        return $this->sendTemplateWithLanguage(
            (string) $order->customer_phone,
            $templateName,
            $language,
            $components,
        );
    }

    public function sendOrderConfirmation(Order $order): array
    {
        $setting = $this->settingsService->get();
        $templateName = (string) ($setting->respond_order_confirmation_template_name ?? '');

        if (blank($templateName)) {
            return [
                'success' => true,
                'message' => 'Order confirmation template is not configured.',
                'skipped' => true,
                'data' => [],
            ];
        }

        $language = (string) ($setting->respond_order_confirmation_template_language ?? self::DEFAULT_LANGUAGE);
        $fields = $this->orderConfirmationTemplateFields($templateName);
        $variables = $this->orderVariables($order);
        $components = $this->buildMappedTemplateComponents($templateName, $fields, $variables);

        return $this->sendTemplateWithLanguage(
            (string) $order->customer_phone,
            $templateName,
            $language,
            $components,
        );
    }

    public function getTemplates(): array
    {
        if ($error = $this->validateConfiguration(requireChannel: true)) {
            return $error;
        }

        try {
            $response = $this->httpClient()->get($this->templatesEndpoint());

            if ($response->failed()) {
                return $this->handleFailure($response, 'Respond.io templates request failed', 'Failed to get Respond.io templates');
            }

            return [
                'success' => true,
                'data' => $this->responseData($response->json()),
            ];
        } catch (Exception $e) {
            return $this->handleException($e, 'Exception occurred while getting Respond.io templates');
        }
    }

    public function getNormalizedTemplates(): array
    {
        if ($this->normalizedTemplates !== null) {
            return $this->normalizedTemplates;
        }

        $response = $this->getTemplates();

        if (! ($response['success'] ?? false)) {
            return $this->normalizedTemplates = [];
        }

        return $this->normalizedTemplates = collect($this->extractTemplateItems($response['data'] ?? []))
            ->map(fn (array $template) => $this->normalizeTemplate($template))
            ->filter(fn (array $template) => ! empty($template['name']))
            ->values()
            ->all();
    }

    public function getTemplateOptions(): array
    {
        $options = [];

        foreach ($this->getNormalizedTemplates() as $template) {
            $language = $template['language'] ?? 'en';
            $status = $template['status'] ?? 'unknown';
            $options[$template['name']] = "{$template['name']} ({$language}, {$status})";
        }

        return $options;
    }

    public function findTemplate(string $templateName): ?array
    {
        $templates = $this->getNormalizedTemplates();

        foreach ($templates as $template) {
            if (($template['name'] ?? null) === $templateName) {
                return $template;
            }
        }

        $normalizedTemplateName = strtolower($templateName);

        foreach ($templates as $template) {
            if (strtolower((string) ($template['name'] ?? '')) === $normalizedTemplateName) {
                return $template;
            }
        }

        return null;
    }

    public function getTemplateMappingFields(string $templateName): array
    {
        $template = $this->findTemplate($templateName);

        if (! $template) {
            return [];
        }

        $fields = [];

        foreach ($template['components'] ?? [] as $component) {
            $componentType = strtoupper((string) ($component['type'] ?? ''));

            if ($componentType === 'HEADER' && ! empty($component['format'])) {
                $fields[] = $this->buildHeaderMappingField((string) $component['format']);
            }

            if ($componentType === 'BODY' && ! empty($component['text'])) {
                for ($i = 1; $i <= $this->countParameters((string) $component['text']); $i++) {
                    $fields[] = [
                        'type' => 'BODY',
                        'format' => 'TEXT',
                        'label' => 'Body {{'.$i.'}}',
                        'variable' => $i === 1 ? 'customer_name' : 'order_number',
                    ];
                }
            }

            if ($componentType === 'BUTTONS') {
                foreach ($component['buttons'] ?? [] as $index => $button) {
                    if (strtoupper((string) ($button['type'] ?? '')) !== 'URL') {
                        continue;
                    }

                    $url = (string) ($button['url'] ?? '');

                    for ($i = 1; $i <= $this->countParameters($url); $i++) {
                        $fields[] = [
                            'type' => 'BUTTON',
                            'format' => 'TEXT',
                            'sub_type' => 'url',
                            'index' => $index,
                            'button_text' => $button['text'] ?? 'Button',
                            'button_url' => $button['url'] ?? null,
                            'label' => 'Button '.($button['text'] ?? $index + 1).' {{'.$i.'}}',
                            'variable' => 'payment_url',
                        ];
                    }
                }
            }
        }

        return $fields;
    }

    public function sendTemplateWithLanguage(string $phoneNumber, string $templateName, string $languageCode, array $components = []): array
    {
        if ($error = $this->validateConfiguration()) {
            return $error;
        }

        $templateName = $this->resolveTemplateName($templateName);

        $payload = [
            'channelId' => $this->channelId(),
            'message' => [
                'type' => 'whatsapp_template',
                'template' => [
                    'name' => $templateName,
                    'languageCode' => $languageCode,
                    'components' => $this->normalizeTemplateComponents($components),
                ],
            ],
        ];

        try {
            $this->ensureContactExists($phoneNumber);

            $endpoint = $this->messageEndpointForPhoneNumber($phoneNumber);

            $this->logger()->info('Respond.io template payload prepared', [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'phone_number' => $phoneNumber,
            ]);

            $response = $this->httpClient()->post($endpoint, $payload);

            if ($response->failed()) {
                return $this->handleFailure(
                    $response,
                    'Respond.io API request failed',
                    'Failed to send template via Respond.io',
                    [
                        'payload' => $payload,
                        'phone_number' => $phoneNumber,
                    ],
                );
            }

            $this->logger()->info('Respond.io API response', [
                'body' => $response->body(),
                'payload' => $payload,
                'phone_number' => $phoneNumber,
                'response' => $this->responseLogContext($response),
            ]);

            return [
                'success' => true,
                'message' => 'Template sent via Respond.io',
                'data' => $this->responseData($response->json()),
            ];
        } catch (Exception $e) {
            return $this->handleException($e, 'Exception occurred while sending template via Respond.io', ['payload' => $payload]);
        }
    }

    private function paymentTemplateFields(string $templateName): array
    {
        return $this->templateFieldsFromSettings('respond_payment_template_fields', $templateName)
            ?: $this->defaultPaymentTemplateFields();
    }

    private function orderConfirmationTemplateFields(string $templateName): array
    {
        $fields = $this->templateFieldsFromSettings('respond_order_confirmation_template_fields', $templateName);

        if (! empty($fields)) {
            return $fields;
        }

        return $this->getTemplateMappingFields($templateName);
    }

    private function templateFieldsFromSettings(string $column, string $templateName): array
    {
        $setting = $this->settingsService->get();
        $fields = $this->normalizeTemplateFields($setting->{$column} ?? []);

        if (! empty($fields)) {
            return $fields;
        }

        return $this->getTemplateMappingFields($templateName);
    }

    private function defaultPaymentTemplateFields(): array
    {
        return [
            [
                'type' => 'BODY',
                'format' => 'TEXT',
                'label' => 'Body {{1}}',
                'variable' => 'customer_name',
            ],
            [
                'type' => 'BODY',
                'format' => 'TEXT',
                'label' => 'Body {{2}}',
                'variable' => 'order_number',
            ],
            [
                'type' => 'BUTTON',
                'format' => 'TEXT',
                'sub_type' => 'url',
                'index' => 0,
                'button_text' => 'Pay',
                'label' => 'Button Pay',
                'variable' => 'payment_url',
            ],
        ];
    }

    private function orderVariables(Order $order): array
    {
        $order->loadMissing('product');

        $paymentUrl = $order->signedPaymentUrl() ?? '';
        $invoicePdfName = "invoice-{$order->order_number}.pdf";

        return [
            'customer_name' => (string) $order->customer_name,
            'customer_phone' => $this->formatPhoneNumberForCustomer((string) $order->customer_phone),
            'order_number' => (string) $order->order_number,
            'order_total' => number_format((float) $order->total, 3, '.', ''),
            'order_currency' => $this->settingsService->getCurrency(),
            'product_name' => (string) ($order->product?->name ?? ''),
            'payment_url' => $paymentUrl,
            'invoice_pdf_url' => route('invoices.download', $order),
            'invoice_pdf_name' => $invoicePdfName,
        ];
    }

    private function ensureContactExists(string $phoneNumber): void
    {
        try {
            $endpoint = $this->contactEndpointForPhoneNumber($phoneNumber);
            $phoneWithoutPlus = ltrim($this->formatPhoneNumberForCustomer($phoneNumber), '+');
            $payload = [
                'firstName' => $phoneWithoutPlus,
                'phone' => $phoneWithoutPlus,
            ];

            $response = $this->httpClient()->post($endpoint, $payload);

            if ($response->failed()) {
                $this->logger()->warning('Respond.io contact save failed (suppressed)', [
                    'status' => $response->status(),
                    'payload' => $payload,
                    'response' => $response->body(),
                    'phone_number' => $phoneNumber,
                ]);
            }
        } catch (Exception $e) {
            $this->logger()->warning('Respond.io contact save threw an exception (suppressed)', [
                'error' => $e->getMessage(),
                'phone_number' => $phoneNumber,
            ]);
        }
    }

    private function buildMappedTemplateComponents(string $templateName, array $fields, array $variables): array
    {
        $template = $this->findTemplate($templateName);
        $components = [];
        $bodyParameters = [];
        $buttonRows = [];

        foreach ($fields as $field) {
            $type = strtoupper((string) ($field['type'] ?? ''));

            if ($type === 'HEADER') {
                if ($header = $this->buildHeaderComponent($field, $variables)) {
                    $components[] = $header;
                }

                continue;
            }

            if ($type === 'BODY') {
                $bodyParameters[] = [
                    'type' => 'text',
                    'text' => $this->mappedValue($field, $variables),
                ];

                continue;
            }

            if ($type === 'BUTTON') {
                $buttonRows[] = $field;
            }
        }

        if (! empty($bodyParameters)) {
            $body = [
                'type' => 'body',
                'parameters' => $bodyParameters,
            ];

            if ($text = $this->componentText($template, 'BODY')) {
                $body['text'] = $text;
            }

            $components[] = $body;
        }

        if (! empty($buttonRows)) {
            $components[] = $this->buildButtonsComponent($buttonRows, $variables);
        }

        return $components;
    }

    private function buildButtonsComponent(array $buttonRows, array $variables): array
    {
        return [
            'type' => 'buttons',
            'buttons' => collect($buttonRows)
                ->groupBy(fn (array $field) => $field['index'] ?? 0)
                ->map(function ($fields) use ($variables) {
                    $first = $fields->first();

                    return [
                        'type' => strtolower((string) ($first['sub_type'] ?? 'url')),
                        'text' => $first['button_text'] ?? 'Button',
                        'url' => $first['button_url'] ?? null,
                        'parameters' => $fields
                            ->map(fn (array $field) => [
                                'type' => 'text',
                                'text' => $this->mappedValue($field, $variables),
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function buildHeaderComponent(array $field, array $variables): ?array
    {
        $format = strtoupper((string) ($field['format'] ?? ''));
        $header = [
            'type' => 'header',
            'format' => strtolower($format),
        ];

        if (in_array($format, self::MEDIA_HEADER_FORMATS, true)) {
            $mediaType = strtolower($format);
            $header['parameters'] = [
                [
                    'type' => $mediaType,
                    $mediaType => [
                        'link' => $this->mappedValue($field, $variables, 'invoice_pdf_url'),
                        'filename' => $this->mappedValue([
                            'variable' => $field['filename_variable'] ?? 'invoice_pdf_name',
                            'custom_value' => $field['filename_custom_value'] ?? null,
                        ], $variables, 'invoice_pdf_name'),
                    ],
                ],
            ];

            return $header;
        }

        if ($format === 'TEXT') {
            $header['parameters'] = [
                [
                    'type' => 'text',
                    'text' => $this->mappedValue($field, $variables),
                ],
            ];

            return $header;
        }

        return null;
    }

    private function buildHeaderMappingField(string $format): array
    {
        $format = strtoupper($format);

        if (in_array($format, self::MEDIA_HEADER_FORMATS, true)) {
            return [
                'type' => 'HEADER',
                'format' => $format,
                'label' => 'Header '.ucfirst(strtolower($format)),
                'variable' => 'invoice_pdf_url',
                'filename_variable' => 'invoice_pdf_name',
            ];
        }

        return [
            'type' => 'HEADER',
            'format' => $format,
            'label' => 'Header Text',
            'variable' => 'customer_name',
        ];
    }

    private function mappedValue(array $field, array $variables, string $defaultVariable = ''): string
    {
        $variable = (string) ($field['variable'] ?? $defaultVariable);

        if ($variable === 'custom') {
            return (string) ($field['custom_value'] ?? '');
        }

        return (string) ($variables[$variable ?: $defaultVariable] ?? '');
    }

    private function normalizeTemplateFields(mixed $fields): array
    {
        if (is_string($fields)) {
            $fields = json_decode($fields, true);
        }

        return is_array($fields) ? $fields : [];
    }

    private function resolveTemplateName(string $templateName): string
    {
        $template = $this->findTemplate($templateName);

        return (string) ($template['name'] ?? $templateName);
    }

    private function apiToken(): string
    {
        $setting = $this->settingsService->get()->respond_channel_api_token;

        if (filled($setting)) {
            return (string) $setting;
        }

        return (string) config('services.respond.channel_api_token', '');
    }

    private function channelId(): ?int
    {
        $setting = $this->settingsService->get()->respond_channel_id;

        if (is_numeric($setting)) {
            return (int) $setting;
        }

        $channelId = config('services.respond.channel_id');

        return is_numeric($channelId) ? (int) $channelId : null;
    }

    private function baseUrl(): string
    {
        $setting = $this->settingsService->get()->respond_base_url;

        if (filled($setting)) {
            return rtrim((string) $setting, '/');
        }

        return rtrim((string) config('services.respond.base_url', self::DEFAULT_BASE_URL), '/');
    }

    private function messageEndpointForPhoneNumber(string $phoneNumber): string
    {
        return $this->contactEndpointForPhoneNumber($phoneNumber).'/message';
    }

    private function contactEndpointForPhoneNumber(string $phoneNumber): string
    {
        $identifier = 'phone:'.$this->formatPhoneNumberForCustomer($phoneNumber);

        return $this->baseUrl().'/contact/'.rawurlencode($identifier);
    }

    private function templatesEndpoint(): string
    {
        return "{$this->baseUrl()}/space/channel/{$this->channelId()}/template";
    }

    private function httpClient(): PendingRequest
    {
        return Http::withToken($this->apiToken())
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);
    }

    private function validateConfiguration(bool $requireChannel = false): ?array
    {
        if (blank($this->apiToken())) {
            return [
                'success' => false,
                'message' => 'Respond.io API token is not configured.',
                'data' => [],
            ];
        }

        if ($requireChannel && $this->channelId() === null) {
            return [
                'success' => false,
                'message' => 'Respond.io channel ID is not configured.',
                'data' => [],
            ];
        }

        return null;
    }

    private function handleFailure(Response $response, string $logMessage, string $fallbackMessage, array $logContext = []): array
    {
        $this->logger()->error($logMessage, array_merge([
            'status' => $response->status(),
            'body' => $response->body(),
        ], $logContext));

        $responseData = $this->responseData($response->json());

        return [
            'success' => false,
            'message' => $this->errorMessage($responseData, $fallbackMessage),
            'data' => $responseData,
        ];
    }

    private function handleException(Exception $e, string $logMessage, array $logContext = []): array
    {
        $this->logger()->error($logMessage, array_merge(['error' => $e->getMessage()], $logContext));

        return [
            'success' => false,
            'message' => 'Exception occurred: '.$e->getMessage(),
            'data' => [],
        ];
    }

    private function normalizeTemplateComponents(array $components): array
    {
        $normalized = [];
        $buttons = [];

        foreach ($components as $component) {
            $type = strtolower((string) ($component['type'] ?? ''));

            if ($type === 'button') {
                $buttons[] = [
                    'type' => strtolower((string) ($component['sub_type'] ?? 'url')),
                    'parameters' => $component['parameters'] ?? [],
                ];

                continue;
            }

            if ($type === 'buttons') {
                $normalized[] = $component;

                continue;
            }

            if ($type === 'header') {
                $component['type'] = 'header';
                $component['format'] = strtolower((string) ($component['format'] ?? $this->detectHeaderFormat($component)));
                $normalized[] = $component;

                continue;
            }

            if ($type === 'body' || $type === 'footer') {
                $component['type'] = $type;
                $normalized[] = $component;
            }
        }

        if (! empty($buttons)) {
            $normalized[] = [
                'type' => 'buttons',
                'buttons' => $buttons,
            ];
        }

        return $normalized;
    }

    private function detectHeaderFormat(array $component): string
    {
        $parameterType = $component['parameters'][0]['type'] ?? null;

        return strtolower((string) ($parameterType ?: 'text'));
    }

    private function extractTemplateItems(array $data): array
    {
        if (array_is_list($data)) {
            return $data;
        }

        foreach (['data', 'templates', 'messageTemplates', 'items'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return array_is_list($data[$key]) ? $data[$key] : ($data[$key]['data'] ?? []);
            }
        }

        return [];
    }

    private function normalizeTemplate(array $template): array
    {
        if (! empty($template['metadata']) && is_string($template['metadata'])) {
            $metadata = json_decode($template['metadata'], true);

            if (is_array($metadata)) {
                $template = array_merge($template, $metadata);
            }
        }

        $template['language'] = $template['language']
            ?? $template['languageCode']
            ?? $template['language_code']
            ?? 'en';
        $template['status'] = $template['status'] ?? 'unknown';
        $template['components'] = collect($template['components'] ?? [])
            ->map(fn (array $component) => $this->normalizeComponent($component))
            ->values()
            ->all();

        return $template;
    }

    private function normalizeComponent(array $component): array
    {
        $component['type'] = strtoupper((string) ($component['type'] ?? ''));

        if (isset($component['format'])) {
            $component['format'] = strtoupper((string) $component['format']);
        }

        if ($component['type'] === 'BUTTONS') {
            $component['buttons'] = collect($component['buttons'] ?? [])
                ->map(function (array $button) {
                    $button['type'] = strtoupper((string) ($button['type'] ?? ''));

                    return $button;
                })
                ->values()
                ->all();
        }

        return $component;
    }

    private function componentText(?array $template, string $componentType): ?string
    {
        foreach ($template['components'] ?? [] as $component) {
            if (($component['type'] ?? null) === $componentType) {
                return $component['text'] ?? null;
            }
        }

        return null;
    }

    private function countParameters(string $text): int
    {
        preg_match_all('/{{\s*\d+\s*}}/', $text, $matches);

        return count($matches[0]);
    }

    private function errorMessage(?array $responseData, string $fallback): string
    {
        return $responseData['message']
            ?? $responseData['error']['message']
            ?? $responseData['description']
            ?? $fallback;
    }

    private function responseData(mixed $responseData): array
    {
        return is_array($responseData) ? $responseData : [];
    }

    private function responseLogContext(Response $response): array
    {
        try {
            $json = $response->json();
        } catch (\Throwable) {
            $json = null;
        }

        return [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'failed' => $response->failed(),
            'body' => $response->body(),
            'json' => $json,
        ];
    }

    private function formatPhoneNumberForCustomer(string $phoneNumber): string
    {
        $digitsOnly = preg_replace('/[^\d]/', '', $phoneNumber) ?? '';

        if (str_starts_with($digitsOnly, '00')) {
            $digitsOnly = substr($digitsOnly, 2);
        }

        $defaultCountryCode = '965';
        $localNumberLength = 8;
        $fullLength = strlen($defaultCountryCode) + $localNumberLength;

        while (
            strlen($digitsOnly) > $fullLength
            && str_starts_with($digitsOnly, $defaultCountryCode.$defaultCountryCode)
        ) {
            $digitsOnly = substr($digitsOnly, strlen($defaultCountryCode));
        }

        if (strlen($digitsOnly) === $localNumberLength) {
            $digitsOnly = "{$defaultCountryCode}{$digitsOnly}";
        }

        return "+{$digitsOnly}";
    }

    private function logger(): LoggerInterface
    {
        return Log::channel('respond');
    }
}
