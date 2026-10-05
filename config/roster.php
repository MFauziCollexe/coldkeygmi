<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Grup departemen untuk template roster
     |--------------------------------------------------------------------------
     | Menggabungkan beberapa kode departemen menjadi satu tipe template roster,
     | sehingga satu file roster bisa mencakup karyawan dari banyak divisi.
     | Karyawan TIDAK dipindahkan; departemen aslinya tetap utuh.
     |
     | Catatan: daftar NRP yang benar-benar dipakai template OFFICE sudah
     | hardcode di RosterController::getFixedTemplateEmployees(). Daftar di sini
     | hanya dokumentasi asal-usul kelima karyawan tersebut, bukan sumber
     | kebenaran. Cakupan hari libur office di modul The Days dibaca dari kolom
     | employees.work_group, bukan dari key ini.
     */
    'department_groups' => [
        'office' => ['HSE', 'HRD', 'OPS', 'FAT'],
    ],

    /*
     |--------------------------------------------------------------------------
     | Approver khusus untuk batch roster OFFICE
     |--------------------------------------------------------------------------
     | Batch berdepartment_id = kode di bawah hanya boleh disetujui manajer
     | yang departemennya salah satu dari daftar ini. Ini mengabaikan aturan
     | all_if (yang membuat HRD/admin dapat approve semua departemen).
     */
    'office_approver_department_codes' => ['OPS'],

    /*
     |--------------------------------------------------------------------------
     | User yang boleh upload semua template
     |--------------------------------------------------------------------------
     | Dicek pertama kali, sebelum template_access. Admin dan IT dipakai
     | untuk perbaikan data dan tidak dibatasi template.
     */
    'bypass_user_ids' => [1, 8],

    /*
     |--------------------------------------------------------------------------
     | User/departemen yang boleh upload tiap template roster
     |--------------------------------------------------------------------------
     | Gate SEBENARNYA untuk upload roster, terpisah dari module permission.
     | Module permission hanya membuka halaman, config ini yang menentukan
     | template mana yang boleh diupload oleh siapa.
     |
     | Aturan resolution: bypass_user_ids -> user_ids -> department_codes.
     | Template yang tidak terdaftar di sini otomatis DITOLAK, jadi
     | template baru harus sengaja ditambahkan.
     |
     | inventory_said   : I Umar Said (16)
     | inventory_imanda : Imanda Ariesandy (22)
     | risk_control     : Chandra Tirto Adi (15), Rahayu Anjas Sari (18)
     | maintanance      : semua user MNT
     | security         : semua user SEC
     | office           : Diah Ayu Ratnasari (20)
     |
     | MNT/SEC memakai department_codes karena roster dipegang banyak orang.
     */
    'template_access' => [
        'inventory_said' => ['user_ids' => [16]],
        'inventory_imanda' => ['user_ids' => [22]],
        'risk_control' => ['user_ids' => [15, 18]],
        'maintanance' => ['department_codes' => ['MNT']],
        'security' => ['department_codes' => ['SEC']],
        'office' => ['user_ids' => [20]],
    ],

    /*
     |--------------------------------------------------------------------------
     | Periode efektif batch roster OFFICE
     |--------------------------------------------------------------------------
     | Roster Office baru berlaku mulai bulan/tahun ini. Periode sebelum
     | nilai ini ditolak pada preview, upload, dan unduh template.
     */
    'office' => [
        'effective_month' => 10,
        'effective_year' => 2026,
    ],

    /*
     |--------------------------------------------------------------------------
     | Jadwal kerja per hari untuk batch roster OFFICE
     |--------------------------------------------------------------------------
     | Durasi (jam) dihitung dari jam masuk yang diketik pada kode shift angka,
     | contoh: kode 8 pada hari Senin dengan durasi 8.5 = 08:00 s/d 16:30.
     | Nilai null berarti hari tersebut tidak ada jam kerja (auto OFF).
     */
    'schedules' => [
        'office' => [
            'default_hours' => [
                'mon' => 8.5,
                'tue' => 8.5,
                'wed' => 8.5,
                'thu' => 8.5,
                'fri' => 9.0,
                'sat' => 5.0,
                'sun' => null,
            ],
            'force_off_days' => ['sun'],
        ],
    ],
];
