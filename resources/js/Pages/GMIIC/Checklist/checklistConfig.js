export const checklistOptions = [
    {
        id: "non_warehouse_sanitation",
        name: "Kebersihan dan Sanitasi (Non-Warehouse Area)",
    },
    { id: "kotak_p3k", name: "Kotak P3K" },
    {
        id: "apar_smoke_detector_fire_alarm",
        name: "APAR, Smoke Detector, Fire Alarm",
    },
    { id: "pengangkutan_sampah_pt_sier", name: "Pengangkutan Sampah PT SIER" },
    { id: "personal_hygiene_karyawan", name: "Personal Hygiene Karyawan" },
    { id: "patroli_security", name: "Patroli Security" },
    { id: "jadwal_cleaning_ob", name: "Jadwal Cleaning OB" },
    { id: "site_visit_hse", name: "Site Visit HSE" },
    { id: "site_visit_maintenance", name: "Site Visit Maintenance" },
    { id: "genset_running", name: "Pemanasan (Running) Genset" },
    { id: "running_genset", name: "Running Genset" },
    { id: "kompresor_harian", name: "Kompresor" },
    { id: "charger_baterai", name: "Charger Baterai" },
    { id: "checklist_baterai", name: "Checklist Baterai" },
    { id: "sarana_dan_prasarana", name: "Sarana dan Prasarana" },
    {
        id: "warehouse_sanitation_1",
        name: "Kebersihan dan Sanitasi (Warehouse Area)",
    },
];

const groupedLocationLabels = {
    pos_security: "Lantai 1 Depan Dan Luar",
    lantai_1_area_luar: "Lantai 1 Depan Dan Luar",
    lobby_lantai_1: "Lantai 1 Dalam",
    lantai_1_area_dalam: "Lantai 1 Dalam",
    loading_dock: "Lantai 1 Dalam",
    ruang_admin: "Lantai 1 Belakang",
    ruang_kontrol: "Lantai 1 Belakang",
    area_ruang_mesin: "Lantai 1 Belakang",
    lantai_1_area_belakang: "Lantai 1 Belakang",
    area_office_lantai_2: "Lantai 2 Office",
    lantai_2: "Lantai 2 Office",
    area_lantai_3: "Lantai 3 Office",
    lantai_3: "Lantai 3 Office",
};

export const locationOptions = [
    { id: "ruang_admin", name: groupedLocationLabels.ruang_admin },
    { id: "ruang_kontrol", name: groupedLocationLabels.ruang_kontrol },
    { id: "pos_security", name: groupedLocationLabels.pos_security },
];

export const fireSafetyCardOptions = [
    { id: "apar", name: "APAR", title: "KARTU PEMELIHARAAN APAR" },
    {
        id: "smoke_detector",
        name: "Smoke Detector",
        title: "KARTU PEMELIHARAAN SMOKE DETECTOR",
    },
    {
        id: "fire_alarm",
        name: "Fire Alarm",
        title: "KARTU PEMELIHARAAN FIRE ALARM",
    },
];

export const fireSafetyLocationOptionsByType = {
    fire_alarm: [
        { id: "lobby_lantai_1", name: groupedLocationLabels.lobby_lantai_1 },
        {
            id: "area_office_lantai_2",
            name: groupedLocationLabels.area_office_lantai_2,
        },
        {
            id: "area_ruang_mesin",
            name: groupedLocationLabels.area_ruang_mesin,
        },
        { id: "area_lantai_3", name: groupedLocationLabels.area_lantai_3 },
    ],
    smoke_detector: [
        { id: "lantai_1_lobby", name: "Lantai 1 - Lobby" },
        { id: "lantai_2_ruang_staff", name: "Lantai 2 - Ruang Staff" },
        { id: "lantai_2_ruang_meeting", name: "Lantai 2 - Ruang Meeting" },
        { id: "lantai_2_ruang_owner", name: "Lantai 2 - Ruang Owner" },
        { id: "lantai_3_ruang_server", name: "Lantai 3 - Ruang Server" },
        { id: "lantai_3_ruang_makan", name: "Lantai 3 - Ruang Makan" },
        { id: "lantai_3_ruang_staff", name: "Lantai 3 - Ruang Staff" },
        { id: "lantai_3_ruang_meeting", name: "Lantai 3 - Ruang Meeting" },
    ],
    apar: [
        {
            id: "lantai_1_ruang_penghangat_powder_6kg_001",
            name: "Lantai 1 - Ruang Penghangat - Powder 6kg 001",
        },
        {
            id: "lantai_1_ruang_admin_powder_3kg_001",
            name: "Lantai 1 - Ruang Admin - Powder 3kg 001",
        },
        {
            id: "lantai_1_ruang_loading_dock_powder_6kg_002",
            name: "Lantai 1 - Ruang Loading Dock - Powder 6kg 002",
        },
        {
            id: "lantai_1_ruang_loading_dock_powder_6kg_003",
            name: "Lantai 1 - Ruang Loading Dock - Powder 6kg 003",
        },
        {
            id: "lantai_1_ruang_anteroom__powder_6kg_001",
            name: "Lantai 1 - Ruang Anteroom - Powder 6kg 001",
        },
        {
            id: "lantai_1_ruang_baterai_co2_5kg_001",
            name: "Lantai 1 - Ruang Baterai - CO2 5kg 001",
        },
        {
            id: "lantai_1_ruang_mesin_co2_5kg_002",
            name: "Lantai 1 - Ruang Mesin - CO2 5kg 002",
        },
        {
            id: "lantai_1_travo_pln_belakang_co2_9kg_001",
            name: "Lantai 1 - Trafo PLN Belakang - CO2 9kg 001",
        },
        {
            id: "lantai_1_travo_pln_depan_co2_9kg_002",
            name: "Lantai 1 - Trafo PLN Depan - CO2 9kg 002",
        },
        {
            id: "lantai_2_pantry_powder_6kg_005",
            name: "Lantai 2 - Pantry - Powder 6kg 005",
        },
        {
            id: "lantai_3_ruang_server_co2_5kg_003",
            name: "Lantai 3 - Ruang Server - CO2 5kg 003",
        },
        {
            id: "lantai_3_ruang_makan_powder_6kg_006",
            name: "Lantai 3 - Ruang Makan - Powder 6kg 006",
        },
        {
            id: "lantai_3_ruang_staff_powder_6kg_007",
            name: "Lantai 3 - Ruang Staff - Powder 6kg 007",
        },
        {
            id: "lantai_3_ruang_meeting_powder_6kg_008",
            name: "Lantai 3 - Ruang Meeting - Powder 6kg 008",
        },
    ],
};

export const fireSafetyItemsByType = {
    apar: [
        { id: "terlihat_jelas", name: "Terlihat Jelas" },
        { id: "mudah_dijangkau", name: "Mudah Dijangkau" },
        { id: "tidak_terhalang_barang", name: "Tidak Terhalang Barang" },
        {
            id: "pressure_normal",
            name: "Tekanan / pressure dalam kondisi normal",
        },
        { id: "pin_segel_lengkap", name: "Pin pengaman dan segel lengkap" },
        { id: "tabung_tidak_korosi", name: "Tabung tidak korosi / kerusakan" },
    ],
    smoke_detector: [
        {
            id: "alarm_menyala_saat_asap",
            name: "Alarm menyala ketika ada asap di smoke detector",
        },
    ],
    fire_alarm: [
        { id: "terlihat_jelas", name: "Terlihat Jelas" },
        { id: "mudah_dijangkau", name: "Mudah Dijangkau" },
        { id: "tidak_terhalang_barang", name: "Tidak Terhalang Barang" },
        {
            id: "label_push_here_terbaca",
            name: "Label Push Here Terbaca Jelas",
        },
        {
            id: "tombol_dapat_ditekan",
            name: "Tombol Dapat Ditekan saat Uji Fungsi",
        },
        {
            id: "dapat_direset",
            name: "Dapat direset Kembali setelah pengujian",
        },
        { id: "kondisi_bersih", name: "Kondisi Bersih dan Tidak Berdebu" },
    ],
};

export const sanitationAreaOptions = [
    {
        id: "lantai_1",
        name: "Lantai 1 Dalam",
        items: [
            "Ruang Admin",
            "Ruang Repacking",
            "Toilet Pria",
            "Toilet Wanita",
            "Toilet Tamu",
            "Urinoir",
            "Ruang Loker",
            "Lobby",
        ],
    },
    {
        id: "lantai_2",
        name: "Lantai 2 Office",
        items: [
            "Ruang Direktur",
            "Ruang Meeting",
            "Ruang Staff",
            "Toilet Wanita",
            "Toilet Pria",
            "Pantry",
        ],
    },
    {
        id: "lantai_1_depan",
        name: "Lantai 1 Depan",
        items: [
            "Pos Security",
            "Musholla",
            "Ruang Laktasi",
            "Ruang Istirahat",
            "Ruang Kesehatan",
        ],
    },
    {
        id: "lantai_1_belakang",
        name: "Lantai 1 Belakang",
        items: ["Ruang Mesin", "Ruang Kontrol", "Ruang Baterai"],
    },
];

export const warehouseAreaOptions = [
    { id: "chiller", name: "Chiller" },
    { id: "freezer", name: "Freezer" },
    { id: "loading_area", name: "Loading Area" },
];

export const warehouseCleanlinessRowsByFrequency = {
    daily: [{ id: "lantai", name: "Lantai" }],
    monthly: [
        { id: "pallet", name: "Pallet" },
        { id: "dinding", name: "Dinding" },
    ],
};

export const warehouseIceControlRows = [
    { id: "penumpukan_es", name: "Tidak ada penumpukan es berlebih" },
    { id: "air_lelehan", name: "Tidak ada air lelehan di lantai" },
    { id: "kondensasi", name: "Tidak ada kondensasi menetes" },
];

export const warehouseCleaningMaterialRows = [{ id: "bahan_1", no: 1 }];

