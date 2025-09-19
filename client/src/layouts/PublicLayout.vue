<script setup>
import HeaderPublic from '@/components/HeaderPublic.vue' 
import FooterPublic from '@/components/FooterPublic.vue' 
</script>

<template>
  <HeaderPublic v-if="$route.meta.showHeader"/>
  <main>
    <slot />
  </main>
  <FooterPublic v-if="$route.meta.showFooter"/>
</template>