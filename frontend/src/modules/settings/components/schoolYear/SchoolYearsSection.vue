<template>
  <section-component class="q-pa-md">
    <div v-if="!loading" class="row">
      <div class="col-6 flex justify-start items-center">
        <school-years-select-default
          ref="selectRef"
          :key="'default-years-select-' + refreshKey"
          v-model="selectedYear"
          @update:model-value="updateSelectedYear"
        />
        <q-btn
          v-if="selectedYear"
          class="q-ml-sm"
          color="transparent"
          text-color="text"
          round
          icon="edit"
          no-caps unelevated
          @click.stop="openEditDialog"
        />
      </div>
      <div class="col-6 flex justify-end items-center">
        <q-btn
          class="q-mr-lg"
          color="primary"
          icon-right="add"
          label="Ajouter une nouvelle année"
          no-caps unelevated
          @click.stop="openAddDialog"
        />
      </div>
    </div>

    <div v-else class="flex items-center justify-center op-80 fs-90">
      <q-spinner class="q-mr-sm" /> Chargement...
    </div>

    <dialog-component
      v-model="dialogShow"
      :title="dialogTitle"
      :highlight-title-words="['année', 'scolaire']"
      :form-ref="formRef"
      :loading="loading"
      size="sm"
    >
      <school-year-form
        ref="formRef"
        :edit-mode="isEditMode"
        :initial-data="selectedYear"
        @submit="onSubmit"
      />
    </dialog-component>
  </section-component>
</template>

<script setup>
  import { ref, computed, watch } from 'vue'
  import SchoolYearsSelectDefault from './SchoolYearsSelectDefault.vue'
  import DialogComponent from 'src/modules/core/components/DialogComponent.vue'
  import SchoolYearForm from './SchoolYearForm.vue'
  import SectionComponent from 'src/modules/core/components/SectionComponent.vue'
  import { useSchoolYearStore } from 'src/utils/stores/useSchoolYearStore.js'
  import { errorNotify } from 'src/utils/notify.js'
  import { logger } from 'src/utils/logger.js'

  defineEmits(['update:modelValue'])

  const store = useSchoolYearStore()

  const dialogShow = ref(false)
  const isEditMode = ref(false)
  const formRef = ref(null)
  const selectRef = ref(null)
  const selectedYear = ref(store.currentYear)
  const loading = ref(false)
  const refreshKey = ref(0)

  const dialogTitle = computed(() =>
    isEditMode.value ? 'Modifier une année scolaire' : 'Ajouter une année scolaire'
  )

  watch(() => store.lastUpdate, () => {
    refreshKey.value++
  })

  function updateSelectedYear(year) {
    selectedYear.value = year
  }

  function openAddDialog() {
    isEditMode.value = false
    dialogShow.value = true
  }

  function openEditDialog() {
    isEditMode.value = true
    dialogShow.value = true
  }

  async function onSubmit() {
    loading.value = true
    try {
      if (selectRef.value) {
        await selectRef.value.refreshYears()
      }

      refreshKey.value++
      dialogShow.value = false
    } catch (error) {
      errorNotify(`Impossible de ${isEditMode.value ? 'modifier' : 'créer'} l'année scolaire`)
      logger.error(error)
    } finally {
      loading.value = false
    }
  }
</script>
