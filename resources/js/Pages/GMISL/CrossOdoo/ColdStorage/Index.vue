<template>
  <AppLayout>
    <main class="cold-storage-report min-h-full bg-gradient-to-br from-sky-50 via-slate-50 to-blue-50 p-4 text-slate-900 md:p-6">
      <div class="mx-auto max-w-375 space-y-5">
        <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
              <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5V6.8a1.8 1.8 0 0 1 1-1.6l6-3a2.2 2.2 0 0 1 2 0l6 3a1.8 1.8 0 0 1 1 1.6v12.7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h4" />
              </svg>
            </span>
            <div>
              <p class="mb-1 text-xs font-semibold uppercase tracking-[0.14em] text-blue-700">GMISL / Cross Odoo</p>
              <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Laporan Cold Storage</h1>
              <p class="mt-1 text-xs text-slate-500">Informasi kapasitas, penggunaan, dan aktivitas cold storage secara real-time.</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-right text-[11px] text-slate-500">Data Odoo diperbarui<br><span class="font-semibold text-slate-700">{{ updatedAt }}</span></span>
            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50" :disabled="refreshing" @click="refreshData">
              <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.542 2.77.75.75 0 0 0-1.114 1.006 7 7 0 0 0 12.134-3.525.75.75 0 1 0-1.478-.251ZM4.688 8.576a5.5 5.5 0 0 1 9.542-2.77.75.75 0 0 0 1.114-1.006A7 7 0 0 0 3.21 8.325a.75.75 0 1 0 1.478.251Z" clip-rule="evenodd" /></svg>
              {{ refreshing ? 'Memuat...' : 'Refresh' }}
            </button>
          </div>
        </header>

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan kapasitas">
          <article class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-3">
              <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5V6.8a1.8 1.8 0 0 1 1-1.6l6-3a2.2 2.2 0 0 1 2 0l6 3a1.8 1.8 0 0 1 1 1.6v12.7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h4" /></svg>
              </span>
              <div>
                <p class="text-[11px] font-semibold text-slate-700">Total Kapasitas</p>
                <p class="mt-0.5 text-xl font-bold leading-tight tabular-nums text-slate-900">{{ formatNumber(totals.capacity) }} <span class="text-xs font-medium text-slate-500">slot</span></p>
              </div>
            </div>
            <p class="mt-2 text-[10px] text-slate-500">{{ storages.length }} lokasi Cold Storage</p>
          </article>
          <article class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-3">
              <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z" /><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v8.5" /></svg>
              </span>
              <div>
                <p class="text-[11px] font-semibold text-slate-700">Total Penggunaan</p>
                <p class="mt-0.5 text-xl font-bold leading-tight tabular-nums text-slate-900">{{ formatNumber(totals.used) }} <span class="text-xs font-medium text-slate-500">slot</span></p>
              </div>
            </div>
            <p class="mt-2 text-[10px] text-emerald-700">{{ formatPercent(totals.occupancy) }} dari total kapasitas</p>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: `${Math.min(100, totals.occupancy)}%` }"></div></div>
          </article>
          <article class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-3">
              <span class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-100 text-violet-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v13a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 18.5v-13Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h3" /></svg>
              </span>
              <div>
                <p class="text-[11px] font-semibold text-slate-700">Total Tersedia</p>
                <p class="mt-0.5 text-xl font-bold leading-tight tabular-nums text-slate-900">{{ formatNumber(totals.available) }} <span class="text-xs font-medium text-slate-500">slot</span></p>
              </div>
            </div>
            <p class="mt-2 text-[10px] text-slate-500">{{ formatPercent(totals.capacity ? totals.available / totals.capacity * 100 : 0) }} dari total kapasitas</p>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-violet-500 transition-all" :style="{ width: `${Math.min(100, totals.capacity ? totals.available / totals.capacity * 100 : 0)}%` }"></div></div>
          </article>
          <article class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-3">
              <span class="flex h-9 w-9 items-center justify-center rounded-full bg-red-100 text-red-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3.5 2.8 19.2A1.2 1.2 0 0 0 3.8 21h16.4a1.2 1.2 0 0 0 1-1.8L12 3.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 3h.01" /></svg>
              </span>
              <div>
                <p class="text-[11px] font-semibold text-slate-700">Cold Storage Kritis</p>
                <p class="mt-0.5 text-xl font-bold leading-tight tabular-nums text-red-600">{{ totals.critical }} <span class="text-xs font-medium text-slate-500">lokasi</span></p>
              </div>
            </div>
            <p class="mt-2 text-[10px] text-slate-500">Tingkat okupansi ≥ 85%</p>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-red-500 transition-all" :style="{ width: `${Math.min(100, storages.length ? totals.critical / storages.length * 100 : 0)}%` }"></div></div>
          </article>
        </section>

        <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="history-title">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <h2 id="history-title" class="text-lg font-semibold text-white">Laporan Historis Cold Storage</h2>
              <p class="mt-1 text-xs text-slate-500">Snapshot harian · {{ history.startDate }} s/d {{ history.endDate }}</p>
            </div>
            <div class="flex flex-wrap gap-2" role="group" aria-label="Periode laporan historis">
              <button
                v-for="period in historyPeriods"
                :key="period.value"
                type="button"
                class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
                :class="historyPeriod === period.value ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                @click="historyPeriod = period.value"
              >
                {{ period.label }}
              </button>
            </div>
          </div>

          <div v-if="historyComparison" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <article class="border-l-2 border-slate-500 bg-[#15181d] px-3 py-2.5">
              <p class="text-[11px] text-slate-500">Jumlah snapshot</p>
              <p class="mt-1 text-lg font-bold tabular-nums text-white">{{ visibleHistorySnapshots.length }} hari</p>
            </article>
            <article class="border-l-2 border-sky-400 bg-[#15181d] px-3 py-2.5">
              <p class="text-[11px] text-slate-500">Perubahan slot terpakai</p>
              <p class="mt-1 text-lg font-bold tabular-nums" :class="historyComparison.usedChange > 0 ? 'text-amber-300' : 'text-emerald-300'">
                {{ historyComparison.usedChange > 0 ? '+' : '' }}{{ formatNumber(historyComparison.usedChange) }} slot
              </p>
              <p class="text-[10px] text-slate-500">{{ historyComparison.first.date }} → {{ historyComparison.last.date }}</p>
            </article>
            <article class="border-l-2 border-violet-400 bg-[#15181d] px-3 py-2.5">
              <p class="text-[11px] text-slate-500">Perubahan okupansi</p>
              <p class="mt-1 text-lg font-bold tabular-nums" :class="historyComparison.occupancyChange > 0 ? 'text-amber-300' : 'text-emerald-300'">
                {{ historyComparison.occupancyChange > 0 ? '+' : '' }}{{ formatNumber(historyComparison.occupancyChange) }} poin
              </p>
              <p class="text-[10px] text-slate-500">Selisih poin persentase</p>
            </article>
          </div>

          <div v-if="visibleHistorySnapshots.length" class="overflow-x-auto border-t border-slate-800 pt-4">
            <div class="flex h-44 min-w-full w-max items-end gap-1.5 px-1">
              <div
                v-for="snapshot in visibleHistorySnapshots"
                :key="snapshot.date"
                class="group flex h-full w-4 flex-col items-center justify-end"
                :title="`${snapshot.date}\nTerpakai: ${formatNumber(snapshot.used)} / ${formatNumber(snapshot.capacity)} slot\nOkupansi: ${formatPercent(snapshot.occupancy)}`"
              >
                <div class="flex h-full w-full items-end rounded-t-sm bg-slate-800">
                  <div
                    class="w-full rounded-t-sm bg-sky-400 transition group-hover:bg-sky-300"
                    :style="{ height: `${Math.max(2, Math.min(100, Number(snapshot.occupancy)))}%` }"
                  ></div>
                </div>
              </div>
            </div>
            <div class="mt-2 flex justify-between text-[10px] text-slate-500">
              <span>{{ visibleHistorySnapshots[0]?.date }}</span>
              <span>{{ visibleHistorySnapshots[visibleHistorySnapshots.length - 1]?.date }}</span>
            </div>
          </div>
          <p v-else class="border-t border-slate-800 py-8 text-center text-sm text-slate-500">
            Belum ada snapshot pada periode ini. Snapshot pertama akan tersedia setelah command harian berhasil dijalankan.
          </p>

          <div v-if="historyComparison" class="grid grid-cols-1 gap-4 border-t border-slate-800 pt-4 xl:grid-cols-2">
            <div>
              <h3 class="mb-2 text-sm font-semibold text-white">Perbandingan per Cold Storage · snapshot terbaru</h3>
              <div class="overflow-x-auto">
                <table class="w-full min-w-[420px] text-left text-xs">
                  <thead class="text-slate-500">
                    <tr>
                      <th class="py-2 pr-3 font-medium">Cold Storage</th>
                      <th class="px-2 py-2 text-right font-medium">Terpakai</th>
                      <th class="px-2 py-2 text-right font-medium">Kapasitas</th>
                      <th class="py-2 pl-2 text-right font-medium">Okupansi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="storage in historyComparison.last.storages" :key="storage.name" class="border-t border-slate-800 text-slate-300">
                      <td class="py-2 pr-3 font-medium text-white">{{ storage.name }}</td>
                      <td class="px-2 py-2 text-right tabular-nums">{{ formatNumber(storage.used) }}</td>
                      <td class="px-2 py-2 text-right tabular-nums">{{ formatNumber(storage.capacity) }}</td>
                      <td class="py-2 pl-2 text-right tabular-nums">{{ formatPercent(storage.occupancy) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div>
              <h3 class="mb-2 text-sm font-semibold text-white">Pemakaian customer · snapshot terbaru</h3>
              <div v-if="history.latestCustomers.length" class="max-h-52 space-y-2 overflow-auto">
                <div v-for="customer in history.latestCustomers" :key="customer.id" class="flex items-center justify-between gap-3 border-t border-slate-800 pt-2 text-xs">
                  <span class="truncate text-slate-300">{{ customer.customer }}</span>
                  <span class="shrink-0 tabular-nums text-white">{{ formatNumber(customer.occupiedSlots) }} slot</span>
                </div>
              </div>
              <p v-else class="border-t border-slate-800 pt-3 text-xs text-slate-500">Belum ada data pemakaian customer pada snapshot terbaru.</p>
            </div>
          </div>
        </section>

        <section class="flex flex-col gap-4 border-y border-slate-200 py-3 sm:flex-row sm:items-center">
          <div class="flex flex-wrap items-center gap-2" aria-label="Filter status">
            <span class="mr-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Filter</span>
            <button v-for="filter in statusFilters" :key="filter.value" type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold transition" :class="statusFilter === filter.value ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100'" @click="statusFilter = filter.value">
              {{ filter.label }}
            </button>
          </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="chart-title">
          <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
            <div>
              <h2 id="chart-title" class="text-lg font-semibold text-white">Perbandingan Kapasitas per Cold Storage</h2>
              <p class="mt-1 text-xs text-slate-500">Pilih batang untuk melihat slot yang terisi.</p>
            </div>
            <div class="flex items-center gap-4 text-xs text-slate-400">
              <span class="inline-flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-sm bg-sky-300"></i>Slot Terpakai</span>
              <span class="inline-flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-sm bg-slate-700"></i>Sisa Kapasitas</span>
            </div>
          </div>

          <div v-if="filteredStorages.length" class="overflow-x-auto">
            <div class="relative grid min-w-155 grid-flow-col auto-cols-[minmax(42px,1fr)] items-end gap-3 px-2 pb-2 pt-5" :style="{ height: '255px' }">
              <div class="pointer-events-none absolute inset-x-2 top-5 bottom-7 flex flex-col justify-between border-b border-slate-700/70">
                <div v-for="tick in 4" :key="tick" class="border-t border-dashed border-slate-800"></div>
              </div>
              <button v-for="storage in filteredStorages" :key="storage.id" type="button" class="group relative z-10 flex h-full min-w-0 flex-col items-center justify-end gap-2 rounded-sm px-1 text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300" :aria-label="`${storage.name}, ${storage.used} dari ${storage.capacity} slot terpakai`" :title="customerBreakdownTitle(storage)" @click="openStorage(storage)">
                <span class="text-[10px] tabular-nums text-slate-400 transition group-hover:text-white">{{ storage.used }}/{{ storage.capacity }}</span>
                <div class="relative flex w-full max-w-10 flex-1 items-end justify-center">
                  <div class="relative w-full rounded-t-sm bg-slate-700/80 transition group-hover:bg-slate-600" :style="{ height: `${Math.max(8, storage.capacity / maxCapacity * 100)}%` }">
                    <div class="absolute inset-x-0 bottom-0 rounded-t-sm bg-sky-300 transition group-hover:bg-sky-200" :style="{ height: `${storage.occupancy}%` }"></div>
                  </div>
                </div>
                <span class="whitespace-nowrap text-[10px] font-medium text-slate-400 group-hover:text-white">{{ storage.name }}</span>
              </button>
            </div>
          </div>
          <p v-else class="py-12 text-center text-sm text-slate-500">Tidak ada Cold Storage yang cocok dengan filter.</p>

          <section class="mt-5 border-t border-slate-800 pt-4" aria-labelledby="cards-title">
            <div class="mb-3 flex items-end justify-between">
              <div>
                <h3 id="cards-title" class="text-lg font-semibold text-white">Cold Storage</h3>
                <p class="mt-1 text-xs text-slate-500">Klik kartu untuk melihat rincian slot terisi.</p>
              </div>
              <span class="text-xs text-slate-500">{{ filteredStorages.length }} lokasi</span>
            </div>
            <div v-if="filteredStorages.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
              <button v-for="storage in filteredStorages" :key="storage.id" type="button" class="flex h-72 flex-col border border-slate-800 bg-[#15181d] p-4 text-left transition hover:border-slate-600 hover:bg-[#191d23] focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300" :title="customerBreakdownTitle(storage)" @click="openStorage(storage)">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <h4 class="text-base font-semibold text-white">{{ storage.name }}</h4>
                    <p class="mt-0.5 text-[11px] text-slate-500">{{ storage.completeName }}</p>
                  </div>
                  <span class="rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-wide" :class="statusClasses(storage.status)">{{ statusLabel(storage.status) }}</span>
                </div>
                <div class="mt-4 flex items-end justify-between">
                  <span class="text-xs text-slate-400">Okupansi</span>
                  <span class="text-lg font-bold tabular-nums" :class="statusTextClass(storage.status)">{{ formatPercent(storage.occupancy) }}</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-800">
                  <div class="h-full rounded-full transition-all" :class="progressClasses(storage.status)" :style="{ width: `${storage.occupancy}%` }"></div>
                </div>
                <div class="mt-2 flex justify-between text-xs tabular-nums text-slate-400">
                  <span>{{ formatNumber(storage.used) }} / {{ formatNumber(storage.capacity) }} slot</span>
                  <span>{{ formatNumber(storage.available) }} tersedia</span>
                </div>
                <div class="mt-auto border-t border-slate-800 pt-2.5">
                  <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Customer per CS</p>
                  <div v-if="storage.customerOccupancy?.length" class="space-y-1">
                    <div v-for="customer in storage.customerOccupancy.slice(0, 3)" :key="customer.customer" class="flex items-center justify-between gap-2 text-[11px]">
                      <span class="flex min-w-0 items-center gap-1.5 text-slate-400" :title="customer.customer"><i class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: customerColor(customer.customer) }" aria-hidden="true"></i><span class="truncate">{{ customer.customer }}</span></span>
                      <span class="shrink-0 font-semibold tabular-nums text-sky-300">{{ formatPercent(customer.percentage) }}</span>
                    </div>
                    <p v-if="storage.customerOccupancy.length > 3" class="pt-0.5 text-[10px] font-medium text-slate-500">+{{ storage.customerOccupancy.length - 3 }} customer</p>
                  </div>
                  <p v-else class="text-[11px] text-slate-500">Belum ada customer pada slot terisi.</p>
                </div>
              </button>
            </div>
            <p v-else class="border border-slate-800 bg-[#12151a] py-8 text-center text-sm text-slate-500">Tidak ada Cold Storage yang cocok dengan filter.</p>
          </section>

          <section class="mt-5 border-t border-slate-200 pt-4" aria-labelledby="customer-share-title">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
              <div>
                <h3 id="customer-share-title" class="text-base font-semibold text-white">Okupansi per Customer</h3>
                <p class="mt-1 text-xs text-slate-500">Porsi slot customer dibanding total kapasitas seluruh Cold Storage.</p>
              </div>
              <span class="text-xs tabular-nums text-slate-500">{{ formatNumber(totals.capacity) }} total slot</span>
            </div>
            <div v-if="customerOccupancy.length" class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
              <div v-for="customer in customerOccupancy" :key="customer.customer" class="min-w-0">
                <div class="mb-1.5 flex items-center justify-between gap-3 text-xs">
                  <span class="flex min-w-0 items-center gap-2 truncate font-medium text-slate-300" :title="customer.customer"><i class="h-2.5 w-2.5 shrink-0 rounded-full ring-1 ring-white/10" :style="{ backgroundColor: customerColor(customer.customer) }" aria-hidden="true"></i>{{ customer.customer }}</span>
                  <span class="shrink-0 text-right tabular-nums text-slate-400">{{ formatNumber(customer.occupiedSlots) }} slot · <strong class="text-sky-300">{{ formatPercent(customer.percentage) }}</strong></span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-slate-800">
                  <div class="h-full rounded-full bg-sky-300" :style="{ width: `${Math.min(100, customer.percentage)}%` }"></div>
                </div>
              </div>
            </div>
            <p v-else class="py-5 text-center text-sm text-slate-500">Belum ada slot terisi yang memiliki customer/owner.</p>
          </section>
        </section>

        <p v-if="!storages.length" class="border border-amber-900/70 bg-amber-950/30 px-4 py-3 text-sm text-amber-200">
          Master location Odoo belum memiliki lokasi internal di bawah root Cold Storage (CS).
        </p>
      </div>

      <div v-if="selectedStorage" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 p-0 backdrop-blur-sm sm:items-center sm:p-5" @click.self="selectedStorage = null" @keydown.esc="selectedStorage = null">
        <section role="dialog" aria-modal="true" :aria-label="`Rincian ${selectedStorage.name}`" class="flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-xl border border-slate-200 bg-white shadow-2xl sm:rounded-xl">
          <header class="flex shrink-0 items-start justify-between border-b border-slate-800 px-5 py-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-widest text-sky-300">Rincian lokasi</p>
              <h2 class="mt-1 text-xl font-bold text-white">{{ selectedStorage.name }}</h2>
              <p class="mt-1 text-xs text-slate-500">{{ selectedStorage.completeName }}</p>
            </div>
            <button type="button" aria-label="Tutup rincian" class="rounded-md border border-slate-200 px-2.5 py-1.5 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900" @click="selectedStorage = null">Tutup</button>
          </header>
          <div class="shrink-0 border-b border-slate-800 p-4 sm:px-5 sm:py-4">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
              <article class="border-l-2 border-slate-500 bg-[#171c22] px-3 py-2.5">
                <p class="text-[11px] text-slate-500">Total Slot</p>
                <p class="mt-1 text-lg font-bold tabular-nums text-white">{{ formatNumber(selectedStorage.capacity) }}</p>
              </article>
              <article class="border-l-2 border-sky-400 bg-[#171c22] px-3 py-2.5">
                <p class="text-[11px] text-slate-500">Terpakai</p>
                <p class="mt-1 text-lg font-bold tabular-nums text-sky-300">{{ formatNumber(selectedStorage.used) }}</p>
              </article>
              <article class="border-l-2 border-emerald-400 bg-[#171c22] px-3 py-2.5">
                <p class="text-[11px] text-slate-500">Tersedia</p>
                <p class="mt-1 text-lg font-bold tabular-nums text-emerald-300">{{ formatNumber(selectedStorage.available) }}</p>
              </article>
              <article class="border-l-2 bg-[#171c22] px-3 py-2.5" :class="selectedStorage.status === 'high' ? 'border-red-400' : selectedStorage.status === 'normal' ? 'border-blue-400' : 'border-emerald-400'">
                <p class="text-[11px] text-slate-500">Okupansi</p>
                <p class="mt-1 text-lg font-bold tabular-nums" :class="statusTextClass(selectedStorage.status)">{{ formatPercent(selectedStorage.occupancy) }}</p>
              </article>
            </div>
          </div>
          <div class="shrink-0 border-b border-slate-800 px-4 py-3 sm:px-5">
            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter status slot">
              <span class="mr-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Tampilkan</span>
              <button v-for="filter in slotFilters" :key="filter.value" type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold transition" :class="slotFilter === filter.value ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100'" @click="slotFilter = filter.value">
                {{ filter.label }} <span class="ml-1 tabular-nums opacity-75">{{ slotFilterCounts[filter.value] }}</span>
              </button>
            </div>
          </div>
          <div v-if="visibleSelectedSlots.length" class="flex min-h-0 flex-1 flex-col">
            <div class="shrink-0 bg-[#11151a] px-4 pt-3 sm:px-5">
              <table class="w-full table-fixed border-collapse text-left text-[11px] uppercase tracking-wide text-slate-400">
                <colgroup><col class="w-[55%]"><col class="w-[22.5%]"><col class="w-[22.5%]"></colgroup>
                <thead><tr>
                  <th class="border-b border-slate-800 bg-[#171c22] px-3 py-2">Slot</th>
                  <th class="border-b border-slate-800 bg-[#171c22] px-3 py-2 text-right">Qty On Hand</th>
                  <th class="border-b border-slate-800 bg-[#171c22] px-3 py-2 text-right">Reserved</th>
                </tr></thead>
              </table>
            </div>
            <div class="min-h-0 flex-1 overflow-auto px-4 pb-4 sm:px-5 sm:pb-5">
              <table class="w-full table-fixed border-collapse text-left text-sm">
                <colgroup><col class="w-[55%]"><col class="w-[22.5%]"><col class="w-[22.5%]"></colgroup>
                <tbody>
                  <tr v-for="slot in visibleSelectedSlots" :key="slot.id" class="border-t border-slate-800 text-slate-200">
                    <td class="truncate px-3 py-2"><span class="font-medium text-white">{{ slot.name }}</span><span v-if="slot.completeName !== slot.name" class="ml-2 text-xs text-slate-500">{{ slot.completeName }}</span></td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ formatNumber(slot.quantity) }}</td>
                    <td class="px-3 py-2 text-right tabular-nums text-slate-400">{{ formatNumber(slot.reserved) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <p v-else class="flex-1 py-12 text-center text-sm text-slate-500">{{ slotFilter === 'unused' ? 'Tidak ada slot kosong pada lokasi ini.' : 'Tidak ada slot terpakai pada lokasi ini.' }}</p>
        </section>
      </div>
    </main>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  storages: { type: Array, default: () => [] },
  customerOccupancy: { type: Array, default: () => [] },
  updatedAt: { type: String, default: '' },
  history: {
    type: Object,
    default: () => ({ snapshots: [], latestCustomers: [], startDate: '', endDate: '' }),
  },
});

const statusFilter = ref('all');
const historyPeriod = ref('week');
const selectedStorage = ref(null);
const slotFilter = ref('used');
const refreshing = ref(false);

const statusFilters = [
  { value: 'all', label: 'Semua Unit' },
  { value: 'high', label: 'Tinggi (≥85%)' },
  { value: 'normal', label: 'Normal (50–84%)' },
  { value: 'low', label: 'Rendah (<50%)' },
];

const customerColors = [
  '#38bdf8', '#34d399', '#fbbf24', '#fb7185', '#a78bfa',
  '#2dd4bf', '#f97316', '#60a5fa', '#e879f9', '#a3e635',
];

const slotFilters = [
  { value: 'used', label: 'Terpakai' },
  { value: 'unused', label: 'Belum terpakai' },
  { value: 'all', label: 'Semua' },
];

const historyPeriods = [
  { value: 'week', label: '1 Minggu' },
  { value: 'month', label: '1 Bulan' },
  { value: 'year', label: '1 Tahun' },
];
const storages = computed(() => props.storages || []);
const customerOccupancy = computed(() => props.customerOccupancy || []);
const history = computed(() => props.history || { snapshots: [], latestCustomers: [] });
const visibleHistorySnapshots = computed(() => {
  const days = historyPeriod.value === 'month' ? 30 : historyPeriod.value === 'year' ? 365 : 7;
  return history.value.snapshots.slice(-days);
});
const historyComparison = computed(() => {
  const snapshots = visibleHistorySnapshots.value;
  if (!snapshots.length) return null;

  const first = snapshots[0];
  const last = snapshots[snapshots.length - 1];
  return {
    first,
    last,
    usedChange: Number(last.used || 0) - Number(first.used || 0),
    occupancyChange: Number(last.occupancy || 0) - Number(first.occupancy || 0),
  };
});
const filteredStorages = computed(() => statusFilter.value === 'all'
  ? storages.value
  : storages.value.filter((storage) => storage.status === statusFilter.value));