export const personalHygieneRows = [
    { id: "suhu_tubuh_tidak_panas", name: "Suhu tubuh tidak panas" },
    {
        id: "tidak_mempunyai_luka_terbuka",
        name: "Tidak mempunyai luka terbuka",
    },
    { id: "jaket_thermal_bersih", name: "Jaket thermal bersih" },
    { id: "sarung_tangan_bersih", name: "Sarung Tangan Bersih" },
    {
        id: "kuku_pendek_tidak_diwarnai",
        name: "Kuku pendek & tidak diwarnai/dicat",
    },
    {
        id: "tidak_memakai_perhiasan",
        name: "Tidak memakai perhiasan/aksesoris/jam tangan",
    },
    {
        id: "tidak_membawa_barang_pribadi",
        name: "Tidak membawa barang bawaan (barang pribadi) ke area warehouse",
    },
    {
        id: "tidak_membawa_makanan",
        name: "Tidak membawa makanan & minuman ke area warehouse (selain produk customer)",
    },
    { id: "rambut_rapi_pendek", name: "Rambut rapi & pendek untuk karyawan" },
    {
        id: "tidak_berjenggot",
        name: "Tidak berjenggot/cambang/kumis untuk karyawan",
    },
    {
        id: "tidak_memakai_bulu_mata",
        name: "Tidak memakai bulu mata palsu/eye shadow",
    },
    { id: "plester_perban_in", name: "Plester/Perban (In)" },
    { id: "plester_perban_out", name: "Plester/Perban (Out)" },
];

