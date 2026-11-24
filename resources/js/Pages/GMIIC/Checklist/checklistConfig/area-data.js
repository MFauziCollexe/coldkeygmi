import { groupedChecklistAreaLabels } from './shared-data';

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

export const printerChecklistSections = [
    {
        id: "printer_epson_l121_lantai_2",
        title: "PRINTER EPSON L121 > LANTAI 2",
        items: [
            "Daya & indikator lampu status printer menyala normal (tidak ada blinking error/alarm)",
            "Koneksi USB / jaringan LAN / Wi-Fi printer terhubung stabil dan terdeteksi Online",
            "Status antrean cetak (Print Spooler) bersih tanpa ada dokumen yang nyangkut (stuck)",
            "Level tinta / toner / pita (ribbon) mencukupi (tidak ada peringatan Low Ink/Toner)",
            "Hasil cetak Test Page jelas, tajam, dan warna akurat (tidak bergaris, pudar, atau putus-putus)",
            "Mekanisme penarik kertas (Paper Roller) berfungsi normal tanpa paper jam atau menarik kertas ganda",
        ],
    },
    {
        id: "printer_dotmatrix_play_1_lantai_1_admin",
        title: "PRINTER DOTMATRIX PLAY 1 > LANTAI 1 ADMIN",
        items: [
            "Daya & indikator panel kontrol printer menyala normal (tidak ada lampu error berkedip atau bunyi alarm)",
            "Koneksi port paralel / USB / LAN / print server terhubung stabil dan status printer Online",
            "Print head bergerak mulus di sepanjang rel (carriage shaft) tanpa macet atau suara gesekan kasar",
            "Kualitas hasil cetak jelas, rata, dan tidak putus-putus pada seluruh pin print head (tidak ada karakter hilang/samar)",
            "Ribbon cartridge (pita tinta) terpasang tepat, terputar lancar, dan tinta masih tebal (tidak pudar atau kusut)",
            "Kebersihan fisik bodi, jalur kertas, sisa sobekan lubang pin-feed, serta kerapian kabel daya dan data",
            "Jarak print head ke kertas (lever ketebalan kertas) sesuai dengan jumlah rangkap / ketebalan media cetak",
            "Tractor feed / friction roller menarik kertas continuous form atau rangkap (NCR) lurus tanpa miring atau paper jam",
        ],
    },
    {
        id: "printer_dotmatrix_play_2_lantai_1_admin",
        title: "PRINTER DOTMATRIX PLAY 2 > LANTAI 1 ADMIN",
        items: [
            "Daya & indikator panel kontrol printer menyala normal (tidak ada lampu error berkedip atau bunyi alarm)",
            "Koneksi port paralel / USB / LAN / print server terhubung stabil dan status printer Online",
            "Print head bergerak mulus di sepanjang rel (carriage shaft) tanpa macet atau suara gesekan kasar",
            "Kualitas hasil cetak jelas, rata, dan tidak putus-putus pada seluruh pin print head (tidak ada karakter hilang/samar)",
            "Ribbon cartridge (pita tinta) terpasang tepat, terputar lancar, dan tinta masih tebal (tidak pudar atau kusut)",
            "Kebersihan fisik bodi, jalur kertas, sisa sobekan lubang pin-feed, serta kerapian kabel daya dan data",
            "Jarak print head ke kertas (lever ketebalan kertas) sesuai dengan jumlah rangkap / ketebalan media cetak",
            "Tractor feed / friction roller menarik kertas continuous form atau rangkap (NCR) lurus tanpa miring atau paper jam",
        ],
    },
    {
        id: "printer_kyocera_ecosys_m2040dn_lantai_1_admin",
        title: "PRINTER KYOCERA ECOSYS M2040DN > LANTAI 1 ADMIN",
        items: [
            "Daya & layar LCD panel menyala normal (indikator Attention tidak berkedip, tidak ada kode error / J-Code)",
            "Koneksi jaringan LAN / USB stabil, status printer Online",
            "Level toner TK-1174 mencukupi (lihat/cek melalui menu Toner Status, tidak ada peringatan Low Toner / Check Toner)",
            "Hasil cetak tajam dan bersih tanpa garis vertikal hitam, bayangan abu-abu (background fog), atau toner luntur",
        ],
    },
    {
        id: "printer_sato_w14_axb_lantai_1_admin",
        title: "PRINTER SATO W14-AXB > LANTAI 1 ADMIN",
        items: [
            "Daya & layar LCD panel menyala normal tanpa lampu indikator merah / peringatan error (Head Open, Ribbon End, Paper End)",
            "Koneksi port USB / LAN / Wi-Fi stabil dan printer terdeteksi Online pada sistem cetak label (BarTender / ERP)",
            "Kondisi elemen pemanas Thermal Print Head bersih tanpa goresan atau residu lem label (tidak ada garis putih vertikal pada hasil cetak)",
            "Kualitas hasil cetak barcode & teks hitam pekat, tajam, dan terbaca lancar oleh barcode scanner (tingkat panas/darkness sesuai)",
            "Gulungan Ribbon (khusus mode Thermal Transfer) terpasang kencang, tidak kusut/melipat, dan tergulung rapi pada ribbon take-up core",
            "Gulungan Label terpasang presisi di media guide, posisi sensor gap / I-mark tepat di tengah, dan kalibrasi ukuran label akurat (tidak meleset)",
            "Kondisi Platen Roller (rol karet bawah) bersih, tidak licin, tidak aus/tergores, dan menarik label mulus tanpa macet atau miring",
        ],
    },
];

