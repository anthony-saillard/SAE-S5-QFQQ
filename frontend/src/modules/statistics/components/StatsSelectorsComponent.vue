<template>
  <div class="selectors-container q-mb-md">
    <div class="row q-col-gutter-md">
      <template v-if="currentView === 'formations'">
        <div class="col-md-1 gt-sm" />
        <!-- Formation selector -->
        <div class="col-12 col-md-3">
          <q-select
            v-model="selectedFormation"
            :options="formations"
            option-value="id"
            option-label="label"
            label="Formation"
            filled
            emit-value
            map-options
            @update:model-value="handleFormationChange"
          />
        </div>

        <!-- Semester selector -->
        <div class="col-12 col-md-3">
          <q-select
            v-model="selectedSemester"
            :options="semesters"
            option-value="id"
            option-label="name"
            label="Semestre"
            filled
            emit-value
            map-options
            :disable="!selectedFormation"
          />
        </div>

        <!-- Teacher selector (disabled in formations view) -->
        <div class="col-12 col-md-3">
          <q-tooltip
            anchor="top middle"
            self="bottom middle"
            :offset="[0, 8]"
          >
            Pour filtrer par professeur, utilisez la vue "Statistiques par enseignant"
          </q-tooltip>
          <UserSelect
            v-model="selectedTeacher"
            label="Enseignant"
            disable
          />
        </div>
      </template>

      <template v-else>
        <div class="col-md-3 gt-sm" />

        <!-- Semester selector -->
        <div class="col-12 col-md-3">
          <q-select
            v-model="selectedSemester"
            :options="allSemesters"
            option-value="id"
            option-label="name"
            label="Semestre"
            filled
            emit-value
            map-options
          />
        </div>

        <!-- Teacher selector using UserSelect component -->
        <div class="col-12 col-md-3">
          <UserSelect
            v-model="selectedTeacher"
            label="Enseignant"
            option-value="id"
            class="bg-white"
            outlined
            simple-display
          />
        </div>

        <div class="col-md-3 gt-sm" />
      </template>
    </div>
  </div>
</template>

<script setup>
  import {ref, onMounted, watch, computed, nextTick} from 'vue'
  import { getAllFormations, getAllSemestersByFormation } from 'src/modules/settings/api.js'
  import { errorNotify } from 'src/utils/notify.js'
  import { ApiService } from 'src/services/apiService.js'
  import { useSchoolYearStore } from 'src/utils/stores/useSchoolYearStore.js'
  import UserSelect from 'src/modules/users/components/UserSelect.vue'
  import {logger} from 'src/utils/logger.js'

  const props = defineProps({
    currentView: {
      type: String,
      default: 'formations',
      validator: (value) => ['formations', 'professeurs'].includes(value)
    }
  })

  const schoolYearStore = useSchoolYearStore()
  const formations = ref([])
  const semesters = ref([])
  const allSemesters = ref([])
  const formationsData = ref([])

  const selectedFormation = ref(null)
  const selectedTeacher = ref(null)
  const selectedSemester = ref(null)
  const firstTeacherId = ref(null)

  const emit = defineEmits(['filters-changed'])

  onMounted(async () => {
    await Promise.all([fetchFormations(), fetchAllSemesters()])

    await fetchFirstTeacher()

    if (props.currentView === 'enseignants' && firstTeacherId.value) {
      selectedTeacher.value = firstTeacherId.value
      await nextTick()
      emitFilters()
    }
  })

  watch(() => props.currentView, async (newView) => {
    if (newView === 'formations') {
      selectedTeacher.value = null
    } else if (newView === 'enseignants') {
      selectedFormation.value = null
      if (firstTeacherId.value !== null) {
        selectedTeacher.value = firstTeacherId.value
      } else {
        await fetchFirstTeacher()
        if (firstTeacherId.value) {
          selectedTeacher.value = firstTeacherId.value
        }
      }
    }

    emitFilters()
  })

  watch([selectedFormation, selectedSemester], () => {
    emitFilters()
  })

  watch(selectedTeacher, () => {
    emitFilters()
  })

  const selectedTeacherId = computed(() => {
    if (selectedTeacher.value === null) {
      return null
    }

    if (typeof selectedTeacher.value === 'object') {
      return selectedTeacher.value.id
    }

    return selectedTeacher.value
  })

  function emitFilters() {
    emit('filters-changed', {
      formationId: selectedFormation.value,
      userId: selectedTeacherId.value,
      semesterId: selectedSemester.value
    })
  }

  async function fetchFormations() {
    try {
      const response = await getAllFormations()
      formationsData.value = response.data
      formations.value = response.data.map((formation) => ({
        id: formation.id,
        label: formation.label
      }))

      if (formations.value.length > 0) {
        selectedFormation.value = formations.value[0].id
        await fetchSemesters()
      } else {
        selectedFormation.value = null
        semesters.value = []
        selectedSemester.value = null
      }
    } catch {
      errorNotify('Erreur lors du chargement des formations')
    }
  }

  async function fetchAllSemesters() {
    try {
      const currentSchoolYearId = schoolYearStore.effectiveYearId
      const filters = { id_school_year: currentSchoolYearId }

      const semestersData = await ApiService.semesters.fetchSemesters(filters)

      if (semestersData && semestersData.length > 0) {
        allSemesters.value = semestersData.map(semester => ({
          id: semester.id,
          name: semester.name || `S${semester.id}`
        }))

        allSemesters.value.unshift({
          id: null,
          name: 'Tous les semestres'
        })
      } else {
        allSemesters.value = [{
          id: null,
          name: 'Tous les semestres'
        }]
      }
    } catch {
      errorNotify('Erreur lors du chargement des semestres pour l\'année en cours')
      allSemesters.value = [{
        id: null,
        name: 'Tous les semestres'
      }]
    }
  }

  async function fetchSemesters() {
    if (!selectedFormation.value) {
      semesters.value = []
      selectedSemester.value = null
      return
    }

    try {
      const response = await getAllSemestersByFormation(selectedFormation.value)

      if (response.data && response.data.length > 0) {
        semesters.value = response.data.map(semester => ({
          id: semester.id,
          name: semester.name || `S${semester.id}`
        }))

        semesters.value.unshift({
          id: null,
          name: 'Tous les semestres'
        })

        selectedSemester.value = null
      } else {
        semesters.value = []
        selectedSemester.value = null
      }
    } catch {
      errorNotify('Erreur lors du chargement des semestres')
      semesters.value = []
      selectedSemester.value = null
    }
  }

  async function handleFormationChange() {
    await fetchSemesters()
  }

  async function fetchFirstTeacher() {
    try {
      const teachersData = await ApiService.users.getUsersByRole('teacher')
      if (teachersData && teachersData.length > 0) {
        firstTeacherId.value = teachersData[0].id

        if (props.currentView === 'enseignants' && !selectedTeacher.value) {
          selectedTeacher.value = firstTeacherId.value
        }
      }
    } catch (error) {
      logger.error('Erreur lors de la récupération du premier enseignant:', error)
    }
  }

  defineExpose({
    selectedFormation,
    selectedTeacher,
    selectedSemester
  })
</script>

<style scoped lang="scss">
.selectors-container {
  padding: 8px;
  border-radius: 8px;
}

@media (max-width: 767px) {
  .selectors-container {
    .row {
      margin-left: -8px;
      margin-right: -8px;
    }
    .col-12 {
      padding-left: 8px;
      padding-right: 8px;
      margin-bottom: 8px;

      &:last-child {
        margin-bottom: 0;
      }
    }
  }
}
</style>
