{{-- resources/views/vendor/pagination/tailwind.blade.php --}}
@if ($paginator->hasPages())
    @php
        // Konfigurasi: Berapa banyak link di awal dan akhir
        $startLinks = 5; // Tampilkan 5 link pertama (1, 2, 3, 4, 5)
        $endLinks = 2;   // Tampilkan 2 link terakhir 
        $lastPage = $paginator->lastPage();
        $currentPage = $paginator->currentPage();
    @endphp

    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
        {{-- Info Hasil (Opsional) --}}
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
             <div>
                <p class="text-sm text-gray-600 leading-5">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-medium">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('of') !!}
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            {{-- Link Pagination --}}
            <div>
                {{-- Container --}}
                <span class="relative z-0 inline-flex shadow-sm rounded-md border border-gray-300">

                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        {{-- Tombol Previous Non-aktif --}}
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-white cursor-default rounded-l-md leading-5" aria-hidden="true">
                                 <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </span>
                        </span>
                    @else
                         {{-- Tombol Previous Aktif --}}
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-white border-r border-gray-300 rounded-l-md leading-5 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-blue-300 active:bg-gray-100 transition ease-in-out duration-150" aria-label="{{ __('pagination.previous') }}">
                              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </a>
                    @endif

                    {{-- Custom Page Number Logic --}}
                    @php $showEllipsis = false; @endphp
                    @for ($i = 1; $i <= $lastPage; $i++)
                        @php
                            // Tentukan apakah link ini harus ditampilkan
                            // Kondisi: (Dalam $startLinks pertama) ATAU (Dalam $endLinks terakhir)
                            $shouldShowLink = ($i <= $startLinks) || ($i > $lastPage - $endLinks);
                        @endphp

                        @if ($shouldShowLink)
                            {{-- Tampilkan Link/Span Halaman --}}
                            @if ($i == $currentPage)
                                {{-- Halaman Aktif --}}
                                <span aria-current="page">
                                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-500 border-l border-blue-500 cursor-default leading-5">{{ $i }}</span>
                                </span>
                            @else
                                {{-- Halaman Biasa --}}
                                <a href="{{ $paginator->url($i) }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border-l border-gray-300 leading-5 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-blue-300 active:bg-gray-100 transition ease-in-out duration-150" aria-label="{{ __('Go to page :page', ['page' => $i]) }}">
                                    {{ $i }}
                                </a>
                            @endif
                            @php $showEllipsis = true; @endphp {{-- Reset flag elipsis jika link ditampilkan --}}
                        @elseif ($showEllipsis && $i > $startLinks && $i <= $lastPage - $endLinks)
                             {{-- Tampilkan Elipsis jika di antara blok awal dan akhir, dan flag true --}}
                             <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border-l border-gray-300 cursor-default leading-5">...</span>
                            </span>
                            @php $showEllipsis = false; @endphp {{-- Hanya tampilkan elipsis sekali --}}
                        @endif
                    @endfor
                    {{-- End Custom Logic --}}


                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        {{-- Tombol Next Aktif --}}
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-white border-l border-gray-300 rounded-r-md leading-5 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-blue-300 active:bg-gray-100 transition ease-in-out duration-150" aria-label="{{ __('pagination.next') }}">
                              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </a>
                    @else
                        {{-- Tombol Next Non-aktif --}}
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-white border-l border-gray-300 cursor-default rounded-r-md leading-5" aria-hidden="true">
                                 <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>

        {{-- Mobile View (Tidak diubah) --}}
        <div class="flex justify-between flex-1 sm:hidden">
             @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default leading-5 rounded-md">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 leading-5 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-blue-300 active:bg-gray-100 transition ease-in-out duration-150">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-600 bg-white border border-gray-300 leading-5 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-blue-300 active:bg-gray-100 transition ease-in-out duration-150">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default leading-5 rounded-md">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>
    </nav>
@endif
