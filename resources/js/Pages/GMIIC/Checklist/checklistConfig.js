export const checklistOptions = [
  { id: 'non_warehouse_sanitation', name: 'Kebersihan dan Sanitasi (Non-Warehouse Area)' },
  { id: 'kotak_p3k', name: 'Kotak P3K' },
  { id: 'apar_smoke_detector_fire_alarm', name: 'APAR, Smoke Detector, Fire Alarm' },
  { id: 'pengangkutan_sampah_pt_sier', name: 'Pengangkutan Sampah PT SIER' },
  { id: 'personal_hygiene_karyawan', name: 'Personal Hygiene Karyawan' },
  { id: 'patroli_security', name: 'Patroli Security' },
  { id: 'site_visit_hse', name: 'Site Visit HSE' },
  { id: 'site_visit_maintenance', name: 'Site Visit Maintenance' },
  { id: 'sarana_dan_prasarana', name: 'Sarana dan Prasarana' },
  { id: 'warehouse_sanitation_1', name: 'Kebersihan dan Sanitasi (Warehouse Area)' },
];

const groupedLocationLabels = {
  pos_security: 'Lantai 1 Depan Dan Luar',
  lantai_1_area_luar: 'Lantai 1 Depan Dan Luar',
  lobby_lantai_1: 'Lantai 1 Dalam',
  lantai_1_area_dalam: 'Lantai 1 Dalam',
  loading_dock: 'Lantai 1 Dalam',
  ruang_admin: 'Lantai 1 Belakang',
  ruang_kontrol: 'Lantai 1 Belakang',
  area_ruang_mesin: 'Lantai 1 Belakang',
  lantai_1_area_belakang: 'Lantai 1 Belakang',
  area_office_lantai_2: 'Lantai 2 Office',
  lantai_2: 'Lantai 2 Office',
  area_lantai_3: 'Lantai 3 Office',
  lantai_3: 'Lantai 3 Office',
};

export const locationOptions = [
  { id: 'ruang_admin', name: groupedLocationLabels.ruang_admin },
  { id: 'ruang_kontrol', name: groupedLocationLabels.ruang_kontrol },
  { id: 'pos_security', name: groupedLocationLabels.pos_security },
];

export const fireSafetyCardOptions = [
  { id: 'apar', name: 'APAR', title: 'KARTU PEMELIHARAAN APAR' },
  { id: 'smoke_detector', name: 'Smoke Detector', title: 'KARTU PEMELIHARAAN SMOKE DETECTOR' },
  { id: 'fire_alarm', name: 'Fire Alarm', title: 'KARTU PEMELIHARAAN FIRE ALARM' },
];

export const fireSafetyLocationOptionsByType = {
  apar: [
    { id: 'lobby_lantai_1', name: groupedLocationLabels.lobby_lantai_1 },
    { id: 'area_office_lantai_2', name: groupedLocationLabels.area_office_lantai_2 },
    { id: 'area_ruang_mesin', name: groupedLocationLabels.area_ruang_mesin },
    { id: 'area_lantai_3', name: groupedLocationLabels.area_lantai_3 },
  ],
  smoke_detector: [
    { id: 'lobby_lantai_1', name: groupedLocationLabels.lobby_lantai_1 },
    { id: 'lantai_2', name: groupedLocationLabels.lantai_2 },
    { id: 'area_ruang_mesin', name: groupedLocationLabels.area_ruang_mesin },
    { id: 'area_lantai_3', name: groupedLocationLabels.area_lantai_3 },
  ],
  fire_alarm: [
    { id: 'lobby_lantai_1', name: groupedLocationLabels.lobby_lantai_1 },
    { id: 'area_office_lantai_2', name: groupedLocationLabels.area_office_lantai_2 },
    { id: 'area_ruang_mesin', name: groupedLocationLabels.area_ruang_mesin },
    { id: 'area_lantai_3', name: groupedLocationLabels.area_lantai_3 },
  ],
};

export const fireSafetyItemsByType = {
  apar: [
    { id: 'terlihat_jelas', name: 'Terlihat Jelas' },
    { id: 'mudah_dijangkau', name: 'Mudah Dijangkau' },
    { id: 'tidak_terhalang_barang', name: 'Tidak Terhalang Barang' },
    { id: 'pressure_normal', name: 'Tekanan / pressure dalam kondisi normal' },
    { id: 'pin_segel_lengkap', name: 'Pin pengaman dan segel lengkap' },
    { id: 'tabung_tidak_korosi', name: 'Tabung tidak korosi / kerusakan' },
  ],
  smoke_detector: [
    { id: 'alarm_menyala_saat_asap', name: 'Alarm menyala ketika ada asap di smoke detector' },
  ],
  fire_alarm: [
    { id: 'terlihat_jelas', name: 'Terlihat Jelas' },
    { id: 'mudah_dijangkau', name: 'Mudah Dijangkau' },
    { id: 'tidak_terhalang_barang', name: 'Tidak Terhalang Barang' },
    { id: 'label_push_here_terbaca', name: 'Label Push Here Terbaca Jelas' },
    { id: 'tombol_dapat_ditekan', name: 'Tombol Dapat Ditekan saat Uji Fungsi' },
    { id: 'dapat_direset', name: 'Dapat direset Kembali setelah pengujian' },
    { id: 'kondisi_bersih', name: 'Kondisi Bersih dan Tidak Berdebu' },
  ],
};

export const sanitationAreaOptions = [
  {
    id: 'lantai_1',
    name: groupedLocationLabels.lantai_1_area_dalam,
    items: [
      'Ruang Admin',
      'Ruang Repacking',
      'Toilet Pria',
      'Toilet Wanita',
      'Toilet Tamu',
      'Urinoir',
      'Ruang Loker',
      'Lobby',
    ],
  },
  {
    id: 'lantai_2',
    name: groupedLocationLabels.lantai_2,
    items: [
      'Ruang Direktur',
      'Ruang Meeting',
      'Ruang Staff',
      'Toilet Wanita',
      'Toilet Pria',
      'Pantry',
    ],
  },
  {
    id: 'area_luar_bangunan',
    name: groupedLocationLabels.lantai_1_area_luar,
    items: [
      'Pos Security',
      'Musholla',
      'Ruang Laktasi',
      'Ruang Istirahat',
      'Ruang Kesehatan',
      'Ruang Mesin',
      'Ruang Kontrol',
      'Ruang Baterai',
    ],
  },
];

export const warehouseAreaOptions = [
  { id: 'chiller', name: 'Chiller' },
  { id: 'freezer', name: 'Freezer' },
  { id: 'loading_area', name: 'Loading Area' },
];

export const warehouseCleanlinessRowsByFrequency = {
  daily: [
    { id: 'lantai', name: 'Lantai' },
  ],
  monthly: [
    { id: 'pallet', name: 'Pallet' },
    { id: 'dinding', name: 'Dinding' },
  ],
};

export const warehouseIceControlRows = [
  { id: 'penumpukan_es', name: 'Tidak ada penumpukan es berlebih' },
  { id: 'air_lelehan', name: 'Tidak ada air lelehan di lantai' },
  { id: 'kondensasi', name: 'Tidak ada kondensasi menetes' },
];

export const warehouseCleaningMaterialRows = [
  { id: 'bahan_1', no: 1 },
];