const visibleSelectedSlots = computed(() => {
  if (!selectedStorage.value) return [];
  if (slotFilter.value === 'used') return selectedStorage.value.slots.filter((slot) => Number(slot.quantity) > 0);
  if (slotFilter.value === 'unused') return selectedStorage.value.slots.filter((slot) => Number(slot.quantity) <= 0);
  return selectedStorage.value.slots;
});
const slotFilterCounts = computed(() => {
  const slots = selectedStorage.value?.slots || [];
  const used = slots.filter((slot) => Number(slot.quantity) > 0).length;
  return { used, unused: slots.length - used, all: slots.length };
});
const maxCapacity = computed(() => Math.max(1, ...filteredStorages.value.map((storage) => Number(storage.capacity || 0))));
const totals = computed(() => {
  const capacity = storages.value.reduce((sum, storage) => sum + Number(storage.capacity || 0), 0);
  const used = storages.value.reduce((sum, storage) => sum + Number(storage.used || 0), 0);
  return {
    capacity,
    used,
    available: Math.max(0, capacity - used),
    occupancy: capacity > 0 ? used / capacity * 100 : 0,
    critical: storages.value.filter((storage) => storage.status === 'high').length,
  };
});

function formatNumber(value) {
  return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
}

function formatPercent(value) {
  return `${Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 })}%`;
}

