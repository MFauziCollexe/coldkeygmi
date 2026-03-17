<template>
  <AppLayout>
    <div class="p-6 max-w-2xl">
      <h2 class="text-2xl font-bold mb-4">Ajukan Permintaan Lembur</h2>

      <form @submit.prevent="submit" class="space-y-4 bg-slate-800 p-4 rounded">
        <div class="flex gap-2">
          <button
            type="button"
            class="px-4 py-2 rounded text-sm border"
            :class="activeTab === 'single' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-slate-700 border-slate-700 text-slate-200'"
            @click="setTab('single')"
          >
            Single
          </button>
          <button
            type="button"
            class="px-4 py-2 rounded text-sm border"
            :disabled="!canSubmitForOthers"
            :class="activeTab === 'multi' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-slate-700 border-slate-700 text-slate-200 disabled:opacity-50 disabled:cursor-not-allowed'"
            @click="setTab('multi')"
            title="Hanya untuk supervisor/manager/admin"
          >
            Multi
          </button>
        </div>

        <div v-if="activeTab === 'single'">
          <div v-if="canSelectEmployee" class="relative group">
            <SearchableSelect
              v-model="form.employee_id"
              :options="employees"
              option-value="id"
              option-label="label"
              placeholder=" "
              empty-label="Pilih Karyawan"
              input-class="w-full h-[52px] pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
              button-class="h-[52px] border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (form.employee_id
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Karyawan
            </label>
            <div v-if="form.errors.employee_id" class="text-red-400 text-sm mt-1">{{ form.errors.employee_id }}</div>
          </div>
        </div>

        <div v-else class="space-y-3">
          <div class="text-xs text-slate-400">
            Multi input akan membuat 1 pengajuan overtime untuk setiap karyawan yang dipilih.
          </div>

          <div class="relative">
            <input
              v-model="employeeQuery"
              type="text"
              placeholder="Cari karyawan..."
              class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700"
            />
          </div>

          <div class="flex items-center justify-between text-xs text-slate-400">
            <div>
              Dipilih: <span class="text-slate-200 font-semibold">{{ form.employee_ids.length }}</span>
            </div>
            <div class="flex gap-2">
              <button type="button" class="px-2 py-1 rounded bg-slate-700 text-slate-200" @click="selectAllFiltered">
                Pilih semua
              </button>
              <button type="button" class="px-2 py-1 rounded bg-slate-700 text-slate-200" @click="clearSelected">
                Clear
              </button>
            </div>
          </div>

          <div class="max-h-64 overflow-auto rounded-lg border border-slate-700 bg-slate-900/20">
            <label
              v-for="emp in filteredEmployees"
              :key="emp.id"
              class="flex items-center gap-3 px-3 py-2 border-b border-slate-800 last:border-b-0 cursor-pointer hover:bg-slate-800/40"
            >
              <input
                type="checkbox"
                class="accent-indigo-500"
                :value="emp.id"
                v-model="form.employee_ids"
              />
              <span class="text-sm">{{ emp.label }}</span>
            </label>
            <div v-if="filteredEmployees.length === 0" class="px-3 py-3 text-xs text-slate-400">
              Tidak ada karyawan yang cocok.
            </div>
          </div>

          <div v-if="form.errors.employee_ids" class="text-red-400 text-sm mt-1">{{ form.errors.employee_ids }}</div>
        </div>

        <div>
          <label class="block text-sm text-slate-300 mb-1">Tanggal Lembur</label>
          <EnhancedDatePicker v-model="form.overtime_date" placeholder="dd/mm/yyyy" />
        </div>

        <div>
          <label class="block text-sm text-slate-300 mb-1">Jam Mulai</label>
          <input v-model="form.start_time" type="time" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" />
        </div>

        <div>
          <label class="block text-sm text-slate-300 mb-1">Jam Selesai</label>
          <input v-model="form.end_time" type="time" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" />
        </div>

        <div>
          <label class="block text-sm text-slate-300 mb-1">Alasan</label>
          <textarea v-model="form.reason" rows="3" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" placeholder="Masukkan alasan lembur..."></textarea>
        </div>

        <div>
          <label class="block text-sm text-slate-300 mb-1">Attachment (Gambar)</label>
          <input
            type="file"
            accept="image/*,application/pdf,.pdf"
            class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-200"
            @change="onAttachmentChange"
          />
          <div v-if="form.errors.attachment" class="text-xs text-red-400 mt-1">{{ form.errors.attachment }}</div>
          <div v-else class="text-xs text-slate-400 mt-1">Opsional, maksimal 5MB (jpg/png/webp/pdf).</div>
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" @click="cancel" class="px-4 py-2 rounded bg-slate-700 text-slate-300">
            Batal
          </button>
          <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white">
            Simpan
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  employees: { type: Array, default: () => [] },
  canSelectEmployee: { type: Boolean, default: false },
  canSubmitForOthers: { type: Boolean, default: false },
  defaultEmployeeId: { type: [String, Number], default: '' },
});

const activeTab = ref('single');
const employeeQuery = ref('');

const form = useForm({
  employee_id: props.canSelectEmployee
    ? (props.canSubmitForOthers ? '' : (props.defaultEmployeeId || ''))
    : '',
  employee_ids: [],
  overtime_date: '',
  start_time: '',
  end_time: '',
  reason: '',
  attachment: null,
});

const filteredEmployees = computed(() => {
  const q = String(employeeQuery.value || '').trim().toLowerCase();
  const list = Array.isArray(props.employees) ? props.employees : [];
  if (!q) return list;
  return list.filter((e) => String(e?.label || '').toLowerCase().includes(q));
});

function onAttachmentChange(e) {
  const file = e?.target?.files?.[0] || null;
  form.attachment = file;
}

function setTab(tab) {
  if (tab === 'multi' && !props.canSubmitForOthers) return;
  activeTab.value = tab;
  form.clearErrors();
}

function selectAllFiltered() {
  const ids = filteredEmployees.value.map((e) => Number(e?.id)).filter((id) => Number.isFinite(id) && id > 0);
  const current = new Set((form.employee_ids || []).map((id) => Number(id)));
  for (const id of ids) current.add(id);
  form.employee_ids = Array.from(current);
}

function clearSelected() {
  form.employee_ids = [];
}

function submit() {
  if (!form.overtime_date || !form.start_time || !form.end_time || !form.reason) {
    alert('Mohon lengkapi semua field');
    return;
  }

  if (activeTab.value === 'multi' && (!form.employee_ids || form.employee_ids.length === 0)) {
    alert('Pilih minimal 1 karyawan');
    return;
  }
  
  form.post('/overtime', {
    forceFormData: true,
    onSuccess: () => {
      router.get('/overtime');
    },
    onError: () => {
      const firstError = Object.values(form.errors || {})[0];
      if (firstError) alert(String(firstError));
    },
  });
}

function cancel() {
  router.get('/overtime');
}
</script>
