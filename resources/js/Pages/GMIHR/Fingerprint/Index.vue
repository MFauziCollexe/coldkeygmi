<template>
    <AppLayout>
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-6">Payroll - Fingerprint</h1>

            <!-- Upload Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Upload Data Fingerprint</h2>
                
                <form @submit.prevent="checkData" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">
                            Pilih File CSV
                        </label>
                        <input
                            type="file"
                            ref="fileInput"
                            @change="handleFileSelect"
                            accept=".csv,.txt"
                            class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100
                            "
                        />
                    </div>

                    <!-- Format Info -->
                    <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg mb-4">
                        <h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">Format CSV:</h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Tanggal scan,Tanggal,Jam,PIN,NIP,Nama,Jabatan,Departemen,Kantor,Verifikasi,I/O,Workcode,SN,Mesin
                        </p>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-2">
                            Contoh: 01-01-2026 06:42:19,01-01-2026,06:42:19,T2P251215002,,THOLUT K,,,,1,2,0,FIO66207023190107,Mesin 1
                        </p>
                    </div>

                    <div class="flex gap-4">
                        <button
                            type="submit"
                            :disabled="!selectedFile || loading"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ loading ? 'Memproses...' : 'Check Data' }}
                        </button>

                        <!-- <button
                            type="button"
                            @click="clearData"
                            :disabled="clearing"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ clearing ? 'Menghapus...' : 'Hapus Semua Data' }}
                        </button> -->
                    </div>
                </form>

                <!-- Message -->
                <div v-if="message" 
                    :class="['mt-4 p-4 rounded-lg', message.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']"
                >
                    <p class="font-semibold">{{ message.text }}</p>
                </div>
            </div>

            <!-- Loading Overlay -->
            <div v-if="saving || loading" class="fixed inset-0 bg-transparent flex items-center justify-center z-50">
                <div class="bg-white dark:bg-white/90 rounded-lg p-8 text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p class="text-lg font-semibold text-gray-700 dark:text-black mb-3">{{ activeLoadingMessage }}</p>
                    <div class="w-72 h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-blue-600 transition-all duration-300 ease-out"
                            :style="{ width: `${activeLoadingProgress}%` }"
                        ></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">{{ activeLoadingProgress }}%</p>
                </div>
            </div>

            <!-- Preview Data Section -->
            <div v-if="previewData.data && previewData.data.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold">Preview Data</h2>
                        <p class="text-sm text-gray-500">Total: {{ previewData.total }} records</p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="saveAllData"
                            :disabled="saving"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ saving ? 'Menyimpan...' : 'Simpan Semua (' + previewData.total + ')' }}
                        </button>
                        <button
                            @click="cancelPreview"
                            :disabled="saving"
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Cancel
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left">No</th>
                                <th class="px-4 py-3 text-left">Tanggal Scan</th>
                                <th class="px-4 py-3 text-left">PIN</th>
                                <th class="px-4 py-3 text-left">NIP</th>
                                <th class="px-4 py-3 text-left">Nama</th>
                                <th class="px-4 py-3 text-left">Jabatan</th>
                                <th class="px-4 py-3 text-left">Departemen</th>
                                <th class="px-4 py-3 text-left">Kantor</th>
                                <th class="px-4 py-3 text-left">Verifikasi</th>
                                <th class="px-4 py-3 text-left">I/O</th>
                                <th class="px-4 py-3 text-left">Mesin</th>
                                <th class="px-4 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="(fp, index) in previewData.data" :key="index" 
                                :class="fp.duplicate ? 'bg-yellow-50 dark:bg-yellow-900' : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
                            >
                                <td class="px-4 py-3">{{ (previewData.current_page - 1) * previewData.per_page + index + 1 }}</td>
                                <td class="px-4 py-3">{{ fp.scan_date }}</td>
                                <td class="px-4 py-3">{{ fp.pin }}</td>
                                <td class="px-4 py-3">{{ fp.nip }}</td>
                                <td class="px-4 py-3">{{ fp.name }}</td>
                                <td class="px-4 py-3">{{ fp.position }}</td>
                                <td class="px-4 py-3">{{ fp.department }}</td>
                                <td class="px-4 py-3">{{ fp.office }}</td>
                                <td class="px-4 py-3">{{ fp.verify }}</td>
                                <td class="px-4 py-3">{{ fp.io }}</td>
                                <td class="px-4 py-3">{{ fp.machine }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="fp.duplicate" class="text-yellow-600">Duplikat</span>
                                    <span v-else class="text-green-600">Baru</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Preview Pagination -->
                <div v-if="previewData.last_page > 1" class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-center gap-2">
                        <button
                            @click="changePreviewPage(previewData.current_page - 1)"
                            :disabled="previewData.current_page === 1"
                            class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>
                        <button
                            v-for="page in previewPaginationRange"
                            :key="page"
                            @click="changePreviewPage(page)"
                            :class="[
                                'px-3 py-1 rounded',
                                page === previewData.current_page 
                                    ? 'bg-blue-600 text-white' 
                                    : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300'
                            ]"
                        >
                            {{ page }}
                        </button>
                        <button
                            @click="changePreviewPage(previewData.current_page + 1)"
                            :disabled="previewData.current_page === previewData.last_page"
                            class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </div>
                </div>

                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex gap-4 text-sm">
                        <span class="text-green-600">Baru: {{ previewNewCount }}</span>
                        <span class="text-yellow-600">Duplikat: {{ previewDuplicateCount }}</span>
                    </div>
                </div>
            </div>

            <!-- Existing Fingerprint Data -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold">Data Fingerprint</h2>
                            <p class="text-sm text-gray-500">Total: {{ fingerprints.total || 0 }} records</p>
                        </div>
                        <input
                            v-model="filters.q"
                            @input="onSearchInput"
                            type="text"
                            placeholder="Cari PIN / Nama / Departemen / Mesin..."
                            class="w-full md:w-96 px-3 py-2 rounded bg-slate-900 border border-slate-700 text-sm"
                        />
                    </div>
                </div>

                <div v-if="!fingerprints.data || fingerprints.data.length === 0" class="p-4 text-sm text-gray-500">
                    Belum ada data fingerprint tersimpan.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left">Tanggal Scan</th>
                                <th class="px-4 py-3 text-left">PIN</th>
                                <th class="px-4 py-3 text-left">Nama</th>
                                <th class="px-4 py-3 text-left">Departemen</th>
                                <th class="px-4 py-3 text-left">I/O</th>
                                <th class="px-4 py-3 text-left">Mesin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="fp in fingerprints.data" :key="fp.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3">{{ formatDateTime(fp.scan_date) }}</td>
                                <td class="px-4 py-3">{{ fp.pin || '-' }}</td>
                                <td class="px-4 py-3">{{ fp.name || '-' }}</td>
                                <td class="px-4 py-3">{{ fp.department || '-' }}</td>
                                <td class="px-4 py-3">{{ fp.io ?? '-' }}</td>
                                <td class="px-4 py-3">{{ fp.machine || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="fingerprints.last_page > 1" class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-center gap-2">
                        <button
                            @click="goToPage(fingerprints.current_page - 1)"
                            :disabled="fingerprints.current_page === 1"
                            class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>
                        <button
                            @click="goToPage(fingerprints.current_page + 1)"
                            :disabled="fingerprints.current_page === fingerprints.last_page"
                            class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    fingerprints: {
        type: Object,
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            per_page: 50,
            total: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            q: '',
        }),
    },
});

