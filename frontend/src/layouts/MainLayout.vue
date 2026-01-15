<script setup>
import { ref } from 'vue';
import Sidebar from '../components/layout/Sidebar.vue';
import Header from '../components/layout/Header.vue';
import Footer from '../components/layout/Footer.vue';

const isSidebarOpen = ref(false);
</script>

<template>
  <div class="flex h-screen bg-gray-50 font-sans overflow-hidden">
    <!-- Sidebar -->
    <Sidebar 
        :is-open="isSidebarOpen" 
        @close="isSidebarOpen = false" 
    />

    <!-- Main Wrapper -->
    <div class="flex-1 flex flex-col min-h-screen transition-all duration-300 md:ml-64">
      
      <!-- Header -->
      <Header @toggle-sidebar="isSidebarOpen = !isSidebarOpen" />

      <!-- Content -->
      <main class="flex-1 p-4 md:p-8 overflow-y-auto">
        <div class="max-w-7xl mx-auto w-full">
            <router-view v-slot="{ Component }">
                <transition name="fade" mode="out-in">
                    <component :is="Component" />
                </transition>
            </router-view>
        </div>
      </main>

      <!-- Footer -->
      <Footer />
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