export const personalHygieneRows = [
  { id: 'suhu_tubuh_tidak_panas', name: 'Suhu tubuh tidak panas' },
  { id: 'tidak_mempunyai_luka_terbuka', name: 'Tidak mempunyai luka terbuka' },
  { id: 'jaket_thermal_bersih', name: 'Jaket thermal bersih' },
  { id: 'sarung_tangan_bersih', name: 'Sarung Tangan Bersih' },
  { id: 'kuku_pendek_tidak_diwarnai', name: 'Kuku pendek & tidak diwarnai/dicat' },
  { id: 'tidak_memakai_perhiasan', name: 'Tidak memakai perhiasan/aksesoris/jam tangan' },
  { id: 'tidak_membawa_barang_pribadi', name: 'Tidak membawa barang bawaan (barang pribadi) ke area warehouse' },
  { id: 'tidak_membawa_makanan', name: 'Tidak membawa makanan & minuman ke area warehouse (selain produk customer)' },
  { id: 'rambut_rapi_pendek', name: 'Rambut rapi & pendek untuk karyawan' },
  { id: 'tidak_berjenggot', name: 'Tidak berjenggot/cambang/kumis untuk karyawan' },
  { id: 'tidak_memakai_bulu_mata', name: 'Tidak memakai bulu mata palsu/eye shadow' },
  { id: 'plester_perban_in', name: 'Plester/Perban (In)' },
  { id: 'plester_perban_out', name: 'Plester/Perban (Out)' },
];

export const saranaPrasaranaSections = [
  {
    id: 'lantai_1_area_luar',
    title: 'A. LANTAI 1 - AREA LUAR',
    items: [
      'Dinding tidak retak',
      'Lantai tidak retak',
      'Plafon tidak retak',
      'Ventilasi / sirkulasi udara baik',
      'Tidak ada genangan air',
      'Toilet bersih dan berfungsi',
      'Ketersediaan sabun dan tisu',
      'Tempat ibadah bersih',
      'Area makan / istirahat bersih',
      'Tempat sampah tertutup',
    ],
  },
  {
    id: 'lantai_1_area_belakang',
    title: 'B. LANTAI 1 - AREA BELAKANG',
    items: [
      'Dinding tidak retak',
      'Lantai tidak retak',
      'Plafon tidak retak',
      'Ventilasi / sirkulasi udara baik',
      'Tidak ada genangan air',
      'Toilet bersih dan berfungsi',
      'Ketersediaan sabun dan tisu',
      'Tidak ada kebocoran pipa',
      'Peralatan kantor berfungsi normal',
      'Tempat sampah tertutup',
    ],
  },
  {
    id: 'lantai_1_area_dalam',
    title: 'C. LANTAI 1 - AREA DALAM',
    items: [
      'Dinding tidak retak',
      'Lantai tidak retak',
      'Plafon tidak retak',
      'AC berfungsi normal',
      'Ventilasi / sirkulasi udara baik',
      'Tidak ada genangan air',
      'Toilet bersih dan berfungsi',
      'Ketersediaan sabun dan tisu',
      'Tidak ada kebocoran pipa',
      'Peralatan kantor berfungsi normal',
      'Tempat sampah tertutup',
    ],
  },
  {
    id: 'lantai_2',
    title: 'D. LANTAI 2',
    items: [
      'Dinding tidak retak',
      'Lantai tidak retak',
      'Plafon tidak retak',
      'Ventilasi / sirkulasi udara baik',
      'AC berfungsi normal',
      'Tidak ada genangan air',
      'Toilet bersih dan berfungsi',
      'Ketersediaan sabun dan tisu',
      'Tidak ada kebocoran pipa',
      'Peralatan kantor berfungsi normal',
      'Tempat sampah tertutup',
    ],
  },
  {
    id: 'lantai_3',
    title: 'E. LANTAI 3',
    items: [
      'Dinding tidak retak',
      'Lantai tidak retak',
      'Plafon tidak retak',
      'Ventilasi / sirkulasi udara baik',
      'Tidak ada kebocoran pipa',
      'Peralatan kantor berfungsi normal',
      'Tempat sampah tertutup',
    ],
  },
];

const groupedChecklistAreaLabels = {
  lantai_1_area_luar: groupedLocationLabels.lantai_1_area_luar,
  lantai_1_area_belakang: groupedLocationLabels.lantai_1_area_belakang,
  lantai_1_area_dalam: groupedLocationLabels.lantai_1_area_dalam,
  lantai_2: groupedLocationLabels.lantai_2,
  lantai_3: groupedLocationLabels.lantai_3,
  loading_dock: groupedLocationLabels.loading_dock,
  pos_security: 'POS SECURITY',
  loading_dock_luar: 'LOADING DOCK LUAR',
  travo_pln_depan: 'TRAVO PLN DEPAN',
  tanah_kosong_dan_sekitar_bangunan: 'TANAH KOSONG DAN SEKITAR BANGUNAN',
  travo_pln_belakang: 'TRAVO PLN BELAKANG',
  genset: 'GENSET',
  ruang_mesin_ruang_kontrol: 'RUANG MESIN, RUANG KONTROL',
  sekitar_bangunan: 'SEKITAR BANGUNAN',
  lobby_ruang_loker: 'LOBBY, RUANG LOKER',
  loading_dock_dalam: 'LOADING DOCK DALAM',
  anteroom_cold_storage: 'ANTEROOM, COLD STORAGE',
  ruang_baterai: 'RUANG BATERAI',
};

export const saranaPrasaranaAreaOptions = saranaPrasaranaSections.map((section) => ({
  id: section.id,
  name: groupedChecklistAreaLabels[section.id] || section.title.replace(/^[A-Z]\.\s*/, ''),
}));

export const siteVisitHseSections = [
  {
    id: 'lantai_1_area_luar',
    title: 'A. LANTAI 1 - AREA LUAR',
    items: [
      'Area APAR tidak terhalang',
      'Area titik kumpul tidak terhalang',
      'Jalur evakuasi tidak terhalang',
      'Rambu K3 terbaca dengan jelas',
    ],
  },
  {
    id: 'lantai_1_area_belakang',
    title: 'B. LANTAI 1 - AREA BELAKANG',
    items: [
      'Area APAR tidak terhalang',
      'Area titik kumpul tidak terhalang',
      'Jalur evakuasi tidak terhalang',
      'Rambu K3 terbaca dengan jelas',
      'Tidak overloading (tidak bertumpuk banyak colokan)',
    ],
  },
  {
    id: 'lantai_1_area_dalam',
    title: 'C. LANTAI 1 - AREA DALAM',
    items: [
      'Pekerja menggunakan APD yang sesuai',
      'Pekerja menggunakan APD dengan benar',
      'APD yang digunakan dalam keadaan baik',
      'Area APAR tidak terhalang',
      'Jalur evakuasi tidak terhalang',
      'Jalur pejalan kaki terlihat jelas',
      'Jalur pejalan kaki tidak terhalang',
      'Rambu K3 terbaca dengan jelas',
      'Tidak overloading (tidak bertumpuk banyak colokan)',
      'Pintu cold storage dapat dibuka secara otomatis dengan baik',
      'Pintu cold storage dapat ditutup secara otomatis dengan baik',
      'Pintu cold storage dapat dibuka secara manual dengan baik',
      'Pintu cold storage dapat ditutup secara manual dengan baik',
      'Lampu cold storage berfungsi',
      'Lampu emergency anteroom berfungsi',
      'Lampu emergency loading dock berfungsi',
      'Sampah dibuang sesuai klasifikasi tempat sampah',
    ],
  },
  {
    id: 'lantai_2',
    title: 'D. LANTAI 2',
    items: [
      'Area APAR tidak terhalang',
      'Area titik kumpul tidak terhalang',
      'Jalur evakuasi tidak terhalang',
      'Rambu K3 terbaca dengan jelas',
      'Tidak overloading (tidak bertumpuk banyak colokan)',
      'Lampu emergency berfungsi',
    ],
  },
  {
    id: 'lantai_3',
    title: 'E. LANTAI 3',
    items: [
      'Area APAR tidak terhalang',
      'Area titik kumpul tidak terhalang',
      'Jalur evakuasi tidak terhalang',
      'Rambu K3 terbaca dengan jelas',
      'Tidak overloading (tidak bertumpuk banyak colokan)',
      'Lampu emergency berfungsi',
    ],
  },
];

