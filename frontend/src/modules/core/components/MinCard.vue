<template>
  <div
    class="bg-white q-pl-md q-pr-xl q-py-sm"
    style="border-radius: 15px"
  >
    <div v-if="title" class="fs-80 text-grey-7">
      {{ title }}
    </div>
    <div class="fs-100 fw-500">
      {{ text }}
    </div>
  </div>
</template>

<script setup>
  import { defineProps } from 'vue'

  defineProps({
    title: String,
    text: {
      type: String,
      required: true
    }
  })
</script>
