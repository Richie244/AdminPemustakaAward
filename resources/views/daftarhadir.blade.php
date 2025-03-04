@extends('layouts.app')

@section('content')
    <div class="bg-white p-5 shadow-lg rounded-lg w-2/3 mx-auto">
        <h2 class="text-2xl font-bold mb-4">Daftar Hadir</h2>

        <!-- Input untuk NIM/NIDN -->
        <div class="mb-4">
            <label class="block font-semibold">Masukkan NIM/NIDN</label>
            <input type="text" id="search" class="border p-2 w-full rounded" placeholder="Cari berdasarkan NIM/NIDN...">
        </div>

        <!-- Tabel Daftar Hadir -->
        <table class="w-full border-collapse border">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border px-4 py-2">NIM</th>
                    <th class="border px-4 py-2">Nama</th>
                    <th class="border px-4 py-2">Email</th>
                </tr>
            </thead>
            <tbody id="table-body">
                @foreach($daftarHadir as $data)
                    <tr class="border">
                        <td class="border px-4 py-2">{{ $data->nim }}</td>
                        <td class="border px-4 py-2">{{ $data->nama }}</td>
                        <td class="border px-4 py-2">{{ $data->email }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
        document.getElementById('search').addEventListener('input', function () {
            let keyword = this.value.toLowerCase();
            let rows = document.querySelectorAll('#table-body tr');

            rows.forEach(row => {
                let nim = row.cells[0].textContent.toLowerCase();
                if (nim.includes(keyword)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
@endsection