export const siteVisitHseAreaOptions = siteVisitHseSections.map((section) => ({
  id: section.id,
  name: groupedChecklistAreaLabels[section.id] || section.title.replace(/^[A-Z]\.\s*/, ''),
}));

export const patroliSecuritySections = [
  {
    id: 'pos_security',
    title: 'LANTAI 1 - AREA LUAR DEPAN (POS SECURITY)',
    items: [
      'Pintu / akses masuk area depan aman',
      'Tidak ada orang mencurigakan',
      'Tidak ada barang mencurigakan',
      'Kendaraan keluar-masuk terkontrol',
      'Karyawan dan tamu menggunakan ID Card',
      'Lampu penerangan berfungsi (menyala saat malam)',
      'CCTV terpasang dalam kondisi baik',
      'Monitor CCTV berfungsi',
      'Server security berfungsi',
      'Lampu pos security berfungsi (menyala saat malam)',
      'Lampu musholla berfungsi (menyala saat malam)',
      'Kran tempat wudhu berfungsi (mati saat tidak digunakan)',
      'Kipas musholla berfungsi (mati saat tidak digunakan)',
      'Lampu toilet berfungsi (mati saat tidak digunakan)',
      'Kran air berfungsi (mati saat tidak digunakan)',
      'Jalur evakuasi tidak terhalang',
      'Area APAR pos security tidak terhalang',
      'Area bebas putung rokok dan asap rokok',
      'Area titik kumpul tidak terhalang',
    ],
  },
  {
    id: 'loading_dock_luar',
    title: 'LANTAI 1 - AREA LUAR DEPAN (LOADING DOCK LUAR)',
    items: [
      'Tidak ada orang mencurigakan',
      'Tidak ada barang mencurigakan',
      'CCTV terpasang dalam kondisi baik',
      'Lampu penerangan berfungsi (menyala saat malam)',
      'Tidak ada orang tidur di bawah truk',
      'Area bebas putung rokok dan asap rokok',
    ],
  },
  {
    id: 'travo_pln_depan',
    title: 'LANTAI 1 - AREA LUAR TIMUR (TRAVO PLN DEPAN)',
    items: [
      'Pintu travo PLN terkunci',
      'Tidak ada orang mencurigakan',
      'Tidak ada barang mencurigakan',
      'CCTV terpasang dalam kondisi baik',
      'Area APAR Travo PLN tidak terhalang',
      'Area bebas putung rokok dan asap rokok',
    ],
  },
  {
    id: 'tanah_kosong_dan_sekitar_bangunan',
    title: 'LANTAI 1 - AREA LUAR TIMUR (TANAH KOSONG DAN SEKITAR BANGUNAN)',
    items: [
      'Tidak ada orang mencurigakan',
      'Tidak ada barang mencurigakan',
      'Tidak ada orang duduk/tidur di bawah tangga darurat',
      'Pintu kontainer CRMI terkunci (saat malam)',
      'Jendela kontainer CRMI tertutup (saat malam)',
    ],
  },
  {
    id: 'travo_pln_belakang',
    title: 'LANTAI 1 - AREA BELAKANG (TRAVO PLN BELAKANG)',
    items: [
      'Pintu travo PLN terkunci',
      'Tidak ada orang mencurigakan',
      'Tidak ada barang mencurigakan',
      'CCTV terpasang dalam kondisi baik',
      'Area APAR Travo PLN tidak terhalang',
      'Area bebas putung rokok dan asap rokok',
    ],
  },
  {
    id: 'genset',
    title: 'LANTAI 1 - AREA BELAKANG (GENSET)',
    items: [
      'Tidak ada orang mencurigakan',
      'Tidak ada barang mencurigakan',
      'Area bebas putung rokok dan asap rokok',
      'Tidak ada percikan / bau terbakar',
      'Tidak ada kabel terkelupas/gosong',
      'Tidak ada suara abnormal',
    ],
  },
  {
    id: 'ruang_mesin_ruang_kontrol',
    title: 'LANTAI 1 - AREA BELAKANG (RUANG MESIN, RUANG KONTROL)',
    items: [
      'Pintu / akses masuk aman',
      'CCTV terpasang dalam kondisi baik',
      'Tidak ada orang mencurigakan',
      'Tidak ada barang mencurigakan',
      'Tidak overloading (tidak bertumpuk banyak colokan)',
      'Tidak ada kabel terkelupas/gosong',
      'Tidak ada percikan / bau terbakar',
      'Area APAR tidak terhalang',
      'Tidak ada tumpahan oli',
      'Area bebas putung rokok dan asap rokok',
      'Lampu ruang mesin berfungsi (menyala saat malam)',
      'Lampu ruang kontrol berfungsi (menyala saat malam)',
      'Lampu toilet berfungsi (mati saat tidak digunakan)',
      'Kran air di toilet berfungsi (mati saat tidak digunakan)',
    ],
  },
  {
    id: 'sekitar_bangunan',
    title: 'LANTAI 1 - AREA LUAR BARAT (SEKITAR BANGUNAN)',
    items: [
      'Tidak ada orang mencurigakan',
      'Tidak ada barang mencurigakan',
      'Parkir motor rapi',
      'Parkir mobil rapi',
      'Area titik kumpul tidak terhalang apapun',
      'Area bebas putung rokok dan asap rokok',
    ],
  },
  {
    id: 'lobby_ruang_loker',
    title: 'LANTAI 1 - AREA DALAM (LOBBY, RUANG LOKER)',
    items: [
      'CCTV terpasang dalam kondisi baik',
      'Tidak ada orang mencurigakan',
      'Tidak ada barang mencurigakan',
      'Karyawan dan tamu menggunakan ID Card',
      'Area APAR tidak terhalang',
      'Area bebas putung rokok dan asap rokok',
    ],
  },
  {
    id: 'loading_dock_dalam',
    title: 'LANTAI 1 - AREA DALAM (LOADING DOCK DALAM)',
    items: [
      'Pintu berfungsi (tertutup saat tidak digunakan)',
      'Lampu berfungsi (mati saat tidak digunakan)',
      'CCTV terpasang dalam kondisi baik',
      'Tidak ada kabel terkelupas/gosong',
      'Pintu R. Repack CRMI berfungsi (tertutup saat tidak digunakan)',
      'Lampu R. Repack CRMI berfungsi (mati saat tidak digunakan)',
      'Tidak ada percikan / bau terbakar di loading dock',
      'Tidak ada percikan / bau terbakar di R. Repack CRMI',
      'Tidak ada orang mencurigakan',
      'Tidak ada barang mencurigakan',
      'Karyawan dan tamu menggunakan ID Card',
      'Jalur evakuasi tidak terhalang',
      'Area APAR tidak terhalang',
      'Jalur pejalan kaki tidak terhalang',
      'Area bebas putung rokok dan asap rokok',
    ],
  },
  {
    id: 'anteroom_cold_storage',
    title: 'LANTAI 1 - AREA DALAM (ANTEROOM, COLD STORAGE)',
    items: [
      'Pintu berfungsi (tertutup saat tidak digunakan)',
      'Lampu berfungsi (mati saat tidak digunakan)',
      'CCTV terpasang dalam kondisi baik',
      'Tidak ada kabel terkelupas/gosong',
      'Tidak ada percikan / bau terbakar',
      'Tidak ada orang mencurigakan',
      'Tidak ada barang mencurigakan',
      'Karyawan dan tamu menggunakan ID Card',
      'Jalur evakuasi tidak terhalang',
      'Area APAR tidak terhalang',
      'Jalur pejalan kaki tidak terhalang',
      'Area bebas putung rokok dan asap rokok',
    ],
  },
  {
    id: 'ruang_baterai',
    title: 'RUANG BATERAI',
    items: [
      'Pintu berfungsi (tertutup saat tidak digunakan)',
      'Lampu berfungsi (mati saat tidak digunakan)',
      'CCTV terpasang dalam kondisi baik',
      'Tidak ada kabel terkelupas/gosong',
      'Tidak ada percikan / bau terbakar',
      'Tidak ada orang mencurigakan',
      'Tidak ada barang mencurigakan',
      'Karyawan dan tamu menggunakan ID Card',
      'Jalur evakuasi tidak terhalang',
      'Area APAR tidak terhalang',
      'Jalur pejalan kaki tidak terhalang',
      'Area bebas putung rokok dan asap rokok',
    ],
  },
  {
    id: 'lantai_2',
    title: 'LANTAI 2',
    items: [
      'CCTV berfungsi',
      'Tidak ada orang mencurigakan',
      'Karyawan dan tamu menggunakan ID Card',
      'Jalur evakuasi tidak terhalang',
      'Area APAR tidak terhalang',
    ],
  },
  {
    id: 'lantai_3',
    title: 'LANTAI 3',
    items: [
      'CCTV berfungsi',
      'Tidak ada orang mencurigakan',
      'Karyawan dan tamu menggunakan ID Card',
      'Jalur evakuasi tidak terhalang',
      'Area APAR tidak terhalang',
    ],
  },
];