export const saranaPrasaranaSections = [
    {
        id: "lantai_1_area_luar",
        title: "A. LANTAI 1 - AREA LUAR",
        items: [
            "Dinding tidak retak",
            "Lantai tidak retak",
            "Plafon tidak retak",
            "Ventilasi / sirkulasi udara baik",
            "Tidak ada genangan air",
            "Toilet bersih dan berfungsi",
            "Ketersediaan sabun dan tisu",
            "Tempat ibadah bersih",
            "Area makan / istirahat bersih",
            "Tempat sampah tertutup",
        ],
    },
    {
        id: "lantai_1_area_belakang",
        title: "B. LANTAI 1 - AREA BELAKANG",
        items: [
            "Dinding tidak retak",
            "Lantai tidak retak",
            "Plafon tidak retak",
            "Ventilasi / sirkulasi udara baik",
            "Tidak ada genangan air",
            "Toilet bersih dan berfungsi",
            "Ketersediaan sabun dan tisu",
            "Tidak ada kebocoran pipa",
            "Peralatan kantor berfungsi normal",
            "Tempat sampah tertutup",
        ],
    },
    {
        id: "lantai_1_area_dalam",
        title: "C. LANTAI 1 - AREA DALAM",
        items: [
            "Dinding tidak retak",
            "Lantai tidak retak",
            "Plafon tidak retak",
            "AC berfungsi normal",
            "Ventilasi / sirkulasi udara baik",
            "Tidak ada genangan air",
            "Toilet bersih dan berfungsi",
            "Ketersediaan sabun dan tisu",
            "Tidak ada kebocoran pipa",
            "Peralatan kantor berfungsi normal",
            "Tempat sampah tertutup",
        ],
    },
    {
        id: "lantai_2",
        title: "D. LANTAI 2",
        items: [
            "Dinding tidak retak",
            "Lantai tidak retak",
            "Plafon tidak retak",
            "Ventilasi / sirkulasi udara baik",
            "AC berfungsi normal",
            "Tidak ada genangan air",
            "Toilet bersih dan berfungsi",
            "Ketersediaan sabun dan tisu",
            "Tidak ada kebocoran pipa",
            "Peralatan kantor berfungsi normal",
            "Tempat sampah tertutup",
        ],
    },
    {
        id: "lantai_3",
        title: "E. LANTAI 3",
        items: [
            "Dinding tidak retak",
            "Lantai tidak retak",
            "Plafon tidak retak",
            "Ventilasi / sirkulasi udara baik",
            "Tidak ada kebocoran pipa",
            "Peralatan kantor berfungsi normal",
            "Tempat sampah tertutup",
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
    pos_security: "POS SECURITY",
    loading_dock_luar: "LOADING DOCK LUAR",
    travo_pln_depan: "TRAVO PLN DEPAN",
    tanah_kosong_dan_sekitar_bangunan: "TANAH KOSONG DAN SEKITAR BANGUNAN",
    travo_pln_belakang: "TRAVO PLN BELAKANG",
    genset: "GENSET",
    ruang_mesin_ruang_kontrol: "RUANG MESIN, RUANG KONTROL",
    sekitar_bangunan: "SEKITAR BANGUNAN",
    lobby_ruang_loker: "LOBBY, RUANG LOKER",
    loading_dock_dalam: "LOADING DOCK DALAM",
    anteroom_cold_storage: "ANTEROOM, COLD STORAGE",
    ruang_baterai: "RUANG BATERAI",
    ruang_admin: "RUANG ADMIN",
};

export const saranaPrasaranaAreaOptions = saranaPrasaranaSections.map(
    (section) => ({
        id: section.id,
        name:
            groupedChecklistAreaLabels[section.id] ||
            section.title.replace(/^[A-Z]\.\s*/, ""),
    }),
);

export const siteVisitHseSections = [
    {
        id: "lantai_1_area_luar",
        title: "A. LANTAI 1 - AREA LUAR",
        items: [
            "Area APAR tidak terhalang",
            "Area titik kumpul tidak terhalang",
            "Jalur evakuasi tidak terhalang",
            "Rambu K3 terbaca dengan jelas",
        ],
    },
    {
        id: "lantai_1_area_belakang",
        title: "B. LANTAI 1 - AREA BELAKANG",
        items: [
            "Area APAR tidak terhalang",
            "Area titik kumpul tidak terhalang",
            "Jalur evakuasi tidak terhalang",
            "Rambu K3 terbaca dengan jelas",
            "Tidak overloading (tidak bertumpuk banyak colokan)",
        ],
    },
    {
        id: "lantai_1_area_dalam",
        title: "C. LANTAI 1 - AREA DALAM",
        items: [
            "Pekerja menggunakan APD yang sesuai",
            "Pekerja menggunakan APD dengan benar",
            "APD yang digunakan dalam keadaan baik",
            "Area APAR tidak terhalang",
            "Jalur evakuasi tidak terhalang",
            "Jalur pejalan kaki terlihat jelas",
            "Jalur pejalan kaki tidak terhalang",
            "Rambu K3 terbaca dengan jelas",
            "Tidak overloading (tidak bertumpuk banyak colokan)",
            "Pintu cold storage dapat dibuka secara otomatis dengan baik",
            "Pintu cold storage dapat ditutup secara otomatis dengan baik",
            "Pintu cold storage dapat dibuka secara manual dengan baik",
            "Pintu cold storage dapat ditutup secara manual dengan baik",
            "Lampu cold storage berfungsi",
            "Lampu emergency anteroom berfungsi",
            "Lampu emergency loading dock berfungsi",
            "Sampah dibuang sesuai klasifikasi tempat sampah",
        ],
    },
    {
        id: "lantai_2",
        title: "D. LANTAI 2",
        items: [
            "Area APAR tidak terhalang",
            "Area titik kumpul tidak terhalang",
            "Jalur evakuasi tidak terhalang",
            "Rambu K3 terbaca dengan jelas",
            "Tidak overloading (tidak bertumpuk banyak colokan)",
            "Lampu emergency berfungsi",
        ],
    },
    {
        id: "lantai_3",
        title: "E. LANTAI 3",
        items: [
            "Area APAR tidak terhalang",
            "Area titik kumpul tidak terhalang",
            "Jalur evakuasi tidak terhalang",
            "Rambu K3 terbaca dengan jelas",
            "Tidak overloading (tidak bertumpuk banyak colokan)",
            "Lampu emergency berfungsi",
        ],
    },
];

export const siteVisitHseAreaOptions = siteVisitHseSections.map((section) => ({
    id: section.id,
    name:
        groupedChecklistAreaLabels[section.id] ||
        section.title.replace(/^[A-Z]\.\s*/, ""),
}));

export const patroliSecuritySections = [
    {
        id: "pos_security",
        title: "LANTAI 1 - AREA LUAR DEPAN (POS SECURITY)",
        items: [
            "Pintu / akses masuk area depan aman",
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Kendaraan keluar-masuk terkontrol",
            "Lampu penerangan berfungsi (menyala saat malam)",
            "Lampu pos security berfungsi",
            "Monitor CCTV berfungsi",
            "Server security berfungsi",
            "Karyawan dan tamu menggunakan ID Card",
            "Lampu musholla berfungsi",
            "Kipas musholla berfungsi (mati saat tidak digunakan)",
            "Kran tempat wudhu berfungsi (mati saat tidak digunakan)",
            "Tidak ada orang tidur di dalam Musholah",
            "Lampu toilet berfungsi (mati saat tidak digunakan)",
            "Kran toilet berfungsi (mati saat tidak digunakan)",
            "Jalur evakuasi tidak terhalang",
            "Area titik kumpul tidak terhalang",
            "Area APAR pos security tidak terhalang",
            "Area bebas putung rokok dan asap rokok",
            "CCTV terpasang dalam kondisi baik",
        ],
    },
    {
        id: "loading_dock_luar",
        title: "LANTAI 1 - AREA LUAR DEPAN (LOADING DOCK LUAR)",
        items: [
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Tidak ada Supir/Driver istirahat di area Loading/Kolong Kendaraan",
            "Lampu penerangan berfungsi (menyala saat malam)",
            "Area bebas putung rokok dan asap rokok",
            "CCTV terpasang dalam kondisi baik",
        ],
    },
    {
        id: "travo_pln_depan",
        title: "LANTAI 1 - AREA LUAR TIMUR (TRAVO PLN DEPAN)",
        items: [
            "Pintu travo PLN terkunci",
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Area APAR Travo PLN tidak terhalang",
            "Area bebas putung rokok dan asap rokok",
            "CCTV terpasang dalam kondisi baik",
        ],
    },
    {
        id: "tanah_kosong_dan_sekitar_bangunan",
        title: "LANTAI 1 - AREA LUAR TIMUR (TANAH KOSONG DAN SEKITAR BANGUNAN)",
        items: [
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Kran air eyewash berfungsi",
            "Tidak ada tumpukan sampah plastik",
            "Tidak ada orang duduk/tidur di bawah tangga darurat",
            "Pintu kontainer CRMI terkunci (saat malam)",
            "Jendela kontainer CRMI tertutup (saat malam)",
            "Pintu TPS tertutup, tidak ada tumpahan oli, tidak overload",
        ],
    },
    {
        id: "travo_pln_belakang",
        title: "LANTAI 1 - AREA BELAKANG (TRAVO PLN BELAKANG)",
        items: [
            "Pintu travo PLN terkunci",
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Kran air eyewash berfungsi",
            "Area APAR Travo PLN tidak terhalang",
            "Area bebas putung rokok dan asap rokok",
            "CCTV terpasang dalam kondisi baik",
        ],
    },
    {
        id: "genset",
        title: "LANTAI 1 - AREA BELAKANG (GENSET)",
        items: [
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Area bebas putung rokok dan asap rokok",
            "Tidak ada percikan / bau terbakar",
            "Tidak ada suara abnormal",
        ],
    },
    {
        id: "ruang_mesin_ruang_kontrol",
        title: "LANTAI 1 - AREA BELAKANG (RUANG MESIN, RUANG KONTROL)",
        items: [
            "Pintu / akses masuk aman",
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Lampu ruang mesin berfungsi (menyala saat malam)",
            "Lampu ruang kontrol berfungsi (menyala saat malam)",
            "Lampu toilet berfungsi (mati saat tidak digunakan)",
            "Kran air di toilet berfungsi (mati saat tidak digunakan)",
            "Tidak ada tumpahan oli",
            "Lantai tidak licin",
            "Tidak ada kebocoran mesin dan atap",
            "Tidak overloading (tidak bertumpuk banyak colokan)",
            "Tidak ada percikan / bau terbakar",
            "Area APAR tidak terhalang",
            "Area bebas putung rokok dan asap rokok",
            "CCTV terpasang dalam kondisi baik",
        ],
    },
    {
        id: "sekitar_bangunan",
        title: "LANTAI 1 - AREA LUAR BARAT (SEKITAR BANGUNAN)",
        items: [
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Parkir motor rapi",
            "Parkir mobil rapi",
            "Area titik kumpul tidak terhalang apapun",
            "Area bebas putung rokok dan asap rokok",
        ],
    },
    {
        id: "lobby_ruang_loker",
        title: "LANTAI 1 - AREA DALAM (LOBBY, RUANG LOKER)",
        items: [
            "Mesin akses pintu lobby dan arah loker berfungsi",
            "Pintu ruang loker ke loading tertutup",
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Lampu area lobby, kamar mandi tamu, loker, kamar mandi karyawan berfungsi (mati saat tidak digunakan)",
            "Ac lobby berfungsi (mati saat tidak digunakan)",
            "Semua kran air area kamar mandi dan wastafel berfungsi",
            "Area APAR tidak terhalang",
            "Area bebas putung rokok dan asap rokok",
            "CCTV terpasang dalam kondisi baik",
        ],
    },
    {
        id: "loading_dock_dalam",
        title: "LANTAI 1 - AREA DALAM (LOADING DOCK DALAM)",
        items: [
            "Pintu loading berfungsi (tertutup saat tidak digunakan)",
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Pintu R. Repack CRMI berfungsi (tertutup saat tidak digunakan)",
            "Lampu R. Repack CRMI berfungsi (mati saat tidak digunakan)",
            "Alat pest kontrol berfungsi kabel tersambung ke listrik",
            "Tidak ada percikan / bau terbakar di loading dock",
            "Tidak ada percikan / bau terbakar di R. Repack CRMI",
            "Jalur pejalan kaki tidak terhalang",
            "Jalur evakuasi tidak terhalang",
            "Area APAR tidak terhalang",
            "Area bebas putung rokok dan asap rokok",
            "CCTV terpasang dalam kondisi baik",
        ],
    },
    {
        id: "anteroom_cold_storage",
        title: "LANTAI 1 - AREA DALAM (ANTEROOM, COLD STORAGE)",
        items: [
            "Pintu berfungsi (tertutup saat tidak digunakan)",
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Lampu berfungsi (mati saat tidak digunakan)",
            "Tidak ada percikan / bau terbakar",
            "Lantai tidak licin, tidak ada kebocoran/gumpalan es",
            "Jalur pejalan kaki tidak terhalang",
            "Jalur evakuasi tidak terhalang",
            "Area APAR tidak terhalang",
            "Area bebas putung rokok dan asap rokok",
            "CCTV terpasang dalam kondisi baik",
        ],
    },
    {
        id: "ruang_baterai",
        title: "RUANG BATERAI",
        items: [
            "Pintu berfungsi (tertutup saat tidak digunakan)",
            "Lampu berfungsi (mati saat tidak digunakan)",
            "CCTV terpasang dalam kondisi baik",
            "Tidak ada kabel terkelupas/gosong",
            "Tidak ada percikan / bau terbakar",
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Karyawan dan tamu menggunakan ID Card",
            "Jalur evakuasi tidak terhalang",
            "Area APAR tidak terhalang",
            "Jalur pejalan kaki tidak terhalang",
            "Area bebas putung rokok dan asap rokok",
        ],
    },
    {
        id: "lantai_2",
        title: "LANTAI 2 Office",
        items: [
            "Pintu ruangan tertutup",
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Lampu ruangan berfungsi (mati saat tidak digunakan)",
            "Lampu kamar mandi berfungsi (mati saat tidak digunakan)",
            "Ac di ruangan berfungsi (mati saat tidak digunakan)",
            "Tidak ada bocor di bagian plafon",
            "Jalur evakuasi tidak terhalang",
            "Area APAR tidak terhalang",
            "CCTV terpasang dalam kondisi baik",
        ],
    },
    {
        id: "lantai_3",
        title: "LANTAI 3 Office",
        items: [
            "Pintu ruangan tertutup",
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Lampu ruangan berfungsi (mati saat tidak digunakan)",
            "Lampu kamar mandi berfungsi (mati saat tidak digunakan)",
            "Ac di ruangan berfungsi (mati saat tidak digunakan)",
            "Tidak ada bocor di bagian plafon",
            "Jalur evakuasi tidak terhalang",
            "Area APAR tidak terhalang",
            "CCTV terpasang dalam kondisi baik",
        ],
    },
    {
        id: "ruang_admin",
        title: "RUANG ADMIN",
        items: [
            "Pintu ruang admin berfungsi (tertutup saat tidak digunakan)",
            "Tidak ada orang mencurigakan",
            "Tidak ada barang mencurigakan",
            "Ac berfungsi (mati saat tidak digunakan)",
            "CCTV terpasang dalam kondisi baik",
        ],
    },
];

export const patroliSecurityAreaOptions = patroliSecuritySections.map(
    (section) => ({
        id: section.id,
        name:
            groupedChecklistAreaLabels[section.id] ||
            section.title.replace(/^[A-Z]\.\s*/, ""),
    }),
);

export const cleaningOBShifts = [
    {
        id: "07_00_08_30_ob_i_ii",
        title: "07:00-08:30 OB I & II",
        sections: [
            {
                id: "area_kantor_lt_2",
                title: "AREA KANTOR LT 2",
                items: [
                    "Sapu, pel lantai",
                    "Lap meja & kursi staf",
                    "Pembuangan sampah",
                    "Pembersihan kaca pintu & jendela",
                ],
            },
            {
                id: "ruang_meeting_lt_2",
                title: "RUANG MEETING LT 2",
                items: [
                    "Sapu, pel lantai",
                    "Lap meja & kursi meeting",
                    "Pembuangan sampah",
                    "Lap TV",
                    "Isi air minum tamu",
                    "Pembersihan kaca pintu & jendela",
                ],
            },
            {
                id: "ruang_direktur",
                title: "RUANG DIREKTUR",
                items: [
                    "Sapu, pel lantai",
                    "Lap meja & kursi kerja",
                    "Lap sofa",
                    "Pembuangan sampah",
                    "Siram tanaman",
                    "Pembersihan kaca pintu & jendela",
                ],
            },
            {
                id: "toilet_staf_lt_2",
                title: "TOILET STAF LT 2",
                items: [
                    "Pembersihan lantai toilet",
                    "Pembersihan closet",
                    "Pembuangan sampah",
                    "Pembersihan wastafel",
                    "Pembersihan kaca",
                    "Lap pantry + kulkas",
                    "Rutin isi galon minum",
                    "Rutin cek ketersediaan sabun dan tissu",
                ],
            },
            {
                id: "toilet_direksi_lt_2",
                title: "TOILET DIREKSI LT 2",
                items: [
                    "Pembersihan lantai toilet",
                    "Pembersihan closet",
                    "Pembuangan sampah",
                    "Rutin cek ketersediaan sabun dan tissu",
                ],
            },
        ],
    },
    {
        id: "08_30_09_30_ob_i",
        title: "08:30-09:30 OB I",
        sections: [
            {
                id: "area_tangga",
                title: "AREA TANGGA",
                items: ["Sapu, pel lantai dan railling tangga"],
            },
            {
                id: "area_lobby",
                title: "AREA LOBBY",
                items: [
                    "Sapu, pel lantai lobby sampai tangga luar",
                    "Lap meja receptionist",
                ],
            },
            {
                id: "toilet_tamu",
                title: "TOILET TAMU",
                items: [
                    "Pembersihan lantai toilet",
                    "Pembersihan closet",
                    "Pembuangan sampah",
                    "Pembersihan wastafel",
                    "Pembersihan kaca",
                    "Rutin cek ketersediaan sabun dan tissu",
                ],
            },
        ],
    },
    {
        id: "08_30_09_30_ob_ii",
        title: "08:30-09:30 OB II",
        sections: [
            {
                id: "ruang_admin",
                title: "RUANG ADMIN",
                items: [
                    "Sapu, pel lantai",
                    "Lap meja & kursi kerja",
                    "Pembuangan sampah",
                    "Rutin isi galon minum",
                ],
            },
            {
                id: "ruang_loker",
                title: "RUANG LOKER",
                items: ["Sapu, pel lantai", "Penggantian kamper", "Scrubber"],
            },
            {
                id: "toilet_karyawan_laki",
                title: "TOILET KARYAWAN LAKI",
                items: [
                    "Pembersihan lantai toilet",
                    "Pembersihan urinoir + mika",
                    "Pembuangan sampah",
                ],
            },
            {
                id: "toilet_karyawan_perempuan",
                title: "TOILET KARYAWAN PEREMPUAN",
                items: [
                    "Pembersihan lantai toilet",
                    "Pembersihan closet",
                    "Pembuangan sampah",
                ],
            },
            {
                id: "wastafel_area_toilet_karyawan",
                title: "WASTAFEL AREA TOILET KARYAWAN",
                items: [
                    "Pembersihan lantai area",
                    "Pembersihan wastafel",
                    "Pembersihan kaca",
                ],
            },
        ],
    },
    {
        id: "10_00_11_00_ob_i",
        title: "10:00-11:00 OB I",
        sections: [
            {
                id: "loading_dock",
                title: "LOADING DOCK",
                items: ["Scrubber lantai loading", "Pembuangan sampah"],
            },
        ],
    },
    {
        id: "11_00_11_30_ob_i",
        title: "11:00-11:30 OB I",
        sections: [
            {
                id: "anterum",
                title: "ANTERUM",
                items: ["Scrubber", "Sapu, pel lantai", "Pembersihan sampah"],
            },
            {
                id: "cs_1_2",
                title: "CS 1-2",
                items: ["Sapu, pel lantai", "Pembersihan sampah"],
            },
            {
                id: "cs_3_4",
                title: "CS 3-4",
                items: ["Sapu, pel lantai", "Pembersihan sampah"],
            },
            {
                id: "cs_5_6",
                title: "CS 5-6",
                items: ["Sapu, pel lantai", "Pembersihan sampah"],
            },
            {
                id: "cs_7_8",
                title: "CS 7-8",
                items: ["Sapu, pel lantai", "Pembersihan sampah"],
            },
            {
                id: "cs_9_10",
                title: "CS 9-10",
                items: ["Sapu, pel lantai", "Pembersihan sampah"],
            },
            {
                id: "cs_11",
                title: "CS 11",
                items: ["Sapu, pel lantai", "Pembersihan sampah"],
            },
        ],
    },
    {
        id: "10_00_10_30_ob_ii",
        title: "10:00-10:30 OB II",
        sections: [
            {
                id: "ruang_baterai",
                title: "RUANG BATERAI",
                items: ["Scrubber", "Sapu, pel lantai", "Pembersihan sampah"],
            },
        ],
    },
    {
        id: "11_30_12_00_ob_ii",
        title: "11:30-12:00 OB II",
        sections: [
            {
                id: "ruang_mesin",
                title: "RUANG MESIN",
                items: ["Sapu, pel lantai", "Pembersihan sampah"],
            },
            {
                id: "toilet",
                title: "TOILET",
                items: [
                    "Pembersihan lantai toilet",
                    "Pembersihan closet",
                    "Pembersihan wastafel",
                    "Pembuangan sampah",
                ],
            },
            {
                id: "ruang_kontrol",
                title: "RUANG KONTROL",
                items: [
                    "Sapu, pel lantai",
                    "Lap meja & kursi kerja",
                    "Pembuangan sampah",
                ],
            },
            {
                id: "area_genset",
                title: "AREA GENSET",
                items: [
                    "Sapu area",
                    "Pembersihan sampah",
                    "Pembersihan wastafel eyewash station",
                    "Pembersihan wastafel area tangga darurat",
                ],
            },
        ],
    },
    {
        id: "13_00_13_30_ob_i",
        title: "13:00-13:30 OB I",
        sections: [
            {
                id: "pos_satpam",
                title: "POS SATPAM",
                items: [
                    "Sapu, pel lantai",
                    "Lap meja & kursi kerja",
                    "Pembuangan sampah",
                ],
            },
        ],
    },
    {
        id: "13_30_14_00_ob_i",
        title: "13:30-14:00 OB I",
        sections: [
            {
                id: "musholah",
                title: "MUSHOLAH",
                items: [
                    "Sapu, pel lantai",
                    "Pembersihan bak pancuran wudhu",
                    "Pembersihan rubber mat",
                ],
            },
        ],
    },
    {
        id: "14_00_14_30_ob_i",
        title: "14:00-14:30 OB I",
        sections: [
            {
                id: "ruang_loker",
                title: "RUANG LOKER",
                items: ["Pembersihan & perapian area"],
            },
            {
                id: "ruang_istirahat",
                title: "RUANG ISTIRAHAT",
                items: ["Pembersihan & perapian area"],
            },
        ],
    },
    {
        id: "14_30_15_00_ob_i",
        title: "14:30-15:00 OB I",
        sections: [
            {
                id: "area_lingkungan_gudang",
                title: "AREA LINGKUNGAN GUDANG",
                items: ["Patroli kebersihan lingkungan gudang"],
            },
        ],
    },
    {
        id: "13_00_15_00_ob_ii",
        title: "13:00-15:00 OB II",
        sections: [
            {
                id: "lobby",
                title: "LOBBY",
                items: ["Pintu kaca area lobby"],
            },
            {
                id: "server",
                title: "SERVER",
                items: ["Pembersihan area server"],
            },
            {
                id: "apar_all_area_gudang",
                title: "APAR ALL AREA GUDANG",
                items: ["Pembersihan APAR ALL AREA GUDANG"],
            },
            {
                id: "kipas_angin_musholah",
                title: "KIPAS ANGIN MUSHOLAH",
                items: ["Pembersihan kipas angin Musholah"],
            },
            {
                id: "tps_b3",
                title: "TPS B3",
                items: ["Pembersihan TPS B3"],
            },
            {
                id: "lantai_area_dock_levelary",
                title: "LANTAI AREA DOCK LEVELARY",
                items: ["Pembersihan lantai area dock levelary"],
            },
        ],
    },
];

export const cleaningOBShiftOptions = cleaningOBShifts.map((shift) => ({
    id: shift.id,
    name: shift.title,
}));

export const maintenanceVisitTypeOptions = [
    {
        id: "maintenance_harian",
        name: "Maintenance Mesin (Harian)",
        title: "CHECKLIST MAINTENANCE MESIN",
        document_no: "FRM.MTC.01.01",
        schedule_label: "Tanggal",
    },
    {
        id: "maintenance_mingguan",
        name: "Maintenance Genset (Mingguan)",
        title: "CHECKLIST MAINTENANCE GENSET",
        document_no: "FRM.MTC.01.02",
        schedule_label: "Periode",
    },
];

export const maintenanceDailySections = [
    {
        id: "mesin_dan_sistem_pendingin",
        area_id: "lantai_1_area_belakang",
        title: "A. MESIN & SISTEM PENDINGIN",
        items: [
            "Suhu sesuai setting",
            "Tidak ada alarm/error",
            "Suara mesin normal (tidak berisik/aneh)",
            "Getaran normal",
            "Tekanan freon normal",
        ],
    },
    {
        id: "evaporator",
        area_id: "loading_dock",
        title: "B. EVAPORATOR",
        items: [
            "Evaporator bersih (tidak ada es)",
            "Kipas evaporator berfungsi",
            "Aliran udara lancar",
        ],
    },
    {
        id: "sistem_drainase",
        area_id: "lantai_1_area_belakang",
        title: "C. SISTEM DRAINASE",
        items: [
            "Saluran drain tidak tersumbat",
            "Tidak ada genangan air/es",
            "Pipa drain dalam kondisi baik",
        ],
    },
    {
        id: "kelistrikan",
        area_id: "lantai_1_area_belakang",
        title: "D. KELISTRIKAN",
        items: [
            "Panel listrik normal (tidak panas)",
            "Kabel tidak rusak",
            "MCB/proteksi berfungsi",
            "Tidak ada bau terbakar",
        ],
    },
    {
        id: "pintu_dan_seal",
        area_id: "loading_dock",
        title: "F. PINTU & SEAL",
        items: [
            "Pintu menutup rapat",
            "Karet seal tidak rusak",
            "Tidak ada kebocoran udara dingin",
            "Alarm pintu berfungsi",
        ],
    },
    {
        id: "kondensor",
        area_id: "lantai_1_area_belakang",
        title: "G. KONDENSOR",
        items: [
            "Fan berfungsi normal / tidak",
            "Pompa berfungsi normal / tidak",
            "Sirkulasi air lancar / tidak",
        ],
    },
];

export const maintenanceWeeklyItems = [
    "Oli Mesin",
    "Filter Oli",
    "Filter Solar",
    "Air Radiator",
    "Connector Battery",
    "Ampere Battery",
    "Ketersediaan Solar",
];

export const gensetRunningSections = [
    {
        id: "visual",
        title: "A. VISUAL",
        items: [
            { id: "bersih", name: "BERSIH" },
            { id: "kotor", name: "KOTOR" },
        ],
    },
    {
        id: "pengecekan",
        title: "B. PENGECEKAN",
        items: [
            { id: "baterai", name: "BATERAI" },
            { id: "oli", name: "OLI" },
            { id: "cooling_water", name: "COOLING WATER" },
            { id: "kecukupan_solar", name: "KECUKUPAN SOLAR" },
        ],
    },
    {
        id: "perlakuan",
        title: "C. PERLAKUAN",
        items: [
            { id: "tambah_oli", name: "TAMBAH OLI" },
            { id: "ganti_oli", name: "GANTI OLI" },
            { id: "ganti_baterai", name: "GANTI BATERAI" },
            { id: "tambah_solar", name: "TAMBAH SOLAR" },
        ],
    },
    {
        id: "kinerja_alat",
        title: "D. KINERJA ALAT",
        items: [{ id: "running", name: "RUNNING" }],
    },
];

export const runningGensetSections = [
    {
        id: "visual",
        title: "B. VISUAL",
        items: [
            { id: "bersih", name: "BERSIH" },
            { id: "kotor", name: "KOTOR" },
        ],
    },
    {
        id: "pengecekan",
        title: "C. PENGECEKAN",
        items: [
            { id: "level_oli", name: "LEVEL OLI" },
            { id: "air_radiator", name: "AIR RADIATOR" },
            { id: "stock_solar", name: "STOCK SOLAR" },
        ],
    },
    {
        id: "perlakuan",
        title: "D. PERLAKUAN",
        items: [
            { id: "tambah_oli", name: "TAMBAH OLI" },
            { id: "tambah_solar", name: "TAMBAH SOLAR" },
        ],
    },
];

export const maintenanceDailyAreaOptions = [
    {
        id: "lantai_1_area_belakang",
        name: groupedChecklistAreaLabels.lantai_1_area_belakang,
    },
    {
        id: "loading_dock",
        name: groupedChecklistAreaLabels.loading_dock,
    },
];

export const kotakP3KItems = [
    { id: "kondisi_kotak_p3k", name: "Kondisi kotak P3K", quantity: "" },
    {
        id: "kasa_steril_terbungkus",
        name: "Kasa steril terbungkus",
        quantity: 20,
    },
    { id: "perban_5_cm", name: "Perban (lebar 5 cm)", quantity: 2 },
    { id: "perban_10_cm", name: "Perban (lebar 10 cm)", quantity: 2 },
    { id: "plester_1_25_cm", name: "Plester (lebar 1,25 cm)", quantity: 2 },
    { id: "plester_cepat", name: "Plester Cepat", quantity: 10 },
    { id: "kapas_25_gram", name: "Kapas (25 gram)", quantity: 1 },
    { id: "kain_segitiga", name: "Kain segitiga/mitela", quantity: 2 },
    { id: "gunting", name: "Gunting", quantity: 1 },
    { id: "peniti", name: "Peniti", quantity: 12 },
    { id: "sarung_tangan", name: "Sarung tangan sekali pakai", quantity: 2 },
    { id: "masker", name: "Masker", quantity: 2 },
    { id: "pinset", name: "Pinset", quantity: 1 },
    { id: "lampu_senter", name: "Lampu senter", quantity: 1 },
    { id: "gelas_cuci_mata", name: "Gelas untuk cuci mata", quantity: 1 },
    {
        id: "kantong_plastik_bersih",
        name: "Kantong plastik bersih",
        quantity: 1,
    },
    { id: "aquades_saline", name: "Aquades (100 ml lar. Saline)", quantity: 1 },
    { id: "povidon_iodin", name: "Povidon Iodin (60 ml)", quantity: 1 },
    { id: "alkohol_70", name: "Alkohol 70%", quantity: 1 },
    {
        id: "buku_panduan_p3k",
        name: "Buku panduan P3K di tempat kerja",
        quantity: 1,
    },
    { id: "catatan_logbook", name: "Catatan / logbook", quantity: 1 },
    { id: "daftar_isi_kotak", name: "Daftar isi kotak", quantity: 1 },
];

export const kotakP3KMonths = [
    { key: "jan", label: "Jan", number: 1 },
    { key: "feb", label: "Feb", number: 2 },
    { key: "mar", label: "Mar", number: 3 },
    { key: "apr", label: "Apr", number: 4 },
    { key: "may", label: "May", number: 5 },
    { key: "jun", label: "Jun", number: 6 },
    { key: "jul", label: "Jul", number: 7 },
    { key: "aug", label: "Aug", number: 8 },
    { key: "sep", label: "Sep", number: 9 },
    { key: "oct", label: "Oct", number: 10 },
    { key: "nov", label: "Nov", number: 11 },
    { key: "dec", label: "Dec", number: 12 },
];

function createFireSafetyMonthValue(initialValue = "") {
    return kotakP3KMonths.reduce((result, month) => {
        result[month.key] = initialValue;
        return result;
    }, {});
}

function createFireSafetyRows(cardType = "fire_alarm") {
    const items =
        fireSafetyItemsByType[cardType] || fireSafetyItemsByType.fire_alarm;

    return items.map((item, index) => ({
        no: index + 1,
        id: item.id,
        name: item.name,
        months: createFireSafetyMonthValue(""),
    }));
}

export function rebuildFireSafetyRows(
    cardType = "fire_alarm",
    existingRows = [],
) {
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

export function getFireSafetyRecordKey(cardType = "", locationId = "") {
    return `${String(cardType || "").trim()}::${String(locationId || "").trim()}`;
}

export function createFireSafetyLocationState(
    cardType = "fire_alarm",
    existingState = {},
) {
    return {
        approved_months: Array.isArray(existingState?.approved_months)
            ? [...existingState.approved_months]
            : [],
        monthly_notes: {
            ...createFireSafetyMonthValue(""),
            ...(existingState?.monthly_notes || {}),
        },
        monthly_barcodes: {
            ...createFireSafetyMonthValue(""),
            ...(existingState?.monthly_barcodes || {}),
        },
        monthly_check_dates: {
            ...createFireSafetyMonthValue(""),
            ...(existingState?.monthly_check_dates || {}),
        },
        rows: rebuildFireSafetyRows(cardType, existingState?.rows || []),
    };
}

export function getFireSafetyCardLabel(cardType) {
    return (
        fireSafetyCardOptions.find((option) => option.id === cardType)?.name ||
        "-"
    );
}

export function getFireSafetyCardTitle(cardType) {
    return (
        fireSafetyCardOptions.find((option) => option.id === cardType)?.title ||
        "KARTU PEMELIHARAAN"
    );
}

export function getFireSafetyLocationOptions(cardType) {
    return fireSafetyLocationOptionsByType[cardType] || [];
}

export function getFireSafetyLocationLabel(cardType, locationId) {
    return (
        getFireSafetyLocationOptions(cardType).find(
            (location) => location.id === locationId,
        )?.name || "-"
    );
}

export function createFireSafetyEntry(userName) {
    const now = new Date();
    const cardType = "fire_alarm";
    const defaultLocation = getFireSafetyLocationOptions(cardType)[0]?.id || "";
    const activeMonth = getCurrentKotakP3KMonthKey(now);

    return {
        id: `apar_smoke_detector_fire_alarm-${Date.now()}`,
        template_id: "apar_smoke_detector_fire_alarm",
        name: "APAR, Smoke Detector, Fire Alarm",
        created_at: formatDateTimeDisplay(now),
        form: {
            card_type: cardType,
            location: defaultLocation,
            year: String(now.getFullYear()),
            active_month: activeMonth,
            approved: false,
            approved_months: [],
            monthly_notes: createFireSafetyMonthValue(""),
            monthly_barcodes: createFireSafetyMonthValue(""),
            monthly_check_dates: createFireSafetyMonthValue(""),
            rows: createFireSafetyRows(cardType),
            location_records: {
                [getFireSafetyRecordKey(cardType, defaultLocation)]:
                    createFireSafetyLocationState(cardType),
            },
            pic: userName || "User Login",
        },
    };
}

function createWarehouseStatusMap() {
    return {
        clean_condition: "",
        no_ice_pooling: "",
        no_odor: "",
        note: "",
    };
}

function createWarehouseBooleanMap() {
    return {
        halal: "",
        dosage: "",
        note: "",
        material_name: "",
    };
}

function getWarehouseCleanlinessRows(frequency = "daily") {
    return (
        warehouseCleanlinessRowsByFrequency[frequency] ||
        warehouseCleanlinessRowsByFrequency.daily
    );
}

export function buildWarehouseAreaRows(frequency = "daily", existingRows = []) {
    return getWarehouseCleanlinessRows(frequency).map((row, index) => {
        const matchedRow = existingRows.find((item) => item.id === row.id);

        return {
            no: index + 1,
            id: row.id,
            name: row.name,
            clean_condition: matchedRow?.clean_condition || "",
            no_ice_pooling: matchedRow?.no_ice_pooling || "",
            no_odor: matchedRow?.no_odor || "",
            note: matchedRow?.note || "",
        };
    });
}

function createPersonalHygieneDayMap(periodValue) {
    return getDaysInPeriod(periodValue).reduce((result, dayInfo) => {
        result[dayInfo.day] = "";
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
        result[dayInfo.day] = "";
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
            status: "",
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
            status: "",
        })),
    }));
}

