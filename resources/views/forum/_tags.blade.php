<p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider" id="communities-headline">
    Tags
</p>
<div class="mt-3 space-y-2" aria-labelledby="tags-menu">
    <a 
        href="{{ route('forum', ['filter' => $filter]) }}" 
        class="group flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:bg-white {{ (! isset($activeTag) ? 'bg-white text-lio-500 border-lio-500 border-l-2' : '') }}"
    >
        <span class="truncate">
            All
        </span>
    </a>

    @foreach (App\Models\Tag::orderBy('name')->get() as $tag)
        <a 
            href="{{ route('forum.tag', [$tag->slug(), 'filter' => $filter]) }}" 
            class="group flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:bg-white {{ (isset($activeTag) && $activeTag->id() === $tag->id() ? 'bg-white text-lio-500 border-lio-500 border-l-2' : '') }}"
        >
            <span class="truncate">
                {{ $tag->name() }}
            </span>
        </a>
    @endforeach
</div>