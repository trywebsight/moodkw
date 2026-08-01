<?php

namespace App\Filament\Pages;

use App\Enums\TapMode;
use App\Models\Setting;
use App\Services\OrderNotificationService;
use App\Services\RespondService;
use App\Services\WorkingHoursService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;

/**
 * @property-read Schema $form
 */
class Settings extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 10;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(WorkingHoursService $workingHoursService): void
    {
        $setting = Setting::current();
        $workingHours = $workingHoursService->getWorkingHours($setting);

        $this->form->fill([
            'tap_secret_key' => null,
            'tap_public_key' => $setting->tap_public_key,
            'tap_mode' => $setting->tap_mode?->value ?? TapMode::Test->value,
            'payment_knet_enabled' => $setting->payment_knet_enabled,
            'payment_card_enabled' => $setting->payment_card_enabled,
            'payment_apple_pay_enabled' => $setting->payment_apple_pay_enabled,
            'payment_cod_enabled' => $setting->payment_cod_enabled,
            'store_name' => $setting->store_name,
            'store_logo' => $setting->store_logo,
            'store_phone' => $setting->store_phone,
            'store_whatsapp' => $setting->store_whatsapp,
            'currency' => $setting->currency,
            'seo_title' => $setting->seo_title,
            'seo_title_ar' => $setting->seo_title_ar,
            'seo_description' => $setting->seo_description,
            'seo_description_ar' => $setting->seo_description_ar,
            'seo_keywords' => $setting->seo_keywords,
            'seo_keywords_ar' => $setting->seo_keywords_ar,
            'og_image' => $setting->og_image,
            'working_hours_enabled' => $setting->working_hours_enabled,
            'working_hours' => $workingHours,
            'working_hours_template' => [
                'enabled' => $workingHours['monday']['enabled'] ?? true,
                'open' => $workingHours['monday']['open'] ?? '09:00',
                'close' => $workingHours['monday']['close'] ?? '22:00',
            ],
            'timezone' => $setting->timezone ?? 'Asia/Kuwait',
            'notification_email' => $setting->notification_email,
            'order_email_notifications' => $setting->order_email_notifications,
            'order_sound_notifications' => $setting->order_sound_notifications,
            'respond_whatsapp_enabled' => $setting->respond_whatsapp_enabled,
            'respond_channel_api_token' => null,
            'respond_channel_id' => $setting->respond_channel_id,
            'respond_base_url' => $setting->respond_base_url,
            'respond_payment_template_name' => $setting->respond_payment_template_name,
            'respond_payment_template_language' => $setting->respond_payment_template_language ?? 'ar',
            'respond_payment_template_fields' => $setting->respond_payment_template_fields ?? [],
            'respond_order_confirmation_template_name' => $setting->respond_order_confirmation_template_name,
            'respond_order_confirmation_template_language' => $setting->respond_order_confirmation_template_language ?? 'ar',
            'respond_order_confirmation_template_fields' => $setting->respond_order_confirmation_template_fields ?? [],
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Store Settings';
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->model(Setting::current())
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $dayFields = [];

        foreach ($days as $day) {
            $dayFields[] = Section::make(ucfirst($day))
                ->schema([
                    Toggle::make("working_hours.{$day}.enabled")
                        ->label('Open'),
                    TextInput::make("working_hours.{$day}.open")
                        ->label('Open Time')
                        ->placeholder('09:00'),
                    TextInput::make("working_hours.{$day}.close")
                        ->label('Close Time')
                        ->placeholder('22:00'),
                ])
                ->columns(3)
                ->compact();
        }

        return $schema
            ->components([
                Tabs::make('Settings')
                    ->id('store-settings')
                    ->persistTabInQueryString('settings_tab')
                    ->tabs([
                        Tab::make('Store')
                            ->icon(Heroicon::OutlinedBuildingStorefront)
                            ->schema([
                                TextInput::make('store_name')->required()->maxLength(255),
                                FileUpload::make('store_logo')
                                    ->image()
                                    ->directory('store')
                                    ->visibility('public')
                                    ->imageEditor(),
                                TextInput::make('store_phone')->tel()->maxLength(50),
                                TextInput::make('store_whatsapp')->tel()->maxLength(50),
                                Select::make('currency')
                                    ->options([
                                        'KWD' => 'KWD',
                                        'BHD' => 'BHD',
                                        'SAR' => 'SAR',
                                        'AED' => 'AED',
                                        'USD' => 'USD',
                                    ])
                                    ->required(),
                            ])
                            ->columns(2),

                        Tab::make('SEO')
                            ->icon(Heroicon::OutlinedMagnifyingGlass)
                            ->schema([
                                TextInput::make('seo_title')->label('SEO Title (EN)')->maxLength(255),
                                TextInput::make('seo_title_ar')->label('SEO Title (AR)')->maxLength(255),
                                Textarea::make('seo_description')->label('Description (EN)')->rows(2),
                                Textarea::make('seo_description_ar')->label('Description (AR)')->rows(2),
                                TextInput::make('seo_keywords')->label('Keywords (EN)'),
                                TextInput::make('seo_keywords_ar')->label('Keywords (AR)'),
                                FileUpload::make('og_image')
                                    ->label('OG Image')
                                    ->image()
                                    ->directory('seo')
                                    ->visibility('public'),
                            ])
                            ->columns(2),

                        Tab::make('Working Hours')
                            ->icon(Heroicon::OutlinedClock)
                            ->schema([
                                Toggle::make('working_hours_enabled')
                                    ->label('Enforce working hours')
                                    ->helperText('Block orders outside configured hours'),
                                Select::make('timezone')
                                    ->options([
                                        'Asia/Kuwait' => 'Asia/Kuwait',
                                        'Asia/Riyadh' => 'Asia/Riyadh',
                                        'Asia/Dubai' => 'Asia/Dubai',
                                    ])
                                    ->required(),
                                Section::make('Default hours')
                                    ->description('Set open/close times once and apply them to every day.')
                                    ->schema([
                                        Toggle::make('working_hours_template.enabled')
                                            ->label('Open')
                                            ->dehydrated(false),
                                        TextInput::make('working_hours_template.open')
                                            ->label('Open Time')
                                            ->placeholder('09:00')
                                            ->dehydrated(false),
                                        TextInput::make('working_hours_template.close')
                                            ->label('Close Time')
                                            ->placeholder('22:00')
                                            ->dehydrated(false),
                                    ])
                                    ->columns(3)
                                    ->compact()
                                    ->footerActions([
                                        Action::make('applyWorkingHoursToAll')
                                            ->label('Apply to all days')
                                            ->icon(Heroicon::OutlinedArrowDownOnSquare)
                                            ->action(fn () => $this->applyWorkingHoursToAll()),
                                    ]),
                                ...$dayFields,
                            ]),

                        Tab::make('Notifications')
                            ->icon(Heroicon::OutlinedBell)
                            ->schema([
                                Section::make('Email alerts')
                                    ->description('Sends when a new order is created. Configure a real mail driver in .env (e.g. MAIL_MAILER=smtp). The log driver only writes to storage/logs/laravel.log.')
                                    ->schema([
                                        Toggle::make('order_email_notifications')
                                            ->label('Email notifications')
                                            ->live(),
                                        TextInput::make('notification_email')
                                            ->email()
                                            ->label('Notification email')
                                            ->helperText('Where new order alerts are delivered.')
                                            ->required(fn (Get $get): bool => (bool) $get('order_email_notifications')),
                                    ])
                                    ->columns(2)
                                    ->footerActions([
                                        Action::make('sendTestNotificationEmail')
                                            ->label('Send test email')
                                            ->icon(Heroicon::OutlinedPaperAirplane)
                                            ->action(fn () => $this->sendTestNotificationEmail()),
                                    ]),
                                Section::make('Admin panel')
                                    ->description('Bell notifications in the top bar are always sent to admin users when an order is created.')
                                    ->schema([
                                        Toggle::make('order_sound_notifications')
                                            ->label('Sound alerts in admin panel')
                                            ->helperText('Play a sound while you are logged into /admin.'),
                                    ]),
                            ]),

                        Tab::make('Payments')
                            ->icon(Heroicon::OutlinedCreditCard)
                            ->schema([
                                Section::make('Payment methods')
                                    ->description('Enable or disable checkout payment options.')
                                    ->schema([
                                        Toggle::make('payment_knet_enabled')
                                            ->label('KNET')
                                            ->helperText('Requires Tap secret key below'),
                                        Toggle::make('payment_card_enabled')
                                            ->label('Credit / debit card')
                                            ->helperText('Requires Tap public key, merchant ID, and secret key'),
                                        Toggle::make('payment_apple_pay_enabled')
                                            ->label('Apple Pay')
                                            ->helperText('Requires card payment credentials'),
                                        Toggle::make('payment_cod_enabled')
                                            ->label('Cash on delivery (COD)')
                                            ->helperText('Customer pays in cash when the order is delivered'),
                                    ])
                                    ->columns(2),
                                Section::make('Tap Payments')
                                    ->description('Credentials for KNET, card, and Apple Pay.')
                                    ->schema([
                                        TextInput::make('tap_secret_key')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('sk_test_… or sk_live_…')
                                            ->helperText('Secret key (sk_test_… for sandbox)')
                                            ->dehydrated(fn (?string $state): bool => filled($state)),
                                        TextInput::make('tap_public_key')
                                            ->placeholder('pk_test_… or pk_live_…')
                                            ->helperText('Public key (required for card / Apple Pay on checkout)'),
                                        Select::make('tap_mode')->options(TapMode::class)->required(),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('WhatsApp')
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->schema([
                                Section::make('Respond.io')
                                    ->description('Send WhatsApp template messages via Respond.io. Credentials can also be set in .env.')
                                    ->schema([
                                        Toggle::make('respond_whatsapp_enabled')
                                            ->label('Enable WhatsApp API')
                                            ->helperText('When enabled, admins can send payment links directly from the order page.'),
                                        TextInput::make('respond_channel_api_token')
                                            ->label('Channel API token')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('Respond.io channel API token')
                                            ->helperText('Leave blank to keep the current token.')
                                            ->dehydrated(fn (?string $state): bool => filled($state)),
                                        TextInput::make('respond_channel_id')
                                            ->label('Channel ID')
                                            ->numeric()
                                            ->placeholder('e.g. 123456'),
                                        TextInput::make('respond_base_url')
                                            ->label('API base URL')
                                            ->placeholder('https://api.respond.io/v2')
                                            ->helperText('Optional. Defaults to Respond.io v2 API.'),
                                    ])
                                    ->columns(2),

                                Section::make('Payment link template')
                                    ->description('Template sent to customers when requesting payment for an order.')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('respond_payment_template_name')
                                            ->label('Template')
                                            ->options(fn () => app(RespondService::class)->getTemplateOptions())
                                            ->searchable()
                                            ->live()
                                            ->columnSpanFull()
                                            ->afterStateUpdated(function ($state, callable $set): void {
                                                $respondService = app(RespondService::class);
                                                $template = $respondService->findTemplate((string) $state);

                                                $set('respond_payment_template_language', $template['language'] ?? 'ar');
                                                $set(
                                                    'respond_payment_template_fields',
                                                    $state ? $respondService->getTemplateMappingFields((string) $state) : [],
                                                );
                                            }),
                                        Hidden::make('respond_payment_template_language')
                                            ->dehydrated(),
                                        Repeater::make('respond_payment_template_fields')
                                            ->label('Template variable mapping')
                                            ->columnSpanFull()
                                            ->columns(2)
                                            ->schema($this->whatsappTemplateMappingSchema())
                                            ->visible(fn (Get $get): bool => filled($get('respond_payment_template_name')))
                                            ->deletable(false)
                                            ->addable(false)
                                            ->reorderable(false),
                                        ViewField::make('respond_payment_template_preview')
                                            ->view('filament.whatsapp-message-preview')
                                            ->viewData(fn (Get $get): array => $this->whatsappPreviewData(
                                                $get,
                                                'respond_payment_template_name',
                                                'respond_payment_template_fields',
                                            ))
                                            ->columnSpanFull()
                                            ->visible(fn (Get $get): bool => filled($get('respond_payment_template_name'))),
                                    ]),

                                Section::make('Order confirmation template')
                                    ->description('Optional template sent after successful payment.')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('respond_order_confirmation_template_name')
                                            ->label('Template')
                                            ->options(fn () => app(RespondService::class)->getTemplateOptions())
                                            ->searchable()
                                            ->live()
                                            ->columnSpanFull()
                                            ->afterStateUpdated(function ($state, callable $set): void {
                                                $respondService = app(RespondService::class);
                                                $template = $respondService->findTemplate((string) $state);

                                                $set('respond_order_confirmation_template_language', $template['language'] ?? 'ar');
                                                $set(
                                                    'respond_order_confirmation_template_fields',
                                                    $state ? $respondService->getTemplateMappingFields((string) $state) : [],
                                                );
                                            }),
                                        Hidden::make('respond_order_confirmation_template_language')
                                            ->dehydrated(),
                                        Repeater::make('respond_order_confirmation_template_fields')
                                            ->label('Template variable mapping')
                                            ->columnSpanFull()
                                            ->columns(2)
                                            ->schema($this->whatsappTemplateMappingSchema())
                                            ->visible(fn (Get $get): bool => filled($get('respond_order_confirmation_template_name')))
                                            ->deletable(false)
                                            ->addable(false)
                                            ->reorderable(false),
                                        ViewField::make('respond_order_confirmation_template_preview')
                                            ->view('filament.whatsapp-message-preview')
                                            ->viewData(fn (Get $get): array => $this->whatsappPreviewData(
                                                $get,
                                                'respond_order_confirmation_template_name',
                                                'respond_order_confirmation_template_fields',
                                            ))
                                            ->columnSpanFull()
                                            ->visible(fn (Get $get): bool => filled($get('respond_order_confirmation_template_name'))),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function applyWorkingHoursToAll(): void
    {
        $template = $this->data['working_hours_template'] ?? [];
        $open = $template['open'] ?? null;
        $close = $template['close'] ?? null;

        if (! filled($open) || ! filled($close)) {
            Notification::make()
                ->warning()
                ->title('Enter open and close times first')
                ->send();

            return;
        }

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $day) {
            $this->data['working_hours'][$day]['enabled'] = (bool) ($template['enabled'] ?? false);
            $this->data['working_hours'][$day]['open'] = $open;
            $this->data['working_hours'][$day]['close'] = $close;
        }

        Notification::make()
            ->success()
            ->title('Applied to all days')
            ->body('Review each day below, then save settings.')
            ->send();
    }

    public function sendTestNotificationEmail(): void
    {
        $email = $this->data['notification_email'] ?? null;

        if (! filled($email)) {
            Notification::make()
                ->warning()
                ->title('Enter a notification email first')
                ->send();

            return;
        }

        try {
            app(OrderNotificationService::class)->sendTestEmail($email);

            Notification::make()
                ->success()
                ->title('Test email sent')
                ->body("Check {$email} (and spam). Mail driver: ".config('mail.default'))
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title('Test email failed')
                ->body($exception->getMessage())
                ->send();
        }
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();
            $setting = Setting::current();

            if (empty($data['tap_secret_key'])) {
                unset($data['tap_secret_key']);
            }

            if (empty($data['respond_channel_api_token'])) {
                unset($data['respond_channel_api_token']);
            }

            unset($data['working_hours_template']);

            $setting->update($data);

            $this->commitDatabaseTransaction();

            Notification::make()->success()->title('Settings saved')->send();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Save Settings')->submit('save'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getFormContentComponent()]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment('start')
                    ->key('form-actions'),
            ]);
    }

    /**
     * @return list<\Filament\Forms\Components\Component>
     */
    private function whatsappTemplateMappingSchema(): array
    {
        return [
            Hidden::make('type'),
            Hidden::make('format'),
            Hidden::make('sub_type'),
            Hidden::make('index'),
            Hidden::make('button_text'),
            TextInput::make('label')
                ->label('Template field')
                ->disabled()
                ->dehydrated(),
            Select::make('variable')
                ->label('Value from')
                ->options($this->whatsappVariableOptions())
                ->required()
                ->searchable()
                ->live(),
            TextInput::make('custom_value')
                ->label('Custom value')
                ->visible(fn (Get $get): bool => $get('variable') === 'custom')
                ->required(fn (Get $get): bool => $get('variable') === 'custom'),
            Select::make('filename_variable')
                ->label('Filename from')
                ->options($this->whatsappVariableOptions())
                ->visible(fn (Get $get): bool => $get('type') === 'HEADER' && in_array($get('format'), ['DOCUMENT', 'IMAGE', 'VIDEO'], true))
                ->searchable(),
            TextInput::make('filename_custom_value')
                ->label('Custom filename')
                ->visible(fn (Get $get): bool => $get('type') === 'HEADER'
                    && in_array($get('format'), ['DOCUMENT', 'IMAGE', 'VIDEO'], true)
                    && $get('filename_variable') === 'custom')
                ->required(fn (Get $get): bool => $get('type') === 'HEADER'
                    && in_array($get('format'), ['DOCUMENT', 'IMAGE', 'VIDEO'], true)
                    && $get('filename_variable') === 'custom'),
            TextInput::make('button_url')
                ->label('Button URL')
                ->visible(fn (Get $get): bool => $get('type') === 'BUTTON'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function whatsappVariableOptions(): array
    {
        return [
            'customer_name' => 'Customer name',
            'customer_phone' => 'Customer phone',
            'order_number' => 'Order number',
            'order_total' => 'Order total',
            'order_currency' => 'Currency',
            'product_name' => 'Product name',
            'payment_url' => 'Payment URL',
            'invoice_pdf_url' => 'Invoice PDF URL',
            'invoice_pdf_name' => 'Invoice PDF filename',
            'custom' => 'Custom value',
        ];
    }

    /**
     * @return array{template: ?array, template_fields: array<int, array<string, mixed>>, customer_name: string, order_number: string}
     */
    private function whatsappPreviewData(Get $get, string $templateKey, string $fieldsKey): array
    {
        $templateName = $get($templateKey);

        if (! $templateName) {
            return ['template' => null, 'template_fields' => [], 'customer_name' => '', 'order_number' => ''];
        }

        $template = app(RespondService::class)->findTemplate((string) $templateName);

        if (! $template) {
            return ['template' => null, 'template_fields' => [], 'customer_name' => '', 'order_number' => ''];
        }

        return [
            'template' => $template,
            'template_fields' => $this->previewWhatsappTemplateFields($get($fieldsKey) ?? []),
            'customer_name' => 'Ahmad Al Kuwait',
            'order_number' => 'MOOD-0001',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function previewWhatsappTemplateFields(array $fields): array
    {
        return collect($fields)
            ->map(function (array $field): array {
                $field['value'] = ($field['variable'] ?? null) === 'custom'
                    ? (string) ($field['custom_value'] ?? '')
                    : $this->sampleWhatsappVariable((string) ($field['variable'] ?? ''));

                return $field;
            })
            ->all();
    }

    private function sampleWhatsappVariable(string $variable): string
    {
        return [
            'customer_name' => 'Ahmad Al Kuwait',
            'customer_phone' => '+96550000000',
            'order_number' => 'MOOD-0001',
            'order_total' => '12.500',
            'order_currency' => 'KWD',
            'product_name' => 'Cocoa Truffle Box',
            'payment_url' => url('/pay/1'),
            'invoice_pdf_url' => url('/invoices/1'),
            'invoice_pdf_name' => 'invoice-MOOD-0001.pdf',
        ][$variable] ?? 'Sample value';
    }
}