const page = usePage();

const selectedFile = ref(null);
const loading = ref(false);
const saving = ref(false);
const savingMessage = ref('Menyimpan data...');
const loadingMessage = ref('Memproses data...');
const loadingProgress = ref(0);
const savingProgress = ref(0);
const clearing = ref(false);
const message = ref(null);
const fileInput = ref(null);
const filters = reactive({
    q: String(props.filters?.q || ''),
});
let searchTimer = null;

// Preview data with pagination
const previewData = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 50,
    total: 0
});

const previewNewCount = computed(() => previewData.value.data.filter(p => !p.duplicate).length);
const previewDuplicateCount = computed(() => previewData.value.data.filter(p => p.duplicate).length);

const previewPaginationRange = computed(() => {
    const range = [];
    const current = previewData.value.current_page;
    const last = previewData.value.last_page;
    const delta = 2;
    
    for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
        range.push(i);
    }
    return range;
});

const activeLoadingMessage = computed(() => (saving.value ? savingMessage.value : loadingMessage.value));
const activeLoadingProgress = computed(() => (saving.value ? savingProgress.value : loadingProgress.value));

function handleFileSelect(event) {
    selectedFile.value = event.target.files[0];
    message.value = null;
    previewData.value = {
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: 50,
        total: 0
    };
}

async function parseApiResponse(response) {
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        return await response.json();
    }

    const rawText = await response.text();
    return {
        message: `Server mengembalikan format non-JSON (HTTP ${response.status}).`,
        raw: rawText?.slice(0, 300) || '',
    };
}

