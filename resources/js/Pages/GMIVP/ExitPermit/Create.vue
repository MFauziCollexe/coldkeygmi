<template>
  <AppLayout>
    <div class="p-6 max-w-3xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Create Exit Permit</h2>
        <Link href="/gmi-visitor-permit/exit-permit" class="text-indigo-400 hover:underline text-sm">
          Back to List
        </Link>
      </div>

      <form class="space-y-4 bg-slate-800 p-4 rounded" @submit.prevent="submit">
        <div class="relative group">
          <EnhancedDatePicker
            v-model="form.request_date"
            placeholder=" "
            input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              form.request_date
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2',
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >Tanggal</label>
        </div>

        <div class="relative">
          <input :value="authProfile?.name || '-'" readonly class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-100" />
          <label class="pointer-events-none absolute left-3 z-10 px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2">Nama</label>
        </div>

        <div class="relative">
          <input :value="authProfile?.department_name || '-'" readonly class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-100" />
          <label class="pointer-events-none absolute left-3 z-10 px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2">Dept/Bagian</label>
        </div>

        <div class="relative">
          <textarea
            v-model="form.purpose"
            rows="3"
            placeholder=" "
            class="peer w-full px-3 pt-6 pb-2 rounded-lg bg-slate-800 border border-slate-700"
          ></textarea>
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-4 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
          >Keperluan</label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="relative">
            <input v-model="form.time_out" type="time" class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2">Pukul Keluar</label>
          </div>
          <div class="relative">
            <input v-model="form.time_back" type="time" class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2">Pukul Kembali</label>
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <Link href="/gmi-visitor-permit/exit-permit" class="bg-slate-600 px-4 py-2 rounded text-white">Cancel</Link>
          <button type="submit" class="bg-indigo-600 px-4 py-2 rounded text-white" :disabled="form.processing">Simpan Surat</button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';

const props = defineProps({
  authProfile: {
    type: Object,
    default: () => ({}),
  },
});

const form = useForm({
  request_date: todayYmd(),
  purpose: '',
  time_out: '',
  time_back: '',
});

function todayYmd() {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function submit() {
  form.post('/gmi-visitor-permit/exit-permit', { preserveScroll: true });
}
</script>
