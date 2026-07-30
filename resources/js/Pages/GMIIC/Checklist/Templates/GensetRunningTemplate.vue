<template>
  <div class="relative">
    <div :class="['rounded border border-slate-300 p-4 text-black shadow-sm transition duration-200', isApproved ? 'bg-slate-50' : 'bg-white']">
      <div class="mb-5 overflow-x-auto border border-black">
      <table class="w-full table-fixed border-collapse text-xs sm:text-sm">
        <tbody>
          <tr>
            <td rowspan="2" class="w-24 border border-black px-2 py-3 text-center sm:w-36 sm:px-3">
              <img
                src="/image/logo-gmi-clean.png"
                alt="PT. Golden Multi Indotama"
                class="mx-auto h-12 w-12 object-contain sm:h-16 sm:w-16"
              />
            </td>
            <td colspan="2" class="border border-black px-2 py-2 text-center text-sm font-bold sm:px-3 sm:text-2xl">
              PT GOLDEN MULTI INDOTAMA
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-3 text-center sm:w-80 sm:px-3">
              <div class="text-sm font-bold leading-tight sm:text-xl">CHECKLIST PEMANASAN (RUNNING) GENSET</div>
            </td>
            <td class="border border-black p-0 align-top">
              <table class="w-full border-collapse text-[11px] sm:text-sm">
                <tbody>
                  <tr>
                    <td class="w-24 border border-black px-2 py-1 sm:w-40">Doc. No.</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.document_no }}</td>
                  </tr>
                  <tr>
                    <td class="border border-black px-2 py-1">Rev.</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.rev }}</td>
                  </tr>
                  <tr>
                    <td class="border border-black px-2 py-1">Tanggal Efektif</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.effective_date }}</td>
                  </tr>
                  <tr>
                    <td class="border border-black px-2 py-1">Halaman</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.page }}</td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
      <div class="flex flex-col gap-3 font-semibold lg:flex-row lg:flex-wrap lg:items-center">
        <div class="grid grid-cols-[72px_minmax(0,1fr)] items-center gap-2 sm:flex sm:items-center sm:gap-3">
          <span class="text-base sm:min-w-24 sm:text-lg">Periode:</span>
          <input
            :value="entry.form.period_value"
            type="week"
            class="w-full max-w-[220px] rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900 sm:max-w-none disabled:bg-white disabled:text-slate-900 disabled:border-slate-400 disabled:cursor-not-allowed"
            :disabled="isApproved"
            @input="$emit('update-period', $event.target.value)"
          />
        </div>

        <div class="grid grid-cols-[72px_minmax(0,1fr)] items-center gap-2 sm:flex sm:items-center sm:gap-3">
          <span class="text-base sm:min-w-24 sm:text-lg">Area:</span>
          <div class="text-sm font-normal text-slate-900">GENSET</div>
        </div>

        <div class="grid grid-cols-[72px_minmax(0,1fr)] items-center gap-2 sm:flex sm:items-center sm:gap-3">
          <span class="text-base sm:min-w-24 sm:text-lg">PIC:</span>
          <div class="text-sm font-normal text-slate-900">{{ entry.form.pic }}</div>
        </div>
      </div>

      <div class="flex flex-col gap-1">
        <div class="flex flex-wrap items-start gap-2">
          <button
            v-if="showQrScanner"
            type="button"
            class="w-[132px] rounded px-4 py-2 text-sm font-semibold transition"
            :disabled="!canScanBarcode"
            :class="!canScanBarcode
              ? 'cursor-not-allowed bg-slate-300 text-slate-500 hover:bg-slate-300'
              : 'bg-sky-600 text-white hover:bg-sky-500'"
            @click="$emit('scan-barcode')"
          >
            QRCode
          </button>
          <div v-else class="flex h-10 w-[132px] items-center text-xs text-slate-600">
            Mode tanpa QRCode aktif.
          </div>

          <ApprovalButton
            :is-ready="approvalReady"
            :disabled="isApproved || !approvalReady"
            :label="isApproved ? 'Approved' : 'Approval'"
            button-class="w-[96px]"
            tooltip="Approval siap jika semua kondisi terpenuhi"
            @click="$emit('approve')"
          />
        </div>

        <div class="max-w-[180px] text-xs text-slate-600">
          {{ showQrScanner ? (currentBarcode || 'QRCode genset belum discan.') : 'Approve dapat langsung dilakukan.' }}
        </div>
        <!-- debug removed -->
        <div v-if="scanDate" class="text-xs text-slate-500">
          Scan: {{ scanDate }}
        </div>
      </div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
      <div>
        <div class="font-semibold">Tanggal</div>
        <div>{{ entry.form.date }}</div>
      </div>
      <div>
        <div class="font-semibold">Periode Tampil</div>
        <div>{{ entry.form.period }}</div>
      </div>
      <div>
        <div class="font-semibold">Status</div>
        <div>{{ statusLabel }}</div>
      </div>
    </div>

    <div class="border border-black">
      <table class="w-full table-fixed border-collapse text-xs sm:text-sm sm:table-auto">
        <thead>
          <tr class="bg-slate-100">
            <th class="w-10 border border-black px-1 py-2 text-center sm:w-12 sm:px-2">No</th>
            <th class="border border-black px-2 py-2 text-center sm:min-w-[420px]">ITEM</th>
            <th class="w-[84px] border border-black px-1 py-2 text-center text-[11px] leading-tight whitespace-normal sm:min-w-[220px] sm:px-2 sm:text-sm">
              Kondisi
            </th>
          </tr>
        </thead>
        <tbody>
          <template
            v-for="section in groupedRows"
            :key="section.id"
          >
            <tr class="bg-slate-50">
              <td colspan="3" class="border border-black px-2 py-2 text-sm font-bold sm:text-base">
                {{ section.title }}
              </td>
            </tr>
            <tr
              v-for="row in section.items"
              :key="row.id"
            >
              <td class="border border-black px-1 py-1 text-center align-top sm:px-2">{{ row.no }}</td>
              <td class="border border-black px-2 py-1 leading-snug break-words">{{ row.name }}</td>
              <td class="border border-black p-0 text-center">
                <button
                  type="button"
                  :disabled="isApproved"
                  :class="[
                    'flex h-10 w-full items-center justify-center text-lg font-semibold leading-none sm:h-11 sm:text-xl',
                    'bg-white text-slate-900 hover:bg-slate-50',
                    isApproved ? 'cursor-not-allowed' : ''
                  ]"
                  @click="$emit('cycle-row-status', { rowId: row.id })"
                >
                  <span v-if="row.status === 'yes'">&#10003;</span>
                  <span v-else-if="row.status === 'no'" class="text-rose-600">&#10005;</span>
                </button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <div class="mt-4 rounded border border-slate-300 bg-slate-50 p-3">
      <div class="mb-2 text-sm font-semibold">Catatan / Temuan</div>
      <textarea
        :value="note"
        rows="4"
        class="w-full rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900 disabled:bg-white disabled:text-slate-900 disabled:border-slate-400 disabled:cursor-not-allowed"
        :disabled="isApproved"
        placeholder="Isi catatan jika ada item bertanda silang."
        @input="$emit('update-note', $event.target.value)"
      ></textarea>
      <div class="mt-2 text-xs text-slate-600">
        Catatan wajib diisi bila ada item dengan tanda silang.
      </div>
    </div>
  </div></div></template>

