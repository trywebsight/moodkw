@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'settings' => null,
])

@php
    $settings = $settings ?? \App\Models\Setting::current();
    $locale = app()->getLocale();
    $isAr = $locale === 'ar';

    $seoTitle = $title ?? ($isAr ? ($settings->seo_title_ar ?? $settings->seo_title) : $settings->seo_title) ?? config('app.name');
    $seoDescription = $description ?? ($isAr ? ($settings->seo_description_ar ?? $settings->seo_description) : $settings->seo_description) ?? __('seo.default_description');
    $seoKeywords = $isAr ? ($settings->seo_keywords_ar ?? $settings->seo_keywords) : $settings->seo_keywords;
    $ogImage = $image ?? ($settings->og_image ? asset('storage/'.$settings->og_image) : ($settings->store_logo ? asset('storage/'.$settings->store_logo) : asset('images/websight-logo.svg')));
    $canonical = url()->current();
@endphp

<meta name="description" content="{{ $seoDescription }}">
@if ($seoKeywords)
    <meta name="keywords" content="{{ $seoKeywords }}">
@endif
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:type" content="website">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:locale" content="{{ $isAr ? 'ar_KW' : 'en_US' }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">

@if ($settings->store_logo)
    <link rel="icon" href="{{ asset('storage/'.$settings->store_logo) }}">
@endif
