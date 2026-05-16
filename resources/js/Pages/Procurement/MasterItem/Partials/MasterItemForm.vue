<template>
  <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
      <label class="mb-1 block text-sm text-slate-300">Item Code</label>
      <input v-model="form.item_code" type="text" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-slate-100" />
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

    <div class="md:col-span-2">
      <label class="mb-1 block text-sm text-slate-300">Specification</label>
      <textarea v-model="form.specification" rows="4" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-slate-100"></textarea>
      <div v-if="form.errors.specification" class="mt-1 text-xs text-rose-300">{{ form.errors.specification }}</div>
    </div>

    <div>
      <label class="mb-1 block text-sm text-slate-300">Unit</label>
      <select v-model="form.unit" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-slate-100">
        <option value="">Pilih Unit</option>
        <option v-for="unit in unitOptions" :key="unit.id" :value="unit.name">{{ unit.name }}</option>
      </select>
      <div v-if="form.errors.unit" class="mt-1 text-xs text-rose-300">{{ form.errors.unit }}</div>
    </div>

    <div>
      <label class="mb-1 block text-sm text-slate-300">Default Price</label>
      <input v-model="form.default_price" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-slate-100" />
      <div v-if="form.errors.default_price" class="mt-1 text-xs text-rose-300">{{ form.errors.default_price }}</div>
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
defineProps({
  form: { type: Object, required: true },
  unitOptions: { type: Array, default: () => [] },
});
</script>
