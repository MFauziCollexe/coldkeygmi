<template>
  <GuestLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-950 to-slate-900 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-md mx-auto">
        <!-- Form Container -->
        <FormContainer>
          <form @submit.prevent="handleLogin" class="space-y-6">
            <!-- Credentials Section -->
            <FormSection title="Login">
              <div class="space-y-6">
                <!-- Account -->
                <FormInput 
                  v-model="form.account"
                  label="Account"
                  type="text"
                  placeholder="Enter your account"
                  required
                  :error="form.errors.account || ''"
                />

                <!-- Password -->
                <PasswordInput 
                  v-model="form.password"
                  label="Password"
                  placeholder="Enter your password"
                  required
                  :error="form.errors.password || ''"
                />
              </div>
            </FormSection>

            <!-- Remember Me -->
            <CheckboxInput v-model="form.remember">
              Remember me for next time
            </CheckboxInput>

            <!-- Sign In Button -->
            <button 
              type="submit" 
              :disabled="form.processing"
              class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-700/50 text-white font-semibold py-3 rounded-lg transition shadow-lg shadow-indigo-500/20"
            >
              {{ form.processing ? 'Signing in...' : 'Sign In' }}
            </button>

            <!-- Links -->
            <div class="space-y-3 pt-4">
              <Link href="/forgot-password" class="block text-center text-indigo-400 hover:text-indigo-300 text-sm font-semibold">Forgot your password?</Link>
              <div class="text-center text-sm">
                <span class="text-slate-400">Don't have an account? </span>
                <Link href="/signup" class="text-indigo-400 hover:text-indigo-300 font-semibold">Sign Up</Link>
              </div>
            </div>
          </form>
        </FormContainer>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Link } from '@inertiajs/vue3';
import FormContainer from '@/Components/FormContainer.vue';
import FormSection from '@/Components/FormSection.vue';
import FormInput from '@/Components/FormInput.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import CheckboxInput from '@/Components/CheckboxInput.vue';

defineProps({
  errors: Object,
});

import { useForm } from '@inertiajs/vue3';

const form = useForm({
  account: '',
  password: '',
  remember: false,
});

const handleLogin = () => {
  form.post('/login', { preserveScroll: true });
};
</script>
