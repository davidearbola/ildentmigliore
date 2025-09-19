<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import PublicLayout from './layouts/PublicLayout.vue'
import AuthLayout from './layouts/AuthLayout.vue'
import DashboardLayout from './layouts/DashboardLayout.vue'

const route = useRoute()

const layouts = {
  PublicLayout,
  AuthLayout,
  DashboardLayout
}

const layout = computed(() => {
  return layouts[route.meta.layout] || PublicLayout
})
</script>

<template>
  <component :is="layout">
    <RouterView />
  </component>
</template>

<style>
body {
  background-color: #f8f9fa;
}
</style>