export const patroliSecurityAreaOptions = patroliSecuritySections.map((section) => ({
  id: section.id,
  name: groupedChecklistAreaLabels[section.id] || section.title.replace(/^[A-Z]\.\s*/, ''),
}));

export const maintenanceVisitTypeOptions = [
  {
    id: 'maintenance_harian',
    name: 'Maintenance Mesin (Harian)',
    title: 'CHECKLIST MAINTENANCE MESIN',
    document_no: 'FRM.MTC.01.01',
    schedule_label: 'Tanggal',
  },
  {
    id: 'maintenance_mingguan',
    name: 'Maintenance Genset (Mingguan)',
    title: 'CHECKLIST MAINTENANCE GENSET',
    document_no: 'FRM.MTC.01.02',
    schedule_label: 'Periode',
  },
];

export const maintenanceDailySections = [
  {
    id: 'mesin_dan_sistem_pendingin',
    area_id: 'lantai_1_area_belakang',
    title: 'A. MESIN & SISTEM PENDINGIN',
    items: [
      'Suhu sesuai setting',
      'Tidak ada alarm/error',
      'Suara mesin normal (tidak berisik/aneh)',
      'Getaran normal',
      'Tekanan amonia normal',
    ],
  },
  {
    id: 'evaporator',
    area_id: 'loading_dock',
    title: 'B. EVAPORATOR',
    items: [
      'Evaporator bersih (tidak ada es)',
      'Kipas evaporator berfungsi normal',
      'Suhu sesuai setting',
      'Proses defrost berjalan sesuai jadwal',
    ],
  },
  {
    id: 'kondensor',
    area_id: 'lantai_1_area_belakang',
    title: 'C. KONDENSOR',
    items: [
      'Kondensor bersih',
      'Kipas kondensor berfungsi normal',
      'Tekanan dan suhu kondensor berjalan normal',
      'Tidak ada kebocoran refrigeran',
      'Sirkulasi udara normal/tidak terhalang',
    ],
  },
  {
    id: 'sistem_drainase',
    area_id: 'lantai_1_area_belakang',
    title: 'D. SISTEM DRAINASE',
    items: [
      'Saluran drain tidak tersumbat',
      'Tidak ada genangan air/es',
      'Pipa drain dalam kondisi baik',
    ],
  },
  {
    id: 'kelistrikan',
    area_id: 'lantai_1_area_belakang',
    title: 'E. KELISTRIKAN',
    items: [
      'Panel listrik normal (tidak panas)',
      'Kabel tidak rusak',
      'MCB/proteksi berfungsi normal',
      'Tidak ada bau terbakar',
    ],
  },
  {
    id: 'pintu_dan_seal',
    area_id: 'loading_dock',
    title: 'F. PINTU & SEAL',
    items: [
      'Pintu menutup rapat',
      'Karet seal tidak rusak',
      'Tidak ada kebocoran udara dingin',
      'Alarm pintu berfungsi normal',
    ],
  },
];

export const maintenanceWeeklyItems = [
  'Oli mesin dalam kondisi normal',
  'Filter oli dalam kondisi normal',
  'Filter solar dalam kondisi normal',
  'Air Radiator dalam kondisi normal',
  'Connector battery dalam kondisi normal',
  'Ampere battery dalam kondisi normal',
  'Ketersediaan solar (minimal 250 liter)',
];

export const maintenanceDailyAreaOptions = [
  {
    id: 'lantai_1_area_belakang',
    name: groupedChecklistAreaLabels.lantai_1_area_belakang,
  },
  {
    id: 'loading_dock',
    name: groupedChecklistAreaLabels.loading_dock,
  },
];

export const kotakP3KItems = [
  { id: 'kondisi_kotak_p3k', name: 'Kondisi kotak P3K', quantity: '' },
  { id: 'kasa_steril_terbungkus', name: 'Kasa steril terbungkus', quantity: 20 },
  { id: 'perban_5_cm', name: 'Perban (lebar 5 cm)', quantity: 2 },
  { id: 'perban_10_cm', name: 'Perban (lebar 10 cm)', quantity: 2 },
  { id: 'plester_1_25_cm', name: 'Plester (lebar 1,25 cm)', quantity: 2 },
  { id: 'plester_cepat', name: 'Plester Cepat', quantity: 10 },
  { id: 'kapas_25_gram', name: 'Kapas (25 gram)', quantity: 1 },
  { id: 'kain_segitiga', name: 'Kain segitiga/mitela', quantity: 2 },
  { id: 'gunting', name: 'Gunting', quantity: 1 },
  { id: 'peniti', name: 'Peniti', quantity: 12 },
  { id: 'sarung_tangan', name: 'Sarung tangan sekali pakai', quantity: 2 },
  { id: 'masker', name: 'Masker', quantity: 2 },
  { id: 'pinset', name: 'Pinset', quantity: 1 },
  { id: 'lampu_senter', name: 'Lampu senter', quantity: 1 },
  { id: 'gelas_cuci_mata', name: 'Gelas untuk cuci mata', quantity: 1 },
  { id: 'kantong_plastik_bersih', name: 'Kantong plastik bersih', quantity: 1 },
  { id: 'aquades_saline', name: 'Aquades (100 ml lar. Saline)', quantity: 1 },
  { id: 'povidon_iodin', name: 'Povidon Iodin (60 ml)', quantity: 1 },
  { id: 'alkohol_70', name: 'Alkohol 70%', quantity: 1 },
  { id: 'buku_panduan_p3k', name: 'Buku panduan P3K di tempat kerja', quantity: 1 },
  { id: 'catatan_logbook', name: 'Catatan / logbook', quantity: 1 },
  { id: 'daftar_isi_kotak', name: 'Daftar isi kotak', quantity: 1 },
];

