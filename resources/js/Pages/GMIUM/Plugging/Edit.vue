<template>
  <AppLayout>
    <div class="p-6 max-w-6xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Edit Plugging</h2>
        <Link href="/gmium/plugging" class="text-indigo-400">Back to list</Link>
      </div>

      <form @submit.prevent="submit" class="bg-slate-800 rounded-lg border border-slate-700 overflow-hidden">
        <div class="border-b border-slate-700 p-4">
          <h3 class="text-xl font-semibold tracking-wide text-center">FORM PLUGGING</h3>
        </div>

        <div class="p-4 border-b border-slate-700">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div class="md:col-span-2">
              <label class="block text-sm mb-1">Nama Customer</label>
              <SearchableSelect
                v-model="form.customer_id"
                :options="customers"
                option-value="id"
                option-label="name"
                placeholder="Pilih Customer"
                empty-label="Pilih Customer"
              />
              <div v-if="form.errors.customer_id" class="text-red-400 text-sm mt-1">{{ form.errors.customer_id }}</div>
            </div>
            <div>
              <label class="block text-sm mb-1">Tanggal Transaksi</label>
              <EnhancedDatePicker v-model="form.tanggal" placeholder="dd/mm/yyyy" />
              <div v-if="form.errors.tanggal" class="text-red-400 text-sm mt-1">{{ form.errors.tanggal }}</div>
            </div>
          </div>
        </div>

        <div class="p-4 border-b border-slate-700">
          <p class="text-base font-medium mb-3">Dilakukan proses <span class="font-semibold">PLUGGING</span> untuk transaksi sbb :</p>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm mb-1">Nomor Dokumen</label>
              <input v-model="form.nomor_dokumen" type="text" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
              <div v-if="form.errors.nomor_dokumen" class="text-red-400 text-sm mt-1">{{ form.errors.nomor_dokumen }}</div>
            </div>

            <div>
              <label class="block text-sm mb-1">Transporter</label>
              <input v-model="form.transporter" type="text" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
              <div v-if="form.errors.transporter" class="text-red-400 text-sm mt-1">{{ form.errors.transporter }}</div>
            </div>
            <div>
              <label class="block text-sm mb-1">Rencana Waktu Kedatangan</label>
              <input v-model="form.rencana_waktu_kedatangan" type="text" placeholder="Contoh: 15:00" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
              <div v-if="form.errors.rencana_waktu_kedatangan" class="text-red-400 text-sm mt-1">{{ form.errors.rencana_waktu_kedatangan }}</div>
            </div>

            <div>
              <label class="block text-sm mb-1">Jumlah Kendaraan</label>
              <input v-model="form.jumlah_kendaraan" type="number" min="0" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
              <div v-if="form.errors.jumlah_kendaraan" class="text-red-400 text-sm mt-1">{{ form.errors.jumlah_kendaraan }}</div>
            </div>
            <div>
              <label class="block text-sm mb-1">No. Polisi/Container & Nama Supir</label>
              <input v-model="form.no_container_no_polisi" type="text" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
              <div v-if="form.errors.no_container_no_polisi" class="text-red-400 text-sm mt-1">{{ form.errors.no_container_no_polisi }}</div>
            </div>

            <div>
              <label class="block text-sm mb-1">Jenis Kendaraan</label>
              <div ref="vehicleTypePickerRef" class="relative">
                <input
                  v-model="vehicleTypeQuery"
                  type="text"
                  placeholder="Ketik untuk cari jenis kendaraan"
                  :class="[
                    'w-full pl-3 pr-10 py-2 bg-slate-900 border border-slate-700 focus:outline-none',
                    showVehicleTypePicker ? 'rounded-t rounded-b-none' : 'rounded',
                  ]"
                  @input="syncVehicleTypeFromQuery"
                  @change="syncVehicleTypeFromQuery"
                />
                <button
                  type="button"
                  class="absolute right-0 top-0 h-full w-10 border-l border-r border-y border-slate-700 rounded-r text-sm leading-none flex items-center justify-center bg-slate-900"
                  @click="showVehicleTypePicker = !showVehicleTypePicker"
                >
                  &#9662;
                </button>
                <div
                  v-if="showVehicleTypePicker"
                  class="absolute z-20 left-0 right-0 top-full max-h-52 overflow-auto rounded-b border border-t-0 border-slate-700 bg-slate-900"
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
              <div v-if="vehicleTypeQuery && !form.vehicle_type_id" class="text-amber-300 text-xs mt-1">
                Pilih jenis kendaraan dari daftar yang tersedia.
              </div>
              <div v-if="form.errors.vehicle_type_id" class="text-red-400 text-sm mt-1">{{ form.errors.vehicle_type_id }}</div>
            </div>
            <div>
              <label class="block text-sm mb-1">Jumlah Pintu Loading</label>
              <select v-model="form.pintu_loading" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700">
                <option value="">Pilih Gate</option>
                <option value="Gate 01">Gate 01</option>
                <option value="Gate 02">Gate 02</option>
                <option value="Gate 04">Gate 04</option>
                <option value="Gate 05">Gate 05</option>
                <option value="Gate 06">Gate 06</option>
                <option value="Gate 07">Gate 07</option>
                <option value="Gate 08">Gate 08</option>
              </select>
              <div v-if="form.errors.pintu_loading" class="text-red-400 text-sm mt-1">{{ form.errors.pintu_loading }}</div>
            </div>

            <div class="lg:col-span-2">
              <label class="block text-sm mb-1">Keterangan</label>
              <textarea v-model="form.keterangan" rows="2" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700"></textarea>
              <div v-if="form.errors.keterangan" class="text-red-400 text-sm mt-1">{{ form.errors.keterangan }}</div>
            </div>
          </div>
        </div>

        <div class="p-4 border-b border-slate-700">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded border border-slate-700">
              <div class="px-3 py-2 border-b border-slate-700 font-semibold">AWAL PLUG</div>
              <div class="p-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                  <label class="block text-sm mb-1">TGL</label>
                  <EnhancedDatePicker v-model="form.tanggal" placeholder="dd/mm/yyyy" />
                </div>
                <div>
                  <label class="block text-sm mb-1">JAM</label>
                  <input v-model="form.jam_mulai" type="time" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
                  <div v-if="form.errors.jam_mulai" class="text-red-400 text-sm mt-1">{{ form.errors.jam_mulai }}</div>
                </div>
                <div>
                  <label class="block text-sm mb-1">SUHU AWAL</label>
                  <input v-model="form.suhu_awal" type="number" step="0.01" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
                  <div v-if="form.errors.suhu_awal" class="text-red-400 text-sm mt-1">{{ form.errors.suhu_awal }}</div>
                </div>
              </div>
            </div>

            <div class="rounded border border-slate-700">
              <div class="px-3 py-2 border-b border-slate-700 font-semibold">AKHIR PLUG</div>
              <div class="p-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                  <label class="block text-sm mb-1">TGL</label>
                  <EnhancedDatePicker v-model="form.tanggal" placeholder="dd/mm/yyyy" />
                </div>
                <div>
                  <label class="block text-sm mb-1">JAM</label>
                  <input v-model="form.jam_selesai" type="time" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
                  <div v-if="form.errors.jam_selesai" class="text-red-400 text-sm mt-1">{{ form.errors.jam_selesai }}</div>
                </div>
                <div>
                  <label class="block text-sm mb-1">SUHU AKHIR</label>
                  <input v-model="form.suhu_akhir" type="number" step="0.01" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
                  <div v-if="form.errors.suhu_akhir" class="text-red-400 text-sm mt-1">{{ form.errors.suhu_akhir }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">
            <div>
              <label class="block text-sm mb-1">Durasi (auto)</label>
              <input :value="durationText" type="text" disabled class="w-full px-3 py-2 rounded bg-slate-700 border border-slate-600" />
            </div>
          </div>
        </div>

        <div class="p-4 border-t border-slate-700 flex justify-end gap-3">
          <Link href="/gmium/plugging" class="px-4 py-2 rounded bg-slate-700 text-white hover:bg-slate-600">Cancel</Link>
          <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700" :disabled="form.processing">Save Changes</button>
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

const props = defineProps({
  plugging: Object,
  customers: {
    type: Array,
    default: () => [],
  },
  selectedCustomerId: {
    type: Number,
    default: null,
  },
  vehicleTypes: {
    type: Array,
    default: () => [],
  },
  selectedVehicleTypeId: {
    type: Number,
    default: null,
  },
});
const customers = props.customers || [];
const vehicleTypes = props.vehicleTypes || [];
const initialVehicleType = vehicleTypes.find((item) => Number(item.id) === Number(props.selectedVehicleTypeId));
const vehicleTypeQuery = ref(initialVehicleType?.name || props.plugging?.jenis_kendaraan || '');
const showVehicleTypePicker = ref(false);
const vehicleTypePickerRef = ref(null);

const form = useForm({
  tanggal: toInputDate(props.plugging?.tanggal),
  jam_mulai: props.plugging?.jam_mulai?.slice(0, 5) || '',
  jam_selesai: props.plugging?.jam_selesai?.slice(0, 5) || '',
  customer_id: props.selectedCustomerId || '',
  no_container_no_polisi: props.plugging?.no_container_no_polisi || '',
  suhu_awal: props.plugging?.suhu_awal || '',
  suhu_akhir: props.plugging?.suhu_akhir || '',
  transporter: props.plugging?.transporter || '',
  nomor_dokumen: props.plugging?.nomor_dokumen || '',
  rencana_waktu_kedatangan: props.plugging?.rencana_waktu_kedatangan || '',
  jumlah_kendaraan: props.plugging?.jumlah_kendaraan || '',
  vehicle_type_id: props.selectedVehicleTypeId || '',
  pintu_loading: props.plugging?.pintu_loading || '',
  keterangan: props.plugging?.keterangan || '',
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

function toInputDate(value) {
  if (!value) return '';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return '';
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${d.getFullYear()}-${mm}-${dd}`;
}

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
  form.put(`/gmium/plugging/${props.plugging.id}`);
}
</script>
