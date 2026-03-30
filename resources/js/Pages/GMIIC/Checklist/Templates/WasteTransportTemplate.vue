<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
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
          <div class="text-xs text-slate-600">Checklist monitoring pengangkutan sampah domestik</div>
        </div>
      </div>

      <div class="text-sm">
        <div class="grid grid-cols-[auto_auto_auto] gap-x-2">
          <span>No. Dokumen</span>
          <span>:</span>
          <span>FRM.HSE.07.02</span>
        </div>
      </div>
    </div>

    <div class="mb-4 text-center">
      <div class="text-2xl font-semibold italic">Checklist Monitoring Pengangkutan Limbah Non-B3 (Sampah Domestik)</div>
      <div class="text-3xl font-bold">{{ periodLabel }}</div>
    </div>

    <div class="mb-4 flex items-center justify-between gap-4">
      <div class="flex items-center gap-3 text-sm">
        <span>Periode</span>
        <span>:</span>
        <input
          v-model="entry.form.period"
          type="month"
          class="w-44 rounded border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900"
        />
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
        Approval
      </button>
    </div>

      <div class="overflow-x-auto border border-black">
        <table class="min-w-full border-collapse text-sm">
          <colgroup>
            <col class="w-20" />
            <col class="w-44" />
            <col class="w-56" />
            <col class="w-56" />
            <col class="w-60" />
          </colgroup>
          <thead>
            <tr class="bg-slate-100">
            <th rowspan="2" class="border border-black px-2 py-1 text-center">Tanggal</th>
            <th rowspan="2" class="border border-black px-2 py-1 text-center">Waktu Pengangkutan</th>
            <th colspan="1" class="border border-black px-2 py-1 text-center">Petugas Penyerahan</th>
            <th colspan="2" class="border border-black px-2 py-1 text-center">Petugas Pengangkut</th>
          </tr>
          <tr class="bg-slate-100">
            <th class="border border-black px-2 py-1 text-center">Nama</th>
            <th class="border border-black px-2 py-1 text-center">Nama</th>
            <th class="border border-black px-2 py-1 text-center">Foto</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in rows"
            :key="row.day"
          >
            <td
              class="border border-black px-2 py-1 text-center"
              :class="approvedDays.includes(row.day) ? 'bg-emerald-100' : ''"
            >
              {{ row.day }}
            </td>
            <td class="border border-black px-2 py-1">
              <input
                :value="row.pickup_time"
                type="time"
                class="w-full border-0 bg-transparent px-0 py-1 text-sm text-slate-900 focus:outline-none focus:ring-0"
                :disabled="approvedDays.includes(row.day)"
                @input="$emit('update-row', row.day, 'pickup_time', $event.target.value)"
              />
            </td>
            <td class="border border-black px-2 py-1">
              <input
                :value="row.handover_name"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-1 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                :disabled="approvedDays.includes(row.day)"
                placeholder="Nama"
                @input="$emit('update-row', row.day, 'handover_name', $event.target.value)"
              />
            </td>
            <td class="border border-black px-2 py-1">
              <input
                :value="row.collector_name"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-1 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                :disabled="approvedDays.includes(row.day)"
                placeholder="Nama"
                @input="$emit('update-row', row.day, 'collector_name', $event.target.value)"
              />
            </td>
            <td class="border border-black px-2 py-1">
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="inline-flex cursor-pointer rounded bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-800"
                  :class="approvedDays.includes(row.day) ? 'cursor-not-allowed opacity-60' : 'hover:bg-slate-300'"
                  :disabled="approvedDays.includes(row.day)"
                  @click="$emit('open-camera', row.day)"
                >
                  Ambil Foto
                </button>
                <div class="min-w-0 flex-1">
                  <div
                    v-if="row.collector_photo_preview"
                    class="mb-1 h-12 w-12 overflow-hidden rounded border border-slate-300 bg-slate-100"
                  >
                    <img
                      :src="row.collector_photo_preview"
                      :alt="row.collector_photo_name || 'Foto petugas pengangkut'"
                      class="h-full w-full object-cover"
                    />
                  </div>
                  <span class="block truncate text-xs text-slate-700">{{ row.collector_photo_name || '-' }}</span>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-3 text-xs text-slate-600">
      Baris yang sudah di-approve akan terkunci. `Stempel` petugas pengangkut diganti dengan foto langsung dari kamera.
    </div>
  </div>
</template>

<script setup>
defineProps({
  entry: {
    type: Object,
    required: true,
  },
  rows: {
    type: Array,
    required: true,
  },
  periodLabel: {
    type: String,
    required: true,
  },
  approvedDays: {
    type: Array,
    required: true,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
});

defineEmits(['approve', 'update-row', 'open-camera']);
</script>