export const kotakP3KMonths = [
  { key: 'jan', label: 'Jan', number: 1 },
  { key: 'feb', label: 'Feb', number: 2 },
  { key: 'mar', label: 'Mar', number: 3 },
  { key: 'apr', label: 'Apr', number: 4 },
  { key: 'may', label: 'May', number: 5 },
  { key: 'jun', label: 'Jun', number: 6 },
  { key: 'jul', label: 'Jul', number: 7 },
  { key: 'aug', label: 'Aug', number: 8 },
  { key: 'sep', label: 'Sep', number: 9 },
  { key: 'oct', label: 'Oct', number: 10 },
  { key: 'nov', label: 'Nov', number: 11 },
  { key: 'dec', label: 'Dec', number: 12 },
];

function createFireSafetyMonthValue(initialValue = '') {
  return kotakP3KMonths.reduce((result, month) => {
    result[month.key] = initialValue;
    return result;
  }, {});
}

function createFireSafetyRows(cardType = 'fire_alarm') {
  const items = fireSafetyItemsByType[cardType] || fireSafetyItemsByType.fire_alarm;

  return items.map((item, index) => ({
    no: index + 1,
    id: item.id,
    name: item.name,
    months: createFireSafetyMonthValue(''),
  }));
}

export function rebuildFireSafetyRows(cardType = 'fire_alarm', existingRows = []) {
  return createFireSafetyRows(cardType).map((row) => {
    const matchedRow = existingRows.find((item) => item.id === row.id);
    if (!matchedRow) {
      return row;
    }

    return {
      ...row,
      months: {
        ...row.months,
        ...(matchedRow.months || {}),
      },
    };
  });
}

export function getFireSafetyRecordKey(cardType = '', locationId = '') {
  return `${String(cardType || '').trim()}::${String(locationId || '').trim()}`;
}

export function createFireSafetyLocationState(cardType = 'fire_alarm', existingState = {}) {
  return {
    approved_months: Array.isArray(existingState?.approved_months) ? [...existingState.approved_months] : [],
    monthly_notes: {
      ...createFireSafetyMonthValue(''),
      ...(existingState?.monthly_notes || {}),
    },
    monthly_barcodes: {
      ...createFireSafetyMonthValue(''),
      ...(existingState?.monthly_barcodes || {}),
    },
    monthly_check_dates: {
      ...createFireSafetyMonthValue(''),
      ...(existingState?.monthly_check_dates || {}),
    },
    rows: rebuildFireSafetyRows(cardType, existingState?.rows || []),
  };
}

export function getFireSafetyCardLabel(cardType) {
  return fireSafetyCardOptions.find((option) => option.id === cardType)?.name || '-';
}

export function getFireSafetyCardTitle(cardType) {
  return fireSafetyCardOptions.find((option) => option.id === cardType)?.title || 'KARTU PEMELIHARAAN';
}

export function getFireSafetyLocationOptions(cardType) {
  return fireSafetyLocationOptionsByType[cardType] || [];
}

export function getFireSafetyLocationLabel(cardType, locationId) {
  return getFireSafetyLocationOptions(cardType).find((location) => location.id === locationId)?.name || '-';
}

export function createFireSafetyEntry(userName) {
  const now = new Date();
  const cardType = 'fire_alarm';
  const defaultLocation = getFireSafetyLocationOptions(cardType)[0]?.id || '';
  const activeMonth = getCurrentKotakP3KMonthKey(now);

  return {
    id: `apar_smoke_detector_fire_alarm-${Date.now()}`,
    template_id: 'apar_smoke_detector_fire_alarm',
    name: 'APAR, Smoke Detector, Fire Alarm',
    created_at: formatDateTimeDisplay(now),
    form: {
      card_type: cardType,
      location: defaultLocation,
      year: String(now.getFullYear()),
      active_month: activeMonth,
      approved: false,
      approved_months: [],
      monthly_notes: createFireSafetyMonthValue(''),
      monthly_barcodes: createFireSafetyMonthValue(''),
      monthly_check_dates: createFireSafetyMonthValue(''),
      rows: createFireSafetyRows(cardType),
      location_records: {
        [getFireSafetyRecordKey(cardType, defaultLocation)]: createFireSafetyLocationState(cardType),
      },
      pic: userName || 'User Login',
    },
  };
}

function createWarehouseStatusMap() {
  return {
    clean_condition: '',
    no_ice_pooling: '',
    no_odor: '',
    note: '',
  };
}

function createWarehouseBooleanMap() {
  return {
    halal: '',
    dosage: '',
    note: '',
    material_name: '',
  };
}

function getWarehouseCleanlinessRows(frequency = 'daily') {
  return warehouseCleanlinessRowsByFrequency[frequency] || warehouseCleanlinessRowsByFrequency.daily;
}

export function buildWarehouseAreaRows(frequency = 'daily', existingRows = []) {
  return getWarehouseCleanlinessRows(frequency).map((row, index) => {
    const matchedRow = existingRows.find((item) => item.id === row.id);

    return {
      no: index + 1,
      id: row.id,
      name: row.name,
      clean_condition: matchedRow?.clean_condition || '',
      no_ice_pooling: matchedRow?.no_ice_pooling || '',
      no_odor: matchedRow?.no_odor || '',
      note: matchedRow?.note || '',
    };
  });
}

function createPersonalHygieneDayMap(periodValue) {
  return getDaysInPeriod(periodValue).reduce((result, dayInfo) => {
    result[dayInfo.day] = '';
    return result;
  }, {});
}

function createPersonalHygieneRows(periodValue) {
  return personalHygieneRows.map((row, index) => ({
    no: index + 1,
    id: row.id,
    name: row.name,
    days: createPersonalHygieneDayMap(periodValue),
  }));
}

function createSaranaPrasaranaDayMap(periodValue) {
  return getDaysInPeriod(periodValue).reduce((result, dayInfo) => {
    result[dayInfo.day] = '';
    return result;
  }, {});
}

function createSiteVisitHseSections() {
  return siteVisitHseSections.map((section) => ({
    id: section.id,
    title: section.title,
    items: section.items.map((itemName, index) => ({
      no: index + 1,
      id: `${section.id}-${index + 1}`,
      name: itemName,
      status: '',
    })),
  }));
}

function createPatroliSecuritySections() {
  return patroliSecuritySections.map((section) => ({
    id: section.id,
    title: section.title,
    items: section.items.map((itemName, index) => ({
      no: index + 1,
      id: `${section.id}-${index + 1}`,
      name: itemName,
      status: '',
    })),
  }));
}

function createMaintenanceRow(id, name, no, locationLabel = '') {
  return {
    id,
    no,
    name,
    status: '',
    note: '',
    scan_location: locationLabel,
  };
}

function createMaintenanceDailySections() {
  return maintenanceDailySections.map((section) => ({
    id: section.id,
    area_id: section.area_id,
    title: section.title,
    items: section.items.map((itemName, index) => createMaintenanceRow(`${section.id}-${index + 1}`, itemName, index + 1)),
  }));
}

