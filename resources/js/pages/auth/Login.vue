<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
  status?: string;
  canResetPassword: boolean;
  canRegister: boolean;
}>();
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.8s ease-out;
}
</style>

<template>
  <Head title="Log in" />

  <div class="relative min-h-screen overflow-hidden">
    <!-- Background Image with Overlay -->
    <div
        class="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style="background-image: url('https://images.unsplash.com/photo-1566576721346-d4a3b4eaeb55?q=80&w=2940&auto=format&fit=crop')"
    >
      <div class="absolute inset-0 bg-gradient-to-br from-black/70 via-black/60 to-black/70"></div>
    </div>

    <!-- Content -->
    <div class="relative z-10 flex min-h-screen flex-col">
      <!-- Header -->
      <header class="w-full p-6 lg:p-8">
        <nav class="flex items-center justify-between max-w-7xl mx-auto">
          <Link href="/" class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
              <span class="text-white font-bold text-xl">JQ</span>
            </div>
            <span class="text-white font-bold text-2xl">JetQuickly</span>
          </Link>
        </nav>
      </header>

      <!-- Login Form -->
      <div class="flex-1 flex items-center justify-center px-6 lg:px-8">
        <div class="w-full max-w-md animate-fade-in">
          <!-- Card -->
          <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-8 shadow-2xl">
            <!-- Header -->
            <div class="text-center mb-8">
              <h1 class="text-3xl font-bold text-white mb-2">Welcome Back</h1>
              <p class="text-gray-300">Enter your credentials to access your account</p>
            </div>

            <!-- Status Message -->
            <div
                v-if="status"
                class="mb-6 rounded-lg bg-green-500/20 border border-green-500/30 px-4 py-3 text-sm text-green-200 text-center"
            >
              {{ status }}
            </div>

            <!-- Form -->
            <Form
                v-bind="store.form()"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="space-y-6"
            >
              <!-- Email -->
              <div class="space-y-2">
                <Label for="email" class="text-white">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="bg-white/10 border-white/20 text-white placeholder:text-gray-400 focus:border-orange-500 focus:ring-orange-500"
                />
                <InputError :message="errors.email" class="text-orange-300" />
              </div>

              <!-- Password -->
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <Label for="password" class="text-white">Password</Label>
                  <Link
                      v-if="canResetPassword"
                      :href="request()"
                      class="text-sm text-orange-300 hover:text-orange-200 transition-colors"
                      :tabindex="5"
                  >
                    Forgot password?
                  </Link>
                </div>
                <Input
                    id="password"
                    type="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="bg-white/10 border-white/20 text-white placeholder:text-gray-400 focus:border-orange-500 focus:ring-orange-500"
                />
                <InputError :message="errors.password" class="text-orange-300" />
              </div>

              <!-- Remember Me -->
              <div class="flex items-center">
                <Label for="remember" class="flex items-center space-x-3 cursor-pointer">
                  <Checkbox
                      id="remember"
                      name="remember"
                      :tabindex="3"
                      class="border-white/20 data-[state=checked]:bg-orange-500 data-[state=checked]:border-orange-500"
                  />
                  <span class="text-white text-sm">Remember me</span>
                </Label>
              </div>

              <!-- Submit Button -->
              <Button
                  type="submit"
                  class="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-semibold py-6 shadow-lg shadow-orange-500/50 hover:shadow-xl hover:shadow-orange-500/60 transition-all duration-200"
                  :tabindex="4"
                  :disabled="processing"
                  data-test="login-button"
              >
                <Spinner v-if="processing" class="mr-2" />
                {{ processing ? 'Logging in...' : 'Log in' }}
              </Button>
            </Form>

            <!-- Sign Up Link -->
            <div
                v-if="canRegister"
                class="mt-6 text-center text-sm text-gray-300"
            >
              Don't have an account?
              <Link
                  :href="register()"
                  :tabindex="5"
                  class="text-orange-300 hover:text-orange-200 font-medium transition-colors"
              >
                Sign up
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <footer class="w-full p-6 lg:p-8">
        <div class="max-w-7xl mx-auto text-center text-sm text-gray-400">
          <p>&copy; 2026 JetQuickly. All rights reserved.</p>
        </div>
      </footer>
    </div>
  </div>
</template>