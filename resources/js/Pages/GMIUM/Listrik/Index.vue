<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-bold">Listrik - Pencatatan</h2>
        <div class="flex flex-col gap-2 sm:flex-row">
          <button
            type="button"
            @click="openModal"
            class="inline-flex items-center justify-center rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
          >
            Tambah
          </button>
        </div>
      </div>

      <div class="mb-4">
        <form @submit.prevent="search" class="flex flex-col gap-3 lg:flex-row">
          <input v-model="bulan" type="month" class="w-full rounded border border-slate-300 px-4 py-2 bg-white text-black lg:max-w-xs" />
          <button type="submit" class="w-full rounded bg-indigo-600 px-4 py-2 text-white lg:w-auto">Tampilkan</button>
          <button type="button" @click="resetSearch" class="w-full rounded bg-gray-500 px-4 py-2 text-white lg:w-auto">Reset</button>
        </form>
      </div>

      <div class="mb-4">
        <div class="rounded-md border border-slate-200 bg-white overflow-hidden shadow-sm">
          <div class="bg-white">
            <ul class="flex">
              <li v-for="(tab, i) in lokasiTabs" :key="tab.value" class="-mb-px">
                <button
                  type="button"
                  @click="selectLokasi(tab.value)"
                  class="px-4 py-2 text-sm font-semibold border border-slate-200 bg-white transition"
                  :class="[
                    activeLokasi === tab.value ? 'border-b-0 text-slate-900' : 'text-slate-600 hover:text-slate-800',
                    i === 0 ? 'rounded-l-md' : '',
                    i === lokasiTabs.length - 1 ? 'rounded-r-md' : ''
                  ]"
                >
                  {{ tab.label }}
                </button>
              </li>
            </ul>
          </div>

          <div class="p-4 bg-white text-black">
            <div class="overflow-x-auto">
              <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-100">
                          <th class="border border-black px-2 py-1 text-center">#</th>
                          <th class="border border-black px-2 py-1 text-center">Tanggal</th>
                          <th class="border border-black px-2 py-1 text-center">Jam</th>
                          <th class="border border-black px-2 py-1 text-center">LbP</th>
                          <th v-if="activeLokasi === 'GMI'" class="border border-black px-2 py-1 text-center">WbP</th>
                          <th v-if="activeLokasi === 'GMI'" class="border border-black px-2 py-1 text-center">Total</th>
                          <th v-if="activeLokasi === 'GMI'" class="border border-black px-2 py-1 text-center">Kvarh</th>
                          <th class="border border-black px-2 py-1 text-center">Foto</th>
                          <th v-if="isIT" class="border border-black px-2 py-1 text-center">Aksi</th>
                        </tr>
                </thead>
                <tbody>
                  <tr v-for="(r, idx) in records.data" :key="r.id">
                    <td class="border border-black px-2 py-1 text-center">{{ (records.current_page - 1) * records.per_page + idx + 1 }}</td>
                    <td class="border border-black px-2 py-1 text-center">{{ formatDate(r.tanggal) || '-' }}</td>
                    <td class="border border-black px-2 py-1 text-center">{{ r.jam || '-' }}</td>
                    <td class="border border-black px-2 py-1 text-right">{{ formatNumber(r.lbp) }}</td>
                    <td v-if="activeLokasi === 'GMI'" class="border border-black px-2 py-1 text-right">{{ formatNumber(r.wbp) }}</td>
                    <td v-if="activeLokasi === 'GMI'" class="border border-black px-2 py-1 text-right">{{ formatNumber(r.total) }}</td>
                    <td v-if="activeLokasi === 'GMI'" class="border border-black px-2 py-1 text-right">{{ formatNumber(r.kvarh) }}</td>
                    <td class="border border-black px-2 py-1 text-center">
                      <div v-if="activeLokasi === 'GMI'" class="flex flex-wrap justify-center gap-1">
                        <template v-for="(fu, i) in gmiPhotos(r)" :key="i">
                          <a v-if="fu" :href="fu" target="_blank" rel="noopener">
                            <img :src="fu" :alt="`Foto ${i + 1}`" class="h-10 w-10 rounded object-cover" />
                          </a>
                          <span v-else class="inline-block h-10 w-10 border border-dashed border-slate-300 text-xs leading-10 text-slate-300">-</span>
                        </template>
                      </div>
                      <a v-else-if="r.foto_url" :href="r.foto_url" target="_blank" rel="noopener">
                        <img :src="r.foto_url" alt="Foto meter listrik" class="inline-block h-10 w-10 rounded object-cover" />
                      </a>
                      <span v-else class="text-slate-400">-</span>
                    </td>
                    <td v-if="isIT" class="border border-black px-2 py-1 text-center">
                      <button
                        type="button"
                        class="rounded bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-800 hover:bg-slate-300"
                        @click="openEdit(r)"
                      >
                        Edit
                      </button>
                    </td>
                  </tr>
                  <tr v-if="records.data.length === 0">
                    <td :colspan="(activeLokasi === 'GMI' ? 8 : 5) + (isIT ? 1 : 0)" class="border border-black px-2 py-4 text-center text-slate-400">Tidak ada data</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-4">
              <Pagination :paginator="records" :onPageChange="goToPage" />
            </div>
          </div>
        </div>
      </div>

    <!-- Modal Tambah -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
      @click.self="closeModal"
    >
      <div class="w-full max-w-md overflow-hidden rounded-xl border border-slate-300 bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between gap-4">
          <h3 class="text-base font-semibold text-black">{{ editingId ? 'Edit Data Listrik' : 'Tambah Data Listrik' }}</h3>
          <button
            type="button"
            class="rounded bg-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-300"
            @click="closeModal"
          >
            Tutup
          </button>
        </div>

        <form @submit.prevent="saveRecord" class="space-y-4">
          <div class="col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Lokasi</label>
            <div>
              <select
                v-model="form.lokasi"
                required
                class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              >
                <option value="GMI">GMI</option>
                <option value="CRMI">CRMI</option>
                <option value="Office">Office</option>
              </select>
            </div>
            <p v-if="errors.lokasi" class="mt-1 text-xs text-red-600">{{ errors.lokasi }}</p>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div v-for="field in formFields" :key="field.key">
              <label class="mb-1 block text-sm font-medium text-slate-700">{{ field.label }}</label>
              <div>
                <input
                  v-model="form[field.key]"
                  type="text"
                  inputmode="decimal"
                  required
                  class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                />
              </div>
              <p v-if="errors[field.key]" class="mt-1 text-xs text-red-600">{{ errors[field.key] }}</p>
            </div>
          </div>

          <div v-if="isIT" class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal</label>
              <input
                v-model="form.tanggal"
                type="date"
                class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
              <p v-if="errors.tanggal" class="mt-1 text-xs text-red-600">{{ errors.tanggal }}</p>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Jam</label>
              <input
                v-model="form.jam"
                type="time"
                class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
              <p v-if="errors.jam" class="mt-1 text-xs text-red-600">{{ errors.jam }}</p>
            </div>
          </div>

          <div class="rounded border border-slate-200 p-3">
            <label class="mb-2 block text-sm font-medium text-slate-700">
              Foto {{ form.lokasi === 'GMI' ? '' : 'Meter / Papan' }}
            </label>
            <input
              ref="cameraInput"
              type="file"
              accept="image/*"
              capture="environment"
              class="hidden"
              @change="handlePhotoChange"
            />
            <input
              ref="galleryInput"
              type="file"
              accept="image/*"
              class="hidden"
              @change="handlePhotoChange"
            />

            <div v-if="photos.length" class="mb-3 grid grid-cols-4 gap-2">
              <div
                v-for="(photo, i) in photos"
                :key="photo.preview"
                class="relative"
              >
                <img
                  :src="photo.preview"
                  :alt="`Foto ${i + 1}`"
                  class="h-20 w-20 rounded border border-slate-300 object-cover"
                />
                <button
                  type="button"
                  class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-[11px] font-bold text-white shadow hover:bg-red-700"
                  :aria-label="`Hapus foto ${i + 1}`"
                  title="Hapus foto"
                  @click="removePhoto(i)"
                >
                  ×
                </button>
              </div>
            </div>

            <div v-if="photos.length < maxPhotos" class="flex flex-wrap items-center gap-2">
              <button
                type="button"
                class="rounded bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
                @click="triggerPhotoCamera"
              >
                <span v-if="photos.length">+ Tambah Foto</span>
                <span v-else>Ambil Foto</span>
              </button>
              <button
                type="button"
                class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                @click="triggerPhotoGallery"
              >
                <span v-if="photos.length">+ Dari Galeri</span>
                <span v-else>Buka Galeri</span>
              </button>
              <span v-if="maxPhotos > 1" class="text-xs text-slate-500">{{ photos.length }} / {{ maxPhotos }} foto</span>
            </div>
            <p v-else class="text-xs text-slate-500">Maksimal {{ maxPhotos }} foto telah ditambahkan.</p>

            <p v-if="errors.foto || errors.foto_1" class="mt-1 text-xs text-red-600">
              {{ errors.foto || errors.foto_1 }}
            </p>
          </div>

          <p v-if="!isIT" class="rounded bg-slate-100 px-3 py-2 text-xs text-slate-600">
            Tanggal dan Jam akan terisi otomatis (waktu sekarang) saat tombol Simpan ditekan.
          </p>
          <p v-else class="rounded bg-slate-100 px-3 py-2 text-xs text-slate-600">
            Role IT dapat memilih Tanggal (termasuk tanggal lama / masa lalu atau setelah hari ini) dan Jam pencatatan.
          </p>

          <div class="flex items-center justify-end gap-2">
            <button
              type="button"
              class="rounded bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-300"
              @click="closeModal"
            >
              Batal
            </button>
            <button
              type="submit"
              class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            >
              {{ editingId ? 'Simpan Perubahan' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
      </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { compressImageToMax } from '@/Utils/imageCompression';

const props = defineProps({ records: Object, filters: Object, currentUser: Object });
const records = computed(() => props.records || { data: [] });
const errors = computed(() => usePage().props.errors || {});

const isIT = computed(() =>
  String(props.currentUser?.department_code || '').toUpperCase() === 'IT',
);

function currentMonth() {
  const now = new Date();
  return `${now.getFullYear()}-${`${now.getMonth() + 1}`.padStart(2, '0')}`;
}
const bulan = ref(props.filters?.bulan || currentMonth());

const lokasiTabs = [
  { label: 'GMI', value: 'GMI' },
  { label: 'CRMI', value: 'CRMI' },
  { label: 'Office', value: 'Office' },
];
const activeLokasi = computed(() => props.filters?.lokasi || 'GMI');

function lokasiParam(value) {
  return value === 'all' ? '' : value;
}

function selectLokasi(value) {
  router.get(
    '/gmium/listrik',
    { lokasi: lokasiParam(value), bulan: bulan.value },
    { preserveState: true, preserveScroll: true },
  );
}

const showModal = ref(false);
const allFormFields = [
  { key: 'lbp', label: 'LbP' },
  { key: 'wbp', label: 'WbP' },
  { key: 'total', label: 'Total' },
  { key: 'kvarh', label: 'Kvarh' },
];
const formFields = computed(() => {
  if (form.value.lokasi === 'CRMI' || form.value.lokasi === 'Office') {
    return allFormFields.filter((f) => f.key === 'lbp');
  }
  return allFormFields;
});
const form = ref({ lokasi: 'GMI', lbp: '', wbp: '', total: '', kvarh: '', tanggal: '', jam: '' });
const editingId = ref(null);

const photos = ref([]);
const cameraInput = ref(null);
const galleryInput = ref(null);

const maxPhotos = computed(() => (form.value.lokasi === 'GMI' ? 4 : 1));

function resetPhotos() {
  photos.value = [];
  if (cameraInput.value) cameraInput.value.value = '';
  if (galleryInput.value) galleryInput.value.value = '';
}

function openModal() {
  editingId.value = null;
  form.value = { lokasi: 'GMI', lbp: '', wbp: '', total: '', kvarh: '', tanggal: '', jam: '' };
  resetPhotos();
  showModal.value = true;
}

function openEdit(record) {
  editingId.value = record.id;
  form.value = {
    lokasi: record.lokasi,
    lbp: record.lbp ?? '',
    wbp: record.wbp ?? '',
    total: record.total ?? '',
    kvarh: record.kvarh ?? '',
    tanggal: record.tanggal ?? '',
    jam: record.jam ?? '',
  };
  resetPhotos();
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingId.value = null;
}

function triggerPhotoCamera() {
  cameraInput.value?.click();
}

function triggerPhotoGallery() {
  galleryInput.value?.click();
}

async function handlePhotoChange(event) {
  const file = event.target.files?.[0];
  event.target.value = '';
  if (!file) return;
  if (photos.value.length >= maxPhotos.value) {
    alert(`Maksimal ${maxPhotos.value} foto per pencatatan.`);
    return;
  }
  if (!String(file.type || '').startsWith('image/')) {
    alert('File yang dipilih bukan gambar.');
    return;
  }
  try {
    const processed = await compressImageToMax(file);
    photos.value.push({ file: processed, preview: URL.createObjectURL(processed) });
  } catch (error) {
    alert(error?.message || 'Foto gagal diproses.');
  }
}

function removePhoto(index) {
  const removed = photos.value.splice(index, 1)[0];
  if (removed?.preview) URL.revokeObjectURL(removed.preview);
}

function normalizeNumber(value) {
  if (value === null || value === undefined || value === '') return '';
  return String(value).replace(/,/g, '.');
}

function saveRecord() {
  const payload = {
    lokasi: form.value.lokasi,
    lbp: normalizeNumber(form.value.lbp),
    wbp: normalizeNumber(form.value.wbp),
    total: normalizeNumber(form.value.total),
    kvarh: normalizeNumber(form.value.kvarh) === '' ? null : normalizeNumber(form.value.kvarh),
  };
  if (isIT.value && form.value.tanggal) {
    payload.tanggal = form.value.tanggal;
    if (form.value.jam) payload.jam = form.value.jam;
  }
  photos.value.forEach((photo, i) => {
    if (form.value.lokasi === 'GMI') {
      if (i < 4) payload[`foto_${i + 1}`] = photo.file;
    } else if (i === 0) {
      payload.foto = photo.file;
    }
  });
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false;
      editingId.value = null;
      form.value = { lokasi: 'GMI', lbp: '', wbp: '', total: '', kvarh: '', tanggal: '', jam: '' };
      resetPhotos();
    },
  };
  if (editingId.value) {
    router.post(`/gmium/listrik/${editingId.value}`, { ...payload, _method: 'put' }, options);
    return;
  }
  router.post('/gmium/listrik', payload, options);
}

function search() {
  router.get('/gmium/listrik', { bulan: bulan.value, lokasi: lokasiParam(activeLokasi.value) }, { preserveState: true, preserveScroll: true });
}

function resetSearch() {
  bulan.value = currentMonth();
  router.get('/gmium/listrik', { bulan: bulan.value, lokasi: lokasiParam(activeLokasi.value) }, { preserveState: true, preserveScroll: true });
}

function goToPage(page) {
  router.get('/gmium/listrik', { page: page, bulan: bulan.value, lokasi: lokasiParam(activeLokasi.value) }, { preserveState: true, preserveScroll: true });
}

function formatDate(d) {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('id-ID');
}

function formatNumber(v) {
  if (v === null || v === undefined || v === '') return '-';
  return Number(v).toLocaleString('id-ID', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
}

function gmiPhotos(r) {
  return [r?.foto_url, r?.foto_url_2, r?.foto_url_3, r?.foto_url_4];
}
</script>
