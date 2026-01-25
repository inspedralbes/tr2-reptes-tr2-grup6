<template>
  <!-- Dashboard Redirection Component - Redirige automaticamente selon el rol -->
  <div style="display: none;"></div>
</template>

<script setup>
import { onMounted } from "vue"
import { useRouter } from "vue-router"
import { useAuthStore } from "@/stores/auth"

const router = useRouter()
const authStore = useAuthStore()

// Redirigir automaticamente selon el rol
onMounted(() => {
  if (!authStore.isAuthenticated) {
    router.push("/login")
    return
  }

  const user = authStore.user
  if (user?.role === "center_coord") {
    router.push("/center-dashboard")
  } else if (user?.role === "admin") {
    router.push("/admin")
  } else if (user?.role === "teacher") {
    router.push("/teacher/schedule")
  } else {
    router.push("/")
  }
})
</script>
