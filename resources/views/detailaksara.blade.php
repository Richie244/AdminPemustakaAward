@extends('layouts.app')

@section('content')
{{-- CSS untuk x-cloak ditempatkan langsung di sini untuk memastikan ia ada --}}
<style>
    [x-cloak] { display: none !important; }
</style>

<div x-data="{ showRejectModal: false }" class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg p-8">
    <div class="flex justify-between items-center mb-8 border-b pb-4">
        <div class="flex items-center space-x-3">
            <a href="{{ route('validasi.aksara.index', request()->query()) }}"
               class="text-gray-600 hover:text-gray-800 transition-colors duration-150 p-2 rounded-full hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Detail Validasi Karya</h1>
        </div>
        <span class="px-4 py-1 rounded-full text-sm font-medium
            @if($peserta->status === 'rejected') bg-red-100 text-red-600
            @elseif($peserta->status === 'accepted') bg-green-100 text-green-600
            @else bg-yellow-100 text-yellow-600 @endif">
            {{ ucfirst($peserta->status === 'pending' ? 'Menunggu' : ($peserta->status === 'accepted' ? 'Diterima' : 'Ditolak')) }}
        </span>
    </div>

    <div class="bg-gray-50 rounded-lg p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Identitas Peserta</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm text-gray-500 block">Nama Lengkap</label>
                <p class="font-medium">{{ $peserta->nama ?? 'Tidak tersedia' }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500 block">NIM</label>
                <p class="font-medium">{{ $peserta->nim ?? 'Tidak tersedia' }}</p>
            </div>
            <div class="col-span-2">
                <label class="text-sm text-gray-500 block">Email</label>
                <p class="font-medium">{{ $peserta->email ?? 'Tidak tersedia' }}</p>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 rounded-lg p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Detail Karya</h2>
        <div class="space-y-5">
            <div>
                <label class="text-sm text-gray-500 block">Judul Karya</label>
                <p class="font-medium">{{ $peserta->judul ?? 'Tidak tersedia' }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500 block">Pengarang</label>
                <p class="font-medium">{{ $peserta->pengarang ?? 'Tidak tersedia' }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500 block">Review</label>
                <div class="prose max-w-none bg-white p-4 rounded border min-h-[60px]">
                    {!! nl2br(e($peserta->review ?? 'Tidak ada review.')) !!}
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-500 block">Rekomendasi Dosen</label>
                    <p class="font-medium">{{ $peserta->dosen ?? 'Tidak tersedia' }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500 block">Link Media Sosial/Lampiran</label>
                    @if($peserta->link && $peserta->link !== '#')
                        <a href="{{ $peserta->link }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">
                            Lihat Lampiran
                        </a>
                    @else
                        <p class="text-gray-500">Tidak ada lampiran</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Alasan Penolakan (jika status ditolak) --}}
    @if($peserta->status === 'rejected' && !empty($peserta->alasan_penolakan))
    <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
        <h2 class="text-lg font-semibold text-red-700 mb-2">Alasan Penolakan</h2>
        <p class="text-red-600">{{ $peserta->alasan_penolakan }}</p>
    </div>
    @endif

    <div class="flex justify-end gap-4 border-t pt-6">
        @if($peserta->status === 'pending')
            <a href="{{ route('aksara.setuju', ['id' => $peserta->id] + request()->query()) }}"
               class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors duration-150">
                Terima
            </a>
            <button @click="showRejectModal = true" 
                    class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-colors duration-150">
                Tolak
            </button>
        @endif
    </div>

    @if($peserta->status === 'pending')
    <div x-show="showRejectModal" 
         x-cloak 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50">
        <div @click.outside="showRejectModal = false" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl">
            <div class="flex justify-between items-center mb-4">
                 <h3 class="text-xl font-semibold text-gray-800">Alasan Penolakan</h3>
                 <button @click="showRejectModal = false" class="text-gray-400 hover:text-gray-600">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                 </button>
            </div>
            <form method="GET" action="{{ route('aksara.tolak', ['id' => $peserta->id]) }}"> 
                @foreach(request()->query() as $key => $value)
                    @if(!in_array($key, ['alasan']))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <textarea 
                    name="alasan"
                    class="w-full border border-gray-300 rounded-lg p-3 mb-4 focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                    placeholder="Tulis alasan penolakan..."
                    rows="4"
                    required></textarea>
                
                <div class="flex justify-end gap-3">
                    <button type="button" @click="showRejectModal = false" 
                            class="px-5 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-150">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-150">
                        Kirim Penolakan
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

{{-- Pastikan AlpineJS dimuat. Jika sudah ada di layout utama dengan defer, ini mungkin tidak perlu. --}}
{{-- Jika Anda memindahkannya ke layout utama, pastikan ada 'defer' --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection