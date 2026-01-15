<template>
  <div class="min-h-screen bg-kairos-surface">
    <div class="max-w-7xl mx-auto px-4 py-16">
      <div class="text-center">
        <h1 class="text-5xl font-bold text-kairos-navy mb-4">
          <span class="text-kairos-gold">KAIROS</span> Project
        </h1>
        <p class="text-xl text-gray-600 mb-8">
          Plataforma de Gestió de Tallers Educatius
        </p>
        <div class="inline-flex gap-4">
          <button class="px-6 py-3 bg-kairos-blue text-white rounded-lg hover:bg-blue-600 transition">
            Començar
          </button>
          <button class="px-6 py-3 border-2 border-kairos-navy text-kairos-navy rounded-lg hover:bg-kairos-navy hover:text-white transition">
            Més Info
          </button>
        </div>
      </div>
      
      <!-- Demostración de colores KAIROS -->
      <div class="mt-16 grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="text-center">
          <div class="h-24 bg-kairos-navy rounded-lg mb-2"></div>
          <p class="text-sm text-gray-600">Navy</p>
        </div>
        <div class="text-center">
          <div class="h-24 bg-kairos-gold rounded-lg mb-2"></div>
          <p class="text-sm text-gray-600">Gold</p>
        </div>
        <div class="text-center">
          <div class="h-24 bg-kairos-blue rounded-lg mb-2"></div>
          <p class="text-sm text-gray-600">Blue</p>
        </div>
        <div class="text-center">
          <div class="h-24 bg-kairos-surface border-2 border-gray-200 rounded-lg mb-2"></div>
          <p class="text-sm text-gray-600">Surface</p>
        </div>
        <div class="text-center">
          <div class="h-24 bg-kairos-light border-2 border-gray-200 rounded-lg mb-2"></div>
          <p class="text-sm text-gray-600">Light</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
</script>
