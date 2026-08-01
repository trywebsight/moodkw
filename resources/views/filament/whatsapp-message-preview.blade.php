@once
    <style>
        .wa-preview {
            border-radius: 0.75rem;
            background-color: #f3f4f6;
            background-position: center;
            background-size: cover;
            padding: 1rem;
        }

        .wa-preview__phone {
            max-width: 28rem;
            margin: 0 auto;
            overflow: hidden;
            border-radius: 0.75rem;
            background: #ffffff;
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.08);
        }

        .wa-preview__header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #16a34a;
            color: #ffffff;
        }

        .wa-preview__avatar {
            display: flex;
            width: 2rem;
            height: 2rem;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: #4ade80;
        }

        .wa-preview__avatar svg,
        .wa-preview__icon {
            width: 1rem;
            height: 1rem;
            flex: 0 0 auto;
        }

        .wa-preview__business {
            font-size: 0.75rem;
            line-height: 1rem;
            color: #dcfce7;
        }

        .wa-preview__body {
            min-height: 300px;
            padding: 1rem;
            background-color: #f0fdf4;
            background-position: center;
            background-size: cover;
        }

        .wa-preview__message-row {
            display: flex;
            justify-content: flex-end;
        }

        .wa-preview__bubble {
            max-width: 20rem;
            border-radius: 0.75rem;
            background: #16a34a;
            padding: 0.75rem;
            color: #ffffff;
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.08);
        }

        .wa-preview__header-component,
        .wa-preview__button-list,
        .wa-preview__footer {
            margin-top: 0.75rem;
        }

        .wa-preview__header-component:first-child {
            margin-top: 0;
        }

        .wa-preview__attachment,
        .wa-preview__button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 0.375rem;
            background: rgb(255 255 255 / 0.2);
            padding: 0.5rem;
            font-size: 0.75rem;
            line-height: 1rem;
        }

        .wa-preview__body-text {
            font-size: 0.875rem;
            line-height: 1.625;
        }

        .wa-preview__footer {
            border-top: 1px solid rgb(255 255 255 / 0.2);
            padding-top: 0.5rem;
            font-size: 0.75rem;
            line-height: 1rem;
            opacity: 0.8;
        }

        .wa-preview__button-list {
            display: grid;
            gap: 0.5rem;
        }

        .wa-preview__button {
            justify-content: center;
            border: 1px solid rgb(255 255 255 / 0.3);
            cursor: default;
        }

        .wa-preview__meta {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            line-height: 1rem;
            color: #dcfce7;
        }

        .wa-preview__meta svg {
            width: 1rem;
            height: 1rem;
            margin-left: 0.25rem;
        }

        .wa-preview__info,
        .wa-preview__empty {
            border-radius: 0.75rem;
            background: #f9fafb;
            color: #4b5563;
            text-align: center;
        }

        .wa-preview__info {
            margin-top: 1rem;
            padding: 1rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }

        .wa-preview__status {
            display: inline-flex;
            border-radius: 0.375rem;
            background: #dcfce7;
            padding: 0.125rem 0.5rem;
            color: #166534;
            font-size: 0.75rem;
            line-height: 1rem;
        }

        .wa-preview__empty {
            padding: 2rem;
        }

        .wa-preview__empty svg {
            width: 3rem;
            height: 3rem;
            margin: 0 auto 1rem;
            color: #d1d5db;
        }
    </style>
@endonce

@if ($template)
    <div class="wa-preview">
        <div class="wa-preview__phone">
            <div class="wa-preview__header">
                <div class="wa-preview__avatar">
                    <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                    </svg>
                </div>
                <div>
                    <div><strong>{{ $customer_name }}</strong></div>
                    <div class="wa-preview__business">WhatsApp Business</div>
                </div>
            </div>

            <div class="wa-preview__body">
                <div class="wa-preview__message-row">
                    <div class="wa-preview__bubble">
                        @foreach ($template['components'] ?? [] as $component)
                            @if ($component['type'] === 'HEADER')
                                <div class="wa-preview__header-component">
                                    @if ($component['format'] === 'TEXT')
                                        @php
                                            $headerText = $component['text'] ?? '';
                                            $paramIndex = 0;

                                            foreach ($template_fields as $field) {
                                                if ($field['type'] === 'HEADER') {
                                                    $headerText = preg_replace(
                                                        '/{{\s*' . ($paramIndex + 1) . '\s*}}/',
                                                        $field['value'] ?? 'Parameter ' . ($paramIndex + 1),
                                                        $headerText,
                                                    );

                                                    $paramIndex++;
                                                }
                                            }
                                        @endphp

                                        <strong>{{ $headerText }}</strong>
                                    @elseif ($component['format'] === 'DOCUMENT')
                                        <div class="wa-preview__attachment">
                                            <svg class="wa-preview__icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" />
                                            </svg>
                                            <span>Invoice {{ $order_number }}.pdf</span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if ($component['type'] === 'BODY')
                                <div class="wa-preview__body-text">
                                    @php
                                        $bodyText = $component['text'] ?? '';
                                        $paramIndex = 1;

                                        foreach ($template_fields as $field) {
                                            if ($field['type'] === 'BODY') {
                                                $bodyText = preg_replace(
                                                    '/{{\s*' . $paramIndex . '\s*}}/',
                                                    $field['value'] ?? 'Parameter ' . $paramIndex,
                                                    $bodyText,
                                                );

                                                $paramIndex++;
                                            }
                                        }

                                        $bodyText = nl2br(e($bodyText));
                                    @endphp

                                    {!! $bodyText !!}
                                </div>
                            @endif
                        @endforeach

                        @foreach ($template['components'] ?? [] as $component)
                            @if ($component['type'] === 'FOOTER')
                                <div class="wa-preview__footer">
                                    {{ $component['text'] ?? '' }}
                                </div>
                            @endif
                        @endforeach

                        @foreach ($template['components'] ?? [] as $component)
                            @if ($component['type'] === 'BUTTONS')
                                <div class="wa-preview__button-list">
                                    @foreach ($component['buttons'] ?? [] as $index => $button)
                                        @if ($button['type'] === 'URL')
                                            <div class="wa-preview__button">
                                                <svg class="wa-preview__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-1.414 1.414a4 4 0 01-5.657-5.657l1.414-1.414m2.829 2.829a4 4 0 010-5.657l1.414-1.414a4 4 0 015.657 5.657l-1.414 1.414" />
                                                </svg>
                                                <span>{{ $button['text'] ?? 'Button' }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        @endforeach

                        <div class="wa-preview__meta">
                            <span>{{ now()->format('H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wa-preview__info">
            <p><strong>Template:</strong> {{ $template['name'] ?? 'N/A' }}</p>
            <p><strong>Status:</strong> <span class="wa-preview__status">{{ $template['status'] ?? 'N/A' }}</span></p>
        </div>
    </div>
@else
    <div class="wa-preview__empty">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <p>Select a template to see the WhatsApp message preview</p>
    </div>
@endif
