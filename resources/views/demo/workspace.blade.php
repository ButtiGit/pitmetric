<x-layouts::app :title="__('demo.nav.'.$initialSection)">
    @php
        $copy = [
            'locale' => app()->getLocale() === 'it' ? 'it' : 'en',
            'section' => $initialSection,
        ];
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-3 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-7">
        <main id="pitmetric-demo" data-user="{{ auth()->id() }}" data-section="{{ $initialSection }}" data-copy='@json($copy)' class="mx-auto w-full max-w-[1360px] space-y-4 sm:space-y-5"></main>
    </div>
</x-layouts::app>
