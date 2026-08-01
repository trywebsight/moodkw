<x-filament::button
    tag="a"
    :href="route('home')"
    target="_blank"
    rel="noopener noreferrer"
    color="primary"
    size="sm"
    icon="heroicon-o-arrow-top-right-on-square"
    class="fi-topbar-visit-store-btn"
>
    {{ __('admin.visit_store') }}
</x-filament::button>
