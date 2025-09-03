<template>
  <div :key="'semester-view-' + semesterId">
    <div class="row q-mb-sm q-mx-lg items-center">
      <p class="q-pl-lg col-grow fw-500 fs-200">
        Semestre
      </p>
      <q-btn-toggle
        v-model="currentView"
        toggle-color="secondary"
        toggle-text-color="white"
        color="op-secondary"
        text-color="secondary"
        rounded no-caps
        :options="[
          { label: 'Liste des ressources', value: 'resources' },
          { label: 'Vue par semaine', value: 'week-view' }
        ]"
        class="q-mr-sm"
      />
    </div>

    <resources-view
      v-show="currentView === 'resources'"
      :semester-id="semesterId"
    />

    <semester-week-view
      v-if="currentView === 'week-view'"
      :semester-id="semesterId"
    />
  </div>
</template>

<script setup>
  import { ref, computed } from 'vue'
  import { useRoute } from 'vue-router'
  import ResourcesView from 'src/modules/formations/components/ResourcesView.vue'
  import SemesterWeekView from 'src/modules/formations/components/SemesterWeekView.vue'

  const route = useRoute()
  const semesterId = computed(() => route.params?.id)
  const currentView = ref('resources')
</script>