export const printerChecklistDeviceOptions = printerChecklistSections.map(
    (section) => ({ id: section.id, name: section.title }),
);

const cctvCameraStandard = "Gambar jelas, fokus tajam, sudut tepat, tidak terhalang";

export const cctvChecklistSections = [
    {
        id: "nvr_1",
        title: "NVR 1",
        items: [
            "Daya & UPS NVR menyala normal (indikator power aktif, UPS tidak alarm)",
            "Status online seluruh 18 channel kamera di NVR (tidak ada sinyal lost / offline)",
            "Kapasitas & kesehatan HDD Storage normal (tidak ada peringatan HDD Error/Full)",
            "Status perekaman (Recording) berjalan aktif sesuai jadwal/mode",
            "Sinkronisasi tanggal & jam NVR akurat (NTP/RTC sesuai waktu aktual)",
            "Koneksi jaringan LAN / PoE Switch ke NVR stabil tanpa putus",
            "Monitor Live View CCTV tampil jelas (tidak blank, flicker, atau freeze)",
            "Kebersihan fisik unit NVR, rak, dan kerapian kabel patch cord",
        ],
    },
    {
        id: "nvr_2",
        title: "NVR 2",
        items: [
            "Daya & UPS NVR menyala normal (indikator power aktif, UPS tidak alarm)",
            "Status online seluruh 27 channel kamera di NVR (tidak ada sinyal lost / offline)",
            "Kapasitas & kesehatan HDD Storage normal (tidak ada peringatan HDD Error/Full)",
            "Status perekaman (Recording) berjalan aktif sesuai jadwal/mode",
            "Sinkronisasi tanggal & jam NVR akurat (NTP/RTC sesuai waktu aktual)",
            "Koneksi jaringan LAN / PoE Switch ke NVR stabil tanpa putus",
            "Monitor Live View CCTV tampil jelas (tidak blank, flicker, atau freeze)",
            "Kebersihan fisik unit NVR, rak, dan kerapian kabel patch cord",
        ],
    },
    {
        id: "cctv_luar",
        title: "CCTV Luar",
        items: [
            "Parkiran",
            "Office",
            "Pintu Loading",
            "Office Lt 1",
            "Locker",
            "Admin Lt 1",
            "Tangga Lt 2",
            "Server",
            "Office Lt 3",
            "Lorong Samping",
            "Pintu Ruang Baterai",
            "Genset / Trafo",
            "Ruang Baterai",
            "Mesin 2",
            "Mesin Utama",
            "Control Room",
            "Pagar Barat",
            "Pagar Utara",
            "Lantai 2",
        ].map((area) => `${area} - ${cctvCameraStandard}`),
    },
    {
        id: "cctv_dalam",
        title: "CCTV Dalam",
        items: [
            "Loading Duct 1",
            "Loading Duct 2",
            "Loading Duct Anteroom",
            "Anteroom Depan",
            "Anteroom Tengah Depan",
            "Anteroom Tengah Belakang",
            "Anteroom Belakang",
            "CS 1 Pintu Besar",
            "CS 1 Pintu Kecil",
            "CS 2 Pintu Besar",
            "CS 2 Pintu Kecil",
            "CS 3 Pintu Besar",
            "CS 3 Pintu Kecil",
            "CS 3 Pintu Besar",
            "CS 4 Pintu Kecil",
            "CS 4 Pintu Besar",
            "CS 5 Pintu Kecil",
            "CS 5 Pintu Besar",
            "CS 6 Pintu Besar",
            "CS 7 Pintu Besar",
            "CS 8 Pintu Kecil",
            "CS 8 Pintu Besar",
            "CS 9 Pintu Kecil",
            "CS 9 Pintu Besar",
            "CS 9 Pintu Kecil (2)",
            "CS 10 Pintu Besar",
            "CS 10 Pintu Kecil",
            "CS 11 Pintu Besar",
            "CS 11 Pintu Kecil",
            "CS 12 Pintu Besar",
            "CS 12 Pintu Kecil",
        ].map((area) => `${area} - ${cctvCameraStandard}`),
    },
];

export const cctvChecklistGroupOptions = cctvChecklistSections.map(
    (section) => ({ id: section.id, name: section.title }),
);

export const cleaningOBShifts = [
    {
        id: "07_00_08_30_ob_i_ii",
        title: "KANTOR , MEETING, DIREKTUR LT 2",
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
        title: "TANGGA , LOBBY , TAMU",
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
        title: "ADMIN , LOKER , KARYAWAN LAKI & PEREMPUAN, WASTAFEL AREA TOILET KARYAWAN",
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
        title: "LOADING DOCK",
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
        title: "ANTERUM , CS 1-12",
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
        title: "RUANG BATERAI",
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
        title: "RUANG MESIN , TOILET , RUANG KONTROL , AREA GENSET",
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
        title: "POS SATPAM",
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
        title: "MUSHOLAH",
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
        title: "RUANG LOKER , RUANG ISTIRAHAT",
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
        title: "LINGKUNGAN GUDANG",
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
        title: "LOBBY , SERVER , APAR ALL AREA GUDANG , KIPAS ANGIN MUSHOLAH , TPS B3 , LANTAI AREA DOCK LEVELARY",
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