async function checkData(pageNum = 1) {
    if (!selectedFile.value) return;

    loading.value = true;
    loadingProgress.value = 15;
    loadingMessage.value = 'Membaca file...';
    message.value = null;

    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('page', pageNum);
    formData.append('per_page', 50);

    try {
        const response = await fetch('/fingerprint/preview', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        loadingProgress.value = 65;
        const data = await parseApiResponse(response);
        loadingProgress.value = 90;
        
        if (response.ok) {
            previewData.value = data.preview || {
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 50,
                total: 0
            };
            message.value = {
                type: 'success',
                text: `Data berhasil dibaca! ${previewData.value.total} baris ditemukan.`
            };
            loadingProgress.value = 100;
        } else {
            message.value = {
                type: 'error',
                text: data.message || 'Gagal membaca data'
            };
            loadingProgress.value = 100;
        }
    } catch (error) {
        message.value = {
            type: 'error',
            text: 'Terjadi kesalahan: ' + error.message
        };
    } finally {
        setTimeout(() => {
            loadingProgress.value = 0;
        }, 200);
        loading.value = false;
    }
}

function changePreviewPage(pageNum) {
    checkData(pageNum);
}

async function saveAllData() {
    if (previewData.value.total === 0) return;
    
    saving.value = true;
    savingProgress.value = 5;
    
    try {
        // Load all data from all pages
        const allData = [];
        
        // Show loading message
        savingMessage.value = `Please wait`;
        
        for (let page = 1; page <= previewData.value.last_page; page++) {
            const formData = new FormData();
            formData.append('file', selectedFile.value);
            formData.append('page', page);
            formData.append('per_page', 50);
            
            const response = await fetch('/fingerprint/preview', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await parseApiResponse(response);
            if (data.preview && data.preview.data) {
                allData.push(...data.preview.data);
            }
            savingProgress.value = Math.min(80, Math.round((page / previewData.value.last_page) * 80));
        }
        
        savingMessage.value = `Menyimpan ${allData.length} data...`;
        savingProgress.value = 90;
        
        // Now save all data
        const saveResponse = await fetch('/fingerprint/confirm-save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ data: allData })
        });
        
        const saveDataResult = await parseApiResponse(saveResponse);
        savingProgress.value = 100;
        
        if (saveResponse.ok) {
            message.value = {
                type: 'success',
                text: saveDataResult.message?.text || 'Data berhasil disimpan!'
            };
            previewData.value = {
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 50,
                total: 0
            };
            window.location.reload();
        } else {
            message.value = {
                type: 'error',
                text: saveDataResult.message || 'Gagal menyimpan data'
            };
        }
    } catch (error) {
        message.value = {
            type: 'error',
            text: 'Terjadi kesalahan: ' + error.message
        };
    } finally {
        setTimeout(() => {
            savingProgress.value = 0;
        }, 200);
        saving.value = false;
    }
}

async function saveData() {
    if (previewData.value.data.length === 0) return;

    saving.value = true;
    savingProgress.value = 25;

    try {
        const response = await fetch('/fingerprint/confirm-save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ data: previewData.value.data })
        });

        const data = await parseApiResponse(response);
        savingProgress.value = 100;
        
        if (response.ok) {
            message.value = {
                type: 'success',
                text: data.message?.text || 'Data berhasil disimpan!'
            };
            previewData.value = {
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 50,
                total: 0
            };
            // Refresh page to show new data
            window.location.reload();
        } else {
            message.value = {
                type: 'error',
                text: data.message || 'Gagal menyimpan data'
            };
        }
    } catch (error) {
        message.value = {
            type: 'error',
            text: 'Terjadi kesalahan: ' + error.message
        };
    } finally {
        setTimeout(() => {
            savingProgress.value = 0;
        }, 200);
        saving.value = false;
    }
}

function cancelPreview() {
    previewData.value = {
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: 50,
        total: 0
    };
    message.value = null;
}

async function clearData() {
    if (!confirm('Apakah Anda yakin ingin menghapus semua data fingerprint?')) {
        return;
    }

    clearing.value = true;

    try {
        const response = await fetch('/fingerprint/clear', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await parseApiResponse(response);

        if (response.ok) {
            message.value = {
                type: 'success',
                text: data.message?.text || 'Data cleared successfully'
            };
            // Refresh page
            window.location.reload();
        } else {
            message.value = {
                type: 'error',
                text: data.message || 'Clear failed'
            };
        }
    } catch (error) {
        message.value = {
            type: 'error',
            text: 'An error occurred'
        };
    } finally {
        clearing.value = false;
    }
}

function goToPage(pageNum) {
    Inertia.get('/fingerprint', {
        page: pageNum,
        q: filters.q || '',
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        Inertia.get('/fingerprint', {
            q: filters.q || '',
            page: 1,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 350);
}

function formatDateTime(dateTime) {
    if (!dateTime) return '-';
    const date = new Date(dateTime);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}
</script>