function createMaintenanceRow(id, name, no, locationLabel = "") {
    return {
        id,
        no,
        name,
        status: "",
        note: "",
        scan_location: locationLabel,
    };
}

function createMaintenanceDailySections() {
    return maintenanceDailySections.map((section) => ({
        id: section.id,
        area_id: section.area_id,
        title: section.title,
        items: section.items.map((itemName, index) =>
            createMaintenanceRow(
                `${section.id}-${index + 1}`,
                itemName,
                index + 1,
            ),
        ),
    }));
}

function createMaintenanceWeeklyRows() {
    return maintenanceWeeklyItems.map((itemName, index) =>
        createMaintenanceRow(
            `genset-${index + 1}`,
            itemName,
            index + 1,
            groupedChecklistAreaLabels.lantai_1_area_belakang,
        ),
    );
}

function createGensetRunningRow(id, name, no, sectionId, sectionTitle) {
    return {
        id,
        no,
        name,
        section_id: sectionId,
        section_title: sectionTitle,
        status: "",
        scan_location: groupedChecklistAreaLabels.genset,
    };
}

function createGensetRunningRows() {
    return gensetRunningSections.flatMap((section) =>
        section.items.map((item, index) =>
            createGensetRunningRow(
                item.id,
                item.name,
                index + 1,
                section.id,
                section.title,
            ),
        ),
    );
}