function customerColor(customerName) {
  let hash = 0;
  for (const character of String(customerName || '').toLowerCase()) {
    hash = (hash * 31 + character.codePointAt(0)) >>> 0;
  }

  return customerColors[hash % customerColors.length];
}

function statusLabel(status) {
  return status === 'high' ? 'Kritis' : status === 'normal' ? 'Normal' : 'Tersedia';
}

function statusClasses(status) {
  return status === 'high'
    ? 'bg-red-50 text-red-700'
    : status === 'normal'
      ? 'bg-blue-50 text-blue-700'
      : 'bg-emerald-50 text-emerald-700';
}

function statusTextClass(status) {
  return status === 'high' ? 'text-red-700' : status === 'normal' ? 'text-blue-700' : 'text-emerald-700';
}

function progressClasses(status) {
  return status === 'high' ? 'bg-red-400' : status === 'normal' ? 'bg-blue-400' : 'bg-emerald-400';
}

function customerBreakdownTitle(storage) {
  const customers = storage.customerOccupancy || [];
  if (customers.length === 0) {
    return `${storage.name}\nBelum ada stok dengan customer/owner pada unit ini.`;
  }

  return [
    `${storage.name} · Customer memakai kapasitas unit`,
    ...customers.map((customer) => `${customer.customer}: ${formatPercent(customer.percentage)} (${formatNumber(customer.occupiedSlots)} slot)`),
  ].join('\n');
}

