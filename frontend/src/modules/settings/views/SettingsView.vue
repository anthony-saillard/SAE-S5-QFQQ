<template>
  <div class="q-mx-lg">
    <p class="fw-600 fs-230">
      Paramètres
    </p>

    <div v-if="schoolYearStore.isSchoolYearSelected" class="q-mx-lg q-mt-md">
      <p class="fw-500 fs-160">
        Gestion des périodes particulières
      </p>

      <pedagogical-interruptions
        :key="schoolYearStore.effectiveYearId"
        class="q-ml-lg"
      />
    </div>

    <div v-if="schoolYearStore.isSchoolYearSelected" class="q-mx-lg q-mt-md">
      <p class="fw-500 fs-160">
        Gestion des formations
      </p>

      <formations-section
        :key="schoolYearStore.effectiveYearId"
        class="q-ml-lg"
      />
    </div>

    <div v-if="schoolYearStore.isSchoolYearSelected" class="q-mx-lg q-mt-md">
      <p class="fw-500 fs-160">
        Gestion des types de cours
      </p>

      <course-types-section
        :key="schoolYearStore.effectiveYearId"
        class="q-ml-lg"
      />
    </div>

    <div class="q-mx-lg q-mt-md q-mb-lg">
      <p class="fw-500 fs-160">
        Gestion de l'année scolaire en cours
      </p>

      <school-years-section class="q-ml-lg" />
    </div>
  </div>
</template>

<script setup>
  import SchoolYearsSection from 'src/modules/settings/components/schoolYear/SchoolYearsSection.vue'
  import CourseTypesSection from 'src/modules/settings/components/courseType/CourseTypesSection.vue'
  import { useSchoolYearStore } from 'src/utils/stores/useSchoolYearStore.js'
  import FormationsSection from 'src/modules/settings/components/FormationsSection.vue'
  import PedagogicalInterruptions from 'src/modules/settings/components/pedagogicalInterruptions/PedagogicalInterruptions.vue'

  const schoolYearStore = useSchoolYearStore()
</script>

<style lang="scss" scoped>
::v-deep(.q-list) {
  display: flex;
  gap: 0;
  width: 100%;

  .q-item {
    background-color: $grey-4;
    font-weight: 500;
    border-radius: 15px;
    padding: 5px 15px;
    min-height: unset;

    .delete-button:hover {
      color: $negative;
    }

    .edit-button:hover {
      color: $edit;
    }

    &.selected-item {
      background-color: $primary-op;
      color: $primary;
    }

    &.add-btn {
      background-color: $primary-op;
      color: $primary;
      border: 2px dotted $primary;
    }
  }
}
</style>