function createMaintenanceWeeklyRows() {
  return maintenanceWeeklyItems.map((itemName, index) => createMaintenanceRow(`genset-${index + 1}`, itemName, index + 1, groupedChecklistAreaLabels.lantai_1_area_belakang));
}

function createSaranaPrasaranaSections(periodValue) {
  return saranaPrasaranaSections.map((section) => ({
    id: section.id,
    title: section.title,
    items: section.items.map((itemName, index) => ({
      no: index + 1,
      id: `${section.id}-${index + 1}`,
      name: itemName,
      days: createSaranaPrasaranaDayMap(periodValue),
    })),
  }));
}

export function rebuildSaranaPrasaranaSections(periodValue, existingSections = []) {
  return createSaranaPrasaranaSections(periodValue).map((section) => {
    const matchedSection = existingSections.find((item) => item.id === section.id);

    return {
      ...section,
      items: section.items.map((item) => {
        const matchedItem = matchedSection?.items?.find((sectionItem) => sectionItem.id === item.id);
        return {
          ...item,
          days: {
            ...item.days,
            ...(matchedItem?.days || {}),
          },
        };
      }),
    };
  });
}

export function rebuildSiteVisitHseSections(existingSections = []) {
  return createSiteVisitHseSections().map((section) => {
    const matchedSection = existingSections.find((item) => item.id === section.id);

    return {
      ...section,
      items: section.items.map((item) => {
        const matchedItem = matchedSection?.items?.find((sectionItem) => sectionItem.id === item.id);
        return {
          ...item,
          status: matchedItem?.status || '',
        };
      }),
    };
  });
}

export function rebuildPatroliSecuritySections(existingSections = []) {
  return createPatroliSecuritySections().map((section) => {
    const matchedSection = existingSections.find((item) => item.id === section.id);

    return {
      ...section,
      items: section.items.map((item) => {
        const matchedItem = matchedSection?.items?.find((sectionItem) => sectionItem.id === item.id);
        return {
          ...item,
          status: matchedItem?.status || '',
        };
      }),
    };
  });
}

export function rebuildMaintenanceDailySections(existingSections = []) {
  return createMaintenanceDailySections().map((section) => {
    const matchedSection = existingSections.find((item) => item.id === section.id);

    return {
      ...section,
      area_id: matchedSection?.area_id || section.area_id,
      items: section.items.map((item) => {
        const matchedItem = matchedSection?.items?.find((sectionItem) => sectionItem.id === item.id);

        return {
          ...item,
          status: matchedItem?.status || '',
          note: matchedItem?.note || '',
          scan_location: matchedItem?.scan_location || '',
        };
      }),
    };
  });
}

export function rebuildMaintenanceWeeklyRows(existingRows = []) {
  return createMaintenanceWeeklyRows().map((row) => {
    const matchedRow = existingRows.find((item) => item.id === row.id);

    return {
      ...row,
      status: matchedRow?.status || '',
      note: matchedRow?.note || '',
      scan_location: matchedRow?.scan_location || row.scan_location || groupedChecklistAreaLabels.lantai_1_area_belakang,
    };
  });
}

export function getMaintenanceVisitTypeMeta(visitType) {
  return maintenanceVisitTypeOptions.find((option) => option.id === visitType) || maintenanceVisitTypeOptions[0];
}

export function getMaintenanceVisitTypeLabel(visitType) {
  return getMaintenanceVisitTypeMeta(visitType)?.name || '-';
}

export function getMaintenanceDailyAreaLabel(areaId) {
  return maintenanceDailyAreaOptions.find((area) => area.id === areaId)?.name || '-';
}

export function getSaranaPrasaranaAreaLabel(areaId) {
  return saranaPrasaranaAreaOptions.find((area) => area.id === areaId)?.name || '-';
}

export function getSiteVisitHseAreaLabel(areaId) {
  return siteVisitHseAreaOptions.find((area) => area.id === areaId)?.name || '-';
}

export function getPatroliSecurityAreaLabel(areaId) {
  return patroliSecurityAreaOptions.find((area) => area.id === areaId)?.name || '-';
}

export function createWarehouseSanitationEntry(userName) {
  const now = new Date();
  const period = toPeriodValue(now);
  const frequency = 'daily';

  return {
    id: `warehouse_sanitation-${Date.now()}`,
    template_id: 'warehouse_sanitation_1',
    name: 'Kebersihan dan Sanitasi (Warehouse Area)',
    created_at: formatDateTimeDisplay(now),
    form: {
      frequency,
      date: formatDateDisplay(now),
      period,
      barcode: '',
      scan_date: '',
      room_temperature: '',
      petugas: userName || 'User Login',
      hse: '',
      selected_areas: [],
      approved: false,
      area_rows: buildWarehouseAreaRows(frequency),
      ice_control_rows: warehouseIceControlRows.map((row, index) => ({
        no: index + 1,
        id: row.id,
        name: row.name,
        status: '',
        note: '',
      })),
      cleaning_material_rows: warehouseCleaningMaterialRows.map((row) => ({
        no: row.no,
        id: row.id,
        ...createWarehouseBooleanMap(),
      })),
      verification: {
        prepared_name: '',
        prepared_signature: '',
        prepared_date: '',
        verified_name: '',
        verified_signature: '',
        verified_date: '',
      },
    },
  };
}

export function createPersonalHygieneEntry(userName) {
  const now = new Date();
  const period = toPeriodValue(now);

  return {
    id: `personal_hygiene_karyawan-${Date.now()}`,
    template_id: 'personal_hygiene_karyawan',
    name: 'Personal Hygiene Karyawan',
    created_at: formatDateTimeDisplay(now),
    form: {
      year: String(now.getFullYear()),
      period,
      employee_name: '',
      gender: '',
      nik: '',
      bagian: '',
      approved: false,
      generated_at: '',
      rows: createPersonalHygieneRows(period),
      generated_employees: [],
    },
  };
}

export function createSaranaPrasaranaEntry(userName) {
  const now = new Date();
  const period = toPeriodValue(now);
  const defaultArea = saranaPrasaranaSections[0]?.id || '';

  return {
    id: `sarana_dan_prasarana-${Date.now()}`,
    template_id: 'sarana_dan_prasarana',
    name: 'Sarana dan Prasarana',
    created_at: formatDateTimeDisplay(now),
    form: {
      period,
      selected_area: defaultArea,
      pic: userName || 'User Login',
      approved: false,
      approved_days_by_area: saranaPrasaranaSections.reduce((result, section) => {
        result[section.id] = [];
        return result;
      }, {}),
      area_scans_by_day: {},
      document_no: 'FRM.HRGA.01.06',
      rev: '00',
      effective_date: '22 Desember 2025',
      page: '1 dari 1',
      sections: createSaranaPrasaranaSections(period),
    },
  };
}

export function createSiteVisitHseEntry(userName) {
  const now = new Date();
  const defaultArea = siteVisitHseSections[0]?.id || '';

  return {
    id: `site_visit_hse-${Date.now()}`,
    template_id: 'site_visit_hse',
    name: 'Site Visit HSE',
    created_at: formatDateTimeDisplay(now),
    form: {
      date_value: toDateInputValue(now),
      date: formatDateDisplay(now),
      selected_area: defaultArea,
      pic: userName || 'User Login',
      approved: false,
      approved_areas: [],
      area_barcodes: {},
      area_notes: {},
      area_scan_dates: {},
      document_no: 'FRM.HSE.15.01',
      rev: '00',
      effective_date: '22 Desember 2025',
      page: '1 dari 1',
      sections: createSiteVisitHseSections(),
    },
  };
}

