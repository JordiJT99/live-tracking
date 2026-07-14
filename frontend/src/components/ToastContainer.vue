<script setup lang="ts">
import { useToast } from '../composables/useToast'
const { toasts, remove } = useToast()
</script>

<template>
  <div class="toast-container">
    <div
      v-for="t in toasts"
      :key="t.id"
      :class="`toast toast-${t.type}`"
      @click="remove(t.id)"
    >
      {{ t.message }}
    </div>
  </div>
</template>
