@extends('layouts.app')

@section('title', 'Periode')

@section('content')

@php
    $periodeList = [
        ['id' => 1, 'nama' => 'Periode Januari - Maret 2025'],
        ['id' => 2, 'nama' => 'Periode April - Juni 2025'],
    ];
@endphp

<div class="bg-white p-6 shadow-lg rounded-lg w-3/4 mx-auto">
    <h2 class="text-2xl font-bold mb-4">Setting Periode</h2>

    @foreach ($periodeList as $periode)
        <button 
            onclick="window.location.href='{{ url('/detailperiode/' . $periode['id']) }}'"
            class="block w-full text-left bg-gray-200 hover:bg-gray-300 p-2 mb-2 rounded">
            {{ $periode['nama'] }}
        </button>
    @endforeach

    <button 
        onclick="window.location.href='{{ url('/settingperiode') }}'"
        class="mt-4 bg-gray-500 text-white p-2 rounded">
        + Tambah Periode
    </button>
</div>

@endsection
