<span class="inline-flex rounded-md shadow-sm">
    @if ($attributes->has('href'))
        <a {{ $attributes->merge(['class' => 'bg-lio-600 border border-transparent rounded-md py-2 px-4 inline-flex justify-center text-sm leading-5 font-medium text-white hover:bg-lio-700 focus:outline-none focus:border-lio-900 focus:ring-lio-900 active:bg-lio-900 transition duration-150 ease-in-out']) }}>
            {{ $slot }}
        </a>
    @else
        <button {{ $attributes->merge(['class' => 'bg-lio-600 border border-transparent rounded-md py-2 px-4 inline-flex justify-center text-sm leading-5 font-medium text-white hover:bg-lio-700 focus:outline-none focus:border-lio-900 focus:ring-lio-900 active:bg-lio-900 transition duration-150 ease-in-out']) }}>
            {{ $slot }}
        </button>
    @endif
</span>