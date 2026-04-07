<template>
  <AppLayout>
    <div class="space-y-6 p-4 sm:p-6">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Database Backup</h2>
          <p class="mt-1 text-sm text-slate-400">
            Buat, unduh, dan hapus file backup database dari Control Panel.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center justify-center rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
          @click="createBackup"
        >
          Buat Backup Baru
        </button>
      </div>

      <div v-if="$page.props.flash?.success" class="rounded border border-green-600 bg-green-600/20 px-4 py-3 text-sm text-green-300">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error" class="rounded border border-rose-600 bg-rose-600/20 px-4 py-3 text-sm text-rose-300">
        {{ $page.props.flash.error }}
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded border border-slate-700 bg-slate-800 p-4">
          <div class="text-xs uppercase tracking-wide text-slate-400">Connection</div>
          <div class="mt-2 text-lg font-semibold text-slate-100">{{ connection || '-' }}</div>
        </div>
        <div class="rounded border border-slate-700 bg-slate-800 p-4">
          <div class="text-xs uppercase tracking-wide text-slate-400">Database</div>
          <div class="mt-2 break-all text-lg font-semibold text-slate-100">{{ databaseName || '-' }}</div>
        </div>
        <div class="rounded border border-slate-700 bg-slate-800 p-4">
          <div class="text-xs uppercase tracking-wide text-slate-400">Lokasi Backup</div>
          <div class="mt-2 break-all text-sm font-medium text-slate-200">{{ backupPath }}</div>
        </div>
      </div>

      <div class="rounded border border-slate-700 bg-slate-800 p-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
          <div class="flex-1">
            <label class="mb-2 block text-sm font-medium text-slate-200">Ubah Lokasi Backup</label>
            <input
              v-model="backupPathForm.backup_path"
              type="text"
              class="w-full rounded border border-slate-700 bg-slate-900/50 px-3 py-2 text-sm text-slate-100 placeholder-slate-500"
              placeholder="Kosongkan untuk kembali ke default storage/app/backups/database"
            />
            <div class="mt-2 text-xs text-slate-400">
              Bisa isi path absolut seperti <span class="font-mono">D:\Backup\DB</span> atau path relatif dari project.
            </div>
          </div>
          <div class="flex flex-col gap-2 sm:flex-row">
            <button
              type="button"
              class="rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-600"
              @click="resetBackupPath"
            >
              Reset
            </button>
            <button
              type="button"
              class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
              @click="saveBackupPath"
            >
              Simpan Lokasi
            </button>
          </div>
        </div>
      </div>

      <div class="rounded border border-slate-700 bg-slate-800 p-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-3">
              <h3 class="text-lg font-semibold text-slate-100">Scheduler Backup</h3>
              <span
                class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold"
                :class="schedulerToneClasses(scheduler.status_tone)"
              >
                {{ scheduler.status_label || 'Unknown' }}
              </span>
            </div>
            <p class="text-sm text-slate-400">{{ scheduler.message || '-' }}</p>
            <div
              v-if="scheduler.query_error"
              class="rounded border border-amber-600/40 bg-amber-600/10 px-3 py-2 text-xs text-amber-200"
            >
              {{ scheduler.query_error }}
            </div>
          </div>

          <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
            <button
              type="button"
              class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!scheduler.supported || !scheduler.installed || scheduler.enabled"
              @click="startScheduler"
            >
              Start
            </button>
            <button
              type="button"
              class="rounded bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!scheduler.supported || !scheduler.installed || !scheduler.enabled"
              @click="stopScheduler"
            >
              Stop
            </button>
          </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div class="rounded border border-slate-700 bg-slate-900/40 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-400">Task State</div>
            <div class="mt-2 text-sm font-semibold text-slate-100">{{ scheduler.task_state || '-' }}</div>
          </div>
          <div class="rounded border border-slate-700 bg-slate-900/40 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-400">Next Run</div>
            <div class="mt-2 text-sm font-semibold text-slate-100">{{ scheduler.next_run_time || '-' }}</div>
          </div>
          <div class="rounded border border-slate-700 bg-slate-900/40 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-400">Scheduler Log</div>
            <div class="mt-2 text-sm font-semibold text-slate-100">
              {{ scheduler.scheduler_log?.last_modified || 'Belum ada log' }}
            </div>
          </div>
          <div class="rounded border border-slate-700 bg-slate-900/40 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-400">Log Size</div>
            <div class="mt-2 text-sm font-semibold text-slate-100">
              {{ scheduler.scheduler_log?.size_human || '-' }}
            </div>
          </div>
        </div>
      </div>

      <div class="rounded border border-slate-700 bg-slate-800 p-4">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <h3 class="text-lg font-semibold text-slate-100">Riwayat Backup</h3>
          <div class="text-sm text-slate-400">{{ backups.length }} file</div>
        </div>

        <div v-if="backups.length === 0" class="rounded border border-dashed border-slate-700 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-400">
          Belum ada file backup database.
        </div>

        <div v-else>
          <div class="space-y-3 lg:hidden">
            <div v-for="item in backups" :key="item.name" class="rounded-lg border border-slate-700 bg-slate-900/40 p-4">
              <div class="break-all text-sm font-semibold text-slate-100">{{ item.name }}</div>
              <div class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                  <span class="text-slate-400">Ukuran</span>
                  <span class="text-right text-slate-200">{{ item.size_human }}</span>
                </div>
                <div class="flex justify-between gap-4">
                  <span class="text-slate-400">Diubah</span>
                  <span class="text-right text-slate-200">{{ formatDate(item.modified_at) }}</span>
                </div>
              </div>
              <div class="mt-4 flex gap-2">
                <a
                  :href="`/control-panel/database-backup/${encodeURIComponent(item.name)}/download`"
                  class="flex-1 rounded bg-emerald-600 px-3 py-2 text-center text-xs font-semibold text-white hover:bg-emerald-500"
                >
                  Download
                </a>
                <button
                  type="button"
                  class="flex-1 rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500"
                  @click="deleteBackup(item)"
                >
                  Hapus
                </button>
              </div>
            </div>
          </div>

          <div class="hidden lg:block overflow-x-auto">
            <table class="min-w-full table-auto">
            <thead>
              <tr class="border-b border-slate-700 text-left text-sm text-slate-400">
                <th class="py-3 pr-3">File</th>
                <th class="py-3 pr-3">Ukuran</th>
                <th class="py-3 pr-3">Diubah</th>
                <th class="py-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in backups" :key="item.name" class="border-b border-slate-700/60 text-sm text-slate-200 last:border-b-0">
                <td class="py-3 pr-3 align-top">
                  <div class="break-all font-medium">{{ item.name }}</div>
                </td>
                <td class="py-3 pr-3 align-top whitespace-nowrap">{{ item.size_human }}</td>
                <td class="py-3 pr-3 align-top whitespace-nowrap">{{ formatDate(item.modified_at) }}</td>
                <td class="py-3 text-right align-top">
                  <div class="flex justify-end gap-2">
                    <a
                      :href="`/control-panel/database-backup/${encodeURIComponent(item.name)}/download`"
                      class="rounded bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500"
                    >
                      Download
                    </a>
                    <button
                      type="button"
                      class="rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500"
                      @click="deleteBackup(item)"
                    >
                      Hapus
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { swalConfirm } from '@/Utils/swalConfirm';

