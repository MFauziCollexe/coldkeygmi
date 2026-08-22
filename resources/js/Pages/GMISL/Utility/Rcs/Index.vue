<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Tally</h2>
          <p class="text-sm text-slate-400">Daftar data tally / Add PO.</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            v-if="props.canDeleteTally"
            type="button"
            @click="deleteTally"
            :disabled="!selectedId && !hasCheckedItems"
            class="inline-flex items-center justify-center rounded bg-rose-600 px-4 py-2 text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Delete Tally
          </button>
          <button
            v-if="props.canDeletePo"
            type="button"
            @click="deletePo"
            :disabled="!selectedId"
            class="inline-flex items-center justify-center rounded bg-rose-700 px-4 py-2 text-white hover:bg-rose-800 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Delete PO
          </button>
          <button
            v-if="props.canAddTally"
            type="button"
            @click="openTallyModal"
            :disabled="!selectedId"
            class="inline-flex items-center justify-center rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Add Tally
          </button>
          <button
            v-if="props.canAddPo"
            type="button"
            @click="openModal"
            class="inline-flex items-center justify-center rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
          >
            Add PO
          </button>
          <button
            v-if="props.canApprove"
            type="button"
            :disabled="!hasCheckedItems"
            @click="approveTally"
            class="inline-flex items-center justify-center rounded bg-sky-600 px-4 py-2 text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Approve
          </button>
          <button
            type="button"
            :disabled="!hasCheckedItems"
            @click="handlePrintClick"
            class="inline-flex items-center justify-center rounded bg-slate-600 px-4 py-2 text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Print
          </button>
        </div>
      </div>

      <div
        v-if="flashMessage"
        class="mb-4 rounded border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800"
      >
        {{ flashMessage }}
      </div>

      <div v-if="tallies.length === 0" class="rounded-md border border-slate-200 bg-white p-10 text-center text-slate-400 shadow-sm">
        Belum ada data tally. Klik Add PO untuk menambahkan.
      </div>

      <div v-else class="overflow-x-auto rounded-md border border-slate-200 bg-white shadow-sm">
        <table class="w-full border-collapse text-sm">
          <thead>
            <tr class="bg-slate-50 text-left text-slate-500">
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">Pilih</th>
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">#</th>
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">PO</th>
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">Nopol</th>
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">Driver</th>
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">Customer</th>
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">Transaksi</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(tally, index) in tallies" :key="tally.id">
              <tr
                class="text-slate-800 hover:bg-slate-50 cursor-pointer"
                @click="toggleRow('po-' + tally.id)"
              >
                <td class="border-b border-slate-100 px-4 py-2 text-center" @click.stop>
                  <input
                    type="checkbox"
                    :checked="selectedId === tally.id || isPoChecked(tally.id)"
                    @change="toggleSelect(tally.id)"
                    class="h-4 w-4 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                  />
                </td>
                <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ index + 1 }}</td>
                <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2 font-medium">
                  <span class="mr-1 text-xs text-slate-400">{{ isRowExpanded('po-' + tally.id) ? '▾' : '▸' }}</span>
                  {{ tally.po || '-' }}
                </td>
                <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ tally.nopol || '-' }}</td>
                <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ tally.driver || '-' }}</td>
                <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ tally.customer?.name || '-' }}</td>
                <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ tally.transaksi || '-' }}</td>
              </tr>

              <tr v-if="isRowExpanded('po-' + tally.id)">
                <td colspan="7" class="border-b border-slate-200 bg-slate-50/50 px-4 py-3">
                  <div v-if="getItemGroups(tally.id).length === 0" class="py-2 text-center text-sm text-slate-400">
                    Belum ada data tally untuk PO ini.
                  </div>
                  <div v-else class="overflow-auto rounded-md border border-slate-200 bg-white">
                    <table class="w-full border-collapse text-xs">
                      <thead>
                        <tr class="bg-slate-100 text-left text-slate-500">
                          <th class="whitespace-nowrap border-b border-slate-200 px-3 py-1.5 font-semibold">No Tally</th>
                          <th class="whitespace-nowrap border-b border-slate-200 px-3 py-1.5 font-semibold">Nama Item</th>
                          <th class="whitespace-nowrap border-b border-slate-200 px-3 py-1.5 font-semibold text-right">Total KG</th>
                          <th class="whitespace-nowrap border-b border-slate-200 px-3 py-1.5 font-semibold text-center">Total Pallet</th>
                          <th class="whitespace-nowrap border-b border-slate-200 px-3 py-1.5 font-semibold text-center">Status</th>
                          <th class="whitespace-nowrap border-b border-slate-200 px-3 py-1.5 font-semibold">Start Date</th>
                          <th class="whitespace-nowrap border-b border-slate-200 px-3 py-1.5 font-semibold">End Date</th>
                          <th class="whitespace-nowrap border-b border-slate-200 px-3 py-1.5 font-semibold">Checker</th>
                        </tr>
                      </thead>
                      <tbody>
                        <template v-for="(group, gi) in getItemGroups(tally.id)" :key="gi">
                          <tr
                            class="text-slate-700 hover:bg-slate-50 cursor-pointer"
                            @click="toggleRow('item-' + tally.id + '-' + gi)"
                          >
                            <td class="whitespace-nowrap border-b border-slate-100 px-3 py-1.5 font-medium" @click.stop>
                              <div class="flex items-center gap-1.5">
                                <input
                                  type="checkbox"
                                  :checked="isItemChecked(tally.id, gi)"
                                  @change="toggleItemCheck(tally.id, gi)"
                                  class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="font-bold text-slate-800">{{ group.entries[0]?.no_tally || '-' }}</span>
                              </div>
                            </td>
                            <td class="whitespace-nowrap border-b border-slate-100 px-3 py-1.5 font-medium">
                              <span class="mr-1 text-xs text-slate-400">{{ isRowExpanded('item-' + tally.id + '-' + gi) ? '▾' : '▸' }}</span>
                              {{ group.item }}
                            </td>
                            <td class="whitespace-nowrap border-b border-slate-100 px-3 py-1.5 text-right font-semibold">
                              {{ Number(group.totalKg).toFixed(2) }}
                            </td>
                            <td class="whitespace-nowrap border-b border-slate-100 px-3 py-1.5 text-center">
                              <span class="inline-flex min-w-[1.5rem] justify-center rounded border border-slate-300 bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-700">
                                {{ group.palletCount }}
                              </span>
                            </td>
                            <td class="whitespace-nowrap border-b border-slate-100 px-3 py-1.5 text-center">
                              <span
                                v-if="group.isFinish"
                                class="inline-flex items-center rounded border border-emerald-400/40 bg-emerald-500/20 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700"
                              >
                                Selesai
                              </span>
                              <span
                                v-else
                                class="inline-flex items-center rounded border border-amber-400/40 bg-amber-500/20 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700"
                              >
                                Draft
                              </span>
                            </td>
                            <td class="whitespace-nowrap border-b border-slate-100 px-3 py-1.5 text-xs text-slate-500">
                              {{ group.startdate ? formatDate(group.startdate) : '-' }}
                            </td>
                            <td class="whitespace-nowrap border-b border-slate-100 px-3 py-1.5 text-xs text-slate-500">
                              {{ group.enddate ? formatDate(group.enddate) : '-' }}
                            </td>
                            <td class="whitespace-nowrap border-b border-slate-100 px-3 py-1.5 text-xs text-slate-500">
                              {{ group.entries[0]?.checker_name || '-' }}
                            </td>
                          </tr>
                          <tr v-if="isRowExpanded('item-' + tally.id + '-' + gi)">
                            <td colspan="8" class="border-b border-slate-100 bg-slate-50/30 px-3 py-2">
                              <table class="w-full border-collapse text-xs">
                                <thead>
                                  <tr class="text-left text-slate-400">
                                    <th class="whitespace-nowrap px-2 py-1 font-semibold text-center">No</th>
                                    <th class="whitespace-nowrap px-2 py-1 font-semibold">Pallet</th>
                                    <th class="whitespace-nowrap px-2 py-1 font-semibold text-right">KG</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <template v-for="(pg, pi) in groupByPallet(group.entries)" :key="'pg-' + pi">
                                    <tr
                                      v-for="(entry, ei) in pg.entries"
                                      :class="getDetailRowBg(pi, ei, group.entries)"
                                    >
                                      <td class="whitespace-nowrap px-2 py-1 text-center">{{ ei + 1 }}</td>
                                      <td class="whitespace-nowrap px-2 py-1">{{ entry.pallet }}</td>
                                      <td class="whitespace-nowrap px-2 py-1 text-right">{{ Number(entry.kg).toFixed(2) }}</td>
                                    </tr>
                                    <tr class="bg-green-50 font-semibold text-green-800">
                                      <td colspan="3" class="whitespace-nowrap px-2 py-1 text-right">Total Pallet {{ pg.pallet }} — {{ Number(pg.totalKg).toFixed(2) }} KG</td>
                                    </tr>
                                  </template>
                                </tbody>
                              </table>
                            </td>
                          </tr>
                        </template>
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Add PO -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
      @click.self="closeModal"
    >
      <div class="w-full max-w-md overflow-hidden rounded-xl border border-slate-300 bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between gap-4">
          <h3 class="text-base font-semibold text-black">Tambah PO</h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="closeModal">
            &times;
          </button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="mb-1 bg-white block text-sm font-medium text-slate-700" for="po">PO</label>
            <input
              id="po"
              v-model="form.po"
              type="text"
              required
              placeholder=""
              @input="form.po = $event.target.value.toUpperCase()"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 uppercase focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
            <p v-if="form.errors.po" class="mt-1 text-xs text-rose-600">{{ form.errors.po }}</p>
          </div>

          <div>
            <label class="mb-1 bg-white block text-sm font-medium text-slate-700" for="nopol">Nopol</label>
            <input
              id="nopol"
              v-model="form.nopol"
              type="text"
              required
              placeholder=""
              @input="form.nopol = $event.target.value.toUpperCase()"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 uppercase focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
            <p v-if="form.errors.nopol" class="mt-1 text-xs text-rose-600">{{ form.errors.nopol }}</p>
          </div>

          <div>
            <label class="mb-1 bg-white block text-sm font-medium text-slate-700" for="driver">Driver</label>
            <input
              id="driver"
              v-model="form.driver"
              type="text"
              required
              placeholder=""
              @input="form.driver = $event.target.value.toUpperCase()"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 uppercase focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
            <p v-if="form.errors.driver" class="mt-1 text-xs text-rose-600">{{ form.errors.driver }}</p>
          </div>

          <div>
            <label class="mb-1 bg-white block text-sm font-medium text-slate-700" for="customer_id">Customer</label>
            <select
              id="customer_id"
              v-model="form.customer_id"
              required
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="" disabled>Pilih Customer</option>
              <option v-for="customer in customers" :key="customer.customers_id_odoo" :value="customer.customers_id_odoo">
                {{ customer.name }}
              </option>
            </select>
            <p v-if="form.errors.customer_id" class="mt-1 text-xs text-rose-600">{{ form.errors.customer_id }}</p>
          </div>

          <div>
            <label class="mb-1 bg-white block text-sm font-medium text-slate-700" for="transaksi">Transaksi</label>
            <select
              id="transaksi"
              v-model="form.transaksi"
              required
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="" disabled>Pilih Transaksi</option>
              <option value="Inbound">Inbound</option>
              <option value="Outbound">Outbound</option>
            </select>
            <p v-if="form.errors.transaksi" class="mt-1 text-xs text-rose-600">{{ form.errors.transaksi }}</p>
          </div>

          <div class="flex items-center justify-end gap-2">
            <button
              type="button"
              class="rounded bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-300"
              @click="closeModal"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {{ form.processing ? 'Menyimpan...' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Add Tally -->
    <div
      v-if="showTallyModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
    >
      <div class="max-h-[90vh] w-full max-w-2xl overflow-hidden rounded-xl border border-slate-300 bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between gap-4">
          <h3 class="text-base font-semibold text-black">
            Add Tally
            <span v-if="selectedPo" class="ml-2 text-sm font-normal text-slate-400">
              (PO {{ selectedPo.po }} - {{ selectedPo.customer?.name || '-' }})
            </span>
          </h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="closeTallyModal">
            &times;
          </button>
        </div>

        <form @submit.prevent="addEntry" class="space-y-4">
          <div>
            <label class="mb-1 block bg-white text-sm font-medium text-slate-700" for="item">Item</label>
            <select
              id="item"
              v-model="tallyForm.item"
              required
              :disabled="itemLocked"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
            >
              <option value="" disabled>Pilih Item</option>
              <option v-for="product in filteredProducts" :key="product.id" :value="product.internal_reference">
                {{ product.internal_reference }} - {{ product.name }}
              </option>
            </select>
          </div>

          <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:gap-4">
            <div class="flex-1">
              <label class="mb-1 block bg-white text-sm font-medium text-slate-700" for="pallet">Pallet</label>
              <input
                id="pallet"
                :value="currentPallet"
                type="number"
                min="1"
                readonly
                class="w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div class="flex-1">
              <label class="mb-1 block bg-white text-sm font-medium text-slate-700" for="kg">KG</label>
              <input
                id="kg"
                v-model="tallyForm.kg"
                type="number"
                min="0"
                step="0.01"
                required
                placeholder=""
                class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <button
              type="submit"
              :disabled="!tallyForm.item"
              class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              OK
            </button>
          </div>
        </form>

        <div class="mt-6">
          <h4 class="mb-2 text-sm font-semibold text-slate-700">List Inputan KG — Pallet {{ currentPallet }}</h4>
          <div v-if="currentEntries.length === 0" class="rounded-md border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400">
            Belum ada inputan KG.
          </div>
          <div v-else class="max-h-64 overflow-y-auto rounded-md border border-slate-200">
            <table class="w-full border-collapse text-sm">
              <thead class="sticky top-0">
                <tr class="bg-slate-50 text-left text-slate-500">
                  <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">#</th>
                  <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">KG</th>
                  <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(entry, index) in currentEntries" :key="index" class="text-slate-800 hover:bg-slate-50">
                  <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ index + 1 }}</td>
                  <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ Number(entry.kg).toFixed(2) }}</td>
                  <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2 text-center">
                    <button type="button" @click="removeEntry(index)" class="text-red-500 hover:text-red-700">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="bg-slate-50 font-semibold text-slate-700">
                  <td class="border-t border-slate-200 px-4 py-2 text-right">Total KG</td>
                  <td class="whitespace-nowrap border-t border-slate-200 px-4 py-2">{{ totalKg }}</td>
                  <td class="border-t border-slate-200 px-4 py-2"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2">
          <button
            type="button"
            class="rounded bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-300"
            @click="closeTallyModal"
          >
            Close
          </button>
          <button
            type="button"
            :disabled="saving"
            class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="finishTally"
          >
            {{ saving ? 'Menyimpan...' : 'Finish' }}
          </button>
          <button
            type="button"
            :disabled="currentPallet <= 1"
            class="rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="prevPallet"
          >
            Prev
          </button>
          <button
            type="button"
            :disabled="isNextDisabled"
            class="rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
            @click="nextPallet"
          >
            Next
          </button>
          <button
            type="button"
            :disabled="!hasUnsaved || saving"
            class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="saveEntries"
          >
            {{ saving ? 'Menyimpan...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Summary -->
    <div
      v-if="showSummary && summaryData"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
    >
      <div class="w-full max-w-md overflow-hidden rounded-xl border border-slate-300 bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-slate-900">Tally Selesai</h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="showSummary = false">
            &times;
          </button>
        </div>

        <div class="space-y-3 text-sm">
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="font-medium text-slate-500">PO</span>
            <span class="text-slate-900">{{ summaryData.po }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="font-medium text-slate-500">Customer</span>
            <span class="text-slate-900">{{ summaryData.customer }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="font-medium text-slate-500">Nopol</span>
            <span class="text-slate-900">{{ summaryData.nopol }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="font-medium text-slate-500">Driver</span>
            <span class="text-slate-900">{{ summaryData.driver }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="font-medium text-slate-500">Item</span>
            <span class="text-slate-900">{{ summaryData.item }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="font-medium text-slate-500">Qty Total</span>
            <span class="text-slate-900">{{ summaryData.totalPallets }} Pallet</span>
          </div>
          <div class="flex justify-between pb-2">
            <span class="font-medium text-slate-500">KG's</span>
            <span class="text-lg font-bold text-slate-900">{{ Number(summaryData.totalKg).toFixed(2) }} KG</span>
          </div>
        </div>

        <div class="mt-5 flex justify-end">
          <button
            type="button"
            class="rounded bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            @click="showSummary = false"
          >
            OK
          </button>
        </div>
      </div>
    </div>
    <!-- Modal Confirm Delete Tally -->
    <div
      v-if="showDeleteConfirm"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
    >
      <div class="w-full max-w-md overflow-hidden rounded-xl border border-slate-300 bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-slate-900">Hapus Tally</h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="cancelDelete">
            &times;
          </button>
        </div>

        <div class="text-sm text-slate-600">
          <p class="mb-3">Yakin ingin menghapus data tally berikut?</p>
          <div class="max-h-40 overflow-y-auto rounded-md border border-slate-200 bg-slate-50 p-3">
            <template v-for="(items, poId) in deleteConfirmGroups" :key="poId">
              <div class="mb-2 last:mb-0">
                <p class="font-medium text-slate-800">{{ getPoName(poId) }}</p>
                <ul class="ml-3 mt-1 list-disc text-slate-500">
                  <li v-if="items.length === 0">Semua item</li>
                  <li v-else v-for="item in items" :key="item">{{ item }}</li>
                </ul>
              </div>
            </template>
          </div>
        </div>

        <div class="mt-5 flex justify-end gap-2">
          <button
            type="button"
            class="rounded bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-300"
            @click="cancelDelete"
          >
            Batal
          </button>
          <button
            type="button"
            class="rounded bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700"
            @click="confirmDeleteTally"
          >
            Hapus
          </button>
        </div>
      </div>
    </div>
    <!-- Modal Finish Info -->
    <div
      v-if="showFinishMsg"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
    >
      <div class="w-full max-w-sm overflow-hidden rounded-xl border border-slate-300 bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-slate-900">Tally Selesai</h3>
        </div>

        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4">
          <p class="text-sm text-slate-700">Data tally untuk item ini sudah <span class="font-bold text-amber-700">Finish</span>.</p>
          <div class="mt-3 space-y-1.5 text-sm">
            <div class="flex gap-2">
              <span class="font-medium text-slate-500">PO</span>
              <span class="font-semibold text-slate-800">{{ finishMsgData.po }}</span>
            </div>
            <div class="flex gap-2">
              <span class="font-medium text-slate-500">Item</span>
              <span class="font-semibold text-slate-800">{{ finishMsgData.item }}</span>
            </div>
          </div>
          <p class="mt-3 text-sm text-slate-600">Silakan hubungi <span class="font-semibold">SPV</span> untuk membuka kembali statusnya.</p>
        </div>

        <div class="flex justify-end">
          <button
            type="button"
            class="rounded bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            @click="showFinishMsg = false"
          >
            Mengerti
          </button>
        </div>
      </div>
    </div>
    <!-- Modal Draft Info -->
    <div
      v-if="showDraftMsg"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
    >
      <div class="w-full max-w-sm overflow-hidden rounded-xl border border-slate-300 bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-sky-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-slate-900">Item Masih Draf</h3>
        </div>

        <div class="mb-5 rounded-lg border border-sky-200 bg-sky-50 p-4">
          <p class="text-sm text-slate-700">Item berikut masih berstatus <span class="font-bold text-sky-700">Draf</span> dan bisa diinputkan:</p>
          <div class="mt-3 space-y-1">
            <div
              v-for="(name, idx) in draftMsgItems"
              :key="idx"
              class="flex items-center gap-2 text-sm"
            >
              <span class="text-slate-400">{{ idx + 1 }}.</span>
              <span class="font-semibold text-slate-800">{{ name }}</span>
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <button
            type="button"
            class="rounded bg-sky-600 px-5 py-2 text-sm font-semibold text-white hover:bg-sky-700"
            @click="showDraftMsg = false"
          >
            Mengerti
          </button>
        </div>
      </div>
    </div>
    <!-- Modal Draft Print Warning -->
    <div
      v-if="showDraftPrintMsg"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
    >
      <div class="w-full max-w-sm overflow-hidden rounded-xl border border-slate-300 bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-slate-900">Item Masih Proses Checker</h3>
        </div>

        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4">
          <p class="text-sm text-slate-700">Item berikut masih berstatus <span class="font-bold text-amber-700">Draft</span> dan belum bisa di-print:</p>
          <div class="mt-3 space-y-1">
            <div
              v-for="(name, idx) in draftPrintItems"
              :key="idx"
              class="flex items-center gap-2 text-sm"
            >
              <span class="text-slate-400">{{ idx + 1 }}.</span>
              <span class="font-semibold text-slate-800">{{ name }}</span>
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <button
            type="button"
            class="rounded bg-amber-600 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-700"
            @click="showDraftPrintMsg = false"
          >
            Mengerti
          </button>
        </div>
      </div>
    </div>
    <!-- Modal Confirm Delete PO -->
    <div
      v-if="showDeletePoConfirm"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
    >
      <div class="w-full max-w-sm overflow-hidden rounded-xl border border-slate-300 bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-slate-900">Hapus PO</h3>
        </div>

        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4">
          <p class="text-sm text-slate-700">Yakin ingin menghapus PO ini beserta <span class="font-bold text-red-700">seluruh data tally</span>-nya?</p>
          <div v-if="selectedPo" class="mt-3 space-y-1.5 text-sm">
            <div class="flex gap-2">
              <span class="font-medium text-slate-500">PO</span>
              <span class="font-semibold text-slate-800">{{ selectedPo.po }}</span>
            </div>
            <div class="flex gap-2">
              <span class="font-medium text-slate-500">Customer</span>
              <span class="font-semibold text-slate-800">{{ selectedPo.customer?.name || '-' }}</span>
            </div>
            <div class="flex gap-2">
              <span class="font-medium text-slate-500">Nopol</span>
              <span class="font-semibold text-slate-800">{{ selectedPo.nopol || '-' }}</span>
            </div>
          </div>
          <p class="mt-3 text-xs text-red-600">Tindakan ini tidak dapat dibatalkan.</p>
        </div>

        <div class="flex justify-end gap-2">
          <button
            type="button"
            class="rounded bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-300"
            @click="showDeletePoConfirm = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="rounded bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700"
            @click="confirmDeletePo"
          >
            Hapus
          </button>
        </div>
      </div>
    </div>
    <!-- Modal Print Preview -->
    <div
      v-if="showPrintModal && printData"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
    >
      <div class="w-full max-w-lg overflow-hidden rounded-xl border border-slate-300 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
          <h3 class="text-lg font-semibold text-slate-900">Print Preview</h3>
          <button
            type="button"
            class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
            @click="showPrintModal = false"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          </button>
        </div>

        <div id="print-content" class="px-6 py-4 text-black">
          <h2 class="mb-4 text-center text-lg font-bold">TALLY SHEET</h2>
          <div class="mb-3 text-sm">
            <div class="info-row"><span class="font-bold">Checker :</span> {{ printData.checker }}</div>
            <div class="info-row"><span class="font-bold">Nopol :</span> {{ printData.nopol }}</div>
            <div class="info-row"><span class="font-bold">Customer :</span> {{ printData.customer }}</div>
            <div class="info-row"><span class="font-bold">Total KG :</span> {{ Number(printData.totalKg).toFixed(2) }}</div>
          </div>
          <div v-for="(item, idx) in printData.items" :key="idx" class="mb-4">
            <h3 class="mb-1 text-sm font-bold text-black">Item : {{ item.item }} <span class="font-normal text-slate-600">(Total: {{ Number(item.totalKg).toFixed(2) }} KG)</span></h3>
            <table class="w-full border-collapse text-sm">
              <thead>
                <tr>
                  <th class="border border-slate-300 bg-slate-100 px-3 py-1.5 text-left text-black">No Tally</th>
                  <th class="border border-slate-300 bg-slate-100 px-3 py-1.5 text-left text-black">Pallet</th>
                  <th class="border border-slate-300 bg-slate-100 px-3 py-1.5 text-right text-black">KG</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pallet in item.pallets" :key="pallet.pallet">
                  <td class="border border-slate-300 px-3 py-1.5 text-black text-xs">{{ pallet.no_tally }}</td>
                  <td class="border border-slate-300 px-3 py-1.5 text-black">{{ pallet.pallet }}</td>
                  <td class="border border-slate-300 px-3 py-1.5 text-right text-black">{{ Number(pallet.kg).toFixed(2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4">
          <button
            type="button"
            class="rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            @click="showPrintModal = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            @click="handlePrint"
          >
            Print
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const checkedItems = ref({});

const hasCheckedItems = computed(() => {
  return Object.values(checkedItems.value).some((items) => items && Object.values(items).some((v) => v));
});

const showDeleteConfirm = ref(false);
const deleteConfirmGroups = ref({});
const showDeletePoConfirm = ref(false);

function getPoName(poId) {
  const tally = tallies.value.find((t) => t.id === Number(poId));
  return tally ? `${tally.po} — ${tally.customer?.name || '-'}` : `PO #${poId}`;
}

function cancelDelete() {
  showDeleteConfirm.value = false;
  deleteConfirmGroups.value = {};
}

function isPoChecked(poId) {
  const items = checkedItems.value[poId];
  return !!(items && Object.values(items).some((v) => v));
}

function isItemChecked(poId, gi) {
  return checkedItems.value[poId]?.[gi] === true;
}

function toggleSelect(id) {
  if (selectedId.value === id) {
    selectedId.value = null;
    const newChecked = {};
    for (const [k, v] of Object.entries(checkedItems.value)) {
      if (String(k) !== String(id)) {
        newChecked[k] = { ...v };
      }
    }
    checkedItems.value = newChecked;
  } else {
    selectedId.value = id;
    const newChecked = {};
    for (const [k, v] of Object.entries(checkedItems.value)) {
      if (String(k) !== String(id)) {
        newChecked[k] = {};
      }
    }
    newChecked[id] = { ...(checkedItems.value[id] || {}) };
    checkedItems.value = newChecked;
  }
}

function toggleItemCheck(poId, gi) {
  const wasChecked = checkedItems.value[poId]?.[gi] === true;

  if (wasChecked) {
    const newChecked = {};
    for (const [k, v] of Object.entries(checkedItems.value)) {
      newChecked[k] = { ...v };
    }
    newChecked[poId][gi] = false;
    const anyLeft = Object.values(newChecked[poId]).some((v) => v);
    if (!anyLeft) {
      delete newChecked[poId];
      if (selectedId.value === poId) {
        selectedId.value = null;
      }
    }
    checkedItems.value = newChecked;
  } else {
    const newChecked = {};
    for (const [k, v] of Object.entries(checkedItems.value)) {
      if (String(k) !== String(poId)) {
        newChecked[k] = {};
      }
    }
    newChecked[poId] = { ...(checkedItems.value[poId] || {}) };
    newChecked[poId][gi] = true;
    checkedItems.value = newChecked;
    selectedId.value = poId;
  }
}

const props = defineProps({
  customers: {
    type: Array,
    default: () => [],
  },
  tallies: {
    type: Array,
    default: () => [],
  },
  products: {
    type: Array,
    default: () => [],
  },
  tallyMaxPallet: {
    type: Object,
    default: () => ({}),
  },
  finishedPoIds: {
    type: Array,
    default: () => [],
  },
  tallyData: {
    type: Object,
    default: () => ({}),
  },
  flash: {
    type: Object,
    default: () => ({}),
  },
  canAddPo: {
    type: Boolean,
    default: false,
  },
  canAddTally: {
    type: Boolean,
    default: false,
  },
  canDeletePo: {
    type: Boolean,
    default: false,
  },
  canDeleteTally: {
    type: Boolean,
    default: false,
  },
  canApprove: {
    type: Boolean,
    default: false,
  },
});

const customers = computed(() => props.customers || []);
const tallies = computed(() => props.tallies || []);
const products = computed(() => props.products || []);
const finishedPoIds = ref([...(props.finishedPoIds || [])]);
const flashMessage = computed(() => props.flash?.success || '');

const showModal = ref(false);
const selectedId = ref(null);
const showPrintModal = ref(false);
const printData = ref(null);
const showDraftPrintMsg = ref(false);
const draftPrintItems = ref([]);

const selectedPo = computed(() => tallies.value.find((tally) => tally.id === selectedId.value) || null);

const filteredProducts = computed(() => {
  if (!selectedPo.value) {
    return [];
  }
  const customerId = selectedPo.value.customer_id;
  return products.value.filter((product) => String(product.customer_id) === String(customerId));
});

const showTallyModal = ref(false);
const showSummary = ref(false);
const saving = ref(false);
const summaryData = ref(null);
const itemLocked = ref(false);
const tallyForm = ref({
  item: '',
  kg: '',
});
const palletEntries = ref({});
const currentPallet = ref(1);
const tallyStates = ref({});
const hasUnsaved = ref(false);
const deletedEntryIds = ref([]);
const expandedRows = reactive({});

function formatDate(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  const seconds = String(d.getSeconds()).padStart(2, '0');
  return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
}

function toggleRow(key) {
  expandedRows[key] = !expandedRows[key];
}

function isRowExpanded(key) {
  return expandedRows[key] === true;
}

function getTallyEntries(poId) {
  return props.tallyData[poId] || [];
}

function getItemGroups(poId) {
  const entries = props.tallyData[poId] || [];
  if (entries.length === 0) return [];
  const map = new Map();
  for (const entry of entries) {
    if (!map.has(entry.item)) {
      map.set(entry.item, { item: entry.item, entries: [], palletSet: new Set(), totalKg: 0, isFinish: false, startdate: null, enddate: null });
    }
    const g = map.get(entry.item);
    g.entries.push(entry);
    g.palletSet.add(entry.pallet);
    g.totalKg += Number(entry.kg);
    if (entry.is_finish) g.isFinish = true;
    if (entry.startdate && (!g.startdate || entry.startdate < g.startdate)) g.startdate = entry.startdate;
    if (entry.enddate && (!g.enddate || entry.enddate > g.enddate)) g.enddate = entry.enddate;
  }
  return Array.from(map.values()).map((g) => ({
    item: g.item,
    entries: g.entries,
    palletCount: g.palletSet.size,
    totalKg: g.totalKg,
    isFinish: g.isFinish,
    startdate: g.startdate,
    enddate: g.enddate,
  }));
}

function isPoFinished(poId) {
  return finishedPoIds.value.includes(poId);
}

function handlePrintClick() {
  const draftItems = [];
  const finishItems = [];

  for (const [poId, items] of Object.entries(checkedItems.value)) {
    if (!items) continue;
    const groups = getItemGroups(Number(poId));
    for (const [gi, checked] of Object.entries(items)) {
      if (!checked) continue;
      const group = groups[Number(gi)];
      if (!group) continue;
      if (group.isFinish) {
        finishItems.push({ poId: Number(poId), group });
      } else {
        draftItems.push(group.item);
      }
    }
  }

  if (draftItems.length > 0) {
    draftPrintItems.value = [...new Set(draftItems)];
    showDraftPrintMsg.value = true;
    return;
  }

  if (finishItems.length === 0) return;

  const itemMap = new Map();
  let totalKg = 0;
  let checker = '-';
  let nopol = '-';
  let customer = '-';

  for (const { poId, group } of finishItems) {
    const po = tallies.value.find((t) => t.id === poId);
    if (nopol === '-' && po) {
      nopol = po?.nopol || '-';
      customer = po?.customer?.name || '-';
    }
    if (!itemMap.has(group.item)) {
      itemMap.set(group.item, { item: group.item, pallets: [], totalKg: 0 });
    }
    const item = itemMap.get(group.item);
    for (const p of groupByPallet(group.entries)) {
      item.pallets.push({ pallet: p.pallet, kg: p.totalKg, no_tally: group.entries[0]?.no_tally || '-' });
    }
    item.totalKg += group.totalKg;
    totalKg += group.totalKg;
    if (checker === '-') checker = group.entries[0]?.checker_name || '-';
  }

  printData.value = { nopol, customer, totalKg, checker, items: Array.from(itemMap.values()) };
  showPrintModal.value = true;
}

function openPrintModal(tally) {
  const po = tallies.value.find((t) => t.id === tally.id);
  const groups = getItemGroups(tally.id);
  const items = [];
  let totalKg = 0;
  let checker = '-';
  for (const group of groups) {
    const pallets = groupByPallet(group.entries);
    const enrichedPallets = pallets.map(p => {
      const matchEntry = group.entries.find(e => e.pallet === p.pallet);
      return { ...p, no_tally: matchEntry?.no_tally || '-' };
    });
    items.push({ item: group.item, totalKg: group.totalKg, pallets: enrichedPallets });
    totalKg += group.totalKg;
    if (!checker || checker === '-') checker = group.entries[0]?.checker_name || '-';
  }
  printData.value = {
    nopol: po?.nopol || '-',
    customer: po?.customer?.name || '-',
    totalKg,
    checker,
    items,
  };
  showPrintModal.value = true;
}

function handlePrint() {
  if (!printData.value) return;

  import('jspdf').then(({ jsPDF }) => {
    const doc = new jsPDF();
    const data = printData.value;
    let y = 20;

    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('TALLY SHEET', 105, y, { align: 'center' });
    y += 12;

    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.text('Checker : ', 20, y);
    doc.setFont('helvetica', 'normal');
    doc.text(String(data.checker), 50, y);
    y += 7;

    doc.setFont('helvetica', 'bold');
    doc.text('Nopol : ', 20, y);
    doc.setFont('helvetica', 'normal');
    doc.text(String(data.nopol), 50, y);
    y += 7;

    doc.setFont('helvetica', 'bold');
    doc.text('Customer : ', 20, y);
    doc.setFont('helvetica', 'normal');
    doc.text(String(data.customer), 55, y);
    y += 7;

    doc.setFont('helvetica', 'bold');
    doc.text('Total KG : ', 20, y);
    doc.setFont('helvetica', 'normal');
    doc.text(Number(data.totalKg).toFixed(2), 55, y);
    y += 12;

    for (const item of data.items) {
      if (y > 260) { doc.addPage(); y = 20; }

      doc.setFontSize(12);
      doc.setFont('helvetica', 'bold');
      doc.text(`Item : ${item.item}  (Total: ${Number(item.totalKg).toFixed(2)} KG)`, 20, y);
      y += 8;

      const colX = [20, 75, 125];
      const colW = [55, 50, 45];
      const headers = ['No Tally', 'Pallet', 'KG'];

      doc.setFillColor(240, 240, 240);
      doc.rect(20, y, 150, 7, 'F');
      doc.setFontSize(10);
      doc.setFont('helvetica', 'bold');
      doc.text(headers[0], colX[0] + 2, y + 5);
      doc.text(headers[1], colX[1] + 2, y + 5);
      doc.text(headers[2], colX[2] + colW[2] - 2, y + 5, { align: 'right' });
      y += 7;

      doc.setFont('helvetica', 'normal');
      doc.setFontSize(10);
      for (const pallet of item.pallets) {
        if (y > 275) { doc.addPage(); y = 20; }
        doc.rect(20, y, colW[0], 6, 'S');
        doc.rect(colX[1], y, colW[1], 6, 'S');
        doc.rect(colX[2], y, colW[2], 6, 'S');
        doc.text(String(pallet.no_tally || '-'), colX[0] + 2, y + 4.5);
        doc.text(String(pallet.pallet), colX[1] + 2, y + 4.5);
        doc.text(Number(pallet.kg).toFixed(2), colX[2] + colW[2] - 2, y + 4.5, { align: 'right' });
        y += 6;
      }
      y += 8;
    }

    const fileName = `Tally_${data.nopol || 'Sheet'}_${new Date().toISOString().slice(0, 10)}.pdf`;
    doc.save(fileName);
  });
}

function groupByPallet(entries) {
  const map = new Map();
  for (const entry of entries) {
    const p = entry.pallet;
    if (!map.has(p)) {
      map.set(p, { pallet: p, entries: [], totalKg: 0 });
    }
    const pg = map.get(p);
    pg.entries.push(entry);
    pg.totalKg += Number(entry.kg);
  }
  return Array.from(map.values());
}

function getDetailRowBg(pi, ei, allEntries) {
  let flatIndex = 0;
  const groups = groupByPallet(allEntries);
  for (let g = 0; g < pi; g++) {
    flatIndex += groups[g].entries.length;
  }
  flatIndex += ei;
  return flatIndex % 2 === 0 ? 'bg-blue-50 text-slate-600' : 'bg-white text-slate-600';
}

const currentEntries = computed(() => palletEntries.value[currentPallet.value] || []);

const totalKg = computed(() => {
  const sum = currentEntries.value.reduce((acc, entry) => acc + Number(entry.kg), 0);
  return Number.isFinite(sum) ? sum : 0;
});

const summaryTotalPallets = computed(() => Object.keys(palletEntries.value).length);

const summaryTotalKg = computed(() => {
  let total = 0;
  for (const entries of Object.values(palletEntries.value)) {
    for (const entry of entries) {
      total += Number(entry.kg);
    }
  }
  return Number.isFinite(total) ? total : 0;
});

const isNextDisabled = computed(() => {
  return currentEntries.value.length === 0;
});

function getUnsavedEntries() {
  const all = [];
  for (const entries of Object.values(palletEntries.value)) {
    for (const entry of entries) {
      if (entry._new) {
        all.push(entry);
      }
    }
  }
  return all;
}

const showFinishMsg = ref(false);
const finishMsgData = ref({ po: '', item: '' });
const showDraftMsg = ref(false);
const draftMsgItems = ref([]);

function openTallyModal() {
  if (!selectedPo.value) {
    return;
  }
  const poId = selectedPo.value.id;
  const poChecked = checkedItems.value[poId];
  const checkedIndices = poChecked ? Object.keys(poChecked).filter((k) => poChecked[k]) : [];
  const hasCheckedItem = checkedIndices.length > 0;

  if (hasCheckedItem) {
    const groups = getItemGroups(poId);
    const checkedGroup = groups[Number(checkedIndices[0])];

    if (checkedGroup && checkedGroup.isFinish) {
      finishMsgData.value = { po: selectedPo.value.po, item: checkedGroup.item };
      showFinishMsg.value = true;
      return;
    }

    const serverEntries = props.tallyData[poId] || [];
    const itemEntries = serverEntries.filter((e) => checkedGroup && e.item === checkedGroup.item);
    if (itemEntries.length > 0) {
      const pe = {};
      let maxPallet = 0;
      for (const e of itemEntries) {
        const p = e.pallet;
        if (p > maxPallet) maxPallet = p;
        if (!pe[p]) pe[p] = [];
        pe[p].push({ id: e.id, item: e.item, pallet: p, kg: e.kg, _new: false });
      }
      palletEntries.value = pe;
      currentPallet.value = maxPallet + 1;
      tallyForm.value = { item: checkedGroup.item, kg: '' };
      itemLocked.value = true;
    } else {
      palletEntries.value = {};
      currentPallet.value = 1;
      tallyForm.value = { item: checkedGroup ? checkedGroup.item : '', kg: '' };
      itemLocked.value = false;
    }
  } else {
    palletEntries.value = {};
    currentPallet.value = 1;
    tallyForm.value = { item: '', kg: '' };
    itemLocked.value = false;
  }
  hasUnsaved.value = false;
  deletedEntryIds.value = [];
  showTallyModal.value = true;
}

function closeTallyModal() {
  if (saving.value) {
    return;
  }
  const unsaved = getUnsavedEntries();
  const hasDeletions = deletedEntryIds.value.length > 0;
  if ((unsaved.length > 0 || hasDeletions) && selectedPo.value) {
    saving.value = true;
    router.post(
      '/gmisl/utility/rcs/tally',
      {
        t_po_id: selectedPo.value.id,
        entries: unsaved.map((entry) => ({
          item: entry.item,
          pallet: entry.pallet,
          kg: entry.kg,
        })),
        deleted_ids: hasDeletions ? deletedEntryIds.value : [],
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          hasUnsaved.value = false;
          deletedEntryIds.value = [];
          const updated = { ...palletEntries.value };
          for (const pallet of Object.keys(updated)) {
            updated[pallet] = updated[pallet].map((e) => ({ ...e, _new: false }));
          }
          palletEntries.value = updated;
          showTallyModal.value = false;
        },
        onFinish: () => {
          saving.value = false;
        },
      }
    );
  } else {
    deletedEntryIds.value = [];
    showTallyModal.value = false;
  }
}

function deleteTally() {
  const poIds = Object.keys(checkedItems.value).filter((poId) => {
    const items = checkedItems.value[poId];
    return items && Object.values(items).some((v) => v);
  });

  if (poIds.length === 0 && !selectedId.value) {
    return;
  }

  const itemsToDelete = [];

  if (poIds.length > 0) {
    poIds.forEach((poId) => {
      const groups = getItemGroups(Number(poId));
      Object.keys(checkedItems.value[poId]).forEach((gi) => {
        if (checkedItems.value[poId][gi] && groups[Number(gi)]) {
          itemsToDelete.push({ poId: Number(poId), item: groups[Number(gi)].item });
        }
      });
    });
  } else if (selectedId.value) {
    itemsToDelete.push({ poId: selectedId.value, item: null });
  }

  if (itemsToDelete.length === 0) {
    return;
  }

  const poGroups = {};
  itemsToDelete.forEach(({ poId, item }) => {
    if (!poGroups[poId]) poGroups[poId] = [];
    if (item) poGroups[poId].push(item);
  });

  deleteConfirmGroups.value = poGroups;
  showDeleteConfirm.value = true;
}

function confirmDeleteTally() {
  const poGroups = deleteConfirmGroups.value;
  showDeleteConfirm.value = false;

  const promises = Object.keys(poGroups).map((poId) => {
    const payload = { items: poGroups[poId].length > 0 ? poGroups[poId] : null };
    return router.post(`/gmisl/utility/rcs/tally/${poId}/destroy`, payload, {
      preserveScroll: true,
    });
  });

  Promise.all(promises).then(() => {
    Object.keys(checkedItems.value).forEach((k) => delete checkedItems.value[k]);
    selectedId.value = null;
    deleteConfirmGroups.value = {};
  });
}

function deletePo() {
  if (!selectedId.value) {
    return;
  }
  showDeletePoConfirm.value = true;
}

function confirmDeletePo() {
  const id = selectedId.value;
  showDeletePoConfirm.value = false;
  if (!id) return;
  router.delete(`/gmisl/utility/rcs/${id}`, {
    preserveScroll: true,
    onSuccess: () => {
      selectedId.value = null;
      checkedItems.value = {};
    },
  });
}

function approveTally() {
  const talliesPayload = [];
  const draftItemNames = [];

  Object.keys(checkedItems.value).forEach((poId) => {
    const items = checkedItems.value[poId];
    if (!items) return;
    const checkedIndices = Object.keys(items).filter((k) => items[k]);
    if (checkedIndices.length === 0) return;

    const groups = getItemGroups(Number(poId));
    const selectedItems = [];
    checkedIndices.forEach((gi) => {
      if (groups[Number(gi)]) {
        const group = groups[Number(gi)];
        if (group.isFinish) {
          selectedItems.push(group.item);
        } else {
          draftItemNames.push(group.item);
        }
      }
    });
    if (selectedItems.length > 0) {
      talliesPayload.push({ po_id: Number(poId), items: selectedItems });
    }
  });

  if (draftItemNames.length > 0) {
    draftMsgItems.value = draftItemNames;
    showDraftMsg.value = true;
  }

  if (talliesPayload.length === 0) {
    return;
  }

  router.post(
    '/gmisl/utility/rcs/tally/approve',
    { tallies: talliesPayload },
    {
      preserveScroll: true,
      onSuccess: () => {
        checkedItems.value = {};
      },
    },
  );
}

function addEntry() {
  const item = tallyForm.value.item;
  const kg = tallyForm.value.kg;
  if (!item || kg === '' || !selectedPo.value) {
    return;
  }
  const updated = { ...palletEntries.value };
  if (!updated[currentPallet.value]) {
    updated[currentPallet.value] = [];
  }
  updated[currentPallet.value] = [
    ...updated[currentPallet.value],
    { item: item, pallet: currentPallet.value, kg: kg, _new: true },
  ];
  palletEntries.value = updated;
  tallyForm.value.kg = '';
  hasUnsaved.value = true;
}

function removeEntry(index) {
  const updated = { ...palletEntries.value };
  const entries = [...(updated[currentPallet.value] || [])];
  const removed = entries.splice(index, 1)[0];
  if (removed && removed.id) {
    deletedEntryIds.value = [...deletedEntryIds.value, removed.id];
  }
  updated[currentPallet.value] = entries;
  palletEntries.value = updated;
  hasUnsaved.value = true;
}

function nextPallet() {
  currentPallet.value += 1;
  tallyForm.value.kg = '';
}

function prevPallet() {
  if (currentPallet.value > 1) {
    currentPallet.value -= 1;
    tallyForm.value.kg = '';
  }
}

function saveEntries() {
  const unsaved = getUnsavedEntries();
  const hasDeletions = deletedEntryIds.value.length > 0;
  if ((unsaved.length === 0 && !hasDeletions) || !selectedPo.value || saving.value) {
    return;
  }
  saving.value = true;
  router.post(
    '/gmisl/utility/rcs/tally',
    {
      t_po_id: selectedPo.value.id,
      entries: unsaved.map((entry) => ({
        item: entry.item,
        pallet: entry.pallet,
        kg: entry.kg,
      })),
      deleted_ids: hasDeletions ? deletedEntryIds.value : [],
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        itemLocked.value = true;
        hasUnsaved.value = false;
        deletedEntryIds.value = [];
        const updated = { ...palletEntries.value };
        for (const pallet of Object.keys(updated)) {
          updated[pallet] = updated[pallet].map((e) => ({ ...e, _new: false }));
        }
        palletEntries.value = updated;
      },
      onFinish: () => {
        saving.value = false;
      },
    }
  );
}

async function finishTally() {
  if (saving.value) {
    return;
  }
  const unsaved = getUnsavedEntries();
  const poId = selectedPo.value?.id;

  summaryData.value = {
    po: selectedPo.value?.po || '-',
    customer: selectedPo.value?.customer?.name || '-',
    nopol: selectedPo.value?.nopol || '-',
    driver: selectedPo.value?.driver || '-',
    item: tallyForm.value.item || '-',
    totalPallets: summaryTotalPallets.value,
    totalKg: summaryTotalKg.value,
  };

  if (selectedPo.value) {
    saving.value = true;
    await new Promise((resolve) => {
      router.post(
        '/gmisl/utility/rcs/tally',
        {
          t_po_id: poId,
          is_finish: true,
          entries: unsaved.map((entry) => ({
            item: entry.item,
            pallet: entry.pallet,
            kg: entry.kg,
          })),
        },
        {
          preserveScroll: true,
          onFinish: () => {
            saving.value = false;
            resolve();
          },
        }
      );
    });
  }

  palletEntries.value = {};
  currentPallet.value = 1;
  if (poId) {
    delete tallyStates.value[poId];
  }
  showTallyModal.value = false;
  showSummary.value = true;

  await new Promise((resolve) => {
    router.reload({
      only: ['tallyMaxPallet', 'tallyData', 'finishedPoIds'],
      onFinish: () => resolve(),
    });
  });
}
const form = useForm({
  po: '',
  nopol: '',
  driver: '',
  customer_id: '',
  transaksi: '',
});

function openModal() {
  form.reset();
  form.clearErrors();
  showModal.value = true;
}

function closeModal() {
  if (form.processing) {
    return;
  }
  form.reset();
  form.clearErrors();
  showModal.value = false;
}

function submitForm() {
  form.post('/gmisl/utility/rcs', {
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
    },
  });
}
</script>
