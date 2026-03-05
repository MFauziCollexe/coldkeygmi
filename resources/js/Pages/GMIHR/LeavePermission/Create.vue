<template>
  <AppLayout>
    <div class="p-6 max-w-2xl">
      <h2 class="text-2xl font-bold mb-4">Ajukan Permintaan Cuti/Izin</h2>

      <form @submit.prevent="submit" class="space-y-4 bg-slate-800 p-4 rounded">
        <div class="relative group">
          <SearchableSelect
            v-model="form.type"
            :options="requestTypeOptions"
            option-value="value"
            option-label="label"
            placeholder=" "
            empty-label="Jenis Permintaan"
            input-class="w-full h-[52px] pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
            button-class="h-[52px] border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.type
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Jenis Permintaan
          </label>
        </div>

        <div class="relative group">
          <EnhancedDatePicker
            v-model="form.start_date"
            placeholder=" "
            input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.start_date
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Tanggal Mulai
          </label>
        </div>

        <div class="relative group">
          <EnhancedDatePicker
            v-model="form.end_date"
            placeholder=" "
            input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.end_date
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Tanggal Selesai
          </label>
        </div>

        <div class="relative">
          <textarea
            v-model="form.reason"
            rows="3"
            placeholder=" "
            class="peer w-full px-3 pt-6 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          ></textarea>
          <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-4 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">
            Alasan
          </label>
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
import { useForm } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const requestTypeOptions = [
  { value: 'cuti', label: 'Cuti' },
  { value: 'izin', label: 'Izin' },
  { value: 'sakit', label: 'Sakit' },
  { value: 'dinas_luar', label: 'Dinas Luar' },
];

const form = useForm({
  type: '',
  start_date: '',
  end_date: '',
  reason: '',
});

function submit() {
  if (!form.start_date || !form.end_date || !form.reason) {
    alert('Mohon lengkapi semua field');
    return;
  }
  
  form.post('/leave-permission', {
    onSuccess: () => {
      Inertia.get('/leave-permission');
    },
  });
}

function cancel() {
  Inertia.get('/leave-permission');
}
</script>