const props = defineProps({
  connection: { type: String, default: '' },
  databaseName: { type: String, default: '' },
  backupPath: { type: String, default: '' },
  backupPathInput: { type: String, default: '' },
  backups: { type: Array, default: () => [] },
  scheduler: { type: Object, default: () => ({}) },
});

const backupPathForm = useForm({
  backup_path: props.backupPathInput || '',
});

function createBackup() {
  router.post('/control-panel/database-backup', {}, { preserveScroll: true });
}

function saveBackupPath() {
  backupPathForm.put('/control-panel/database-backup/path', {
    preserveScroll: true,
  });
}

function resetBackupPath() {
  backupPathForm.backup_path = '';
}

function startScheduler() {
  router.post('/control-panel/database-backup/start', {}, { preserveScroll: true });
}

function stopScheduler() {
  router.post('/control-panel/database-backup/stop', {}, { preserveScroll: true });
}

async function deleteBackup(item) {
  const ok = await swalConfirm({
    title: 'Hapus File Backup?',
    text: `Yakin ingin menghapus ${item.name}?`,
    confirmButtonText: 'Hapus',
    confirmButtonColor: '#dc2626',
  });

  if (!ok) {
    return;
  }

  router.delete(`/control-panel/database-backup/${encodeURIComponent(item.name)}`, {
    preserveScroll: true,
  });
}

function formatDate(value) {
  if (!value) {
    return '-';
  }

  return new Date(value).toLocaleString();
}

function schedulerToneClasses(tone) {
  return {
    emerald: 'border-emerald-600/50 bg-emerald-600/15 text-emerald-300',
    amber: 'border-amber-600/50 bg-amber-600/15 text-amber-300',
    rose: 'border-rose-600/50 bg-rose-600/15 text-rose-300',
    slate: 'border-slate-600/50 bg-slate-700/40 text-slate-300',
  }[tone] || 'border-slate-600/50 bg-slate-700/40 text-slate-300';
}
</script>
