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
            <template v-for="(tally, index) in sortedTallies" :key="tally.id">
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
                                     <th class="whitespace-nowrap px-2 py-1 font-semibold">Exp Date</th>
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
                                       <td class="whitespace-nowrap px-2 py-1">{{ entry.exp_date || '-' }}</td>
                                       <td class="whitespace-nowrap px-2 py-1 text-right">{{ Number(entry.kg).toFixed(2) }}</td>
                                     </tr>
                                     <tr class="bg-green-50 font-semibold text-green-800">
                                       <td colspan="4" class="whitespace-nowrap px-2 py-1 text-right">Total Pallet {{ pg.pallet }} — {{ Number(pg.totalKg).toFixed(2) }} KG</td>
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
      <div class="max-h-[90vh] w-full max-w-2xl -mt-24 overflow-hidden rounded-xl border border-slate-300 bg-white p-5 shadow-2xl sm:mt-0">
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
          <div ref="itemWrapEl" class="relative">
            <label class="mb-1 block bg-white text-sm font-medium text-slate-700" for="item">Item</label>
            <input
              id="item"
              type="text"
              role="combobox"
              :aria-expanded="itemDropdownOpen"
              autocomplete="off"
              :value="itemDisplayValue"
              :placeholder="itemLocked ? '' : ''"
              :disabled="itemLocked"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
              @focus="openItemDropdown"
              @input="onItemSearchInput"
              @keydown.escape.prevent="closeItemDropdown"
            />
            <div
              v-if="itemDropdownOpen && !itemLocked"
              class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded border border-slate-300 bg-white shadow-lg"
            >
              <p v-if="searchableProducts.length === 0" class="px-3 py-2 text-sm text-slate-400">
                Item tidak ditemukan.
              </p>
              <button
                v-for="product in searchableProducts"
                :key="product.id"
                type="button"
                class="block w-full px-3 py-2 text-left text-sm text-slate-900 hover:bg-indigo-50"
                :class="tallyForm.item === product.internal_reference ? 'bg-indigo-50 font-semibold' : ''"
                @click="chooseItem(product)"
              >
                {{ product.internal_reference }} - {{ product.name }}
              </button>
            </div>
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
              <label class="mb-1 block bg-white text-sm font-medium text-slate-700" for="exp_date">Exp Date</label>
              <input
                id="exp_date"
                v-model="tallyForm.exp_date"
                type="date"
                class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                @input="onExpDateChange"
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
          <div v-else ref="entriesListRef" class="max-h-64 overflow-y-auto rounded-md border border-slate-200">
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
                    <div class="flex items-center justify-center gap-2">
                      <button type="button" title="Edit KG" @click="startEditEntry(index)" class="text-sky-500 hover:text-sky-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                      </button>
                      <button type="button" title="Hapus" @click="removeEntry(index)" class="text-red-500 hover:text-red-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                      </button>
                    </div>
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
          <p v-if="expDateError" class="mr-auto text-xs font-medium text-rose-600">{{ expDateError }}</p>
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
    <!-- Modal Warning Multiple Tally Checked -->
    <div
      v-if="showMultiTallyWarn"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
    >
      <div class="w-full max-w-sm overflow-hidden rounded-xl border border-slate-300 bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-slate-900">Hanya Boleh Satu Tally</h3>
        </div>

        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4">
          <p class="text-sm text-slate-700">
            Silahkan pilih salah satu tally, tidak boleh memilih 2 tally sekaligus.
          </p>
        </div>

        <div class="flex justify-end">
          <button
            type="button"
            class="rounded bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700"
            @click="showMultiTallyWarn = false"
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
  </AppLayout>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue';
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

const sortedTallies = computed(() => {
  const list = props.tallies || [];
  const priority = (poId) => {
    const entries = props.tallyData[poId] || [];
    if (entries.length === 0) return 0;
    const hasFinish = entries.some((e) => e.is_finish);
    const hasDraft = entries.some((e) => !e.is_finish);
    if (hasFinish && hasDraft) return 2;
    if (hasFinish) return 3;
    return 1;
  };
  return [...list].sort((a, b) => priority(a.id) - priority(b.id));
});
const products = computed(() => props.products || []);
const finishedPoIds = ref([...(props.finishedPoIds || [])]);
const flashMessage = computed(() => props.flash?.success || '');

