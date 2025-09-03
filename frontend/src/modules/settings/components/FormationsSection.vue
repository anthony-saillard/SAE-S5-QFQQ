<template>
  <section-component class="row q-pa-md">
    <formations-list
      v-model:selected-formation="selectedFormation"
      v-model:selected-semester="selectedSemester"
      class="col-4"
    />
    <div class="col-grow q-px-lg q-pt-md">
      <div v-if="selectedFormation">
        <div
          class="fs-150 fw-500 q-mb-md"
        >
          {{ selectedFormation.label }}
        </div>
        <div v-if="selectedSemester" class="q-gutter-sm q-ml-sm">
          <q-input
            v-model="selectedSemester.start_date"
            type="date"
            label="Date de début"
            filled
            :error="empty(selectedSemester.start_date)"
            :error-message="empty(selectedSemester.start_date) ? 'Veuillez compléter la date de début de ce semestre !' : ''"
            @update:model-value="handleDateChange"
          />
          <q-input
            v-model="selectedSemester.end_date"
            type="date"
            label="Date de fin"
            filled
            :error="empty(selectedSemester.end_date)"
            :error-message="empty(selectedSemester.end_date) ? 'Veuillez compléter la date de fin de ce semestre !' : ''"
            @update:model-value="handleDateChange"
          />
        </div>
        <div v-else class="q-ml-sm">
          <group-list :formation-id="selectedFormation.id" />
        </div>
      </div>
    </div>
  </section-component>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import FormationsList from 'src/modules/formations/components/FormationsList.vue'
  import SectionComponent from 'src/modules/core/components/SectionComponent.vue'
  import GroupList from 'src/modules/settings/components/GroupList.vue'
  import { editSemester } from 'src/modules/settings/api.js'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import { logger } from 'src/utils/logger.js'
  import { empty } from 'src/utils/utils.js'

  const selectedFormation = ref(null)
  const selectedSemester = ref(null)

  async function handleDateChange() {
    if (!selectedSemester.value) {
      return
    }

    try {
      await editSemester(selectedSemester.value.id, {
        ...selectedSemester.value,
        start_date: selectedSemester.value.start_date,
        end_date: selectedSemester.value.end_date
      })
      successNotify('Dates mises à jour avec succès')
    } catch (error) {
      errorNotify('Erreur lors de la mise à jour des dates')
      logger.error(error)
    }
  }
</script>
