<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

// Emits
defineEmits(['toggle-sidebar']);

const route = useRoute();

const breadcrumbs = computed(() => {
    const path = route.path;
    if (path === '/' || path === '/dashboard') return [{ name: 'Catàleg de Tallers', current: true }];

    const parts = path.split('/').filter(p => p);
    
    const names = {
        'dashboard': 'Catàleg de Tallers',
        'mis-solicitudes': 'Les Meves Sol·licituds',
        'alumnes': 'Gestió d\'Alumnes',
        'professor': 'Professorat',
        'agenda': 'Agenda',
        'admin': 'Administració',
        'stats': 'Analítica',
        'solicitudes': 'Peticions',
        'talleres': 'Catàleg',
        'users': 'Usuaris'
    };

    let crumbs = [];
    // Always start with Dashboard as home? Or just Home icon.
    
    let currentPath = '';
    parts.forEach((part, index) => {
        currentPath += '/' + part;
        crumbs.push({
            name: names[part] || part.charAt(0).toUpperCase() + part.slice(1),
            to: currentPath,
            current: index === parts.length - 1
        });
    });
    
    return crumbs;
});
</script>

<template>
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30 px-4 md:px-8 py-4 flex items-center justify-between shadow-sm h-16">
        <div class="flex items-center">
            <!-- Mobile Toggle Button -->
            <button @click="$emit('toggle-sidebar')" class="mr-4 md:hidden text-gray-500 hover:text-kairos-navy focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>

            <!-- Breadcrumbs -->
            <nav class="hidden sm:flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li class="inline-flex items-center">
                        <router-link to="/" class="text-gray-400 hover:text-kairos-blue transition-colors">
                            <span class="sr-only">Home</span>
                            🏠
                        </router-link>
                    </li>
                    <li v-for="(crumb, index) in breadcrumbs" :key="index">
                        <div class="flex items-center">
                            <span class="text-gray-400 mx-2">/</span>
                            <span v-if="crumb.current" class="text-sm font-medium text-kairos-navy cursor-default">
                                {{ crumb.name }}
                            </span>
                            <router-link v-else :to="crumb.to" class="text-sm font-medium text-gray-500 hover:text-kairos-blue transition-colors">
                                {{ crumb.name }}
                            </router-link>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Right Side Actions -->
        <div class="flex items-center gap-4">
            <div class="text-xs text-right hidden sm:block">
                <p class="text-gray-900 font-bold">Curs 2025-26</p>
                <p class="text-gray-500">Programa ENGINY</p>
            </div>
        </div>
    </header>
</template>
