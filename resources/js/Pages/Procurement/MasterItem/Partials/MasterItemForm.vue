<template>
  <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
      <label class="mb-1 block text-sm text-slate-300">Item Code</label>
      <input v-model="form.item_code" type="text" readonly class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-400 px-3 py-3" />
      <div v-if="form.errors.item_code" class="mt-1 text-xs text-rose-300">{{ form.errors.item_code }}</div>
    </div>

    <div>
      <label class="mb-1 block text-sm text-slate-300">Item Name</label>
      <input v-model="form.item_name" type="text" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-slate-100" />
      <div v-if="form.errors.item_name" class="mt-1 text-xs text-rose-300">{{ form.errors.item_name }}</div>
    </div>

    <div class="md:col-span-2">
      <label class="mb-1 block text-sm text-slate-300">Description of Goods</label>
      <textarea v-model="form.description_of_goods" rows="3" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-slate-100"></textarea>
      <div v-if="form.errors.description_of_goods" class="mt-1 text-xs text-rose-300">{{ form.errors.description_of_goods }}</div>
    </div>

    <div>
      <label class="mb-1 block text-sm text-slate-300">Type Item</label>
      <select v-model="form.item_type" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-slate-100">
        <option value="">Pilih Type Item</option>
        <option v-for="itemType in itemTypeOptions" :key="itemType.id" :value="itemType.name">{{ itemType.name }}</option>
      </select>
      <div v-if="form.errors.item_type" class="mt-1 text-xs text-rose-300">{{ form.errors.item_type }}</div>
    </div>

    <div>
      <label class="mb-1 block text-sm text-slate-300">Category</label>
      <select v-model="form.category_id" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-slate-100">
        <option value="">Pilih Category</option>
        <option v-for="category in categoryOptions" :key="category.id" :value="category.id">{{ category.name }}</option>
      </select>
      <div v-if="form.errors.category_id" class="mt-1 text-xs text-rose-300">{{ form.errors.category_id }}</div>
    </div>

    <div>
      <label class="mb-1 block text-sm text-slate-300">Unit</label>
      <select v-model="form.unit" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-slate-100">
        <option value="">Pilih Unit</option>
        <option v-for="unit in unitOptions" :key="unit.id" :value="unit.name">{{ unit.name }}</option>
      </select>
      <div v-if="form.errors.unit" class="mt-1 text-xs text-rose-300">{{ form.errors.unit }}</div>
    </div>

    <div class="md:col-span-2">
      <label class="inline-flex items-center gap-3 text-sm text-slate-300">
        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-indigo-600" />
        Item aktif
      </label>
      <div v-if="form.errors.is_active" class="mt-1 text-xs text-rose-300">{{ form.errors.is_active }}</div>
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue';

const props = defineProps({
  form: { type: Object, required: true },
  itemTypeOptions: { type: Array, default: () => [] },
  unitOptions: { type: Array, default: () => [] },
  categoryOptions: { type: Array, default: () => [] },
  isEdit: { type: Boolean, default: false },
});

watch(
  () => props.form.item_type,
  (newType) => {
    if (!props.isEdit && newType) {
      const selectedType = props.itemTypeOptions.find((t) => t.name === newType);
      if (selectedType?.code) {
        fetch(`/procurement-master-item/generate-code?type=${encodeURIComponent(newType)}`)
          .then((r) => r.json())
          .then((data) => {
            if (data.item_code) {
              props.form.item_code = data.item_code;
            }
          })
          .catch(() => {});
      }
    }
  }
);
</script>