const showModal = ref(false);
const selectedId = ref(null);
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
  exp_date: '',
});
const palletEntries = ref({});
const currentPallet = ref(1);
const entriesListRef = ref(null);
const tallyStates = ref({});
const hasUnsaved = ref(false);
const deletedEntryIds = ref([]);
const editingEntryIndex = ref(null);const currentFinishItem = ref(null);

const itemWrapEl = ref(null);
const itemSearch = ref('');
const itemDropdownOpen = ref(false);
const expDateError = ref('');

const selectedItem = computed(
  () => filteredProducts.value.find((product) => product.internal_reference === tallyForm.value.item) || null
);

const itemDisplayValue = computed(() => {
  if (itemSearch.value !== '') {
    return itemSearch.value;
  }
  return selectedItem.value ? `${selectedItem.value.internal_reference} - ${selectedItem.value.name}` : '';
});

const searchableProducts = computed(() => {
  const query = itemSearch.value.trim().toLowerCase();
  if (!query) {
    return filteredProducts.value;
  }
  return filteredProducts.value.filter((product) =>
    `${product.internal_reference} ${product.name}`.toLowerCase().includes(query)
  );
});

watch(showTallyModal, (open) => {
  if (!open) {
    itemSearch.value = '';
    itemDropdownOpen.value = false;
    expDateError.value = '';
  }
});

function openItemDropdown() {
  if (itemLocked.value) {
    return;
  }
  itemDropdownOpen.value = true;
}

function closeItemDropdown() {
  itemDropdownOpen.value = false;
  itemSearch.value = '';
}

function onItemSearchInput(event) {
  if (itemLocked.value) {
    return;
  }
  itemSearch.value = event.target.value;
  itemDropdownOpen.value = true;
}

function chooseItem(product) {
  tallyForm.value.item = product.internal_reference;
  itemSearch.value = '';
  itemDropdownOpen.value = false;
}

function onDocPointerDownItem(event) {
  if (!itemDropdownOpen.value) {
    return;
  }
  if (itemWrapEl.value && !itemWrapEl.value.contains(event.target)) {
    itemDropdownOpen.value = false;
    itemSearch.value = '';
  }
}

document.addEventListener('pointerdown', onDocPointerDownItem);

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocPointerDownItem);
});
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
  return Array.from(map.values())
    .map((g) => ({
      item: g.item,
      entries: g.entries,
      palletCount: g.palletSet.size,
      totalKg: g.totalKg,
      isFinish: g.isFinish,
      startdate: g.startdate,
      enddate: g.enddate,
    }))
    .sort((a, b) => Number(a.isFinish) - Number(b.isFinish));
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

  const tallyMap = new Map();
  for (const { poId, group } of finishItems) {
    const tallyNo = group.entries[0]?.no_tally || '-';
    if (!tallyMap.has(tallyNo)) {
      const po = tallies.value.find((t) => t.id === poId);
      tallyMap.set(tallyNo, {
        nopol: po?.nopol || '-',
        customer: po?.customer?.name || '-',
        poNumber: po?.po || '-',
        operationType: po?.transaksi || '-',
        driver: po?.driver || '-',
        checker: group.entries[0]?.checker_name || '-',
        noTally: tallyNo,
        date: group.startdate || '-',
        totalKg: 0,
        items: new Map(),
      });
    }
    const sheet = tallyMap.get(tallyNo);
    const startDate = group.startdate || '-';
    const endDate = group.enddate || '-';
    if (!sheet.items.has(group.item)) {
      sheet.items.set(group.item, {
        item: group.item,
        pallets: [],
        totalKg: 0,
        totalQty: 0,
        expiredDate: endDate,
      });
    }
    const item = sheet.items.get(group.item);
    for (const entry of group.entries) {
      item.pallets.push({ pallet: entry.pallet, kg: entry.kg, exp_date: entry.exp_date || '' });
      item.totalQty += 1;
    }
    item.totalKg += group.totalKg;
    sheet.totalKg += group.totalKg;
    sheet.checker = group.entries[0]?.checker_name || sheet.checker;
  }

  printData.value = Array.from(tallyMap.values()).map(s => ({
    ...s,
    items: Array.from(s.items.values()),
  }));
  handlePrint();
}

