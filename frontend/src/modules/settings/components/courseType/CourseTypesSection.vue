<template>
  <section-component class="q-pa-md">
    <q-list v-if="!loading" class="row">
      <div
        v-for="type in courseTypesList"
        :key="type.id"
        class="col-3 q-pl-sm q-pt-sm"
      >
        <q-item clickable>
          <q-item-section>
            {{ type.name }}
          </q-item-section>
          <div>
            <q-btn
              class="edit-button"
              flat round dense icon="edit"
              size="11px"
              @click.stop="openEditDialog(type)"
            />
            <q-btn
              class="delete-button"
              flat round dense icon="delete"
              size="11px"
              @click.stop="onDelete(type)"
            />
          </div>
        </q-item>
      </div>

      <div class="col-3 q-pl-sm q-pt-sm">
        <q-item
          clickable
          class="add-btn row flex items-center justify-start"
          @click="openCreateDialog"
        >
          <q-icon name="add" size="sm" class="q-mr-md" />
          Ajouter
        </q-item>
      </div>
    </q-list>

    <div v-else class="flex items-center justify-center op-80 fs-90">
      <q-spinner class="q-mr-sm" /> Chargement...
    </div>

    <dialog-component
      v-model="dialogShow"
      :title="editMode ? 'Modifier le type de cours' : 'Ajouter un type de cours'"
      :highlight-title-words="['type', 'de', 'cours']"
      :form-ref="formRef"
      size="sm"
    >
      <course-type-form
        ref="formRef"
        :edit-mode="editMode"
        :initial-data="editMode ? selectedType : null"
        @submit="onFormSubmit"
      />
    </dialog-component>
  </section-component>
</template>

<script setup>
  import DialogComponent from 'src/modules/core/components/DialogComponent.vue'
  import CourseTypeForm from './CourseTypeForm.vue'
  import SectionComponent from 'src/modules/core/components/SectionComponent.vue'
  import { ref, onMounted } from 'vue'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import { logger } from 'src/utils/logger.js'
  import { confirmDialog } from 'src/utils/dialog.js'
  import {ApiService} from 'src/services/apiService.js'

  const dialogShow = ref(false)

  const deleteConfirm = ref(false)

  const editMode = ref(false)

  const courseTypesList = ref([])

  const selectedType = ref(null)
  const formRef = ref(null)

  const loading = ref(false)

  onMounted(load)

  async function load() {
    loading.value = true
    try {
      const response = await ApiService.courseTypes.fetchCourseTypes(false, true)
      courseTypesList.value = response.sort((a, b) => a.name.localeCompare(b.name))
      if (courseTypesList.value.length > 0) {
        selectedType.value = { ...courseTypesList.value[0] }
      }
    } catch (error) {
      errorNotify('Erreur lors du chargement des types de cours')
      logger.error(error)
    } finally {
      loading.value = false
    }
  }

  function openCreateDialog() {
    editMode.value = false
    dialogShow.value = true
  }

  function openEditDialog(type) {
    selectedType.value = type
    editMode.value = true
    dialogShow.value = true
  }

  async function onFormSubmit() {
    dialogShow.value = false
    await load()
  }

  async function onDelete(type) {
    if (!await confirmDialog('Voulez-vous vraiment désactiver ce type de cours ?')) {
      return
    }

    loading.value = true
    try {
      await ApiService.courseTypes.deleteCourseType(type.id)
      successNotify('Type de cours supprimé avec succès')
      deleteConfirm.value = false
      await load()
    } catch (error) {
      errorNotify('Erreur lors de la suppression du type de cours')
      logger.error(error)
    } finally {
      loading.value = false
    }
  }
</script>
