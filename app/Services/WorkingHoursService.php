<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;

class WorkingHoursService
{
    private const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    public function isOpen(): bool
    {
        $settings = $this->settingsService->get();

        if (! $settings->working_hours_enabled) {
            return true;
        }

        $hours = $this->getWorkingHours($settings);
        $timezone = $settings->timezone ?? 'Asia/Kuwait';
        $now = Carbon::now($timezone);
        $dayKey = strtolower($now->englishDayOfWeek);

        $dayHours = $hours[$dayKey] ?? null;

        if (! $dayHours || ! ($dayHours['enabled'] ?? false)) {
            return false;
        }

        $open = $dayHours['open'] ?? null;
        $close = $dayHours['close'] ?? null;

        if (! $open || ! $close) {
            return false;
        }

        $current = $now->format('H:i');

        return $current >= $open && $current < $close;
    }

    public function getStatusMessage(): string
    {
        if ($this->isOpen()) {
            return __('checkout.store_open');
        }

        return __('checkout.store_closed');
    }

    public function getNextOpenTime(): ?string
    {
        $settings = $this->settingsService->get();
        $hours = $this->getWorkingHours($settings);
        $timezone = $settings->timezone ?? 'Asia/Kuwait';
        $now = Carbon::now($timezone);

        for ($i = 0; $i < 7; $i++) {
            $check = $now->copy()->addDays($i);
            $dayKey = strtolower($check->englishDayOfWeek);
            $dayHours = $hours[$dayKey] ?? null;

            if ($dayHours && ($dayHours['enabled'] ?? false) && ($dayHours['open'] ?? null)) {
                if ($i === 0 && $check->format('H:i') < ($dayHours['open'] ?? '')) {
                    return __('checkout.opens_at', [
                        'time' => $dayHours['open'],
                        'day' => __('checkout.days.'.$dayKey),
                    ]);
                }

                if ($i > 0) {
                    return __('checkout.opens_at', [
                        'time' => $dayHours['open'],
                        'day' => __('checkout.days.'.$dayKey),
                    ]);
                }
            }
        }

        return null;
    }

    public function defaultWorkingHours(): array
    {
        $defaults = [];

        foreach (self::DAYS as $day) {
            $defaults[$day] = [
                'enabled' => ! in_array($day, ['friday'], true),
                'open' => '09:00',
                'close' => '22:00',
            ];
        }

        return $defaults;
    }

    public function getWorkingHours(?Setting $settings = null): array
    {
        $settings ??= $this->settingsService->get();
        $stored = $settings->working_hours;

        if (is_array($stored) && ! empty($stored)) {
            return array_merge($this->defaultWorkingHours(), $stored);
        }

        return $this->defaultWorkingHours();
    }
}
