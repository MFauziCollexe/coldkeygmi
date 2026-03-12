<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Positions</h2>
        <Link href="/master-data/position/create" class="bg-indigo-600 px-4 py-2 rounded text-white">Add Position</Link>
      </div>

      <div class="bg-slate-800 rounded p-4">
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