<script setup>
import ApprovalButton from '../Components/ApprovalButton.vue';
import { computed } from 'vue';

const props = defineProps({
  entry: {
    type: Object,
    required: true,
  },
  rows: {
    type: Array,
    required: true,
  },
  note: {
    type: String,
    default: '',
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
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
  isApproved: {
    type: Boolean,
    default: false,
  },
  statusLabel: {
    type: String,
    default: 'Pending',
  },
  showQrScanner: {
    type: Boolean,
    default: true,
  },
});

const approvalReady = computed(() => {
  const rows = props.rows || [];
  const allRowsFilled = rows.length > 0 && rows.every((row) => row.status === 'yes' || row.status === 'no');
  const hasNoAnswer = rows.some((row) => row.status === 'no');
  const hasRequiredNote = !hasNoAnswer || String(props.note || '').trim() !== '';
  const hasPeriod = Boolean(String(props.entry?.form?.period_value || '').trim());

  return hasPeriod && allRowsFilled && hasRequiredNote;
});

const groupedRows = computed(() => {
  const groups = [];

  for (const row of props.rows || []) {
    const lastGroup = groups[groups.length - 1];

    if (!lastGroup || lastGroup.id !== row.section_id) {
      groups.push({
        id: row.section_id || row.id,
        title: row.section_title || '-',
        items: [row],
      });
      continue;
    }

    lastGroup.items.push(row);
  }

  return groups;
});

defineEmits([
  'approve',
  'scan-barcode',
  'update-period',
  'cycle-row-status',
  'update-note',
]);
</script>