function openStorage(storage) {
  selectedStorage.value = storage;
  slotFilter.value = 'used';
}

function refreshData() {
  refreshing.value = true;
  router.reload({
    only: ['storages', 'customerOccupancy', 'updatedAt'],
    preserveScroll: true,
    onFinish: () => { refreshing.value = false; },
  });
}
</script>

<style scoped>
:deep(.text-white:not(button)) {
  color: #0f172a;
}

:deep(.text-slate-100),
:deep(.text-slate-200),
:deep(.text-slate-300) {
  color: #334155;
}

:deep(.text-slate-400) {
  color: #64748b;
}

:deep(.text-sky-300) {
  color: #0369a1;
}

:deep(.text-emerald-300) {
  color: #047857;
}

:deep(.text-red-300) {
  color: #b91c1c;
}

:deep(.text-blue-300) {
  color: #1d4ed8;
}

:deep(.text-violet-300) {
  color: #6d28d9;
}

:deep(.text-amber-300) {
  color: #b45309;
}

:deep([class~="bg-[#15181d]"]) {
  background-color: #f8fafc;
}

:deep([class~="bg-[#12151a]"]),
:deep([class~="bg-[#11151a]"]) {
  background-color: #fff;
}

:deep([class~="bg-[#171c22]"]) {
  background-color: #f1f5f9;
}

:deep(.bg-slate-900) {
  background-color: #fff;
}

:deep(.bg-slate-800) {
  background-color: #e2e8f0;
}

:deep(.bg-slate-700) {
  background-color: #cbd5e1;
}

:deep(.border-slate-800) {
  border-color: #e2e8f0;
}

:deep(.border-slate-700) {
  border-color: #cbd5e1;
}

:deep(.hover\:bg-slate-800:hover) {
  background-color: #f1f5f9;
}

:deep(.hover\:bg-\[\#191d23\]:hover) {
  background-color: #f8fafc;
}
</style>