export function rebuildGensetRunningRows(existingRows = []) {
    return createGensetRunningRows().map((row) => {
        const matchedRow = existingRows.find((item) => item.id === row.id);

        return {
            ...row,
            status: matchedRow?.status || "",
        };
    });
}

function createRunningGensetRow(id, name, no, sectionId, sectionTitle) {
    return {
        id,
        no,
        name,
        section_id: sectionId,
        section_title: sectionTitle,
        status: "",
        scan_location: groupedChecklistAreaLabels.genset,
    };
}

function createRunningGensetRows() {
    return runningGensetSections.flatMap((section) =>
        section.items.map((item, index) =>
            createRunningGensetRow(
                item.id,
                item.name,
                index + 1,
                section.id,
                section.title,
            ),
        ),
    );
}

export function rebuildRunningGensetRows(existingRows = []) {
    return createRunningGensetRows().map((row) => {
        const matchedRow = existingRows.find((item) => item.id === row.id);

        return {
            ...row,
            status: matchedRow?.status || "",
        };
    });
}

function createKompresorDailyRows(periodValue) {
    return getDaysInPeriod(periodValue).map((dayInfo) => ({
        day: dayInfo.day,
        date: dayInfo.date,
        status_mesin: "",
        visual_bersih: "",
        visual_kotor: "",
        tek_suct: "",
        tek_disch: "",
        delta_tekanan_oli: "",
        check_1: "",
        check_2: "",
        check_3: "",
        check_4: "",
        tambah_grease_motor: "",
        tambah_oli: "",
        hours_meter: "",
    }));
}

