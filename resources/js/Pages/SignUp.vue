<template>
  <GuestLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-950 to-slate-900 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-4xl mx-auto">
        <!-- Form Container -->
        <FormContainer>
          <form @submit.prevent="handleSignUp" class="space-y-8">
            <!-- Personal Information Section -->
            <FormSection title="Personal Information">
              <div class="space-y-6">
                <!-- Name -->
                <FormInput 
                  v-model="form.name"
                  label="Name"
                  placeholder="Enter your name"
                  required
                />

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
              </div>
            </FormSection>

            <!-- Account Security Section -->
            <FormSection title="Account Security">
              <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <PasswordInput 
                  v-model="form.password"
                  label="Password"
                  placeholder="Enter your password"
                  required
                  :error="form.errors.password || ''"
                />
                <PasswordInput 
                  v-model="form.password_confirmation"
                  label="Confirm Password"
                  placeholder="Confirm your password"
                  required
                  :error="form.errors.password_confirmation || ''"
                />
              </div>
            </FormSection>

            <!-- Terms & Conditions Section -->
            <CheckboxInput v-model="form.acceptTerms" required>
              By creating an account, I agree to the 
              <Link href="/terms-and-conditions" class="text-indigo-400 hover:text-indigo-300 font-semibold">Terms and Conditions</Link>
               and 
              <Link href="/privacy-policy" class="text-indigo-400 hover:text-indigo-300 font-semibold">Privacy Policy</Link>
            </CheckboxInput>

            <!-- Submit Button Section -->
            <div class="flex gap-4 pt-4">
              <button 
                type="submit" 
                :disabled="form.processing"
                class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-700/50 text-white font-semibold py-3 rounded-lg transition shadow-lg shadow-indigo-500/20"
              >
                {{ form.processing ? 'Creating account...' : 'Create Account' }}
              </button>
            </div>

            <!-- Sign In Link -->
            <div class="text-center pt-4 border-t border-slate-700">
              <span class="text-slate-400 text-sm">Already have an account? </span>
              <Link href="/" class="text-indigo-400 hover:text-indigo-300 font-semibold text-sm">Sign In</Link>
            </div>
          </form>
        </FormContainer>
      </div>
    </div>
  </GuestLayout>
</template>



<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormContainer from '@/Components/FormContainer.vue';
import FormSection from '@/Components/FormSection.vue';
import FormInput from '@/Components/FormInput.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import CheckboxInput from '@/Components/CheckboxInput.vue';

const form = useForm({
  name: '',
  account: '',
  email: '',
  password: '',
  password_confirmation: '',
  acceptTerms: false,
});

const handleSignUp = () => {
  form.post('/signup', {
    preserveScroll: true,
  });
};
</script>
