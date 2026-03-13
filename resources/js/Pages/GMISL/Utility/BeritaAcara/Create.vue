<template>
  <AppLayout>
    <div class="p-6 space-y-4 max-w-5xl">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h2 class="text-2xl font-bold">Buat Berita Acara</h2>
          <p class="text-slate-400 text-sm">Isi data sesuai format BA.</p>
        </div>
        <Link href="/gmisl/utility/berita-acara" class="text-indigo-400 hover:underline text-sm">← Back to List</Link>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
        <form @submit.prevent="submit" class="space-y-4">
          <div class="w-full md:max-w-[640px]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="relative">
                <input
                  :value="defaults.document_number || ''"
                  placeholder=" "
                  disabled
                  class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 font-mono disabled:opacity-100"
                />
                <label
                  class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
                >
                  No. Dokumen
                </label>
              </div>

              <div class="relative">
                <input
                  :value="defaults.ba_number || ''"
                  placeholder=" "
                  disabled
                  class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 font-mono disabled:opacity-100"
                />
                <label
                  class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
                >
                  No BA
                </label>
              </div>

              <div class="relative group">
                <EnhancedDatePicker
                  v-model="form.incident_date"
                  placeholder=" "
                  input-class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
                />
                <label
                  :class="[
                    'pointer-events-none absolute left-3 z-10 transition-all',
                    (form.incident_date
                      ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                      : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                    'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                  ]"
                >
                  Tanggal Kejadian
                </label>
                <div v-if="form.errors.incident_date" class="text-rose-300 text-xs mt-1">{{ form.errors.incident_date }}</div>
              </div>

              <div class="relative group">
                <EnhancedDatePicker
                  :model-value="defaults.created_date || ''"
                  disabled
                  placeholder=" "
                  input-class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent text-slate-400"
                />
                <label
                  :class="[
                    'pointer-events-none absolute left-3 z-10 transition-all',
                    (defaults.created_date
                      ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                      : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                    'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                  ]"
                >
                  Tanggal Dibuat
                </label>
              </div>

              <div class="relative">
                <input
                  v-model="form.incident_place"
                  type="text"
                  placeholder=" "
                  class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700"
                />
                <label
                  class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
                >
                  Tempat Kejadian
                </label>
                <div v-if="form.errors.incident_place" class="text-rose-300 text-xs mt-1">{{ form.errors.incident_place }}</div>
              </div>

              <div class="relative">
                <input
                  v-model="form.incident_time"
                  type="time"
                  placeholder=" "
                  class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700"
                />
                <label
                  class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
                >
                  Waktu Kejadian
                </label>
                <div v-if="form.errors.incident_time" class="text-rose-300 text-xs mt-1">{{ form.errors.incident_time }}</div>
              </div>

              <div class="relative group">
                <SearchableSelect
                  v-model="form.customer_id"
                  :options="customers"
                  option-value="id"
                  option-label="name"
                  placeholder=" "
                  empty-label="Pilih customer"
                  input-class="w-full pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100"
                  button-class="border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
                />
                <label
                  :class="[
                    'pointer-events-none absolute left-3 z-10 transition-all',
                    (form.customer_id
                      ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                      : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                    'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                  ]"
                >
                  Customer
                </label>
                <div v-if="form.errors.customer_id" class="text-rose-300 text-xs mt-1">{{ form.errors.customer_id }}</div>
              </div>

              <div class="relative group">
                <SearchableSelect
                  v-model="form.department_id"
                  :options="departments"
                  option-value="id"
                  option-label="name"
                  placeholder=" "
                  empty-label="Pilih divisi"
                  input-class="w-full pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100"
                  button-class="border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
                />
                <label
                  :class="[
                    'pointer-events-none absolute left-3 z-10 transition-all',
                    (form.department_id
                      ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                      : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                    'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                  ]"
                >
                  Divisi
                </label>
                <div v-if="form.errors.department_id" class="text-rose-300 text-xs mt-1">{{ form.errors.department_id }}</div>
              </div>

              <div class="relative">
                <input v-model="form.vehicle_no" type="text" placeholder=" " class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700" />
                <label
                  class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
                >
                  No Mobil
                </label>
                <div v-if="form.errors.vehicle_no" class="text-rose-300 text-xs mt-1">{{ form.errors.vehicle_no }}</div>
              </div>
            </div>
          </div>

          <div class="relative">
            <textarea
              v-model="form.chronology"
              rows="8"
              placeholder=" "
              class="peer w-full px-3 pt-6 pb-2 rounded-lg bg-slate-800 border border-slate-700"
            ></textarea>
            <label
              class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-4 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
            >
              Kronologis Kejadian
            </label>
            <div v-if="form.errors.chronology" class="text-rose-300 text-xs mt-1">{{ form.errors.chronology }}</div>
          </div>

          <div class="flex justify-end">
            <button type="submit" class="bg-indigo-600 px-4 py-2 rounded text-white" :disabled="form.processing">
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  defaults: { type: Object, default: () => ({}) },
  customers: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
});

const form = useForm({
  incident_date: String(props.defaults?.incident_date || ''),
  incident_place: '',
  incident_time: '',
  customer_id: '',
  vehicle_no: '',
  department_id: '',
  chronology: '',
});

function submit() {
  form.post('/gmisl/utility/berita-acara');
}
</script>