export function rebuildKompresorDailyRows(periodValue, existingRows = []) {
    return createKompresorDailyRows(periodValue).map((row) => {
        const matchedRow = existingRows.find(
            (item) => Number(item.day) === Number(row.day),
        );

        return {
            ...row,
            status_mesin: matchedRow?.status_mesin || "",
            visual_bersih: matchedRow?.visual_bersih || "",
            visual_kotor: matchedRow?.visual_kotor || "",
            tek_suct: matchedRow?.tek_suct || "",
            tek_disch: matchedRow?.tek_disch || "",
            delta_tekanan_oli: matchedRow?.delta_tekanan_oli || "",
            check_1: matchedRow?.check_1 || "",
            check_2: matchedRow?.check_2 || "",
            check_3: matchedRow?.check_3 || "",
            check_4: matchedRow?.check_4 || "",
            tambah_grease_motor: matchedRow?.tambah_grease_motor || "",
            tambah_oli: matchedRow?.tambah_oli || "",
            hours_meter: matchedRow?.hours_meter || "",
        };
    });
}

function createChargerBateraiRows(periodValue) {
    return getDaysInPeriod(periodValue).map((dayInfo) => ({
        day: dayInfo.day,
        date: dayInfo.date,
        switch_on_off: "",
        kondisi_fisik: "",
        kabel_konektor: "",
        legrand: "",
        display_charger: "",
        temuan: "",
        tindakan: "",
    }));
}

export function rebuildChargerBateraiRows(periodValue, existingRows = []) {
    return createChargerBateraiRows(periodValue).map((row) => {
        const matchedRow = existingRows.find(
            (item) => Number(item.day) === Number(row.day),
        );

        return {
            ...row,
            switch_on_off: matchedRow?.switch_on_off || "",
            kondisi_fisik: matchedRow?.kondisi_fisik || "",
            kabel_konektor: matchedRow?.kabel_konektor || "",
            legrand: matchedRow?.legrand || "",
            display_charger: matchedRow?.display_charger || "",
            temuan: matchedRow?.temuan || "",
            tindakan: matchedRow?.tindakan || "",
        };
    });
}

function createChecklistBateraiRows(periodValue) {
    return getDaysInPeriod(periodValue).map((dayInfo) => ({
        day: dayInfo.day,
        date: dayInfo.date,
        level_elektrolit: "",
        kabel_konektor: "",
        cover_pelampung: "",
        kebersihan_baterai: "",
        voltage_dc: "",
    }));
}

export function rebuildChecklistBateraiRows(periodValue, existingRows = []) {
    return createChecklistBateraiRows(periodValue).map((row) => {
        const matchedRow = existingRows.find(
            (item) => Number(item.day) === Number(row.day),
        );

        return {
            ...row,
            level_elektrolit: matchedRow?.level_elektrolit || "",
            kabel_konektor: matchedRow?.kabel_konektor || "",
            cover_pelampung: matchedRow?.cover_pelampung || "",
            kebersihan_baterai: matchedRow?.kebersihan_baterai || "",
            voltage_dc: matchedRow?.voltage_dc || "",
        };
    });
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

export function rebuildSaranaPrasaranaSections(
    periodValue,
    existingSections = [],
) {
    return createSaranaPrasaranaSections(periodValue).map((section) => {
        const matchedSection = existingSections.find(
            (item) => item.id === section.id,
        );

        return {
            ...section,
            items: section.items.map((item) => {
                const matchedItem = matchedSection?.items?.find(
                    (sectionItem) => sectionItem.id === item.id,
                );
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
        const matchedSection = existingSections.find(
            (item) => item.id === section.id,
        );

        return {
            ...section,
            items: section.items.map((item) => {
                const matchedItem = matchedSection?.items?.find(
                    (sectionItem) => sectionItem.id === item.id,
                );
                return {
                    ...item,
                    status: matchedItem?.status || "",
                };
            }),
        };
    });
}

export function rebuildPatroliSecuritySections(existingSections = []) {
    return createPatroliSecuritySections().map((section) => {
        const matchedSection = existingSections.find(
            (item) => item.id === section.id,
        );

        return {
            ...section,
            items: section.items.map((item) => {
                const matchedItem = matchedSection?.items?.find(
                    (sectionItem) => sectionItem.id === item.id,
                );
                return {
                    ...item,
                    status: matchedItem?.status || "",
                };
            }),
        };
    });
}

export function rebuildMaintenanceDailySections(existingSections = []) {
    return createMaintenanceDailySections().map((section) => {
        const matchedSection = existingSections.find(
            (item) => item.id === section.id,
        );

        return {
            ...section,
            area_id: matchedSection?.area_id || section.area_id,
            items: section.items.map((item) => {
                const matchedItem = matchedSection?.items?.find(
                    (sectionItem) => sectionItem.id === item.id,
                );

                return {
                    ...item,
                    status: matchedItem?.status || "",
                    note: matchedItem?.note || "",
                    scan_location: matchedItem?.scan_location || "",
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
            status: matchedRow?.status || "",
            note: matchedRow?.note || "",
            scan_location:
                matchedRow?.scan_location ||
                row.scan_location ||
                groupedChecklistAreaLabels.lantai_1_area_belakang,
        };
    });
}

export function getMaintenanceVisitTypeMeta(visitType) {
    return (
        maintenanceVisitTypeOptions.find((option) => option.id === visitType) ||
        maintenanceVisitTypeOptions[0]
    );
}

export function getMaintenanceVisitTypeLabel(visitType) {
    return getMaintenanceVisitTypeMeta(visitType)?.name || "-";
}

export function getMaintenanceDailyAreaLabel(areaId) {
    return (
        maintenanceDailyAreaOptions.find((area) => area.id === areaId)?.name ||
        "-"
    );
}

export function getSaranaPrasaranaAreaLabel(areaId) {
    return (
        saranaPrasaranaAreaOptions.find((area) => area.id === areaId)?.name ||
        "-"
    );
}

export function getSiteVisitHseAreaLabel(areaId) {
    return (
        siteVisitHseAreaOptions.find((area) => area.id === areaId)?.name || "-"
    );
}

export function getPatroliSecurityAreaLabel(areaId) {
    return (
        patroliSecurityAreaOptions.find((area) => area.id === areaId)?.name ||
        "-"
    );
}

export function getPatroliSecurityBarcodeAliases(areaId) {
    const aliases = {
        pos_security: "Lantai 1 - Depan dan Luar",
        loading_dock_luar: "Lantai 1 - Area Dalam (Ateroom, Loading Dock)",
        travo_pln_depan: "Lantai 1 - Area Depan (Travo Pln Depan)",
        tanah_kosong_dan_sekitar_bangunan: "Lantai 1 Area Luar Timur",
        travo_pln_belakang: "Lantai 1 - Area Belakang (Travo Pln Belakang)",
        genset: "Lantai 1 - Area Belakang (Genset)",
        ruang_mesin_ruang_kontrol:
            "Lantai 1 - Area Belakang (R. Mesin, R. Kontrol)",
        sekitar_bangunan: "Lantai 1 Area Luar Barat",
        loading_dock_dalam: "Lantai 1 - Area Dalam (Loading Dock Dalam)",
        anteroom_cold_storage: "Lantai 1 - Area Dalam (Anteroom, Cold Storage)",
        lobby_ruang_loker: "Lantai 1 - Area Dalam (Lobby, Ruang Loker)",
        ruang_baterai: "Lantai 1 - Ruang Baterai",
        lantai_2: "Lantai 2",
        lantai_3: "Lantai 3",
        ruang_admin: "Ruang Admin",
    };
    const fullTitle =
        aliases[areaId] ||
        patroliSecuritySections.find((section) => section.id === areaId)
            ?.title ||
        "";
    const shortLabel = getPatroliSecurityAreaLabel(areaId);

    return [
        ...new Set(
            [fullTitle, shortLabel].filter(
                (value) => String(value || "").trim() !== "",
            ),
        ),
    ];
}

export function createWarehouseSanitationEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);
    const frequency = "daily";

    return {
        id: `warehouse_sanitation-${Date.now()}`,
        template_id: "warehouse_sanitation_1",
        name: "Kebersihan dan Sanitasi (Warehouse Area)",
        created_at: formatDateTimeDisplay(now),
        form: {
            frequency,
            date: formatDateDisplay(now),
            period,
            barcode: "",
            scan_date: "",
            room_temperature: "",
            petugas: userName || "User Login",
            hse: "",
            selected_areas: [],
            approved: false,
            area_rows: buildWarehouseAreaRows(frequency),
            ice_control_rows: warehouseIceControlRows.map((row, index) => ({
                no: index + 1,
                id: row.id,
                name: row.name,
                status: "",
                note: "",
            })),
            cleaning_material_rows: warehouseCleaningMaterialRows.map(
                (row) => ({
                    no: row.no,
                    id: row.id,
                    ...createWarehouseBooleanMap(),
                }),
            ),
            verification: {
                prepared_name: "",
                prepared_signature: "",
                prepared_date: "",
                verified_name: "",
                verified_signature: "",
                verified_date: "",
            },
        },
    };
}

export function createPersonalHygieneEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);

    return {
        id: `personal_hygiene_karyawan-${Date.now()}`,
        template_id: "personal_hygiene_karyawan",
        name: "Personal Hygiene Karyawan",
        created_at: formatDateTimeDisplay(now),
        form: {
            year: String(now.getFullYear()),
            period,
            employee_name: "",
            gender: "",
            nik: "",
            bagian: "",
            approved: false,
            generated_at: "",
            rows: createPersonalHygieneRows(period),
            generated_employees: [],
        },
    };
}

export function createSaranaPrasaranaEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);
    const defaultArea = saranaPrasaranaSections[0]?.id || "";

    return {
        id: `sarana_dan_prasarana-${Date.now()}`,
        template_id: "sarana_dan_prasarana",
        name: "Sarana dan Prasarana",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            selected_area: defaultArea,
            pic: userName || "User Login",
            approved: false,
            approved_days_by_area: saranaPrasaranaSections.reduce(
                (result, section) => {
                    result[section.id] = [];
                    return result;
                },
                {},
            ),
            area_scans_by_day: {},
            document_no: "FRM.HRGA.01.06",
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1 dari 1",
            sections: createSaranaPrasaranaSections(period),
        },
    };
}