export function createPatroliSecurityEntry(userName) {
  const now = new Date();
  const defaultArea = patroliSecuritySections[0]?.id || '';

  return {
    id: `patroli_security-${Date.now()}`,
    template_id: 'patroli_security',
    name: 'Patroli Security',
    created_at: formatDateTimeDisplay(now),
    form: {
      date_value: toDateInputValue(now),
      date: formatDateDisplay(now),
      selected_area: defaultArea,
      pic: userName || 'User Login',
      approved: false,
      approved_areas: [],
      area_barcodes: {},
      area_notes: {},
      area_scan_dates: {},
      document_no: 'FRM.HSE.15.02',
      rev: '00',
      effective_date: '22 Desember 2025',
      page: '1 dari 1',
      sections: createPatroliSecuritySections(),
    },
  };
}

export function createSiteVisitMaintenanceEntry(userName) {
  const now = new Date();
  const visitType = maintenanceVisitTypeOptions[0]?.id || 'maintenance_harian';
  const typeMeta = getMaintenanceVisitTypeMeta(visitType);
  const dateValue = toDateInputValue(now);
  const periodValue = toWeekInputValue(now);

  return {
    id: `site_visit_maintenance-${Date.now()}`,
    template_id: 'site_visit_maintenance',
    name: 'Site Visit Maintenance',
    created_at: formatDateTimeDisplay(now),
    form: {
      visit_type: visitType,
      pic: userName || 'User Login',
      approved: false,
      document_no: typeMeta.document_no,
      rev: '00',
      effective_date: '22 Desember 2025',
      page: '1 dari 1',
      selected_area: 'lantai_1_area_belakang',
      area_barcodes: {},
      area_scan_dates: {},
      area_notes: {},
      date_value: dateValue,
      date: formatDateDisplay(now),
      period_value: periodValue,
      period: formatWeekDisplay(periodValue),
      sections: createMaintenanceDailySections(),
      rows: createMaintenanceWeeklyRows(),
    },
  };
}

export function createWasteTransportEntry(userName) {
  const now = new Date();
  const period = toPeriodValue(now);

  return {
    id: `pengangkutan_sampah_pt_sier-${Date.now()}`,
    template_id: 'pengangkutan_sampah_pt_sier',
    name: 'Pengangkutan Sampah PT SIER',
    created_at: formatDateTimeDisplay(now),
    form: {
      period,
      date: formatDateDisplay(now),
      pic: userName || 'User Login',
      approved: false,
      approved_days: [],
      rows: createWasteTransportRows(period),
    },
  };
}

function createWasteTransportRows(periodValue) {
  return getDaysInPeriod(periodValue).map((dayInfo) => ({
    day: dayInfo.day,
    handover_name: '',
    pickup_time: '',
    collector_name: '',
    collector_photo_name: '',
    collector_photo_preview: '',
  }));
}

export function rebuildWasteTransportRows(periodValue, existingRows = []) {
  return getDaysInPeriod(periodValue).map((dayInfo) => {
    const matchedRow = existingRows.find((row) => Number(row.day) === dayInfo.day);

    return {
      day: dayInfo.day,
      handover_name: matchedRow?.handover_name || '',
      pickup_time: matchedRow?.pickup_time || '',
      collector_name: matchedRow?.collector_name || '',
      collector_photo_name: matchedRow?.collector_photo_name || '',
      collector_photo_preview: matchedRow?.collector_photo_preview || '',
    };
  });
}

export function rebuildPersonalHygieneRows(periodValue, existingRows = []) {
  const baseRows = createPersonalHygieneRows(periodValue);

  return baseRows.map((row) => {
    const matchedRow = existingRows.find((item) => item.id === row.id);
    if (!matchedRow) {
      return row;
    }

    const nextDays = { ...row.days };
    Object.keys(nextDays).forEach((day) => {
      if (Object.prototype.hasOwnProperty.call(matchedRow.days || {}, day)) {
        const value = matchedRow.days[day];
        nextDays[day] = value === true ? 'yes' : (value || '');
      }
    });

    return {
      ...row,
      days: nextDays,
    };
  });
}

export function formatDateDisplay(date = new Date()) {
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date);
}

export function formatDateInputDisplay(dateValue) {
  if (!String(dateValue || '').trim()) {
    return '-';
  }

  const [year, month, day] = String(dateValue).split('-').map(Number);
  if (!year || !month || !day) {
    return '-';
  }

  return formatDateDisplay(new Date(year, month - 1, day));
}

export function formatShortDateDisplay(date = new Date()) {
  return new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: 'short',
  }).format(date);
}

export function formatDayMonthDisplay(date = new Date()) {
  return `${date.getDate()}/${date.getMonth() + 1}`;
}

export function formatDateTimeDisplay(date = new Date()) {
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}

export function formatMonthYearDisplay(periodValue) {
  const [year, month] = String(periodValue || '').split('-');
  if (!year || !month) {
    return '-';
  }

  const date = new Date(Number(year), Number(month) - 1, 1);
  return new Intl.DateTimeFormat('id-ID', {
    month: 'long',
    year: 'numeric',
  }).format(date);
}

export function toPeriodValue(date = new Date()) {
  const month = `${date.getMonth() + 1}`.padStart(2, '0');
  return `${date.getFullYear()}-${month}`;
}

export function toDateInputValue(date = new Date()) {
  const month = `${date.getMonth() + 1}`.padStart(2, '0');
  const day = `${date.getDate()}`.padStart(2, '0');
  return `${date.getFullYear()}-${month}-${day}`;
}

function getIsoWeekNumber(date) {
  const target = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
  const dayNumber = target.getUTCDay() || 7;
  target.setUTCDate(target.getUTCDate() + 4 - dayNumber);
  const yearStart = new Date(Date.UTC(target.getUTCFullYear(), 0, 1));
  return Math.ceil((((target - yearStart) / 86400000) + 1) / 7);
}

export function toWeekInputValue(date = new Date()) {
  const year = date.getFullYear();
  const week = `${getIsoWeekNumber(date)}`.padStart(2, '0');
  return `${year}-W${week}`;
}

export function formatWeekDisplay(weekValue) {
  const match = String(weekValue || '').match(/^(\d{4})-W(\d{2})$/);
  if (!match) {
    return '-';
  }

  const [, year, week] = match;
  return `Minggu ${Number(week)}, ${year}`;
}

export function getCurrentKotakP3KMonthKey(date = new Date()) {
  return kotakP3KMonths[date.getMonth()]?.key || 'jan';
}

export function getKotakP3KMonthLabel(monthKey) {
  return kotakP3KMonths.find((month) => month.key === monthKey)?.label || monthKey;
}

function createKotakP3KMonthValue(initialValue = '') {
  return kotakP3KMonths.reduce((result, month) => {
    result[month.key] = initialValue;
    return result;
  }, {});
}

export function getLocationLabel(locationId) {
  return locationOptions.find((location) => location.id === locationId)?.name || '-';
}

export function getLocationBarcodeAliases(locationId) {
  const aliases = {
    ruang_admin: ['Lantai 1 Belakang', 'Ruang Admin'],
    ruang_kontrol: ['Lantai 1 Belakang', 'Ruang Kontrol'],
    pos_security: ['Lantai 1 Depan Dan Luar', 'Pos Security'],
    lobby_lantai_1: ['Lantai 1 Dalam', 'Lobby Lantai 1'],
    area_office_lantai_2: ['Lantai 2 Office', 'Area Office Lantai 2'],
    area_ruang_mesin: ['Lantai 1 Belakang', 'Area Ruang Mesin'],
    area_lantai_3: ['Lantai 3 Office', 'Area Lantai 3'],
    lantai_2: ['Lantai 2 Office', 'Lantai 2'],
  };

  return aliases[locationId] || [getLocationLabel(locationId)];
}

