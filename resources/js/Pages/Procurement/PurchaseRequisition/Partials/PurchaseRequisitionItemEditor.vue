<template>
  <div class="overflow-hidden rounded-lg border border-slate-600 bg-[#d9dde8]">
    <div class="flex flex-col gap-3 border-b border-slate-500 bg-gradient-to-b from-[#7286b8] to-[#506898] px-4 py-2.5 md:flex-row md:items-center md:justify-between">
      <div>
        <h3 class="text-sm font-semibold uppercase tracking-wide text-white">PR Item Detail</h3>
        <p class="text-xs text-slate-100/90">Isi detail item dalam format tabel.</p>
      </div>
      <div class="flex gap-2">
        <Link href="/master-data/master-item" class="rounded bg-white/15 px-3 py-2 text-sm text-white hover:bg-white/25">Open Master Item</Link>
        <button type="button" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-700" @click="addItem">
          Add Item
        </button>
      </div>
    </div>

    <div class="overflow-x-auto p-1">
      <table class="w-full min-w-[1500px] border-collapse text-sm text-slate-800">
<colgroup>
            <col class="w-[52px]" />
            <col class="w-[150px]" />
            <col class="w-[170px]" />
            <col class="w-[170px]" />
            <col class="w-[90px]" />
            <col class="w-[120px]" />
            <col class="w-[130px]" />
            <col class="w-[90px]" />
          </colgroup>
        <thead>
<tr class="bg-gradient-to-b from-[#7489ba] to-[#556d9a] text-center text-[12px] font-semibold text-white">
             <th class="border border-slate-400 px-2 py-1.5">No</th>
             <th class="border border-slate-400 px-2 py-1.5">Master Item</th>
             <th class="border border-slate-400 px-2 py-1.5">Description</th>
             <th class="border border-slate-400 px-2 py-1.5">Note</th>
             <th class="border border-slate-400 px-2 py-1.5">Qty</th>
             <th class="border border-slate-400 px-2 py-1.5">Item Unit</th>
             <th class="border border-slate-400 px-2 py-1.5">Required Date</th>
             <th class="border border-slate-400 px-2 py-1.5">Action</th>
           </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in form.items" :key="item._key || index" class="align-top bg-[#f7f7f9]">
            <td class="border border-slate-300 px-2 py-2 text-center font-medium">{{ index + 1 }}</td>

            <td class="border border-slate-300 px-2 py-2">
              <select
                :value="item.procurement_master_item_id || ''"
                class="w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-800"
                @change="applyMasterItem(index, $event.target.value)"
              >
                <option value="">Pilih Barang</option>
                <option v-for="masterItem in masterItems" :key="masterItem.id" :value="masterItem.id">
                  {{ masterItem.label }}
                </option>
              </select>
              <div v-if="itemError(index, 'procurement_master_item_id')" class="mt-1 text-xs text-rose-600">{{ itemError(index, 'procurement_master_item_id') }}</div>
            </td>
            <td class="border border-slate-300 px-2 py-2">
              <textarea v-model="item.description_of_goods" rows="1" readonly class="h-[36px] min-h-[36px] w-full resize-none rounded border border-slate-300 bg-slate-100 px-2 py-1.5 text-sm text-slate-700"></textarea>
              <div v-if="itemError(index, 'description_of_goods')" class="mt-1 text-xs text-rose-600">{{ itemError(index, 'description_of_goods') }}</div>
            </td>

            <td class="border border-slate-300 px-2 py-2">
              <textarea
                :value="form.note || ''"
                rows="1"
                readonly
                class="h-[36px] min-h-[36px] w-full resize-none rounded border border-slate-300 bg-slate-100 px-2 py-1.5 text-sm text-slate-700"
                placeholder="Note PR"
              ></textarea>
            </td>

            <td class="border border-slate-300 px-2 py-2">
              <input
                v-model="item.quantity"
                type="number"
                min="1"
                step="1"
                class="h-[36px] w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-center text-sm text-slate-800"
                @input="normalizeQuantity(index)"
              />
              <div v-if="itemError(index, 'quantity')" class="mt-1 text-xs text-rose-600">{{ itemError(index, 'quantity') }}</div>
            </td>

            <td class="border border-slate-300 px-2 py-2">
              <select v-model="item.unit" disabled class="w-full rounded border border-slate-300 bg-slate-100 px-2 py-1.5 text-sm text-slate-700">
                <option value="">Pilih Unit</option>
                <option v-for="unit in uomOptions" :key="unit.id" :value="unit.name">{{ unit.name }}</option>
              </select>
              <div v-if="itemError(index, 'unit')" class="mt-1 text-xs text-rose-600">{{ itemError(index, 'unit') }}</div>
            </td>

            <td class="border border-slate-300 px-2 py-2">
              <EnhancedDatePicker
                v-model="item.required_date"
                placeholder="dd/mm/yyyy"
                :min-date="minimumRequiredDate"
                input-class="h-[36px] w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-800"
              />
              <div v-if="itemError(index, 'required_date')" class="mt-1 text-xs text-rose-600">{{ itemError(index, 'required_date') }}</div>
            </td>



            <td class="border border-slate-300 px-2 py-2 text-center">
              <button type="button" class="rounded bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700" @click="removeItem(index)">
                Remove
              </button>
            </td>
          </tr>

          <tr v-if="!form.items.length">
            <td colspan="8" class="border border-slate-300 py-4 text-center text-sm text-slate-500">Belum ada item.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="form.errors.items" class="border-t border-slate-400 px-3 py-2 text-xs text-rose-700">
      {{ form.errors.items }}
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';

const props = defineProps({
  form: { type: Object, required: true },
  uomOptions: { type: Array, default: () => [] },
  masterItems: { type: Array, default: () => [] },
  minimumRequiredDate: { type: String, default: '' },
});

function createItem() {
  return {
    _key: `${Date.now()}-${Math.random()}`,
    procurement_master_item_id: '',
    item_code: '',
    item_name: '',
    description_of_goods: '',
    specification: '',
    unit: '',
    category_id: '',
    quantity: '',
    required_date: '',
    price: '',
  };
}

if (!props.form.items.length) {
  props.form.items.push(createItem());
}

function addItem() {
  props.form.items.push(createItem());
}

function removeItem(index) {
  props.form.items.splice(index, 1);
}

function itemError(index, field) {
  return props.form.errors[`items.${index}.${field}`] || '';
}

function applyMasterItem(index, rawId) {
  const item = props.form.items[index];
  const masterItemId = Number(rawId || 0);
  const masterItem = props.masterItems.find((entry) => entry.id === masterItemId);

  item.procurement_master_item_id = masterItem ? masterItem.id : '';

  if (!masterItem) {
    return;
  }

  item.item_code = masterItem.item_code || '';
  item.item_name = masterItem.item_name || '';
  item.description_of_goods = masterItem.description_of_goods || '';
  if (!item.specification) {
    item.specification = '';
  }
  item.unit = masterItem.unit || '';
  item.category_id = masterItem.category_id || '';
}

function normalizeQuantity(index) {
  const item = props.form.items[index];
  const rawValue = String(item?.quantity ?? '').trim();

  if (rawValue === '') {
    item.quantity = '';
    return;
  }

  const normalized = rawValue.replace(/[^\d-]/g, '');
  const parsed = Number.parseInt(normalized, 10);

  item.quantity = Number.isNaN(parsed) || parsed < 1 ? '' : parsed;
}
</script>
