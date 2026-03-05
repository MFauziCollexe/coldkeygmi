<template>
  <GuestLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-950 to-slate-900 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-md mx-auto">
        <!-- Form Container -->
        <FormContainer>
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Title -->
            <div class="text-center mb-6">
              <h2 class="text-2xl font-bold text-white">Forgot Password</h2>
              <p class="text-slate-400 text-sm mt-2">Enter your account and email to verify your identity</p>
            </div>

            <!-- Account -->
            <FormInput 
              v-model="form.account"
              label="Account"
              type="text"
              placeholder="Enter your account"
              required
              :error="form.errors.account || ''"
            />

            <!-- Email -->
            <FormInput 
              v-model="form.email"
              label="Email Address"
              type="email"
              placeholder="Enter your email address"
              required
              :error="form.errors.email || ''"
            />

            <!-- Submit Button -->
            <button 
              type="submit" 
              :disabled="form.processing"
              class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-700/50 text-white font-semibold py-3 rounded-lg transition shadow-lg shadow-indigo-500/20"
            >
              {{ form.processing ? 'Verifying...' : 'Verify Identity' }}
            </button>

            <!-- Back to Login -->
            <div class="text-center pt-4">
              <Link href="/" class="text-indigo-400 hover:text-indigo-300 text-sm font-semibold">Back to Login</Link>
            </div>
          </form>
        </FormContainer>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Link } from '@inertiajs/vue3';
import FormContainer from '@/Components/FormContainer.vue';
import FormInput from '@/Components/FormInput.vue';

const form = useForm({
  account: '',
  email: '',
});

function handleSubmit() {
  form.post('/forgot-password/verify', { preserveScroll: true });
}
</script>
