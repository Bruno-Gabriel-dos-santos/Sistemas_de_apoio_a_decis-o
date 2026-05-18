@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center mt-4">
        <ul class="inline-flex items-center space-x-1">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li>
                    <span class="px-3 py-1 rounded bg-gray-200 text-gray-400 cursor-not-allowed">&laquo;</span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1 rounded bg-white border border-gray-300 text-gray-700 hover:bg-indigo-100 hover:text-indigo-700 transition">&laquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li>
                        <span class="px-3 py-1 rounded bg-gray-100 text-gray-500">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li>
                                <span class="px-3 py-1 rounded bg-indigo-600 text-white font-bold shadow">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}" class="px-3 py-1 rounded bg-white border border-gray-300 text-gray-700 hover:bg-indigo-100 hover:text-indigo-700 transition">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1 rounded bg-white border border-gray-300 text-gray-700 hover:bg-indigo-100 hover:text-indigo-700 transition">&raquo;</a>
                </li>
            @else
                <li>
                    <span class="px-3 py-1 rounded bg-gray-200 text-gray-400 cursor-not-allowed">&raquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
