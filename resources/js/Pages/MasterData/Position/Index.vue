<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-bold">Positions</h2>
        <Link href="/master-data/position/create" class="bg-indigo-600 px-4 py-2 rounded text-white">Add Position</Link>
      </div>

      <div class="bg-slate-800 rounded p-4">
        <div class="hidden overflow-auto lg:block">
        <table class="w-full table-auto">
          <thead>
            <tr class="text-left text-slate-400">
              <th class="py-2">#</th>
              <th>Code</th>
              <th>Name</th>
              <th>Department</th>
              <th>Description</th>
              <th>Status</th>
              <th>Created</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="position in positions.data" :key="position.id" class="border-t border-slate-700">
              <td class="py-3">{{ position.id }}</td>
              <td>{{ position.code }}</td>
              <td>{{ position.name }}</td>
              <td>{{ position.department ? position.department.name : '-' }}</td>
              <td>{{ position.description || '-' }}</td>
              <td>
                <span :class="position.is_active ? 'text-green-400' : 'text-red-400'">
                  {{ position.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td>{{ new Date(position.created_at).toLocaleDateString() }}</td>
              <td class="text-right">
                <Link :href="`/master-data/position/${position.id}/edit`" class="text-indigo-400 mr-3">Edit</Link>
                <button @click="deletePosition(position.id)" class="text-red-400">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-700 lg:hidden">
          <div v-for="position in positions.data" :key="`mobile-${position.id}`" class="border-b border-slate-700 bg-slate-900/30 p-4 last:border-b-0">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate font-semibold text-white">{{ position.name }}</div>
                <div class="text-sm text-slate-400">{{ position.code }}</div>
              </div>
              <span :class="position.is_active ? 'text-green-400' : 'text-red-400'">
                {{ position.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <div class="mt-3 space-y-2 text-sm">
              <div class="flex items-start justify-between gap-3">
                <div class="text-slate-400">Department</div>
                <div class="text-right">{{ position.department ? position.department.name : '-' }}</div>
              </div>
              <div class="flex items-start justify-between gap-3">
                <div class="text-slate-400">Description</div>
                <div class="text-right">{{ position.description || '-' }}</div>
              </div>
              <div class="flex items-start justify-between gap-3">
                <div class="text-slate-400">Created</div>
                <div class="text-right">{{ new Date(position.created_at).toLocaleDateString() }}</div>
              </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
              <Link :href="`/master-data/position/${position.id}/edit`" class="inline-flex items-center justify-center rounded bg-indigo-600 px-3 py-2 text-sm text-white">Edit</Link>
              <button @click="deletePosition(position.id)" class="rounded bg-rose-600 px-3 py-2 text-sm text-white">Delete</button>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <Pagination :paginator="positions" :onPageChange="goToPage" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { swalConfirm } from '@/Utils/swalConfirm';

const props = defineProps({ positions: Object });

const positions = computed(() => props.positions);

function next() {
  if (positions.value.next_page_url) {
    router.get(positions.value.next_page_url, {}, { preserveState: true, preserveScroll: true });
  }
}

function prev() {
  if (positions.value.prev_page_url) {
    router.get(positions.value.prev_page_url, {}, { preserveState: true, preserveScroll: true });
  }
}

async function deletePosition(id) {
  const ok = await swalConfirm({
    title: 'Delete Position',
    text: 'Are you sure you want to delete this position?',
    confirmButtonText: 'Delete',
    confirmButtonColor: '#dc2626',
  });
  if (!ok) return;

  router.delete(`/master-data/position/${id}`, {
    onSuccess: () => {
      // Will refresh automatically
    },
  });
}

function goToPage(page) {
  router.get('/master-data/position', { page }, { preserveState: true, preserveScroll: true });
}
</script>
