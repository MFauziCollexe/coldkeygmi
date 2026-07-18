<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-5 overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <tbody>
          <tr>
            <td rowspan="2" class="w-36 border border-black px-3 py-3 text-center">
              <img
                src="/image/logo-gmi-clean.png"
                alt="PT. Golden Multi Indotama"
                class="mx-auto h-16 w-16 object-contain"
              />
            </td>
            <td colspan="2" class="border border-black px-3 py-2 text-center text-2xl font-bold">
              PT GOLDEN MULTI INDOTAMA
            </td>
          </tr>
          <tr>
            <td class="w-80 border border-black px-3 py-3 text-center">
              <div class="text-xl font-bold leading-tight">CHECKLIST INSPEKSI LOKER</div>
            </td>
            <td class="border border-black p-0 align-top">
              <table class="min-w-full border-collapse text-sm">
                <tbody>
                  <tr>
                    <td class="w-40 border border-black px-2 py-1">Doc. No.</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.document_no || 'FRM.HSE.XX.01' }}</td>
                  </tr>
                  <tr>
                    <td class="border border-black px-2 py-1">Rev.</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.rev || '00' }}</td>
                  </tr>
                  <tr>
                    <td class="border border-black px-2 py-1">Tanggal Efektif</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.effective_date || '-' }}</td>
                  </tr>
                  <tr>
                    <td class="border border-black px-2 py-1">Halaman</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.page || 'Page 1 dari 1' }}</td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mb-4 rounded border border-black bg-slate-50 p-4">
      <div class="grid gap-4 lg:grid-cols-[1.2fr_1fr_auto] lg:items-end">
        <div class="grid items-center gap-3 sm:grid-cols-[auto_minmax(0,1fr)]">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">Bulan</div>
          <input
            :value="entry.form.date_value"
            type="month"
            class="w-full border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:outline-none"
            @input="$emit('update-date', $event.target.value)"
          />
        </div>

        <div class="grid items-center gap-3 sm:grid-cols-[auto_minmax(0,1fr)]">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">PIC</div>
          <input
            :value="entry.form.pic"
            type="text"
            readonly
            class="w-full border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-600 focus:outline-none"
          />
        </div>

        <div class="flex justify-end">
          <button
            type="button"
            :disabled="!canApproveEntry"
            class="h-9 rounded bg-amber-500 px-5 text-sm font-semibold text-white transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500"
            @click="$emit('approve')"
          >
            Approval
          </button>
        </div>
      </div>
    </div>

    <div class="overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-[11px]">
        <thead>
          <tr class="bg-slate-100 text-left text-[10px] uppercase tracking-wide text-slate-700">
            <th class="sticky left-0 z-10 border border-black bg-slate-100 px-2 py-2">No.</th>
            <th class="sticky left-12 z-10 border border-black bg-slate-100 px-2 py-2 min-w-60">Parameter</th>
            <th v-for="locker in lockerNumbers" :key="locker" class="border border-black px-1 py-2 text-center">{{ locker }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in entry.form.rows" :key="row.key">
            <td class="sticky left-0 z-0 border border-black bg-white px-2 py-2 text-center">{{ row.no }}</td>
            <td class="sticky left-12 z-0 border border-black bg-white px-2 py-2">{{ row.label }}</td>
            <td
              v-for="locker in lockerNumbers"
              :key="`${row.key}-${locker}`"
              class="border border-black px-0 py-0 text-center"
            >
              <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded bg-white text-xs font-semibold leading-none text-slate-800 transition hover:bg-slate-200 focus:outline-none focus-visible:outline-none"
                :class="{
                  'text-rose-600': row.lockers?.[String(locker)] === 'no',
                }"
                @click="$emit('cycle-locker-status', row.no, locker)"
              >
                <span v-if="row.lockers?.[String(locker)] === 'yes'">✓</span>
                <span v-else-if="row.lockers?.[String(locker)] === 'no'">✕</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-3 text-xs text-slate-600">
      Klik setiap kotak untuk mengubah status loker: <span class="font-semibold">Ya / Tidak / Kosong</span>.
    </div>
    <div class="mt-4 rounded border border-slate-300 bg-slate-50 p-3">
      <div class="mb-2 text-sm font-semibold">Keterangan</div>
      <textarea
        :value="entry.form.note || ''"
        rows="3"
        class="w-full rounded border border-slate-400 bg-slate-100 px-3 py-2 text-sm text-slate-900"
        placeholder="Isi keterangan jika ada temuan atau catatan..."
        @input="$emit('update-note', $event.target.value)"
      ></textarea>
    </div>
  </div>
</template>

<script setup>
const lockerNumbers = Array.from({ length: 32 }, (_, index) => index + 1)

defineProps({
  entry: {
    type: Object,
    required: true,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
})

defineEmits(['approve', 'update-date', 'update-pic', 'update-note', 'cycle-locker-status'])
</script>