export function createSiteVisitHseEntry(userName) {
    const now = new Date();
    const defaultArea = siteVisitHseSections[0]?.id || "";

    return {
        id: `site_visit_hse-${Date.now()}`,
        template_id: "site_visit_hse",
        name: "Site Visit HSE",
        created_at: formatDateTimeDisplay(now),
        form: {
            date_value: toDateInputValue(now),
            date: formatDateDisplay(now),
            selected_area: defaultArea,
            pic: userName || "User Login",
            approved: false,
            approved_areas: [],
            area_barcodes: {},
            area_notes: {},
            area_scan_dates: {},
            document_no: "FRM.HSE.15.01",
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1 dari 1",
            sections: createSiteVisitHseSections(),
        },
    };
}

export function createPatroliSecurityEntry(userName) {
    const now = new Date();
    const defaultArea = patroliSecuritySections[0]?.id || "";

    return {
        id: `patroli_security-${Date.now()}`,
        template_id: "patroli_security",
        name: "Patroli Security",
        created_at: formatDateTimeDisplay(now),
        form: {
            date_value: toDateInputValue(now),
            date: formatDateDisplay(now),
            selected_area: defaultArea,
            pic: userName || "User Login",
            approved: false,
            approved_areas: [],
            area_barcodes: {},
            area_notes: {},
            area_photo_paths: {},
            area_photo_urls: {},
            area_photo_names: {},
            area_scan_dates: {},
            document_no: "FRM.HSE.15.02",
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1 dari 1",
            sections: createPatroliSecuritySections(),
        },
    };
}

function createCleaningOBSections() {
    return cleaningOBShifts.map((shift) => ({
        id: shift.id,
        title: shift.title,
        sections: shift.sections.map((section) => ({
            id: section.id,
            title: section.title,
            items: section.items.map((itemName, index) => ({
                no: index + 1,
                id: `${section.id}-${index + 1}`,
                name: itemName,
                status: "",
            })),
        })),
    }));
}

export function rebuildCleaningOBSections(existingSections = []) {
    return createCleaningOBSections().map((shift) => {
        const matchedShift = existingSections.find(
            (item) => item.id === shift.id,
        );
        return {
            ...shift,
            sections: shift.sections.map((section) => {
                const matchedSection = matchedShift?.sections?.find(
                    (s) => s.id === section.id,
                );
                return {
                    ...section,
                    items: section.items.map((item) => {
                        const matchedItem = matchedSection?.items?.find(
                            (i) => i.id === item.id,
                        );
                        return {
                            ...item,
                            status: matchedItem?.status || "",
                        };
                    }),
                };
            }),
        };
    });
}

export function getCleaningOBShiftLabel(shiftId) {
    return (
        cleaningOBShiftOptions.find((shift) => shift.id === shiftId)?.name ||
        "-"
    );
}

export function createCleaningOBEntry(userName) {
    const now = new Date();
    const defaultShift = cleaningOBShiftOptions[0]?.id || "";

    return {
        id: `jadwal_cleaning_ob-${Date.now()}`,
        template_id: "jadwal_cleaning_ob",
        name: "Jadwal Cleaning OB",
        created_at: formatDateTimeDisplay(now),
        form: {
            date_value: toDateInputValue(now),
            date: formatDateDisplay(now),
            selected_shift: defaultShift,
            pic: userName || "User Login",
            approved: false,
            approved_areas: [],
            area_barcodes: {},
            area_notes: {},
            area_photo_paths: {},
            area_photo_urls: {},
            area_photo_names: {},
            area_scan_dates: {},
            document_no: "FRM.HSE.15.02",
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1 dari 1",
            sections: createCleaningOBSections(),
        },
    };
}

export function createSiteVisitMaintenanceEntry(userName) {
    const now = new Date();
    const visitType =
        maintenanceVisitTypeOptions[0]?.id || "maintenance_harian";
    const typeMeta = getMaintenanceVisitTypeMeta(visitType);
    const dateValue = toDateInputValue(now);
    const periodValue = toWeekInputValue(now);

    return {
        id: `site_visit_maintenance-${Date.now()}`,
        template_id: "site_visit_maintenance",
        name: "Site Visit Maintenance",
        created_at: formatDateTimeDisplay(now),
        form: {
            visit_type: visitType,
            pic: userName || "User Login",
            approved: false,
            document_no: typeMeta.document_no,
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1 dari 1",
            selected_area: "lantai_1_area_belakang",
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

export function createGensetRunningEntry(userName) {
    const now = new Date();
    const periodValue = toWeekInputValue(now);

    return {
        id: `genset_running-${Date.now()}`,
        template_id: "genset_running",
        name: "Pemanasan (Running) Genset",
        created_at: formatDateTimeDisplay(now),
        form: {
            period_value: periodValue,
            period: formatWeekDisplay(periodValue),
            selected_area: "genset",
            pic: userName || "User Login",
            date: formatDateDisplay(now),
            document_no: "DF-GMI-MTC-04",
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1",
            approved: false,
            area_barcodes: {},
            area_scan_dates: {},
            area_notes: {
                genset: "",
            },
            rows: createGensetRunningRows(),
        },
    };
}

export function createRunningGensetEntry(userName) {
    const now = new Date();
    const dateValue = toDateInputValue(now);

    return {
        id: `running_genset-${Date.now()}`,
        template_id: "running_genset",
        name: "Running Genset",
        created_at: formatDateTimeDisplay(now),
        form: {
            selected_area: "genset",
            pic: userName || "User Login",
            approved: false,
            document_no: "FRM.MTC.01.03",
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1 dari 1",
            date_value: dateValue,
            date: formatDateDisplay(now),
            hour_meter: "",
            area_barcodes: {},
            area_scan_dates: {},
            area_notes: {
                genset: "",
            },
            rows: createRunningGensetRows(),
        },
    };
}

export function createKompresorHarianEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);

    return {
        id: `kompresor_harian-${Date.now()}`,
        template_id: "kompresor_harian",
        name: "Kompresor",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            date_value: toDateInputValue(now),
            year: String(now.getFullYear()),
            active_day: now.getDate(),
            compressor_no: "",
            location: "GOLDEN MULTI INDOTAMA",
            pic: userName || "User Login",
            document_no: "DF-GMI-MTC-06",
            approved: false,
            approved_days: [],
            note: "",
            check_headers: {
                check_1: "TEMP SUCT (deg C)",
                check_2: "TEMP DISCH (deg C)",
                check_3: "TEMP OLI (deg C)",
                check_4: "LEVE OLI (%)",
            },
            rows: createKompresorDailyRows(period),
        },
    };
}

export function createChargerBateraiEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);

    return {
        id: `charger_baterai-${Date.now()}`,
        template_id: "charger_baterai",
        name: "Charger Baterai",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            date_value: toDateInputValue(now),
            year: String(now.getFullYear()),
            active_day: now.getDate(),
            serial_no: "",
            pic: userName || "User Login",
            document_no: "DF-GMI-MTC-08",
            approved: false,
            approved_days: [],
            note: "",
            rows: createChargerBateraiRows(period),
        },
    };
}

export function createChecklistBateraiEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);

    return {
        id: `checklist_baterai-${Date.now()}`,
        template_id: "checklist_baterai",
        name: "Checklist Baterai",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            date_value: toDateInputValue(now),
            year: String(now.getFullYear()),
            active_day: now.getDate(),
            battery_no: "",
            pic: userName || "User Login",
            document_no: "DF-GMI-MTC-09",
            approved: false,
            approved_days: [],
            note: "",
            rows: createChecklistBateraiRows(period),
        },
    };
}

export function createWasteTransportEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);

    return {
        id: `pengangkutan_sampah_pt_sier-${Date.now()}`,
        template_id: "pengangkutan_sampah_pt_sier",
        name: "Pengangkutan Sampah PT SIER",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            date: formatDateDisplay(now),
            pic: userName || "User Login",
            approved: false,
            approved_days: [],
            rows: createWasteTransportRows(period),
        },
    };
}

function createWasteTransportRows(periodValue) {
    return getDaysInPeriod(periodValue).map((dayInfo) => ({
        day: dayInfo.day,
        handover_name: "",
        pickup_time: "",
        collector_name: "",
        collector_photo_name: "",
        collector_photo_preview: "",
    }));
}

export function rebuildWasteTransportRows(periodValue, existingRows = []) {
    return getDaysInPeriod(periodValue).map((dayInfo) => {
        const matchedRow = existingRows.find(
            (row) => Number(row.day) === dayInfo.day,
        );

        return {
            day: dayInfo.day,
            handover_name: matchedRow?.handover_name || "",
            pickup_time: matchedRow?.pickup_time || "",
            collector_name: matchedRow?.collector_name || "",
            collector_photo_name: matchedRow?.collector_photo_name || "",
            collector_photo_preview: matchedRow?.collector_photo_preview || "",
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
            if (
                Object.prototype.hasOwnProperty.call(matchedRow.days || {}, day)
            ) {
                const value = matchedRow.days[day];
                nextDays[day] = value === true ? "yes" : value || "";
            }
        });

        return {
            ...row,
            days: nextDays,
        };
    });
}

