<table>
    <tr>
        <td colspan="2" class="text-center font-bold" style="font-size:12px;">PERSONAL HYGIENE KARYAWAN</td>
    </tr>
    <tr>
        <td style="width:50%"><strong>Nama:</strong> {{ $form['employee_name'] ?? '-' }}</td>
        <td style="width:50%"><strong>NIK:</strong> {{ $form['nik'] ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Bagian:</strong> {{ $form['bagian'] ?? '-' }}</td>
        <td><strong>Gender:</strong> {{ $form['gender'] ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Periode:</strong> {{ $form['period'] ?? '-' }} {{ $form['year'] ?? '' }}</td>
        <td><strong>Status:</strong> {{ !empty($form['approved']) ? 'Approved' : (!empty($form['generated_at']) ? 'Generated' : 'Draft') }}</td>
    </tr>
</table>

@php
    $personalHygieneItems = [
        ['id' => 'suhu_tubuh_tidak_panas', 'name' => 'Suhu tubuh tidak panas'],
        ['id' => 'tidak_mempunyai_luka_terbuka', 'name' => 'Tidak mempunyai luka terbuka'],
        ['id' => 'jaket_thermal_bersih', 'name' => 'Jaket thermal bersih'],
        ['id' => 'sarung_tangan_bersih', 'name' => 'Sarung Tangan Bersih'],
        ['id' => 'kuku_pendek_tidak_diwarnai', 'name' => 'Kuku pendek & tidak diwarnai/dicat'],
        ['id' => 'tidak_memakai_perhiasan', 'name' => 'Tidak memakai perhiasan/aksesoris/jam tangan'],
        ['id' => 'tidak_membawa_barang_pribadi', 'name' => 'Tidak membawa barang bawaan (barang pribadi) ke area warehouse'],
        ['id' => 'tidak_membawa_makanan', 'name' => 'Tidak membawa makanan & minuman ke area warehouse (selain produk customer)'],
        ['id' => 'rambut_rapi_pendek', 'name' => 'Rambut rapi & pendek untuk karyawan'],
        ['id' => 'tidak_berjenggot', 'name' => 'Tidak berjenggot/cambang/kumis untuk karyawan'],
        ['id' => 'tidak_memakai_bulu_mata', 'name' => 'Tidak memakai bulu mata palsu/eye shadow'],
        ['id' => 'plester_perban_in', 'name' => 'Plester/Perban (In)'],
        ['id' => 'plester_perban_out', 'name' => 'Plester/Perban (Out)'],
    ];
    $rows = $form['rows'] ?? [];
    $days = $form['days'] ?? range(1, 31);
@endphp

<table>
    <thead>
        <tr>
            <th style="width:8%">No</th>
            <th style="width:42%">Item</th>
            @foreach(array_slice($days, 0, 10) as $d)
                <th style="width:5%; font-size:7px;">H{{ $d }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $idx => $row)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>{{ $row['name'] ?? $row }}</td>
                @foreach(array_slice($days, 0, 10) as $d)
                    <td class="text-center">
                        @php $val = $row['days'][$d] ?? null; @endphp
                        @if($val === 'yes') <span class="check-yes">✓</span>
                        @elseif($val === 'no') <span class="check-no">✕</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
