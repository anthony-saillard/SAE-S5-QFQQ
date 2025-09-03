<template>
  <div class="statistics-container q-mx-lg q-pb-xl">
    <p class="fw-600 fs-230">
      Statistiques
    </p>

    <div class="flex justify-end">
      <q-btn-toggle
        v-model="activeView"
        toggle-color="secondary"
        toggle-text-color="white"
        color="grey"
        text-color="dark"
        rounded
        no-caps
        :options="[
          { label: 'Statistiques par formations', value: 'formations' },
          { label: 'Statistiques par enseignants', value: 'enseignants' }
        ]"
        class="q-mx-lg q-mt-sm"
      />
    </div>


    <div v-if="activeView === 'formations'" class="q-mx-lg q-mt-md">
      <p class="fw-500 fs-160">
        Nombres d'heures par formation
      </p>

      <stats-by-formation-component
        :key="schoolYearStore.effectiveYearId"
        :formation-id="filters.formationId"
        :semester-id="filters.semesterId"
      />

      <stats-of-teachers
        :key="schoolYearStore.effectiveYearId"
        :formation-id="filters.formationId"
        :semester-id="filters.semesterId"
      />
    </div>

    <div v-if="activeView === 'enseignants'" class="q-mx-lg q-mt-md">
      <p class="fw-500 fs-160">
        Nombre d'heures par enseignant
      </p>

      <stats-by-teachers-component
        :key="schoolYearStore.effectiveYearId"
        :formation-id="filters.formationId"
        :user-id="filters.userId"
        :semester-id="filters.semesterId"
      />

      <StatsTeachersSalary
        :key="schoolYearStore.effectiveYearId"
        :formation-id="filters.formationId"
        :user-id="filters.userId"
        :semester-id="filters.semesterId"
      />
    </div>

    <div class="selectors-spacer" />
  </div>

  <div ref="fixedSelectors" class="fixed-selectors">
    <stats-selectors-component
      :key="`${schoolYearStore.effectiveYearId}-${activeView}`"
      ref="selectors"
      :current-view="activeView"
      @filters-changed="updateFilters"
    />
  </div>
</template>

<script setup>
  import {ref, onMounted, onUnmounted, watch, nextTick} from 'vue'
  import StatsByFormationComponent from 'src/modules/statistics/components/StatsByFormationComponent.vue'
  import StatsByTeachersComponent from 'src/modules/statistics/components/StatsByTeachersComponent.vue'
  import StatsSelectorsComponent from 'src/modules/statistics/components/StatsSelectorsComponent.vue'
  import { useSchoolYearStore } from 'src/utils/stores/useSchoolYearStore.js'
  import StatsOfTeachers from 'src/modules/statistics/components/StatsOfTeachers.vue'
  import StatsTeachersSalary from 'src/modules/statistics/components/StatsTeachersSalary.vue'

  const schoolYearStore = useSchoolYearStore()
  const fixedSelectors = ref(null)
  const selectors = ref(null)
  const activeView = ref('formations')

  const filters = ref({
    formationId: null,
    schoolYearId: null,
    userId: null,
    semesterId: null
  })

  function updateFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
  }

  watch(activeView, async (newView) => {
    if (newView === 'enseignants' && selectors.value) {
      await nextTick()
      if (!filters.value.userId && selectors.value.selectedTeacher) {
        updateFilters({ userId: selectors.value.selectedTeacher })
      }
    }
  })

  onMounted(() => {
    function handleDrawerToggle() {
      if (!fixedSelectors.value) {
        return
      }

      setTimeout(() => {
        const drawer = document.querySelector('.q-drawer')
        if (!drawer) {
          fixedSelectors.value.style.left = '0'
          return
        }

        const rect = drawer.getBoundingClientRect()
        const isVisible = rect.width > 0 && rect.x >= 0

        fixedSelectors.value.style.left = isVisible ? '280px' : '0'
      }, 50)
    }

    const bodyObserver = new MutationObserver(() => {
      handleDrawerToggle()
    })

    bodyObserver.observe(document.body, {
      attributes: true,
      attributeFilter: ['class']
    })

    const drawer = document.querySelector('.q-drawer')
    if (drawer) {
      const drawerObserver = new MutationObserver(() => {
        handleDrawerToggle()
      })

      drawerObserver.observe(drawer, {
        attributes: true,
        attributeFilter: ['style', 'class']
      })
    }

    const menuButton = document.querySelector('button[icon="menu"]')
    if (menuButton) {
      menuButton.addEventListener('click', () => {
        setTimeout(handleDrawerToggle, 100)
      })
    }

    window.addEventListener('resize', handleDrawerToggle)

    handleDrawerToggle()
  })

  onUnmounted(() => {
    window.removeEventListener('resize', () => {})
  })
</script>

<style scoped lang="scss">
.fixed-selectors {
  position: fixed;
  bottom: 0;
  right: 0;
  left: 0;
  background-color: white;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  padding: 10px;
}

.selectors-spacer {
  height: 150px;
}

@media (max-width: 1023px) {
  .fixed-selectors {
    left: 0 !important;
  }
}
</style>
