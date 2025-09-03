<template>
  <q-ajax-bar
    ref="ajaxBar"
    position="top"
    color="primary"
    size="4px"
    skip-hijack
    :delay="100"
  />
  <div v-if="userStore.isLoading || empty(layout)" class="fullscreen flex flex-center column">
    <q-spinner-dots
      color="primary"
      size="10em"
      :thickness="2"
    />
  </div>
  <component :is="layout" v-else>
    <router-view />
  </component>
</template>

<script setup>
  import {onMounted, ref, shallowRef, watchEffect} from 'vue'
  import { useRoute } from 'vue-router'
  import { useUserStore } from 'src/utils/stores/useUserStore.js'
  import { setAjaxBar } from 'boot/axios'
  import { empty } from 'src/utils/utils.js'
  import { logger } from 'src/utils/logger.js'

  const userStore = useUserStore()
  const route = useRoute()
  const ajaxBar = ref(null)

  const layout = shallowRef(null)

  watchEffect(async () => {
    if (route.meta?.layout) {
      try {
        const layoutModule = await route.meta.layout()
        layout.value = layoutModule?.default || null
      } catch(e) {
        logger.error(e)
      }
    }
  })

  onMounted(() => {
    if (ajaxBar.value) {
      setAjaxBar(ajaxBar.value)
    }
  })
</script>

<style>
.fullscreen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 9999;
}
</style>