function openPrintModal(tally) {
  const po = tallies.value.find((t) => t.id === tally.id);
  const groups = getItemGroups(tally.id);
  const items = [];
  let totalKg = 0;
  let totalQty = 0;
  let checker = '-';
  for (const group of groups) {
    const entries = group.entries.map(e => ({ pallet: e.pallet, kg: e.kg, exp_date: e.exp_date || '' }));
    items.push({
      item: group.item,
      totalKg: group.totalKg,
      totalQty: entries.length,
      expiredDate: group.enddate || '-',
      pallets: entries,
    });
    totalKg += group.totalKg;
    totalQty += entries.length;
    if (!checker || checker === '-') checker = group.entries[0]?.checker_name || '-';
  }
  printData.value = [{
    nopol: po?.nopol || '-',
    customer: po?.customer?.name || '-',
    poNumber: po?.po || '-',
    operationType: po?.transaksi || '-',
    driver: po?.driver || '-',
    totalKg,
    totalQty,
    checker,
    noTally: groups[0]?.entries[0]?.no_tally || '-',
    date: groups[0]?.startdate || '-',
    items,
  }];
  handlePrint();
}

function handlePrint() {
  if (!printData.value || !printData.value.length) return;

  import('jspdf').then(({ jsPDF }) => {
    const doc = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' });
    const sheets = printData.value;
    const pw = 210, ph = 297;
    const ml = 15, mr = 15, mt = 10;
    const contentW = pw - ml - mr;
    const now = new Date();

    function getSkuName(code) {
      const p = products.value.find(pr => String(pr.internal_reference) === String(code));
      return p ? p.name : String(code);
    }

    function formatDate(d) {
      if (!d || d === '-') return '-';
      const dt = new Date(d);
      if (isNaN(dt.getTime())) return String(d);
      return dt.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function fmtNum(v) {
      return Number(v).toLocaleString('id-ID');
    }

    const marginBottom = 10;
    const signatureH = 30;

    const colNoW = 10;
    const colSkuW = 22;
    const colPalletW = 14;
    const colExpW = 22;
    const colQtyW = 14;
    const colKgW = 22;
    const colItemW = contentW - colNoW - colSkuW - colPalletW - colExpW - colQtyW - colKgW;

    const colX = [ml];
    colX.push(colX[0] + colNoW);
    colX.push(colX[1] + colSkuW);
    colX.push(colX[2] + colItemW);
    colX.push(colX[3] + colPalletW);
    colX.push(colX[4] + colExpW);
    colX.push(colX[5] + colQtyW);

    const headerH = 7;
    const rowH = 5.5;

    function drawPageHeader(data) {
      let y = mt;
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(15);
      doc.text('Tally Sheet', pw / 2, y + 2, { align: 'center' });
      y += 7;
      doc.setFontSize(10);
      doc.text('Operation Type (' + String(data.operationType || '-') + ')', pw / 2, y, { align: 'center' });
      y += 6;
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(11);
      doc.text(String(data.noTally || '-'), pw / 2, y, { align: 'center' });
      y += 7;

      doc.setFont('helvetica', 'normal');
      doc.setFontSize(9);
      const labelX = ml;
      const valX = ml + 28;
      doc.text('Tanggal', labelX, y);
      doc.text(': ' + formatDate(data.date), valX, y);
      y += 5;
      doc.text('Customer', labelX, y);
      doc.text(': ' + String(data.customer || '-'), valX, y);
      y += 5;
      doc.text('No PO', labelX, y);
      doc.text(': ' + String(data.poNumber || '-'), valX, y);
      y += 5;
      doc.text('Nopol', labelX, y);
      doc.text(': ' + String(data.nopol || '-'), valX, y);
      y += 5;
      doc.text('Checker', labelX, y);
      doc.text(': ' + String(data.checker || '-'), valX, y);
      y += 6;

      doc.setDrawColor(0);
      doc.setLineWidth(0.3);
      doc.line(ml, y, pw - mr, y);
      y += 2;
      return y;
    }

    function drawTableHeader(cy) {
      doc.setDrawColor(0);
      doc.setLineWidth(0.15);
      doc.line(ml, cy, pw - mr, cy);
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(8);
      const headers = ['No', 'SKU', 'Item', 'Pallet', 'Exp Date', 'Qty', 'KG'];
      const widths = [colNoW, colSkuW, colItemW, colPalletW, colExpW, colQtyW, colKgW];
      const aligns = ['center', 'center', 'left', 'center', 'center', 'right', 'right'];
      for (let i = 0; i < headers.length; i++) {
        let tx = colX[i] + 1;
        if (aligns[i] === 'center') tx = colX[i] + widths[i] / 2;
        if (aligns[i] === 'right') tx = colX[i] + widths[i] - 1;
        doc.text(headers[i], tx, cy + 5, { align: aligns[i] });
      }
      cy += headerH;
      doc.setLineWidth(0.3);
      doc.line(ml, cy, pw - mr, cy);
      return cy + 1;
    }

    function calcGroupHeight(ig) {
      const nameLines = doc.splitTextToSize(getSkuName(ig.sku), colItemW - 4);
      const nameH = Math.max(rowH, nameLines.length > 1 ? rowH * 2 : rowH);
      const uniquePallets = new Set(ig.pallets.map(p => p.pallet)).size;
      return nameH + uniquePallets * rowH + 7;
    }

    function drawGroup(ig, cy) {
      const nameLines = doc.splitTextToSize(getSkuName(ig.sku), colItemW - 4);
      const nameH = Math.max(rowH, nameLines.length > 1 ? rowH * 2 : rowH);

      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8);
      doc.text(String(ig.number), colX[0] + colNoW / 2, cy + nameH / 2 + 1.5, { align: 'center' });
      doc.text(String(ig.sku), colX[1] + colSkuW / 2, cy + nameH / 2 + 1.5, { align: 'center' });
      doc.text(nameLines, colX[2] + 2, cy + 4);
      cy += nameH;

      const palletAgg = new Map();
      for (const p of ig.pallets) {
        const key = p.pallet;
        if (!palletAgg.has(key)) palletAgg.set(key, { pallet: key, qty: 0, kg: 0, exp_date: p.exp_date || '-' });
        const a = palletAgg.get(key);
        a.qty += 1;
        a.kg += Number(p.kg);
      }
      for (const [, a] of palletAgg) {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.text(String(a.pallet), colX[3] + colPalletW / 2, cy + rowH / 2 + 1.5, { align: 'center' });
        doc.text(formatDate(a.exp_date), colX[4] + colExpW / 2, cy + rowH / 2 + 1.5, { align: 'center' });
        doc.text(fmtNum(a.qty), colX[5] + colQtyW - 1, cy + rowH / 2 + 1.5, { align: 'right' });
        doc.text(fmtNum(a.kg), colX[6] + colKgW - 1, cy + rowH / 2 + 1.5, { align: 'right' });
        cy += rowH;
      }

      doc.setLineWidth(0.2);
      doc.line(colX[5] + colQtyW - 10, cy + 2, pw - mr, cy + 2);
      cy += 3;

      doc.setFont('helvetica', 'bold');
      doc.setFontSize(8);
      doc.text('Subtotal', colX[2] + colItemW - 2, cy + 2, { align: 'right' });
      doc.text(fmtNum(ig.totalQty), colX[5] + colQtyW - 1, cy + 2, { align: 'right' });
      doc.text(fmtNum(ig.totalKg), colX[6] + colKgW - 1, cy + 2, { align: 'right' });
      cy += 5;
      return cy;
    }

    function needPageBreak(cy, needed) {
      return cy + needed > ph - marginBottom - signatureH;
    }

    const allSheetData = sheets.map((data) => {
      const itemGroups = [];
      let itemNum = 0;
      for (const item of data.items) {
        itemNum++;
        const totalQty = item.totalQty || item.pallets.length;
        const totalKg = item.totalKg || item.pallets.reduce((s, p) => s + Number(p.kg), 0);
        const expiredDate = item.expiredDate || '-';
        itemGroups.push({
          number: itemNum,
          sku: item.item,
          expiredDate: formatDate(expiredDate),
          totalQty: totalQty,
          totalKg: totalKg,
          pallets: item.pallets,
        });
      }
      return Object.assign({}, data, { itemGroups: itemGroups });
    });

    function drawSignature(y) {
      if (y + 28 > ph - marginBottom) {
        doc.addPage();
        y = mt;
      }
      const sigW = contentW / 3;
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(9);

      doc.text('Checker', ml + sigW * 0.5, y, { align: 'center' });
      doc.text('Driver', ml + sigW * 1.5, y, { align: 'center' });
      doc.text('Admin', ml + sigW * 2.5, y, { align: 'center' });

      y += 18;
      doc.setLineWidth(0.3);
      doc.line(ml + sigW * 0.1, y, ml + sigW * 0.9, y);
      doc.line(ml + sigW * 1.1, y, ml + sigW * 1.9, y);
      doc.line(ml + sigW * 2.1, y, ml + sigW * 2.9, y);
      return y;
    }

    for (let si = 0; si < allSheetData.length; si++) {
      const data = allSheetData[si];
      if (si > 0) doc.addPage();

      let y = drawPageHeader(data);
      y = drawTableHeader(y);

      let sheetTotalQty = 0;
      let sheetTotalKg = 0;

      for (let ii = 0; ii < data.itemGroups.length; ii++) {
        const ig = data.itemGroups[ii];
        const groupH = calcGroupHeight(ig);

        if (needPageBreak(y, groupH)) {
          doc.addPage();
          y = drawPageHeader(data);
          y = drawTableHeader(y);
        }

        y = drawGroup(ig, y);
        sheetTotalQty += ig.totalQty;
        sheetTotalKg += ig.totalKg;
      }

      if (y + 12 > ph - marginBottom - signatureH) {
        doc.addPage();
        y = drawPageHeader(data);
        y = drawTableHeader(y);
      }

      y += 2;
      doc.setDrawColor(0);
      doc.setLineWidth(0.3);
      doc.line(ml, y, pw - mr, y);
      y += 3;

      doc.setFont('helvetica', 'bold');
      doc.setFontSize(9);
      doc.text('TOTAL', colX[2] + colItemW - 2, y + 2, { align: 'right' });
      doc.text(fmtNum(sheetTotalQty), colX[5] + colQtyW - 1, y + 2, { align: 'right' });
      doc.text(fmtNum(sheetTotalKg), colX[6] + colKgW - 1, y + 2, { align: 'right' });
      y += 3;
      doc.setLineWidth(0.4);
      doc.line(ml, y, pw - mr, y);
      y += 10;

      y = drawSignature(y);
    }

    const firstNoTally = sheets[0] ? sheets[0].noTally : 'Sheet';
    const fileName = 'Tally_' + firstNoTally + '_' + now.toISOString().slice(0, 10) + '.pdf';
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

function getEditedEntries() {
  const all = [];
  for (const entries of Object.values(palletEntries.value)) {
    for (const entry of entries) {
      if (entry._edit) {
        all.push(entry);
      }
    }
  }
  return all;
}

const showFinishMsg = ref(false);
const showMultiTallyWarn = ref(false);
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

  if (checkedIndices.length > 1) {
    showMultiTallyWarn.value = true;
    return;
  }

  if (hasCheckedItem) {
    const groups = getItemGroups(poId);
    const checkedGroup = groups[Number(checkedIndices[0])];
    currentFinishItem.value = checkedGroup ? checkedGroup.item : null;

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
      let lastExpDate = '';
      for (const e of itemEntries) {
        const p = e.pallet;
        if (p > maxPallet) maxPallet = p;
        if (!pe[p]) pe[p] = [];
        pe[p].push({ id: e.id, item: e.item, pallet: p, kg: e.kg, exp_date: e.exp_date || '', _new: false });
        if (e.exp_date) lastExpDate = e.exp_date;
      }
      palletEntries.value = pe;
      currentPallet.value = maxPallet + 1;
      tallyForm.value = { item: checkedGroup.item, kg: '', exp_date: todayLocal() };
      itemLocked.value = true;
    } else {
      palletEntries.value = {};
      currentPallet.value = 1;
      tallyForm.value = { item: checkedGroup ? checkedGroup.item : '', kg: '', exp_date: todayLocal() };
      itemLocked.value = false;
    }
  } else {
    palletEntries.value = {};
    currentPallet.value = 1;
    tallyForm.value = { item: '', kg: '', exp_date: todayLocal() };
    itemLocked.value = false;
  }
  hasUnsaved.value = false;
  deletedEntryIds.value = [];
  currentFinishItem.value = null;
  editingEntryIndex.value = null;
  showTallyModal.value = true;
}

function closeTallyModal() {
  if (saving.value) {
    return;
  }
  const unsaved = getUnsavedEntries();
  const edits = getEditedEntries();
  const hasDeletions = deletedEntryIds.value.length > 0;
  if (unsaved.some((entry) => !entry.exp_date)) {
    expDateError.value = 'Exp Date wajib diisi untuk setiap inputan KG sebelum menyimpan.';
    return;
  }
  expDateError.value = '';
  if ((unsaved.length > 0 || edits.length > 0 || hasDeletions) && selectedPo.value) {
    saving.value = true;
    router.post(
      '/gmisl/utility/rcs/tally',
      {
        t_po_id: selectedPo.value.id,
        entries: unsaved.map((entry) => ({
          item: entry.item,
          pallet: entry.pallet,
          kg: entry.kg,
          exp_date: entry.exp_date || null,
        })),
        edits: edits.map((entry) => ({
          id: entry.id,
          kg: entry.kg,
          exp_date: entry.exp_date || null,
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
            updated[pallet] = updated[pallet].map((e) => ({ ...e, _new: false, _edit: false }));
          }
          palletEntries.value = updated;
          editingEntryIndex.value = null;
          showTallyModal.value = false;
        },
        onFinish: () => {
          saving.value = false;
        },
      }
    );
  } else {
    deletedEntryIds.value = [];
    editingEntryIndex.value = null;
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
  const list = [...updated[currentPallet.value]];
  const editingIndex = editingEntryIndex.value;
  if (editingIndex !== null && editingIndex >= 0 && editingIndex < list.length) {
    const oldEntry = list[editingIndex];
    const isPersisted = Boolean(oldEntry.id) && !oldEntry._new;
    list[editingIndex] = isPersisted
      ? {
          ...oldEntry,
          kg: kg,
          exp_date: tallyForm.value.exp_date || oldEntry.exp_date || '',
          _edit: true,
        }
      : {
          item: oldEntry.item,
          pallet: oldEntry.pallet,
          kg: kg,
          exp_date: tallyForm.value.exp_date || oldEntry.exp_date || '',
          _new: true,
        };
  } else {
    list.push({ item: item, pallet: currentPallet.value, kg: kg, exp_date: tallyForm.value.exp_date || '', _new: true });
  }
  updated[currentPallet.value] = list;
  palletEntries.value = updated;
  tallyForm.value.kg = '';
  editingEntryIndex.value = null;
  hasUnsaved.value = true;
  scrollEntriesToBottom();
}

function scrollEntriesToBottom() {
  nextTick(() => {
    const el = entriesListRef.value;
    if (el) {
      el.scrollTop = el.scrollHeight;
    }
  });
}

function onExpDateChange() {
  const expDate = tallyForm.value.exp_date || '';
  const item = tallyForm.value.item;
  if (!item) {
    return;
  }
  const palletKey = String(currentPallet.value);
  const updated = { ...palletEntries.value };
  const list = updated[palletKey] ? [...updated[palletKey]] : [];
  let changed = false;
  updated[palletKey] = list.map((entry) => {
    if (entry.item !== item) {
      return entry;
    }
    changed = true;
    if (entry._new) {
      return { ...entry, exp_date: expDate };
    }
    if (entry.exp_date !== expDate) {
      return { ...entry, exp_date: expDate, _edit: true };
    }
    return entry;
  });
  if (changed) {
    palletEntries.value = updated;
    hasUnsaved.value = true;
  }
  if (!getUnsavedEntries().some((entry) => !entry.exp_date)) {
    expDateError.value = '';
  }
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

function startEditEntry(index) {
  const entries = palletEntries.value[currentPallet.value] || [];
  const target = entries[index];
  if (!target) {
    return;
  }
  tallyForm.value.kg = String(target.kg);
  if (target.exp_date) {
    tallyForm.value.exp_date = target.exp_date;
  }
  editingEntryIndex.value = index;
}

function todayLocal() {
  const d = new Date();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${d.getFullYear()}-${mm}-${dd}`;
}

function syncExpDateForCurrentPallet() {
  const entries = palletEntries.value[currentPallet.value] || [];
  const savedDate = entries.find((e) => e.exp_date)?.exp_date || '';
  tallyForm.value.exp_date = savedDate || todayLocal();
}

function nextPallet() {
  currentPallet.value += 1;
  tallyForm.value.kg = '';
  editingEntryIndex.value = null;
  syncExpDateForCurrentPallet();
}

function prevPallet() {
  if (currentPallet.value > 1) {
    currentPallet.value -= 1;
    tallyForm.value.kg = '';
    editingEntryIndex.value = null;
    syncExpDateForCurrentPallet();
  }
}

function saveEntries() {
  const unsaved = getUnsavedEntries();
  const edits = getEditedEntries();
  const hasDeletions = deletedEntryIds.value.length > 0;
  if ((unsaved.length === 0 && edits.length === 0 && !hasDeletions) || !selectedPo.value || saving.value) {
    return;
  }
  if (unsaved.some((entry) => !entry.exp_date)) {
    expDateError.value = 'Exp Date wajib diisi untuk setiap inputan KG sebelum menyimpan.';
    return;
  }
  expDateError.value = '';
  saving.value = true;
  router.post(
    '/gmisl/utility/rcs/tally',
    {
      t_po_id: selectedPo.value.id,
      entries: unsaved.map((entry) => ({
        item: entry.item,
        pallet: entry.pallet,
        kg: entry.kg,
        exp_date: entry.exp_date || null,
      })),
      edits: edits.map((entry) => ({
        id: entry.id,
        kg: entry.kg,
        exp_date: entry.exp_date || null,
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
          updated[pallet] = updated[pallet].map((e) => ({ ...e, _new: false, _edit: false }));
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
  const edits = getEditedEntries();
  const poId = selectedPo.value?.id;

  if (unsaved.some((entry) => !entry.exp_date)) {
    expDateError.value = 'Exp Date wajib diisi untuk setiap inputan KG sebelum menyimpan.';
    return;
  }

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
          item: currentFinishItem.value || tallyForm.value.item || undefined,
          entries: unsaved.map((entry) => ({
            item: entry.item,
            pallet: entry.pallet,
            kg: entry.kg,
            exp_date: entry.exp_date || null,
          })),
          edits: edits.map((entry) => ({
            id: entry.id,
            kg: entry.kg,
            exp_date: entry.exp_date || null,
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
  editingEntryIndex.value = null;
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
