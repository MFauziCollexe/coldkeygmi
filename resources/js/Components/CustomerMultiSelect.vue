<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between gap-3">
      <label class="text-sm font-medium text-slate-300">Customer Accounts</label>
      <span class="text-xs text-slate-400">{{ selectedIds.length }} dipilih</span>
    </div>
    <p class="text-xs text-slate-400">Jika tidak ada customer dipilih, user tetap dapat melihat semua customer.</p>

    <input
      v-model="search"
      type="search"
      placeholder="Cari nama atau kode customer..."
      class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-500"
    />

    <div class="overflow-hidden rounded-lg border border-slate-700">
      <div class="flex items-center justify-between border-b border-slate-700 bg-slate-900/60 px-3 py-2">
        <span class="text-xs text-slate-400">{{ filteredCustomers.length }} customer</span>
        <div class="flex items-center gap-3">
          <button type="button" class="text-xs font-medium text-indigo-300 hover:text-indigo-200" @click="toggleVisible">
            {{ allVisibleSelected ? 'Hapus pilihan terlihat' : 'Pilih yang terlihat' }}
          </button>
          <button
            v-if="selectedIds.length"
            type="button"
            class="text-xs text-slate-400 hover:text-white"
            @click="emit('update:modelValue', [])"
          >
            Hapus semua
          </button>
        </div>
      </div>

      <div class="max-h-64 overflow-y-auto divide-y divide-slate-700/70">
        <label
          v-for="customer in filteredCustomers"
          :key="customer.id"
          class="flex cursor-pointer items-center gap-3 px-3 py-2.5 text-sm hover:bg-slate-700/50"
        >
          <input
            type="checkbox"
            :checked="selectedIds.includes(Number(customer.id))"
            class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500"
            @change="toggleCustomer(customer.id)"
          />
          <span class="min-w-0 flex-1 truncate text-slate-200">{{ customer.name }}</span>
          <span v-if="customer.code" class="shrink-0 text-xs text-slate-400">{{ customer.code }}</span>
        </label>
        <p v-if="!filteredCustomers.length" class="px-3 py-6 text-center text-sm text-slate-400">
          Customer tidak ditemukan.
        </p>
      </div>
    </div>

    <p v-if="error" class="text-sm text-red-400">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  options: {
    type: Array,
    default: () => [],
  },
  error: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue']);
const search = ref('');
const selectedIds = computed(() => props.modelValue.map(Number));
const filteredCustomers = computed(() => {
  const query = search.value.trim().toLocaleLowerCase();
  if (!query) return props.options;

  return props.options.filter((customer) =>
    `${customer.name || ''} ${customer.code || ''}`.toLocaleLowerCase().includes(query)
  );
});
const allVisibleSelected = computed(() =>
  filteredCustomers.value.length > 0
  && filteredCustomers.value.every((customer) => selectedIds.value.includes(Number(customer.id)))
);

function toggleCustomer(customerId) {
  const id = Number(customerId);
  const currentIds = selectedIds.value;
  emit(
    'update:modelValue',
    currentIds.includes(id) ? currentIds.filter((selectedId) => selectedId !== id) : [...currentIds, id]
  );
}

function toggleVisible() {
  const visibleIds = new Set(filteredCustomers.value.map((customer) => Number(customer.id)));
  emit(
    'update:modelValue',
    allVisibleSelected.value
      ? selectedIds.value.filter((id) => !visibleIds.has(id))
      : [...new Set([...selectedIds.value, ...visibleIds])]
  );
}
</script>
