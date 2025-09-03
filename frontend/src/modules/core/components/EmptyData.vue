<template>
  <div class="row items-center justify-center q-pa-sm q-gutter-x-md">
    <q-icon
      :name="icon"
      :color="textColor"
      :size="iconSize"
    />
    <div v-if="message" class="text-subtitle1 text-center fs-80" :class="'text-' + textColor">
      {{ message }}
    </div>

    <q-btn
      v-if="action"
      :color="actionColor"
      :label="actionLabel"
      :icon="actionIcon"
      no-caps size="9px" dense
      @click="emit('action')"
    />
  </div>
</template>

<script setup>
  import { defineProps, defineEmits } from 'vue'

  defineProps({
    message: {
      type: String,
      default: 'Aucune donnée disponible !'
    },
    textColor: {
      type: String,
      default: 'secondary'
    },

    icon: {
      type: String,
      default: 'info'
    },
    iconSize: {
      type: String,
      default: '25px'
    },

    action: Boolean,
    actionLabel: {
      type: String,
      default: 'Recharger'
    },
    actionIcon: {
      type: String,
      default: 'refresh'
    },
    actionColor: {
      type: String,
      default: 'secondary'
    }
  })

  const emit = defineEmits(['action'])
</script>
