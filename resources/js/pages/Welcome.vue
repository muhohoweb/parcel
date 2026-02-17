<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login } from '@/routes';

withDefaults(
    defineProps<{
      canLogin: boolean;
    }>(),
    {
      canLogin: true,
    },
);
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
  <Head title="JetQuickly - Fast & Reliable Parcel Delivery">
    <link rel="preconnect" href="https://rsms.me/" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
  </Head>

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
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
              <span class="text-white font-bold text-xl">JQ</span>
            </div>
            <span class="text-white font-bold text-2xl">JetQuickly</span>
          </div>

          <Link
              v-if="canLogin"
              :href="login()"
              class="inline-flex items-center gap-2 rounded-lg bg-white/10 backdrop-blur-sm border border-white/20 px-6 py-2.5 text-sm font-medium text-white hover:bg-white/20 transition-all duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Login
          </Link>
        </nav>
      </header>

      <!-- Hero Section -->
      <div class="flex-1 flex items-center justify-center px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center space-y-8 animate-fade-in">
          <!-- Badge -->
          <div class="inline-flex items-center gap-2 rounded-full bg-orange-500/20 border border-orange-500/30 px-4 py-2 backdrop-blur-sm">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-500"></span>
            </span>
            <span class="text-orange-200 text-sm font-medium">Delivering across Kenya</span>
          </div>

          <!-- Main Heading -->
          <div class="space-y-4">
            <h1 class="text-5xl md:text-7xl lg:text-8xl font-bold text-white tracking-tight">
              Fast & Reliable
              <span class="block bg-gradient-to-r from-orange-400 via-orange-500 to-orange-600 bg-clip-text text-transparent">
                Parcel Delivery
              </span>
            </h1>
            <p class="text-xl md:text-2xl text-gray-300 max-w-2xl mx-auto leading-relaxed">
              Delivering your parcels safely and on time, anywhere in Kenya
            </p>
          </div>

          <!-- CTA Buttons -->
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
            <Link
                :href="login()"
                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 px-8 py-4 text-lg font-semibold text-white shadow-lg shadow-orange-500/50 hover:shadow-xl hover:shadow-orange-500/60 transition-all duration-200 hover:scale-105"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
              Track Your Parcel
            </Link>


            <a href="#features"
            class="inline-flex items-center gap-2 rounded-lg bg-white/10 backdrop-blur-sm border border-white/20 px-8 py-4 text-lg font-semibold text-white hover:bg-white/20 transition-all duration-200"
            >
            Learn More
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
            </a>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-3 gap-8 pt-12 max-w-2xl mx-auto">
            <div class="text-center">
              <div class="text-3xl md:text-4xl font-bold text-white">24/7</div>
              <div class="text-sm text-gray-400 mt-1">Available</div>
            </div>
            <div class="text-center">
              <div class="text-3xl md:text-4xl font-bold text-white">1000+</div>
              <div class="text-sm text-gray-400 mt-1">Deliveries</div>
            </div>
            <div class="text-center">
              <div class="text-3xl md:text-4xl font-bold text-white">47</div>
              <div class="text-sm text-gray-400 mt-1">Counties</div>
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