export function getSanitationAreaLabel(areaId) {
  return sanitationAreaOptions.find((area) => area.id === areaId)?.name || '-';
}

export function getSanitationAreaBarcodeAliases(areaId) {
  const aliases = {
    lantai_1: ['Lantai 1 Dalam', 'Lantai 1'],
    lantai_2: ['Lantai 2 Office', 'Lantai 2'],
    area_luar_bangunan: ['Lantai 1 Depan Dan Luar', 'Area Luar Bangunan'],
  };

  return aliases[areaId] || [getSanitationAreaLabel(areaId)];
}

export function getChecklistLabel(templateId) {
  return checklistOptions.find((option) => option.id === templateId)?.name || '-';
}

export function getChecklistEntryAreaLabel(entry) {
  if (entry?.template_id === 'apar_smoke_detector_fire_alarm') {
    const cardLabel = getFireSafetyCardLabel(entry.form?.card_type);
    const locationLabel = getFireSafetyLocationLabel(entry.form?.card_type, entry.form?.location);
    return `${cardLabel} - ${locationLabel}`;
  }

  if (entry?.template_id === 'kotak_p3k') {
    return getLocationLabel(entry.form?.location);
  }

  if (entry?.template_id === 'non_warehouse_sanitation') {
    return getSanitationAreaLabel(entry.form?.area);
  }

  if (entry?.template_id === 'pengangkutan_sampah_pt_sier') {
    return 'PT SIER';
  }

  if (entry?.template_id === 'warehouse_sanitation_1') {
    const areas = Array.isArray(entry.form?.selected_areas) ? entry.form.selected_areas : [];
    const labels = warehouseAreaOptions
      .filter((area) => areas.includes(area.id))
      .map((area) => area.name);

    return labels.length ? labels.join(', ') : 'Warehouse';
  }

  if (entry?.template_id === 'personal_hygiene_karyawan') {
    return entry.form?.employee_name || entry.form?.bagian || 'Personal Hygiene';
  }

  if (entry?.template_id === 'sarana_dan_prasarana') {
    return getSaranaPrasaranaAreaLabel(entry.form?.selected_area);
  }

  if (entry?.template_id === 'site_visit_hse') {
    return getSiteVisitHseAreaLabel(entry.form?.selected_area);
  }

  if (entry?.template_id === 'patroli_security') {
    return getPatroliSecurityAreaLabel(entry.form?.selected_area);
  }

  if (entry?.template_id === 'site_visit_maintenance') {
    if (entry.form?.visit_type === 'maintenance_mingguan') {
      return groupedChecklistAreaLabels.lantai_1_area_belakang;
    }

    return getMaintenanceDailyAreaLabel(entry.form?.selected_area);
  }

  return '-';
}

export function getDaysInPeriod(periodValue) {
  const [year, month] = String(periodValue || '').split('-');
  const safeYear = Number(year);
  const safeMonth = Number(month);

  if (!safeYear || !safeMonth) {
    return [];
  }

  const lastDay = new Date(safeYear, safeMonth, 0).getDate();

  return Array.from({ length: lastDay }, (_, index) => {
    const day = index + 1;
    const date = new Date(safeYear, safeMonth - 1, day);
    const isSunday = date.getDay() === 0;

    return {
      day,
      date: `${periodValue}-${String(day).padStart(2, '0')}`,
      key: `${periodValue}-${day}`,
      isSunday,
    };
  });
}

function createSanitationDayMap(periodValue) {
  return getDaysInPeriod(periodValue).reduce((result, dayInfo) => {
    result[dayInfo.day] = false;
    return result;
  }, {});
}

function createSanitationRows(areaId, periodValue) {
  const selectedArea = sanitationAreaOptions.find((area) => area.id === areaId) || sanitationAreaOptions[0];

  return selectedArea.items.map((itemName, index) => ({
    id: `${selectedArea.id}-${index + 1}`,
    name: itemName,
    days: createSanitationDayMap(periodValue),
  }));
}

function createSanitationRowsByArea(periodValue) {
  return sanitationAreaOptions.reduce((result, area) => {
    result[area.id] = createSanitationRows(area.id, periodValue);
    return result;
  }, {});
}

export function createKotakP3KEntry(userName) {
  const now = new Date();
  const activeMonth = getCurrentKotakP3KMonthKey(now);

  return {
    id: `kotak_p3k-${Date.now()}`,
    template_id: 'kotak_p3k',
    name: 'Kotak P3K',
    created_at: formatDateTimeDisplay(now),
    form: {
      location: 'ruang_kontrol',
      box_type: 'A',
      pic: userName || 'User Login',
      year: String(now.getFullYear()),
      document_no: 'FRM.HSE.11.01',
      date: formatDateDisplay(now),
      rev: '00',
      page: '1',
      barcode: '',
      approved: false,
      active_month: activeMonth,
      submitted_months: [],
      approved_months: [],
      monthly_hse_approved_by: createKotakP3KMonthValue(''),
      monthly_notes: createKotakP3KMonthValue(''),
      monthly_barcodes: createKotakP3KMonthValue(''),
      monthly_check_dates: createKotakP3KMonthValue(''),
      check_date: formatDateDisplay(now),
      items: kotakP3KItems.map((item) => ({
        ...item,
        months: createKotakP3KMonthValue(''),
      })),
    },
  };
}

export function createNonWarehouseSanitationEntry(userName) {
  const now = new Date();
  const period = toPeriodValue(now);
  const defaultArea = sanitationAreaOptions[0];
  const rowsByArea = createSanitationRowsByArea(period);

  return {
    id: `non_warehouse_sanitation-${Date.now()}`,
    template_id: 'non_warehouse_sanitation',
    name: 'Kebersihan dan Sanitasi (Non-Warehouse Area)',
    created_at: formatDateTimeDisplay(now),
    form: {
      period,
      area: defaultArea.id,
      pic: userName || 'User Login',
      document_no: 'FRM/HSE/02/02',
      rev: '00',
      approved: false,
      approved_days: [],
      area_scans_by_day: {},
      area_notes: {},
      date: formatDateDisplay(now),
      verifier: 'HSE',
      verifier_title: 'Diperiksa oleh,',
      rows_by_area: rowsByArea,
    },
  };
}

export function rebuildSanitationRows(areaId, periodValue, existingRows = []) {
  const baseRows = createSanitationRows(areaId, periodValue);

  return baseRows.map((row) => {
    const matchedRow = existingRows.find((item) => item.name === row.name);
    if (!matchedRow) {
      return row;
    }

    const nextDays = { ...row.days };
    Object.keys(nextDays).forEach((day) => {
      if (Object.prototype.hasOwnProperty.call(matchedRow.days || {}, day)) {
        nextDays[day] = Boolean(matchedRow.days[day]);
      }
    });

    return {
      ...row,
      days: nextDays,
    };
  });
}

export function rebuildAllSanitationRowsByArea(periodValue, existingRowsByArea = {}) {
  return sanitationAreaOptions.reduce((result, area) => {
    result[area.id] = rebuildSanitationRows(
      area.id,
      periodValue,
      existingRowsByArea?.[area.id] || []
    );

    return result;
  }, {});
}
