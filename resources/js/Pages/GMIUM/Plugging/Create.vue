<template>
  <AppLayout>
    <div class="p-6 max-w-6xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Create Plugging</h2>
        <Link href="/gmium/plugging" class="text-indigo-400">Back to list</Link>
      </div>

      <form @submit.prevent="submit" class="bg-slate-800 rounded-lg border border-slate-700 overflow-hidden">
        <div class="border-b border-slate-700 p-4">
          <h3 class="text-xl font-semibold tracking-wide text-center">FORM PLUGGING</h3>
        </div>

        <div class="p-4 border-b border-slate-700">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div class="md:col-span-2 relative group">
              <SearchableSelect
                v-model="form.customer_id"
                :options="customers"
                option-value="id"
                option-label="name"
                placeholder=" "
                empty-label="Pilih Customer"
                input-class="w-full pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
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
                Nama Customer
              </label>
              <div v-if="form.errors.customer_id" class="text-red-400 text-sm mt-1">{{ form.errors.customer_id }}</div>
            </div>
            <div class="relative group">
              <EnhancedDatePicker
                v-model="form.tanggal"
                placeholder=" "
                input-class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
              />
              <label
                :class="[
                  'pointer-events-none absolute left-3 z-10 transition-all',
                  (form.tanggal
                    ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                    : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                  'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                ]"
              >
                Tanggal Transaksi
              </label>
              <div v-if="form.errors.tanggal" class="text-red-400 text-sm mt-1">{{ form.errors.tanggal }}</div>
            </div>
          </div>
        </div>

        <div class="p-4 border-b border-slate-700">
          <p class="text-base font-medium mb-3">Dilakukan proses <span class="font-semibold">PLUGGING</span> untuk transaksi sbb :</p>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <div class="relative">
              <input v-model="form.nomor_dokumen" type="text" placeholder=" " class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
              <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Nomor Dokumen</label>
              <div v-if="form.errors.nomor_dokumen" class="text-red-400 text-sm mt-1">{{ form.errors.nomor_dokumen }}</div>
            </div>

            <div class="relative">
              <input v-model="form.transporter" type="text" placeholder=" " class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
              <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Transporter</label>
              <div v-if="form.errors.transporter" class="text-red-400 text-sm mt-1">{{ form.errors.transporter }}</div>
            </div>
            <div class="relative">
              <input v-model="form.rencana_waktu_kedatangan" type="text" placeholder=" " class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
              <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Rencana Waktu Kedatangan</label>
              <div v-if="form.errors.rencana_waktu_kedatangan" class="text-red-400 text-sm mt-1">{{ form.errors.rencana_waktu_kedatangan }}</div>
            </div>

            <div class="relative">
              <input v-model="form.jumlah_kendaraan" type="number" min="0" placeholder=" " class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
              <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Jumlah Kendaraan</label>
              <div v-if="form.errors.jumlah_kendaraan" class="text-red-400 text-sm mt-1">{{ form.errors.jumlah_kendaraan }}</div>
            </div>
            <div class="relative">
              <input v-model="form.no_container_no_polisi" type="text" placeholder=" " class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
              <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">No. Polisi/Container & Nama Supir</label>
              <div v-if="form.errors.no_container_no_polisi" class="text-red-400 text-sm mt-1">{{ form.errors.no_container_no_polisi }}</div>
            </div>

            <div class="relative group">
              <div ref="vehicleTypePickerRef" class="relative">
                <input
                  v-model="vehicleTypeQuery"
                  type="text"
                  placeholder=" "
                  :class="[
                    'w-full pl-3 pr-10 pt-5 pb-2 bg-slate-800 border border-slate-700 focus:outline-none placeholder-transparent',
                    showVehicleTypePicker ? 'rounded-t-lg rounded-b-none' : 'rounded-lg',
                  ]"
                  @input="syncVehicleTypeFromQuery"
                  @change="syncVehicleTypeFromQuery"
                />
                <button
                  type="button"
                  class="absolute right-0 top-0 h-full w-10 border-l border-r border-y border-slate-700 rounded-r-lg text-sm leading-none flex items-center justify-center bg-slate-800 text-slate-100"
                  @click="showVehicleTypePicker = !showVehicleTypePicker"
                >
                  &#9662;
                </button>
                <div
                  v-if="showVehicleTypePicker"
                  class="absolute z-20 left-0 right-0 top-full max-h-52 overflow-auto rounded-b-lg border border-t-0 border-slate-700 bg-slate-800"
                >
                  <button
                    v-for="vehicleType in visibleVehicleTypes"
                    :key="vehicleType.id"
                    type="button"
                    class="w-full text-left px-3 py-2 text-sm hover:bg-slate-800 border-b border-slate-800 last:border-b-0"
                    @click="selectVehicleTypeOption(vehicleType)"
                  >
                    {{ vehicleType.name }}
                  </button>
                  <div v-if="!visibleVehicleTypes.length" class="px-3 py-2 text-xs text-slate-400">
                    Tidak ada data yang cocok.
                  </div>
                </div>
              </div>
              <label
                :class="[
                  'pointer-events-none absolute left-3 z-10 transition-all',
                  (form.vehicle_type_id || vehicleTypeQuery
                    ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                    : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                  'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                ]"
              >
                Jenis Kendaraan
              </label>
              <div v-if="vehicleTypeQuery && !form.vehicle_type_id" class="text-amber-300 text-xs mt-1">
                Jika tidak ada di daftar, saat simpan akan otomatis ditambahkan ke Master Data.
              </div>
              <div v-if="form.errors.vehicle_type_id" class="text-red-400 text-sm mt-1">{{ form.errors.vehicle_type_id }}</div>
              <div v-if="form.errors.vehicle_type_name" class="text-red-400 text-sm mt-1">{{ form.errors.vehicle_type_name }}</div>
            </div>
            <div class="relative">
              <select v-model="form.pintu_loading" class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700">
                <option value="">Pilih Gate</option>
                <option value="Gate 01">Gate 01</option>
                <option value="Gate 02">Gate 02</option>
                <option value="Gate 04">Gate 04</option>
                <option value="Gate 05">Gate 05</option>
                <option value="Gate 06">Gate 06</option>
                <option value="Gate 07">Gate 07</option>
                <option value="Gate 08">Gate 08</option>
              </select>
              <label class="pointer-events-none absolute left-3 z-10 px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2">Jumlah Pintu Loading</label>
              <div v-if="form.errors.pintu_loading" class="text-red-400 text-sm mt-1">{{ form.errors.pintu_loading }}</div>
            </div>

            <div class="lg:col-span-2 relative">
              <textarea v-model="form.keterangan" rows="2" placeholder=" " class="peer w-full px-3 pt-6 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"></textarea>
              <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-4 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Keterangan</label>
              <div v-if="form.errors.keterangan" class="text-red-400 text-sm mt-1">{{ form.errors.keterangan }}</div>
            </div>
          </div>
        </div>

        <div class="p-4 border-b border-slate-700">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded border border-slate-700">
              <div class="px-3 py-2 border-b border-slate-700 font-semibold">AWAL PLUG</div>
              <div class="p-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="relative group">
                  <EnhancedDatePicker
                    v-model="form.tanggal"
                    placeholder=" "
                    input-class="w-full rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent plugging-row-field"
                  />
                  <label
                    :class="[
                      'pointer-events-none absolute left-3 z-10 transition-all',
                      (form.tanggal
                        ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                        : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                      'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                    ]"
                  >
                    TGL
                  </label>
                </div>
                <div class="relative">
                  <input v-model="form.jam_mulai" type="time" placeholder=" " class="peer w-full rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent plugging-row-field" />
                  <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">JAM</label>
                  <div v-if="form.errors.jam_mulai" class="text-red-400 text-sm mt-1">{{ form.errors.jam_mulai }}</div>
                </div>
                <div class="relative">
                  <input v-model="form.suhu_awal" type="number" step="0.01" placeholder=" " class="peer w-full rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent plugging-row-field" />
                  <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">SUHU AWAL</label>
                  <div v-if="form.errors.suhu_awal" class="text-red-400 text-sm mt-1">{{ form.errors.suhu_awal }}</div>
                </div>
              </div>
            </div>

            <div class="rounded border border-slate-700">
              <div class="px-3 py-2 border-b border-slate-700 font-semibold">AKHIR PLUG</div>
              <div class="p-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="relative group">
                  <EnhancedDatePicker
                    v-model="form.tanggal"
                    placeholder=" "
                    input-class="w-full rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent plugging-row-field"
                  />
                  <label
                    :class="[
                      'pointer-events-none absolute left-3 z-10 transition-all',
                      (form.tanggal
                        ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                        : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                      'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                    ]"
                  >
                    TGL
                  </label>
                </div>
                <div class="relative">
                  <input v-model="form.jam_selesai" type="time" placeholder=" " class="peer w-full rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent plugging-row-field" />
                  <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">JAM</label>
                  <div v-if="form.errors.jam_selesai" class="text-red-400 text-sm mt-1">{{ form.errors.jam_selesai }}</div>
                </div>
                <div class="relative">
                  <input v-model="form.suhu_akhir" type="number" step="0.01" placeholder=" " class="peer w-full rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent plugging-row-field" />
                  <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">SUHU AKHIR</label>
                  <div v-if="form.errors.suhu_akhir" class="text-red-400 text-sm mt-1">{{ form.errors.suhu_akhir }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">
            <div class="relative">
              <label class="block text-sm mb-1">Durasi (auto)</label>
              <div>
                <input :value="durationText" type="text" disabled class="w-full px-3 py-2 rounded bg-slate-700 border border-slate-600" />
              </div>
            </div>
          </div>
        </div>

        <div class="p-4 border-t border-slate-700 flex justify-end gap-3">
          <Link href="/gmium/plugging" class="px-4 py-2 rounded bg-slate-700 text-white hover:bg-slate-600">Cancel</Link>
          <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700" :disabled="form.processing">Save</button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const now = new Date();
const defaultDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
const defaultTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

const props = defineProps({
  customers: {
    type: Array,
    default: () => [],
  },
  vehicleTypes: {
    type: Array,
    default: () => [],
  },
});
const customers = props.customers || [];
const vehicleTypes = props.vehicleTypes || [];
const vehicleTypeQuery = ref('');
const showVehicleTypePicker = ref(false);
const vehicleTypePickerRef = ref(null);

const form = useForm({
  tanggal: defaultDate,
  jam_mulai: defaultTime,
  jam_selesai: '',
  customer_id: '',
  no_container_no_polisi: '',
  suhu_awal: '',
  suhu_akhir: '',
  transporter: '',
  nomor_dokumen: '',
  rencana_waktu_kedatangan: '',
  jumlah_kendaraan: '',
  vehicle_type_id: '',
  vehicle_type_name: '',
  pintu_loading: '',
  keterangan: '',
});

const durationText = computed(() => {
  if (!form.jam_mulai || !form.jam_selesai) return '-';
  const minutes = calcDuration(form.jam_mulai, form.jam_selesai);
  return minutes === null ? '-' : `${minutes} menit`;
});

const visibleVehicleTypes = computed(() => {
  const query = String(vehicleTypeQuery.value || '').trim().toLowerCase();
  if (!query) return vehicleTypes;
  return vehicleTypes.filter((vehicleType) => String(vehicleType?.name || '').toLowerCase().includes(query));
});

function calcDuration(start, end) {
  if (!start || !end) return null;
  const [sh, sm] = start.split(':').map(Number);
  const [eh, em] = end.split(':').map(Number);
  if ([sh, sm, eh, em].some(Number.isNaN)) return null;
  let s = sh * 60 + sm;
  let e = eh * 60 + em;
  if (e < s) e += 24 * 60;
  return e - s;
}

function syncVehicleTypeFromQuery() {
  const query = String(vehicleTypeQuery.value || '').trim().toLowerCase();
  if (!query) {
    form.vehicle_type_id = '';
    return;
  }

  const match = vehicleTypes.find(
    (vehicleType) => String(vehicleType?.name || '').trim().toLowerCase() === query
  );
  form.vehicle_type_id = match ? match.id : '';
  showVehicleTypePicker.value = true;
}

function selectVehicleTypeOption(vehicleType) {
  form.vehicle_type_id = vehicleType?.id || '';
  vehicleTypeQuery.value = String(vehicleType?.name || '');
  showVehicleTypePicker.value = false;
}

function handleOutsideClick(event) {
  const root = vehicleTypePickerRef.value;
  if (!root) return;
  if (!root.contains(event.target)) {
    showVehicleTypePicker.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleOutsideClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick);
});

function submit() {
  form.vehicle_type_name = String(vehicleTypeQuery.value || '').trim();
  form.post('/gmium/plugging');
}
</script>

<style scoped>
:deep(.plugging-row-field) {
  height: 48px !important;
  padding-top: 20px !important;
  padding-bottom: 8px !important;
  padding-left: 12px !important;
  padding-right: 12px !important;
}
</style>