export function formatDateDisplay(date = new Date()) {
    return new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(date);
}

export function formatDateInputDisplay(dateValue) {
    if (!String(dateValue || "").trim()) {
        return "-";
    }

    const [year, month, day] = String(dateValue).split("-").map(Number);
    if (!year || !month || !day) {
        return "-";
    }

    return formatDateDisplay(new Date(year, month - 1, day));
}

export function formatShortDateDisplay(date = new Date()) {
    return new Intl.DateTimeFormat("en-GB", {
        day: "2-digit",
        month: "short",
    }).format(date);
}

export function formatDayMonthDisplay(date = new Date()) {
    return `${date.getDate()}/${date.getMonth() + 1}`;
}

export function formatDateTimeDisplay(date = new Date()) {
    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
}

export function formatMonthYearDisplay(periodValue) {
    const [year, month] = String(periodValue || "").split("-");
    if (!year || !month) {
        return "-";
    }

    const date = new Date(Number(year), Number(month) - 1, 1);
    return new Intl.DateTimeFormat("id-ID", {
        month: "long",
        year: "numeric",
    }).format(date);
}

export function toPeriodValue(date = new Date()) {
    const month = `${date.getMonth() + 1}`.padStart(2, "0");
    return `${date.getFullYear()}-${month}`;
}

export function toDateInputValue(date = new Date()) {
    const month = `${date.getMonth() + 1}`.padStart(2, "0");
    const day = `${date.getDate()}`.padStart(2, "0");
    return `${date.getFullYear()}-${month}-${day}`;
}

function getIsoWeekNumber(date) {
    const target = new Date(
        Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()),
    );
    const dayNumber = target.getUTCDay() || 7;
    target.setUTCDate(target.getUTCDate() + 4 - dayNumber);
    const yearStart = new Date(Date.UTC(target.getUTCFullYear(), 0, 1));
    return Math.ceil(((target - yearStart) / 86400000 + 1) / 7);
}

export function toWeekInputValue(date = new Date()) {
    const year = date.getFullYear();
    const week = `${getIsoWeekNumber(date)}`.padStart(2, "0");
    return `${year}-W${week}`;
}

export function formatWeekDisplay(weekValue) {
    const match = String(weekValue || "").match(/^(\d{4})-W(\d{2})$/);
    if (!match) {
        return "-";
    }

    const [, year, week] = match;
    return `Minggu ${Number(week)}, ${year}`;
}

export function getCurrentKotakP3KMonthKey(date = new Date()) {
    return kotakP3KMonths[date.getMonth()]?.key || "jan";
}

export function getKotakP3KMonthLabel(monthKey) {
    return (
        kotakP3KMonths.find((month) => month.key === monthKey)?.label ||
        monthKey
    );
}

function createKotakP3KMonthValue(initialValue = "") {
    return kotakP3KMonths.reduce((result, month) => {
        result[month.key] = initialValue;
        return result;
    }, {});
}

export function getLocationLabel(locationId) {
    return (
        locationOptions.find((location) => location.id === locationId)?.name ||
        "-"
    );
}

export function getLocationBarcodeAliases(locationId) {
    const aliases = {
        ruang_admin: ["Lantai 1 Belakang", "Ruang Admin"],
        ruang_kontrol: ["Lantai 1 Belakang", "Ruang Kontrol"],
        pos_security: ["Lantai 1 Depan Dan Luar", "Pos Security"],
        lobby_lantai_1: ["Lantai 1 Dalam", "Lobby Lantai 1"],
        area_office_lantai_2: ["Lantai 2 Office", "Area Office Lantai 2"],
        area_ruang_mesin: ["Lantai 1 Belakang", "Area Ruang Mesin"],
        area_lantai_3: ["Lantai 3 Office", "Area Lantai 3"],
        lantai_2: ["Lantai 2 Office", "Lantai 2"],
    };

    return aliases[locationId] || [getLocationLabel(locationId)];
}

export function getSanitationAreaLabel(areaId) {
    if (areaId === "area_luar_bangunan") {
        return "Lantai 1 Depan";
    }

    return (
        sanitationAreaOptions.find((area) => area.id === areaId)?.name || "-"
    );
}

export function getSanitationAreaBarcodeAliases(areaId) {
    const aliases = {
        lantai_1: ["Lobby", "Ruang Loker"],
        lantai_2: ["Lantai 2"],
        lantai_1_depan: ["Pos Security"],
        lantai_1_belakang: ["Ruang Mesin", "Ruang Kontrol"],
        area_luar_bangunan: [
            "Lantai 1 Depan",
            "Lantai 1 Depan Dan Luar",
            "Area Luar Bangunan",
            "Pos Security",
        ],
    };

    return aliases[areaId] || [getSanitationAreaLabel(areaId)];
}

export function getChecklistLabel(templateId) {
    return (
        checklistOptions.find((option) => option.id === templateId)?.name || "-"
    );
}

export function getChecklistEntryAreaLabel(entry) {
    if (entry?.template_id === "apar_smoke_detector_fire_alarm") {
        const cardLabel = getFireSafetyCardLabel(entry.form?.card_type);
        const locationLabel = getFireSafetyLocationLabel(
            entry.form?.card_type,
            entry.form?.location,
        );
        return `${cardLabel} - ${locationLabel}`;
    }

    if (entry?.template_id === "kotak_p3k") {
        return getLocationLabel(entry.form?.location);
    }

    if (entry?.template_id === "non_warehouse_sanitation") {
        return sanitationAreaOptions.map((area) => area.name).join(", ");
    }

    if (entry?.template_id === "pengangkutan_sampah_pt_sier") {
        return "PT SIER";
    }

    if (entry?.template_id === "warehouse_sanitation_1") {
        const areas = Array.isArray(entry.form?.selected_areas)
            ? entry.form.selected_areas
            : [];
        const labels = warehouseAreaOptions
            .filter((area) => areas.includes(area.id))
            .map((area) => area.name);

        return labels.length ? labels.join(", ") : "Warehouse";
    }

    if (entry?.template_id === "personal_hygiene_karyawan") {
        return (
            entry.form?.employee_name ||
            entry.form?.bagian ||
            "Personal Hygiene"
        );
    }

    if (entry?.template_id === "sarana_dan_prasarana") {
        return getSaranaPrasaranaAreaLabel(entry.form?.selected_area);
    }

    if (entry?.template_id === "site_visit_hse") {
        return getSiteVisitHseAreaLabel(entry.form?.selected_area);
    }

    if (entry?.template_id === "patroli_security") {
        return getPatroliSecurityAreaLabel(entry.form?.selected_area);
    }

    if (entry?.template_id === "site_visit_maintenance") {
        if (entry.form?.visit_type === "maintenance_mingguan") {
            return groupedChecklistAreaLabels.lantai_1_area_belakang;
        }

        return getMaintenanceDailyAreaLabel(entry.form?.selected_area);
    }

    if (entry?.template_id === "genset_running") {
        return groupedChecklistAreaLabels.genset;
    }

    if (entry?.template_id === "running_genset") {
        return groupedChecklistAreaLabels.genset;
    }

    if (entry?.template_id === "kompresor_harian") {
        return "KOMPRESOR";
    }

    if (entry?.template_id === "charger_baterai") {
        return "CHARGER BATERAI";
    }

    if (entry?.template_id === "checklist_baterai") {
        return "BATERAI";
    }

    return "-";
}

export function getDaysInPeriod(periodValue) {
    const [year, month] = String(periodValue || "").split("-");
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
            date: `${periodValue}-${String(day).padStart(2, "0")}`,
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
    const selectedArea =
        sanitationAreaOptions.find((area) => area.id === areaId) ||
        sanitationAreaOptions[0];

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
        template_id: "kotak_p3k",
        name: "Kotak P3K",
        created_at: formatDateTimeDisplay(now),
        form: {
            location: "ruang_kontrol",
            box_type: "A",
            pic: userName || "User Login",
            year: String(now.getFullYear()),
            document_no: "FRM.HSE.11.01",
            date: formatDateDisplay(now),
            rev: "00",
            page: "1",
            barcode: "",
            approved: false,
            active_month: activeMonth,
            submitted_months: [],
            approved_months: [],
            monthly_hse_approved_by: createKotakP3KMonthValue(""),
            monthly_notes: createKotakP3KMonthValue(""),
            monthly_barcodes: createKotakP3KMonthValue(""),
            monthly_check_dates: createKotakP3KMonthValue(""),
            check_date: formatDateDisplay(now),
            items: kotakP3KItems.map((item) => ({
                ...item,
                months: createKotakP3KMonthValue(""),
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
        template_id: "non_warehouse_sanitation",
        name: "Kebersihan dan Sanitasi (Non-Warehouse Area)",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            area: defaultArea.id,
            pic: userName || "User Login",
            document_no: "FRM/HSE/02/02",
            rev: "00",
            approved: false,
            approved_days: [],
            submitted_days: [],
            approval_requests_by_day: {},
            area_scans_by_day: {},
            area_notes: {},
            date: formatDateDisplay(now),
            verifier: "HSE",
            verifier_title: "Diperiksa oleh,",
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
            if (
                Object.prototype.hasOwnProperty.call(matchedRow.days || {}, day)
            ) {
                nextDays[day] = Boolean(matchedRow.days[day]);
            }
        });

        return {
            ...row,
            days: nextDays,
        };
    });
}

export function rebuildAllSanitationRowsByArea(
    periodValue,
    existingRowsByArea = {},
) {
    return sanitationAreaOptions.reduce((result, area) => {
        result[area.id] = rebuildSanitationRows(
            area.id,
            periodValue,
            existingRowsByArea?.[area.id] || [],
        );

        return result;
    }, {});
}
