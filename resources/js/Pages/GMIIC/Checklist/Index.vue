<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between mb-4">
        <h2 class="text-2xl font-bold">Checklist</h2>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <div class="w-full sm:w-[360px]">
            <SearchableSelect
              v-model="selectedChecklist"
              :options="checklistOptions"
              option-value="id"
              option-label="name"
              placeholder="Pilih Checklist..."
              empty-label="Pilih Checklist"
              input-class="w-full bg-slate-800 text-sm border-slate-700 rounded"
              button-class="border-0 border-l !border-slate-700 rounded-r !bg-slate-800 text-slate-100"
            />
          </div>

          <button
            type="button"
            @click="addChecklist"
            :disabled="!canCreateSelectedChecklist"
            class="px-4 py-2 rounded text-white text-sm font-semibold transition"
            :class="canCreateSelectedChecklist ? 'bg-indigo-600 hover:bg-indigo-500' : 'bg-slate-700 text-slate-400 cursor-not-allowed'"
          >
            New Checklist
          </button>
        </div>
      </div>

      <div
        v-if="selectedChecklist && !canCreateSelectedChecklist"
        class="mb-4 text-sm text-amber-300"
      >
        Template detail saat ini baru tersedia untuk checklist `Kotak P3K`.
      </div>

      <div class="bg-slate-800 rounded p-4">
        <div v-if="checklistEntries.length" class="overflow-x-auto">
          <table class="w-full table-auto">
            <thead>
              <tr class="text-left text-slate-400">
                <th class="py-2">#</th>
                <th>Checklist</th>
                <th>Lokasi</th>
                <th>Date</th>
                <th>PIC</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(entry, index) in checklistEntries"
                :key="entry.id"
                class="border-t border-slate-700"
              >
                <td class="py-3">{{ index + 1 }}</td>
                <td>{{ entry.name }}</td>
                <td>{{ getLocationLabel(entry.form.location) }}</td>
                <td>{{ entry.form.date }}</td>
                <td>{{ entry.form.pic }}</td>
                <td class="text-right space-x-3">
                  <button
                    type="button"
                    class="text-indigo-400 hover:text-indigo-300"
                    @click="setActiveEntry(entry.id)"
                  >
                    View
                  </button>
                  <button
                    type="button"
                    class="text-rose-400 hover:text-rose-300"
                    @click="removeChecklist(entry.id)"
                  >
                    Remove
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="text-center py-8 text-slate-400">
          Belum ada checklist yang ditambahkan.
        </div>

        <div class="mt-4 text-sm text-slate-400">
          Showing {{ checklistEntries.length ? 1 : 0 }} to {{ checklistEntries.length }} of {{ checklistEntries.length }} checklist
        </div>
      </div>

      <div v-if="activeEntry" class="mt-4 bg-slate-800 rounded p-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-4">
          <div>
            <h3 class="text-lg font-semibold text-white">{{ activeEntry.name }}</h3>
          </div>

          <div class="flex flex-col gap-2 sm:flex-row">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
              @click="scanBarcode(activeEntry)"
            >
              Scan Barcode
            </button>

            <button
              type="button"
              @click="toggleApproval(activeEntry)"
              :disabled="!canApproveEntry(activeEntry)"
              class="px-4 py-2 rounded text-sm font-semibold transition"
              :class="canApproveEntry(activeEntry)
                ? (activeEntry.form.approved ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-amber-600 hover:bg-amber-500 text-white')
                : 'bg-slate-700 text-slate-400 cursor-not-allowed'"
            >
              {{ activeEntry.form.approved ? 'Approved' : 'Approval' }}
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="w-full overflow-hidden rounded border border-slate-700">
            <table class="w-full table-fixed text-sm">
              <tbody>
                <tr class="border-b border-slate-700">
                  <td class="w-[32%] bg-slate-900 px-3 py-3 align-middle text-slate-300">Lokasi</td>
                    <td class="bg-slate-950 px-3 py-3 align-middle">
                      <div ref="locationMenuRef" class="relative">
                        <button
                          type="button"
                          class="flex w-full items-center justify-between bg-transparent p-0 text-left font-semibold text-slate-100 transition hover:text-white"
                          @click="toggleLocationMenu"
                        >
                          <span>{{ getLocationLabel(activeEntry.form.location) }}</span>
                          <span class="text-xs text-slate-400">{{ locationMenuOpen ? '▲' : '▼' }}</span>
                        </button>

                        <div
                          v-if="locationMenuOpen"
                          class="absolute left-0 right-0 top-full z-20 mt-1 overflow-hidden rounded border border-slate-700 bg-slate-900 shadow-lg"
                        >
                          <button
                            v-for="location in locationOptions"
                            :key="location.id"
                            type="button"
                            class="block w-full border-b border-slate-800 bg-slate-900 px-3 py-2 text-left text-sm text-slate-200 transition last:border-b-0 hover:bg-slate-800"
                            :class="location.id === activeEntry.form.location ? 'bg-slate-800 text-white' : ''"
                            @click="selectLocation(location.id)"
                          >
                            {{ location.name }}
                          </button>
                        </div>
                      </div>
                    </td>
                  </tr>
                <tr class="border-b border-slate-700">
                  <td class="bg-slate-900 px-3 py-3 align-middle text-slate-300">No. / Tipe Kotak</td>
                  <td class="bg-slate-950 px-3 py-3 align-middle text-left font-semibold text-slate-100">{{ activeEntry.form.box_type }}</td>
                </tr>
                <tr class="border-b border-slate-700">
                  <td class="bg-slate-900 px-3 py-3 align-middle text-slate-300">PIC</td>
                  <td class="bg-slate-950 px-3 py-3 align-middle text-left font-semibold text-slate-100">{{ activeEntry.form.pic }}</td>
                </tr>
                <tr>
                  <td class="bg-slate-900 px-3 py-3 align-middle text-slate-300">Tahun</td>
                  <td class="bg-slate-950 px-3 py-3 align-middle text-left font-semibold text-slate-100">{{ activeEntry.form.year }}</td>
                </tr>
                <tr class="border-t border-slate-700">
                  <td class="bg-slate-900 px-3 py-3 align-middle text-slate-300">Tanggal Check</td>
                  <td class="bg-slate-950 px-3 py-3 align-middle text-left font-semibold text-slate-100">{{ activeEntry.form.check_date }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="w-full overflow-hidden rounded border border-slate-700">
            <table class="w-full table-fixed text-sm">
              <tbody>
                <tr class="border-b border-slate-700">
                  <td class="w-[32%] bg-slate-900 px-3 py-3 align-middle text-slate-300">No. Doc</td>
                  <td class="bg-slate-950 px-3 py-3 align-middle text-left font-semibold text-slate-100">{{ activeEntry.form.document_no }}</td>
                </tr>
                <tr class="border-b border-slate-700">
                  <td class="bg-slate-900 px-3 py-3 align-middle text-slate-300">Date</td>
                  <td class="bg-slate-950 px-3 py-3 align-middle text-left font-semibold text-slate-100">{{ activeEntry.form.date }}</td>
                </tr>
                <tr class="border-b border-slate-700">
                  <td class="bg-slate-900 px-3 py-3 align-middle text-slate-300">Rev</td>
                  <td class="bg-slate-950 px-3 py-3 align-middle text-left font-semibold text-slate-100">{{ activeEntry.form.rev }}</td>
                </tr>
                <tr>
                  <td class="bg-slate-900 px-3 py-3 align-middle text-slate-300">Page</td>
                  <td class="bg-slate-950 px-3 py-3 align-middle text-left font-semibold text-slate-100">{{ activeEntry.form.page }}</td>
                </tr>
                <tr class="border-t border-slate-700">
                  <td class="bg-slate-900 px-3 py-3 align-middle text-slate-300">Status Approval</td>
                  <td class="bg-slate-950 px-3 py-3 align-middle text-left font-semibold text-slate-100">
                    {{ activeEntry.form.approved ? 'Approved' : 'Waiting Approval' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="w-full table-auto text-sm">
            <thead>
              <tr class="text-left text-slate-400 border-b border-slate-700">
                <th class="py-2 pr-3">No</th>
                <th class="py-2 pr-3">Item Check</th>
                <th class="py-2 pr-3">Jumlah</th>
                <th class="py-2">Hasil</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(item, index) in activeEntry.form.items" :key="item.id">
                <tr v-if="item.is_section" class="border-b border-slate-700/50">
                  <td class="py-2 pr-3">{{ index + 1 }}</td>
                  <td class="py-2 pr-3 font-semibold text-slate-200">{{ item.name }}</td>
                  <td class="py-2 pr-3 text-slate-400">-</td>
                  <td class="py-2 text-slate-400">-</td>
                </tr>

                <tr v-else class="border-b border-slate-700/50">
                  <td class="py-2 pr-3">{{ index + 1 }}</td>
                  <td class="py-2 pr-3 text-slate-200">{{ item.name }}</td>
                  <td class="py-2 pr-3 text-slate-300">{{ item.quantity }}</td>
                  <td class="py-2">
                    <div class="flex gap-2">
                      <button
                        type="button"
                        class="px-3 py-1 rounded text-xs font-semibold transition"
                        :class="item.answer === 'yes' ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
                        @click="item.answer = 'yes'"
                      >
                        Yes
                      </button>
                      <button
                        type="button"
                        class="px-3 py-1 rounded text-xs font-semibold transition"
                        :class="item.answer === 'no' ? 'bg-rose-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
                        @click="item.answer = 'no'"
                      >
                        No
                      </button>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

      </div>

      <div
        v-if="scannerModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
        @click.self="closeScannerModal"
      >
        <div class="w-full max-w-xl rounded-xl border border-slate-700 bg-slate-900 p-4 shadow-2xl">
          <div class="mb-4 flex items-center justify-between gap-4">
            <div>
              <h3 class="text-lg font-semibold text-white">Scan Barcode</h3>
              <p class="text-sm text-slate-400">Gunakan kamera HP atau laptop untuk membaca barcode lokasi.</p>
            </div>

            <button
              type="button"
              class="rounded bg-slate-700 px-3 py-2 text-sm text-white hover:bg-slate-600"
              @click="closeScannerModal"
            >
              Close
            </button>
          </div>

          <div v-if="scannerError" class="mb-3 rounded border border-rose-700 bg-rose-950/40 px-3 py-2 text-sm text-rose-200">
            {{ scannerError }}
          </div>

          <div v-if="scannerLoading" class="mb-3 text-sm text-slate-400">
            Menyiapkan kamera...
          </div>

          <div id="barcode-scanner-region" class="min-h-[320px] overflow-hidden rounded-lg border border-slate-700 bg-black"></div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const page = usePage();

const checklistOptions = [
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
  { id: 'warehouse_sanitation_2', name: 'Kebersihan dan Sanitasi (Warehouse Area)' },
];

const locationOptions = [
  { id: 'ruang_admin', name: 'Ruang Admin' },
  { id: 'ruang_kontrol', name: 'Ruang Kontrol' },
  { id: 'pos_security', name: 'Pos Security' },
];

const kotakP3KItems = [
  { id: 'kondisi_kotak_p3k', name: 'Kondisi kotak P3K', quantity: 20 },
  { id: 'kelengkapan_isi', name: 'Kelengkapan isi', is_section: true },
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

const selectedChecklist = ref('');
const checklistEntries = ref([]);
const activeEntryId = ref(null);
const locationMenuOpen = ref(false);
const locationMenuRef = ref(null);
const scannerModalOpen = ref(false);
const scannerLoading = ref(false);
const scannerError = ref('');
const scannerTargetEntryId = ref(null);

let html5QrcodeInstance = null;
let scannerStarting = false;
let scannerFinishing = false;

const activeEntry = computed(() => {
  return checklistEntries.value.find((entry) => entry.id === activeEntryId.value) || null;
});

const canCreateSelectedChecklist = computed(() => selectedChecklist.value === 'kotak_p3k');
const scannerTargetEntry = computed(() => {
  return checklistEntries.value.find((entry) => entry.id === scannerTargetEntryId.value) || null;
});

function getCurrentUserName() {
  return page.props.auth?.user?.name || 'User Login';
}

function formatDateDisplay(date = new Date()) {
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date);
}

function formatDateTimeDisplay(date = new Date()) {
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}

function getLocationLabel(locationId) {
  return locationOptions.find((location) => location.id === locationId)?.name || '-';
}

function createKotakP3KEntry() {
  const now = new Date();

  return {
    id: `kotak_p3k-${Date.now()}`,
    template_id: 'kotak_p3k',
    name: 'Kotak P3K',
    created_at: formatDateTimeDisplay(now),
    form: {
      location: 'ruang_kontrol',
      box_type: 'A',
      pic: getCurrentUserName(),
      year: String(now.getFullYear()),
      document_no: 'FRM.HSE.11.01',
      date: formatDateDisplay(now),
      rev: '00',
      page: '1',
      barcode: '',
      approved: false,
      check_date: formatDateDisplay(now),
      items: kotakP3KItems.map((item) => ({
        ...item,
        answer: item.is_section ? '' : '',
      })),
    },
  };
}

function addChecklist() {
  if (!canCreateSelectedChecklist.value) {
    return;
  }

  const entry = createKotakP3KEntry();
  checklistEntries.value.unshift(entry);
  activeEntryId.value = entry.id;
}

function setActiveEntry(id) {
  activeEntryId.value = id;
  locationMenuOpen.value = false;
}

function removeChecklist(id) {
  checklistEntries.value = checklistEntries.value.filter((entry) => entry.id !== id);

  if (activeEntryId.value === id) {
    activeEntryId.value = checklistEntries.value[0]?.id || null;
  }
}

function toggleApproval(entry) {
  if (!canApproveEntry(entry)) {
    return;
  }

  entry.form.approved = !entry.form.approved;
}

function canApproveEntry(entry) {
  if (!entry?.form?.items?.length) {
    return false;
  }

  const allAnswersFilled = entry.form.items
    .filter((item) => !item.is_section)
    .every((item) => item.answer === 'yes' || item.answer === 'no');

  return allAnswersFilled && String(entry.form.barcode || '').trim() !== '';
}

async function scanBarcode(entry) {
  if (!entry) {
    return;
  }

  scannerTargetEntryId.value = entry.id;
  scannerError.value = '';
  scannerLoading.value = true;
  scannerModalOpen.value = true;

  await nextTick();
  await startBarcodeScanner();
}

function toggleLocationMenu() {
  locationMenuOpen.value = !locationMenuOpen.value;
}

function selectLocation(locationId) {
  if (activeEntry.value) {
    activeEntry.value.form.location = locationId;
  }
  locationMenuOpen.value = false;
}

function handleOutsideLocationMenu(event) {
  const root = locationMenuRef.value;
  if (!root) {
    return;
  }

  if (!root.contains(event.target)) {
    locationMenuOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleOutsideLocationMenu);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideLocationMenu);
  stopBarcodeScanner();
});

async function startBarcodeScanner() {
  if (scannerStarting) {
    return;
  }

  scannerStarting = true;

  try {
    await stopBarcodeScanner();

    const scannerModule = await import('html5-qrcode');
    const { Html5Qrcode } = scannerModule;

    html5QrcodeInstance = new Html5Qrcode('barcode-scanner-region');

    const cameras = await Html5Qrcode.getCameras();
    if (!cameras.length) {
      throw new Error('Kamera tidak ditemukan pada perangkat ini.');
    }

    const preferredCamera = choosePreferredCamera(cameras);

    await html5QrcodeInstance.start(
      preferredCamera.id,
      {
        fps: 10,
        qrbox: { width: 280, height: 140 },
        aspectRatio: 1.777778,
      },
      async (decodedText) => {
        if (scannerFinishing) {
          return;
        }

        scannerFinishing = true;

        const targetEntry = scannerTargetEntry.value;
        if (targetEntry) {
          targetEntry.form.barcode = decodedText;
        }

        await closeScannerModal();
        scannerFinishing = false;
      },
      () => {}
    );

    scannerError.value = '';
  } catch (error) {
    scannerError.value = normalizeScannerError(error);
    await stopBarcodeScanner();
  } finally {
    scannerLoading.value = false;
    scannerStarting = false;
  }
}

function choosePreferredCamera(cameras) {
  const scored = [...cameras].sort((a, b) => scoreCameraLabel(b.label) - scoreCameraLabel(a.label));
  return scored[0];
}

function scoreCameraLabel(label) {
  const text = String(label || '').toLowerCase();
  let score = 0;
  if (text.includes('back') || text.includes('rear') || text.includes('environment')) score += 20;
  if (text.includes('front') || text.includes('user')) score -= 5;
  return score;
}

function normalizeScannerError(error) {
  const message = String(error?.message || error || '');
  const lowered = message.toLowerCase();

  if (lowered.includes('permission')) {
    return 'Izin kamera ditolak. Izinkan akses kamera lalu coba lagi.';
  }

  if (lowered.includes('secure context') || lowered.includes('https')) {
    return 'Kamera membutuhkan koneksi aman (HTTPS) atau localhost.';
  }

  return message || 'Scanner barcode gagal dijalankan.';
}

async function closeScannerModal() {
  scannerModalOpen.value = false;
  scannerLoading.value = false;
  await stopBarcodeScanner();
}

async function stopBarcodeScanner() {
  if (!html5QrcodeInstance) {
    return;
  }

  try {
    if (html5QrcodeInstance.isScanning) {
      await html5QrcodeInstance.stop();
    }
  } catch (error) {
  }

  try {
    await html5QrcodeInstance.clear();
  } catch (error) {
  }

  html5QrcodeInstance = null;
}
</script>
