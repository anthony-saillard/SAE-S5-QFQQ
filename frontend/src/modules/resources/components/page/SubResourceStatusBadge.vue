<template>
  <q-chip
    :color="getStatusColor"
    text-color="white"
    class="fw-500 fs-100"
  >
    {{ getStatusText }}
  </q-chip>
</template>

<script setup>
  import { computed } from 'vue'

  const props = defineProps({
    status: {
      type: String,
      default: 'NOT_STARTED'
    }
  })

  const getStatusColor = computed(() => {
    switch (props.status) {
      case 'NOT_STARTED':
        return 'secondary'
      case 'IN_PROGRESS':
        return 'primary'
      case 'COMPLETED':
        return 'positive'
      default:
        return 'secondary'
    }
  })

  const getStatusText = computed(() => {
    switch (props.status) {
      case 'NOT_STARTED':
        return 'Non commencé'
      case 'IN_PROGRESS':
        return 'En cours'
      case 'COMPLETED':
        return 'Terminé'
      default:
        return 'Non défini'
    }
  })
</script>
