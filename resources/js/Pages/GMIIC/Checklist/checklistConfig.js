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

export const locationOptions = [
  { id: 'ruang_admin', name: 'Ruang Admin' },
  { id: 'ruang_kontrol', name: 'Ruang Kontrol' },
  { id: 'pos_security', name: 'Pos Security' },
];

export const sanitationAreaOptions = [
  {
    id: 'lantai_1',
    name: 'Lantai 1',
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
    name: 'Lantai 2',
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
    name: 'Area Luar Bangunan',
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
      rows: createPersonalHygieneRows(period),
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

export function formatShortDateDisplay(date = new Date()) {
  return new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: 'short',
  }).format(date);
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

export function getSanitationAreaLabel(areaId) {
  return sanitationAreaOptions.find((area) => area.id === areaId)?.name || '-';
}

export function getChecklistLabel(templateId) {
  return checklistOptions.find((option) => option.id === templateId)?.name || '-';
}

export function getChecklistEntryAreaLabel(entry) {
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
