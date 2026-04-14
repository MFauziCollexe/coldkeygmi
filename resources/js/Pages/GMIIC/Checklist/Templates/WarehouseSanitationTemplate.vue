<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-4 flex items-start justify-between gap-4">
      <div class="flex items-start gap-4">
        <div class="flex h-16 w-16 items-center justify-center rounded border border-slate-300 bg-white">
          <img
            src="/image/logo-gmi-clean.png"
            alt="PT. Golden Multi Indotama"
            class="h-12 w-12 object-contain"
          />
        </div>
        <div class="space-y-1">
          <div class="text-base font-semibold">PT. GOLDEN MULTI INDOTAMA</div>
          <div class="text-xs text-slate-600">Checklist kebersihan dan sanitasi warehouse area</div>
        </div>
      </div>

      <div class="text-sm">
        <div class="grid grid-cols-[auto_auto_auto] gap-x-2">
          <span>No. Dokumen</span>
          <span>:</span>
          <span>FRM.HSE.06.03</span>
        </div>
      </div>
    </div>

    <div class="mb-4 flex items-start justify-between gap-4">
      <div>
        <div class="text-2xl font-bold">Checklist Kebersihan dan Sanitasi (Warehouse Area)</div>
        <div class="text-sm text-slate-600">Form warehouse dalam satu template dengan section A sampai E.</div>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
        <div class="flex w-[132px] flex-col gap-1">
          <button
            type="button"
            class="w-full rounded px-4 py-2 text-sm font-semibold transition"
            :disabled="!canScanBarcode"
            :class="!canScanBarcode
              ? 'cursor-not-allowed bg-slate-300 text-slate-500 hover:bg-slate-300'
              : 'bg-sky-600 text-white hover:bg-sky-500'"
            @click="$emit('scan-barcode')"
          >
            Scan Barcode
          </button>
          <div class="text-xs text-slate-600">
            {{ currentBarcode ? `Discan ${scanDate || '-'}` : 'Barcode checklist belum discan.' }}
          </div>
        </div>

        <button
          type="button"
          :disabled="!canApproveEntry"
          class="rounded px-4 py-2 text-sm font-semibold transition"
          :class="canApproveEntry
            ? 'bg-amber-500 text-white hover:bg-amber-400'
            : 'cursor-not-allowed bg-slate-300 text-slate-500'"
          @click="$emit('approve')"
        >
          {{ approvalButtonLabel }}
        </button>
      </div>
    </div>

    <div class="overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <tbody>
          <tr class="bg-slate-100">
            <td colspan="2" class="border border-black px-2 py-1 text-lg font-bold">A. INFORMASI UMUM</td>
          </tr>
          <tr>
            <td class="w-1/3 border border-black px-2 py-2">Frekuensi</td>
            <td class="border border-black px-2 py-2">
              <select
                :value="entry.form.frequency"
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
                @change="$emit('update-frequency', $event.target.value)"
              >
                <option value="daily">Rutin (Harian)</option>
                <option value="monthly">Berkala (Bulanan)</option>
              </select>
            </td>
          </tr>
          <tr>
            <td class="w-1/3 border border-black px-2 py-2">{{ entry.form.frequency === 'monthly' ? 'Periode' : 'Tanggal' }}</td>
            <td class="border border-black px-2 py-2">
              <input
                v-if="entry.form.frequency === 'monthly'"
                :value="entry.form.period"
                type="month"
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
                @input="$emit('update-general-field', 'period', $event.target.value)"
              />
              <input
                v-else
                :value="entry.form.date"
                type="text"
                readonly
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
              />
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-2">Area Cold Storage</td>
            <td class="border border-black px-2 py-2">
              <div class="flex flex-wrap gap-4">
                <label
                  v-for="area in warehouseAreaOptions"
                  :key="area.id"
                  class="inline-flex items-center gap-2"
                >
                  <input
                    :checked="entry.form.selected_areas.includes(area.id)"
                    type="checkbox"
                    class="h-4 w-4 border border-slate-400 text-slate-900"
                    @change="$emit('toggle-area', area.id)"
                  />
                  <span>{{ area.name }}</span>
                </label>
              </div>
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-2">Suhu Ruangan (&#176;C)</td>
            <td class="border border-black px-2 py-2">
              <input
                :value="entry.form.room_temperature"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
                @input="$emit('update-general-field', 'room_temperature', $event.target.value)"
              />
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-2">Petugas</td>
            <td class="border border-black px-2 py-2">
              <input
                :value="entry.form.petugas"
                type="text"
                readonly
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
              />
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-2">HSE</td>
            <td class="border border-black px-2 py-2">
              <input
                :value="entry.form.hse"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
                @input="$emit('update-general-field', 'hse', $event.target.value)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr class="bg-slate-100">
            <th colspan="6" class="border border-black px-2 py-1 text-left text-lg font-bold">B. CHECKLIST KEBERSIHAN AREA</th>
          </tr>
          <tr class="bg-slate-100 text-center">
            <th class="border border-black px-2 py-1">No</th>
            <th class="border border-black px-2 py-1">Area / Objek</th>
            <th class="border border-black px-2 py-1">Kondisi Bersih</th>
            <th class="border border-black px-2 py-1">Tidak Ada Es / Genangan</th>
            <th class="border border-black px-2 py-1">Tidak Bau</th>
            <th class="border border-black px-2 py-1">Keterangan</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in entry.form.area_rows"
            :key="row.id"
          >
            <td class="border border-black px-2 py-2 text-center">{{ row.no }}</td>
            <td class="border border-black px-2 py-2">{{ row.name }}</td>
            <td class="border border-black p-0 text-center">
              <button
                type="button"
                class="flex h-10 w-full items-center justify-center text-sm font-semibold"
                @click="$emit('set-area-row-status', row.id, 'clean_condition', getNextBinaryStatus(row.clean_condition))"
              >
                <span v-if="row.clean_condition === 'yes'">✓</span>
                <span v-else-if="row.clean_condition === 'no'" class="text-rose-600">✕</span>
              </button>
            </td>
            <td class="border border-black p-0 text-center">
              <button
                type="button"
                class="flex h-10 w-full items-center justify-center text-sm font-semibold"
                @click="$emit('set-area-row-status', row.id, 'no_ice_pooling', getNextBinaryStatus(row.no_ice_pooling))"
              >
                <span v-if="row.no_ice_pooling === 'yes'">✓</span>
                <span v-else-if="row.no_ice_pooling === 'no'" class="text-rose-600">✕</span>
              </button>
            </td>
            <td class="border border-black p-0 text-center">
              <button
                type="button"
                class="flex h-10 w-full items-center justify-center text-sm font-semibold"
                @click="$emit('set-area-row-status', row.id, 'no_odor', getNextBinaryStatus(row.no_odor))"
              >
                <span v-if="row.no_odor === 'yes'">✓</span>
                <span v-else-if="row.no_odor === 'no'" class="text-rose-600">✕</span>
              </button>
            </td>
            <td class="border border-black px-2 py-2">
              <input
                :value="row.note"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
                @input="$emit('set-area-row-note', row.id, $event.target.value)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr class="bg-slate-100">
            <th colspan="4" class="border border-black px-2 py-1 text-left text-lg font-bold">C. PENGENDALIAN ES, AIR LELEHAN &amp; KONDENSASI</th>
          </tr>
          <tr class="bg-slate-100 text-center">
            <th class="border border-black px-2 py-1">No</th>
            <th class="border border-black px-2 py-1">Item Pemeriksaan</th>
            <th class="border border-black px-2 py-1">Hasil Pemeriksaan</th>
            <th class="border border-black px-2 py-1">Keterangan</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in entry.form.ice_control_rows"
            :key="row.id"
          >
            <td class="border border-black px-2 py-2 text-center">{{ row.no }}</td>
            <td class="border border-black px-2 py-2">{{ row.name }}</td>
            <td class="border border-black p-0 text-center">
              <button
                type="button"
                class="flex h-10 w-full items-center justify-center text-sm font-semibold"
                @click="$emit('set-ice-control-status', row.id, getNextIceControlStatus(row.status))"
              >
                <span v-if="row.status === 'sesuai'">✓</span>
                <span v-else-if="row.status === 'tidak_sesuai'" class="text-rose-600">✕</span>
              </button>
            </td>
            <td class="border border-black px-2 py-2">
              <input
                :value="row.note"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
                @input="$emit('set-ice-control-note', row.id, $event.target.value)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr class="bg-slate-100">
            <th colspan="5" class="border border-black px-2 py-1 text-left text-lg font-bold">D. PENGGUNAAN BAHAN PEMBERSIH</th>
          </tr>
          <tr class="bg-slate-100 text-center">
            <th class="border border-black px-2 py-1">No</th>
            <th class="border border-black px-2 py-1">Nama Bahan</th>
            <th class="border border-black px-2 py-1">Halal</th>
            <th class="border border-black px-2 py-1">Dosis Sesuai</th>
            <th class="border border-black px-2 py-1">Keterangan</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in entry.form.cleaning_material_rows"
            :key="row.id"
          >
            <td class="border border-black px-2 py-2 text-center">{{ row.no }}</td>
            <td class="border border-black px-2 py-2">
              <input
                :value="row.material_name"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
                @input="$emit('set-cleaning-material-field', row.id, 'material_name', $event.target.value)"
              />
            </td>
            <td class="border border-black p-0 text-center">
              <button
                type="button"
                class="flex h-10 w-full items-center justify-center text-sm font-semibold"
                @click="$emit('set-cleaning-material-field', row.id, 'halal', getNextBinaryStatus(row.halal))"
              >
                <span v-if="row.halal === 'yes'">✓</span>
                <span v-else-if="row.halal === 'no'" class="text-rose-600">✕</span>
              </button>
            </td>
            <td class="border border-black p-0 text-center">
              <button
                type="button"
                class="flex h-10 w-full items-center justify-center text-sm font-semibold"
                @click="$emit('set-cleaning-material-field', row.id, 'dosage', getNextBinaryStatus(row.dosage))"
              >
                <span v-if="row.dosage === 'yes'">✓</span>
                <span v-else-if="row.dosage === 'no'" class="text-rose-600">✕</span>
              </button>
            </td>
            <td class="border border-black px-2 py-2">
              <input
                :value="row.note"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
                @input="$emit('set-cleaning-material-field', row.id, 'note', $event.target.value)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr class="bg-slate-100">
            <th colspan="4" class="border border-black px-2 py-1 text-left text-lg font-bold">E. VERIFIKASI</th>
          </tr>
          <tr class="bg-slate-100 text-center">
            <th class="border border-black px-2 py-1">Disiapkan / Diverifikasi oleh</th>
            <th class="border border-black px-2 py-1">Nama</th>
            <th class="border border-black px-2 py-1">Tanda Tangan</th>
            <th class="border border-black px-2 py-1">Tanggal</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="border border-black px-2 py-2 text-center">Petugas</td>
            <td class="border border-black px-2 py-2">
              <input
                :value="entry.form.verification.prepared_name"
                type="text"
                readonly
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
              />
            </td>
            <td class="border border-black px-2 py-2">
              <input
                :value="entry.form.verification.prepared_signature"
                type="text"
                readonly
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
              />
            </td>
            <td class="border border-black px-2 py-2">
              <input
                :value="entry.form.verification.prepared_date"
                type="text"
                readonly
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
              />
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-2 text-center">Manager/HSE</td>
            <td class="border border-black px-2 py-2">
              <input
                :value="entry.form.verification.verified_name"
                type="text"
                readonly
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
              />
            </td>
            <td class="border border-black px-2 py-2">
              <input
                :value="entry.form.verification.verified_signature"
                type="text"
                readonly
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
              />
            </td>
            <td class="border border-black px-2 py-2">
              <input
                :value="entry.form.verification.verified_date"
                type="text"
                readonly
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
function getNextIceControlStatus(currentValue) {
  if (currentValue === '') {
    return 'sesuai';
  }

  if (currentValue === 'sesuai') {
    return 'tidak_sesuai';
  }

  return '';
}

function getNextBinaryStatus(currentValue) {
  if (currentValue === '') {
    return 'yes';
  }

  if (currentValue === 'yes') {
    return 'no';
  }

  return '';
}

defineProps({
  entry: {
    type: Object,
    required: true,
  },
  warehouseAreaOptions: {
    type: Array,
    required: true,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
  currentBarcode: {
    type: String,
    default: '',
  },
  scanDate: {
    type: String,
    default: '',
  },
  canScanBarcode: {
    type: Boolean,
    required: true,
  },
  approvalButtonLabel: {
    type: String,
    required: true,
  },
});

defineEmits([
  'approve',
  'scan-barcode',
  'update-frequency',
  'toggle-area',
  'update-general-field',
  'set-area-row-status',
  'set-area-row-note',
  'set-ice-control-status',
  'set-ice-control-note',
  'set-cleaning-material-field',
]);
</script>
