import { groupedLocationLabels, groupedChecklistAreaLabels } from './shared-data';

export const locationOptions = [
    { id: "ruang_admin", name: groupedLocationLabels.ruang_admin },
    { id: "ruang_kontrol", name: groupedLocationLabels.ruang_kontrol },
    { id: "pos_security", name: groupedLocationLabels.pos_security },
];

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
    { id: "tidak_mempunyai_luka_terbuka", name: "Tidak mempunyai luka terbuka" },
    { id: "jaket_thermal_bersih", name: "Jaket thermal bersih" },
    { id: "sarung_tangan_bersih", name: "Sarung Tangan Bersih" },
    { id: "kuku_pendek_tidak_diwarnai", name: "Kuku pendek & tidak diwarnai/dicat" },
    { id: "tidak_memakai_perhiasan", name: "Tidak memakai perhiasan/aksesoris/jam tangan" },
    { id: "tidak_membawa_barang_pribadi", name: "Tidak membawa barang bawaan (barang pribadi) ke area warehouse" },
    { id: "tidak_membawa_makanan", name: "Tidak membawa makanan & minuman ke area warehouse (selain produk customer)" },
    { id: "rambut_rapi_pendek", name: "Rambut rapi & pendek untuk karyawan" },
    { id: "tidak_berjenggot", name: "Tidak berjenggot/cambang/kumis untuk karyawan" },
    { id: "tidak_memakai_bulu_mata", name: "Tidak memakai bulu mata palsu/eye shadow" },
    { id: "plester_perban_in", name: "Plester/Perban (In)" },
    { id: "plester_perban_out", name: "Plester/Perban (Out)" },
];

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
