<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-4 flex items-start gap-4">
      <div class="flex h-16 w-16 items-center justify-center rounded border border-slate-300 bg-white">
        <img
          src="/image/logo-gmi-clean.png"
          alt="PT. Golden Multi Indotama"
          class="h-12 w-12 object-contain"
        />
      </div>
      <div class="space-y-1">
        <div class="text-base font-semibold">PT. GOLDEN MULTI INDOTAMA</div>
        <div class="text-xs text-slate-600">Check sheet kotak P3K</div>
      </div>
    </div>

    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
      <div class="w-full max-w-[18rem] space-y-2 text-sm">
        <div class="flex items-center gap-3">
          <span class="w-24">Bulan Aktif</span>
          <span>:</span>
          <div class="relative">
            <select
              :value="activeMonth"
              class="w-40 appearance-none rounded border border-slate-300 bg-white px-2 py-1 pr-10 text-sm text-slate-900"
              @change="$emit('set-active-month', $event.target.value)"
            >
              <option
                v-for="month in months"
                :key="month.key"
                :value="month.key"
              >
                {{ month.label }}
              </option>
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-600">
              ▼
            </span>
          </div>
        </div>
        <div class="text-xs text-slate-600">
          Untuk Kondisi kotak P3K, klik kolom gabungan untuk memilih centang atau silang. Item lain: A = jumlah tersedia, R = jumlah perlu diisi ulang/diganti; isi 0 jika tidak ada.
        </div>
      </div>

      <div class="flex-1">
        <div class="mx-auto max-w-2xl rounded-xl border-2 border-black px-8 py-2 text-center">
          <div class="text-xl font-semibold">Check Sheet</div>
          <div class="text-4xl font-bold">Kotak P3K</div>
        </div>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
        <div v-if="showQrScanner" class="flex w-[132px] flex-col gap-1">
          <button
            type="button"
            class="w-full rounded px-4 py-2 text-sm font-semibold transition"
            :disabled="!canScanBarcode"
            :class="!canScanBarcode
              ? 'cursor-not-allowed bg-slate-300 text-slate-500 hover:bg-slate-300'
              : 'bg-sky-600 text-white hover:bg-sky-500'"
            @click="$emit('scan-barcode')"
          >
            QRCode
          </button>
          <div class="text-xs text-slate-600">
            {{ currentBarcode || `QRCode bulan ${activeMonthLabel} belum discan.` }}
          </div>
        </div>
        <div v-else class="flex w-[132px] flex-col justify-center text-xs text-slate-600">
          Mode tanpa QRCode aktif.
        </div>

        <ApprovalButton
          :is-ready="canApproveEntry"
          :disabled="!canApproveEntry"
          :label="approvalButtonLabel"
          button-class="w-[132px]"
          tooltip="Approval siap jika semua kondisi terpenuhi"
          @click="$emit('approve')"
        />
      </div>
    </div>

    <div class="overflow-visible border border-black">
      <table class="min-w-full border-collapse text-sm">
        <tbody>
          <tr>
            <td class="w-[25%] border border-black px-2 py-1">Lokasi</td>
            <td class="w-[25%] border border-black px-2 py-1 font-semibold">
              <div class="relative" data-location-menu-root>
                <button
                  type="button"
                  class="block w-full bg-transparent p-0 text-left"
                  @click="$emit('toggle-location-menu')"
                >
                  <span>{{ getLocationLabel(entry.form.location) }}</span>
                </button>

                <div
                  v-if="locationMenuOpen"
                  class="absolute left-0 right-0 top-full z-20 mt-1 overflow-hidden rounded border border-slate-300 bg-white shadow-lg"
                >
                  <button
                    v-for="location in locationOptions"
                    :key="location.id"
                    type="button"
                    class="block w-full border-b border-slate-200 px-3 py-2 text-left text-sm transition last:border-b-0 hover:bg-slate-100"
                    :class="location.id === entry.form.location ? 'bg-slate-100 font-semibold' : ''"
                    @click="$emit('select-location', location.id)"
                  >
                    {{ location.name }}
                  </button>
                </div>
              </div>
            </td>
            <td class="w-[25%] border border-black text-center">
              <div class="text-xs font-semibold">Approved</div>
            </td>
            <td class="w-[25%] border border-black text-center">
              <div class="text-xs font-semibold">Prepared</div>
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-1">No. / Tipe Kotak</td>
            <td class="border border-black px-2 py-1 font-semibold">{{ entry.form.box_type }}</td>
            <td class="border border-black px-2 py-1 text-center text-xs">
              {{ activeMonthStatusLabel }}
            </td>
            <td class="border border-black px-2 py-1 text-center text-xs">
              {{ entry.form.pic }}
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-1">PIC</td>
            <td class="border border-black px-2 py-1 font-semibold">{{ entry.form.pic }}</td>
            <td class="border border-black px-2 py-1">No. Doc : {{ entry.form.document_no }}</td>
            <td class="border border-black px-2 py-1">Rev : {{ entry.form.rev }}</td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-1">Tahun</td>
            <td class="border border-black px-2 py-1 font-semibold">{{ entry.form.year }}</td>
            <td class="border border-black px-2 py-1">Date : {{ entry.form.date }}</td>
            <td class="border border-black px-2 py-1">Page : {{ entry.form.page }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr class="bg-slate-200">
            <th rowspan="2" class="w-12 border border-black px-2 py-1 text-center">No</th>
            <th rowspan="2" class="w-[28rem] border border-black px-2 py-1 text-left">Item Check</th>
            <th rowspan="2" class="w-20 border border-black px-2 py-1 text-center">Jumlah</th>
            <th
              v-for="month in months"
              :key="month.key"
              colspan="2"
              class="border border-black px-2 py-1 text-center"
              :class="month.key === activeMonth ? 'bg-amber-100' : ''"
            >
              {{ month.label }}
            </th>
          </tr>
          <tr class="bg-slate-100 text-xs">
            <template v-for="month in months" :key="`quantity-columns-${month.key}`">
              <th class="w-10 border border-black px-1 py-1 text-center" :class="month.key === activeMonth ? 'bg-amber-100' : ''">A</th>
              <th class="w-10 border border-black px-1 py-1 text-center" :class="month.key === activeMonth ? 'bg-amber-100' : ''">R</th>
            </template>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(item, index) in currentLocationItems"
            :key="item.id"
          >
            <td class="border border-black px-2 py-1 text-center">{{ index + 1 }}</td>
            <td class="border border-black px-2 py-1">{{ item.name }}</td>
            <td class="border border-black px-2 py-1 text-center">{{ item.quantity }}</td>
            <template v-for="month in months" :key="`${item.id}-${month.key}`">
              <td
                v-if="item.id === 'kondisi_kotak_p3k'"
                colspan="2"
                class="border border-black p-0 text-center"
                :class="month.key === activeMonth ? 'bg-amber-50' : ''"
              >
                <button
                  type="button"
                  class="flex h-10 w-full items-center justify-center text-base font-semibold"
                  :disabled="month.key !== activeMonth || isActiveMonthLocked"
                  :aria-label="`${item.name}, ${month.label}`"
                  @click="$emit('update-month-condition', item, month.key, getNextConditionStatus(item, month.key))"
                >
                  <span v-if="getDisplayedConditionStatus(item, month.key) === 'yes'">✓</span>
                  <span v-else-if="getDisplayedConditionStatus(item, month.key) === 'no'" class="text-rose-600">✕</span>
                </button>
              </td>
              <template v-else>
                <td
                  v-for="column in quantityColumns"
                  :key="`${item.id}-${month.key}-${column.key}`"
                  class="border border-black p-0 text-center"
                  :class="month.key === activeMonth ? 'bg-amber-50' : ''"
                >
                  <input
                    v-if="month.key === activeMonth"
                    :value="getDisplayedMonthQuantity(item, month.key, column.key)"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    class="h-10 w-full border-0 bg-transparent px-1 text-center text-sm text-slate-900 focus:ring-1 focus:ring-inset focus:ring-sky-500 disabled:text-slate-500"
                    :aria-label="`${item.name}, ${month.label}, ${column.label}`"
                    :disabled="isActiveMonthLocked"
                    @input="$emit('update-month-quantity', item, month.key, column.key, $event.target.value)"
                  />
                  <span v-else class="flex h-10 items-center justify-center text-sm">
                    {{ getDisplayedMonthQuantity(item, month.key, column.key) }}
                  </span>
                </td>
              </template>
            </template>
          </tr>
          <tr>
            <td colspan="3" class="border border-black px-2 py-1 text-right font-semibold">Tanggal Check</td>
            <td
              v-for="month in months"
              colspan="2"
              :key="`check-date-${month.key}`"
              class="border border-black px-2 py-1 text-center text-xs"
              :class="month.key === activeMonth ? 'bg-amber-50' : ''"
            >
              {{ getDisplayedCheckDate(month.key) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 rounded border border-slate-300 bg-slate-50 p-3">
        <div class="mb-2 text-sm font-semibold">Keterangan Bulan {{ activeMonthLabel }}</div>
        <textarea
          :value="monthNote"
          rows="4"
          class="w-full rounded border border-slate-400 bg-slate-100 px-3 py-2 text-sm text-slate-900"
          :disabled="isActiveMonthLocked"
          placeholder="Isi catatan / temuan untuk bulan aktif..."
          @input="$emit('update-month-note', $event.target.value)"
        ></textarea>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ApprovalButton from '../Components/ApprovalButton.vue';
const props = defineProps({
  entry: {
    type: Object,
    required: true,
  },
  canScanBarcode: {
    type: Boolean,
    required: true,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
  showQrScanner: {
    type: Boolean,
    default: true,
  },
  locationMenuOpen: {
    type: Boolean,
    required: true,
  },
  locationOptions: {
    type: Array,
    required: true,
  },
  getLocationLabel: {
    type: Function,
    required: true,
  },
  months: {
    type: Array,
    required: true,
  },
  activeMonth: {
    type: String,
    required: true,
  },
  activeMonthLabel: {
    type: String,
    required: true,
  },
  currentBarcode: {
    type: String,
    required: true,
  },
  currentLocationItems: {
    type: Array,
    required: true,
  },
  currentLocationMonthlyCheckDates: {
    type: Object,
    required: true,
  },
  previousMonthReference: {
    type: Object,
    default: null,
  },
  monthNote: {
    type: String,
    required: true,
  },
  isActiveMonthApproved: {
    type: Boolean,
    required: true,
  },
  isActiveMonthLocked: {
    type: Boolean,
    required: true,
  },
  activeMonthStatusLabel: {
    type: String,
    required: true,
  },
  approvalButtonLabel: {
    type: String,
    required: true,
  },
});

const quantityColumns = [
  { key: 'a', label: 'A' },
  { key: 'r', label: 'R' },
];

const previousMonthLocationReference = computed(() => {
  const locationId = String(props.entry?.form?.location || '');

  return props.previousMonthReference?.locations?.[locationId] || null;
});

const getDisplayedMonthQuantity = (item, monthKey, columnKey) => {
  const currentValue = item.months?.[monthKey]?.[columnKey];
  if (currentValue !== undefined && currentValue !== null && currentValue !== '') {
    return currentValue;
  }

  if (monthKey === props.activeMonth) {
    return '';
  }

  return previousMonthLocationReference.value?.months?.[monthKey]?.items?.[item.id]?.[columnKey] || '';
};

const getDisplayedConditionStatus = (item, monthKey) => {
  const currentValue = item.months?.[monthKey];
  if (currentValue === 'yes' || currentValue === 'no') {
    return currentValue;
  }

  if (monthKey === props.activeMonth) {
    return '';
  }

  return previousMonthLocationReference.value?.months?.[monthKey]?.items?.[item.id] || '';
};

const getNextConditionStatus = (item, monthKey) => {
  const currentStatus = getDisplayedConditionStatus(item, monthKey);
  return currentStatus === '' ? 'yes' : currentStatus === 'yes' ? 'no' : '';
};

const getDisplayedCheckDate = (monthKey) => {
  const currentDate = props.currentLocationMonthlyCheckDates?.[monthKey];
  if (currentDate || monthKey === props.activeMonth) {
    return currentDate || '';
  }

  return previousMonthLocationReference.value?.months?.[monthKey]?.check_date || '';
};

defineEmits([
  'approve',
  'scan-barcode',
  'toggle-location-menu',
  'select-location',
  'set-active-month',
  'update-month-quantity',
  'update-month-condition',
  'update-month-note',
]);
</script>
