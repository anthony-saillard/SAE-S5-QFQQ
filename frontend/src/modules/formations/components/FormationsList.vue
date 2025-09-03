<template>
  <div v-if="!loading">
    <div
      ref="formationsList"
      class="formations-list"
    >
      <div
        v-for="(formation, index) in localFormations"
        :key="formation.id"
        class="formation-item q-mb-sm"
        :class="{ 'selected': !isEditing && selectedFormation?.id === formation.id }"
        @click="!isEditing && handleFormationClick(formation)"
      >
        <div class="row items-center bg-grey-4 q-pa-sm rounded-borders">
          <q-icon
            v-if="isEditing"
            name="drag_indicator"
            class="drag-handle cursor-move q-mr-sm"
          />

          <div v-if="isEditing" class="row items-center q-mr-md">
            <q-btn
              flat dense round
              size="sm"
              icon="arrow_upward"
              :disable="index === 0"
              @click.stop="moveFormation(index, -1)"
            />
            <q-btn
              flat dense round
              size="sm"
              icon="arrow_downward"
              :disable="index === localFormations.length - 1"
              @click.stop="moveFormation(index, 1)"
            />
          </div>

          <div class="col">
            <div v-if="isEditing && editingFormationId === formation.id">
              <q-input
                v-model="formation.label"
                dense borderless
                bg-color="white"
                class="q-px-sm"
                @blur="finishLabelEdit(formation)"
                @keyup.enter="finishLabelEdit(formation)"
                @input="updateFormationOrder"
              />
            </div>
            <div
              v-else
              class="cursor-pointer fw-500 q-px-sm"
              @click.stop="isEditing ? startLabelEdit(formation) : handleFormationClick(formation)"
            >
              {{ formation.label }}
            </div>
          </div>

          <div v-if="isEditing" class="row items-center">
            <q-badge
              v-if="isFormationModified(formation) || hasModifiedSemesters(formation)"
              color="blue" rounded
              class="q-mr-sm" style="min-width: 8px;min-height: 8px;padding: unset;"
            />

            <q-btn
              v-if="formation.semesters.length <= 0"
              flat dense round
              size="sm"
              icon="delete"
              color="negative"
              @click.stop="removeFormation(index)"
            />
          </div>
        </div>

        <div class="q-pl-lg">
          <div
            :ref="el => { if (el) semestersListRefs[formation.id] = el }"
            class="semesters-list"
          >
            <div
              v-for="(semester, semesterIndex) in formation.semesters"
              :key="semester.id"
              class="semester-item q-my-sm"
              :class="{ 'selected': !isEditing && selectedSemester?.id === semester.id }"
              @click.stop="!isEditing && handleSemesterClick(formation, semester)"
            >
              <div class="row items-center bg-grey-3 q-pa-sm rounded-borders">
                <q-icon
                  v-if="isEditing"
                  name="drag_indicator"
                  class="semester-drag-handle cursor-move q-mr-sm"
                />
                <div class="col">
                  <div v-if="isEditing && editingSemesterId === semester.id">
                    <q-input
                      v-model="semester.name"
                      dense
                      borderless
                      bg-color="white"
                      class="q-px-sm"
                      @blur="finishSemesterEdit(semester)"
                      @keyup.enter="finishSemesterEdit(semester)"
                      @input="updateSemesterOrder(formation)"
                    />
                  </div>
                  <div
                    v-else
                    class="cursor-pointer fw-500"
                    @click.stop="isEditing ? startSemesterEdit(semester) : handleSemesterClick(formation, semester)"
                  >
                    {{ semester.name }}
                  </div>
                </div>
                <div v-if="isEditing" class="row items-center">
                  <q-badge
                    v-if="isSemesterModified(formation, semester)"
                    color="blue" rounded
                    class="q-mr-sm" style="min-width: 8px;min-height: 8px;padding: unset;"
                  />
                  <q-btn
                    flat dense round
                    size="sm"
                    icon="delete"
                    color="negative"
                    @click.stop="removeSemester(formation, semesterIndex)"
                  />
                </div>
                <div v-else class="row items-center">
                  <q-badge
                    v-if="!semester.start_date || !semester.end_date"
                    color="red" rounded
                    class="q-mr-sm" style="min-width: 8px;min-height: 8px;padding: unset;"
                  />
                </div>
              </div>
            </div>
          </div>

          <q-btn
            v-if="isEditing"
            color="primary" icon="add"
            label="Ajouter un semestre"
            class="custom-btn full-width q-my-sm"
            no-caps flat size="sm"
            @click.stop="addSemester(formation)"
          />
        </div>
      </div>
    </div>

    <q-btn
      v-if="isEditing"
      class="custom-btn full-width q-mb-md"
      no-caps flat size="sm"
      color="primary"
      icon="add"
      label="Ajouter une formation"
      @click="addFormation"
    />

    <div class="row q-mb-md">
      <q-btn
        v-if="!isEditing"
        color="primary"
        icon="edit"
        label="Éditer"
        size="sm"
        no-caps unelevated
        @click="startEditing"
      />
      <template v-else>
        <q-btn
          color="positive"
          icon="save"
          label="Sauvegarder"
          size="sm"
          class="q-mr-sm"
          no-caps unelevated
          @click="saveChanges"
        />
        <q-btn
          color="negative"
          icon="close"
          label="Annuler"
          size="sm"
          no-caps unelevated
          @click="cancelEditing"
        />
      </template>
    </div>
  </div>

  <div v-else class="flex items-center justify-center op-80 fs-90">
    <q-spinner class="q-mr-sm" /> Chargement...
  </div>
</template>

<script setup>
  import { ref, onMounted, nextTick, watch } from 'vue'
  import Sortable from 'sortablejs'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import {
    createFormation,
    deleteFormation,
    getAllFormations,
    editFormation,
    deleteSemester,
    editSemester,
    createSemester,
    getAllSemestersByFormation
  } from 'src/modules/settings/api.js'
  import { logger } from 'src/utils/logger.js'

  const emit = defineEmits(['update:selectedFormation', 'update:selectedSemester'])

  const loading = ref(false)
  const isEditing = ref(false)

  const editingFormationId = ref(null)
  const editingSemesterId = ref(null)

  const selectedFormation = ref(null)
  const selectedSemester = ref(null)

  const originalFormations = ref([])
  const localFormations = ref([])
  const deletedFormations = ref([])
  const modifiedFormations = ref([])

  const modifiedSemesters = ref([])

  const formationsList = ref(null)
  const semestersListRefs = ref({})

  const formationOrderChanged = ref(false)
  const semesterOrdersChanged = ref({})

  async function load() {
    loading.value = true
    try {
      const formationsResponse = await getAllFormations()
      const formations = formationsResponse.data || []

      // Sort formations by order_number
      formations.sort((a, b) => a.order_number - b.order_number)

      const tempFormations = formations.map(f => ({
        ...f,
        semesters: []
      }))

      const semestersPromises = formations
        .filter(f => f.id)
        .map(f => getAllSemestersByFormation(f.id))
      const semestersResponses = await Promise.all(semestersPromises)

      for (let i = 0; i < formations.length; i++) {
        // Sort semesters by order_number
        const semesters = semestersResponses[i]?.data || []
        tempFormations[i].semesters = semesters.sort((a, b) => a.order_number - b.order_number)
      }

      originalFormations.value = JSON.parse(JSON.stringify(tempFormations))
      localFormations.value = JSON.parse(JSON.stringify(tempFormations))
    } catch (error) {
      logger.error('Error loading formations and semesters:', error)
    } finally {
      loading.value = false
    }
  }

  function initializeSortable() {
    nextTick(() => {
      // For formations
      if (formationsList.value) {
        new Sortable(formationsList.value, {
          handle: '.drag-handle',
          animation: 150,
          group: 'formations',
          onEnd: (evt) => {
            const item = localFormations.value.splice(evt.oldIndex, 1)[0]
            localFormations.value.splice(evt.newIndex, 0, item)
            updateFormationOrder()
          }
        })
      }

      // For semesters
      Object.entries(semestersListRefs.value).forEach(([formationId, semestersList]) => {
        if (semestersList) {
          new Sortable(semestersList, {
            handle: '.semester-drag-handle',
            animation: 150,
            group: `semesters-${formationId}`,
            onEnd: (evt) => {
              const formation = localFormations.value.find(f => f.id == formationId)
              if (formation) {
                const item = formation.semesters.splice(evt.oldIndex, 1)[0]
                formation.semesters.splice(evt.newIndex, 0, item)
                updateSemesterOrder(formation)
              }
            }
          })
        }
      })
    })
  }

  function updateFormationOrder() {
    // Update order numbers
    localFormations.value.forEach((formation, index) => {
      formation.order_number = index + 1
    })

    // For each course, check whether its position has changed
    localFormations.value.forEach(formation => {
      const originalIndex = originalFormations.value.findIndex(f => f.id === formation.id)
      const currentIndex = localFormations.value.findIndex(f => f.id === formation.id)

      if (originalIndex !== currentIndex) {
        formationOrderChanged.value = true
      }
    })
  }

  function updateSemesterOrder(formation) {
    if (!formation?.semesters) {
      return
    }

    formation.semesters.forEach((semester, index) => {
      semester.order_number = index + 1
    })

    const originalFormation = originalFormations.value.find(f => f.id === formation.id)
    if (!originalFormation) {
      return
    }

    // Checks if the order is different from the original
    const hasOrderChanged = formation.semesters.some((semester, currentIndex) => {
      const originalSemester = originalFormation.semesters.find(s => s.id === semester.id)
      return originalSemester && originalSemester.order_number !== (currentIndex + 1)
    })

    // Update change state
    semesterOrdersChanged.value = {
      ...semesterOrdersChanged.value,
      [formation.id]: hasOrderChanged
    }
  }

  function startEditing() {
    isEditing.value = true
    selectedFormation.value = null
    selectedSemester.value = null
    emit('update:selectedFormation', null)
    emit('update:selectedSemester', null)
  }

  function handleFormationClick(formation) {
    selectedFormation.value = formation
    selectedSemester.value = null
    emit('update:selectedFormation', formation)
    emit('update:selectedSemester', null)
  }

  function handleSemesterClick(formation, semester) {
    selectedFormation.value = formation
    selectedSemester.value = semester
    emit('update:selectedFormation', formation)
    emit('update:selectedSemester', semester)
  }

  async function saveChanges() {
    try {
      const newEntities = []

      // Create all new formations
      const newFormations = localFormations.value.filter(f => String(f.id).startsWith('temp-'))
      for (const formation of newFormations) {
        const tempId = formation.id
        const response = await createFormation({
          ...formation,
          order_number: formation.order_number
        })
        newEntities.push({
          tempId,
          newEntity: response.data,
          type: 'formation'
        })

        // Update formation ID in localFormations
        const formationIndex = localFormations.value.findIndex(f => f.id === tempId)
        if (formationIndex !== -1) {
          localFormations.value[formationIndex] = {
            ...response.data,
            semesters: localFormations.value[formationIndex].semesters
          }
        }
      }

      // Update existing formations
      const updatedFormations = localFormations.value.filter(f =>
        !String(f.id).startsWith('temp-') && isFormationModified(f)
      )
      const updatePromises = updatedFormations.map(formation =>
        editFormation(formation.id, {
          ...formation,
          order_number: formation.order_number
        })
      )
      await Promise.all(updatePromises)

      const semesterPromises = []

      for (const formation of localFormations.value) {
        // Identify all semesters requiring updating
        const semestersToUpdate = formation.semesters.filter(s =>
          !String(s.id).startsWith('temp-') && (
            isSemesterModified(formation, s) ||
            semesterOrdersChanged.value[formation.id]
          )
        )

        // Update the semesters in the formation
        if (semesterOrdersChanged.value[formation.id]) {
          const existingSemesters = formation.semesters.filter(s => !String(s.id).startsWith('temp-'))
          semesterPromises.push(...existingSemesters.map(semester =>
            editSemester(semester.id, {
              ...semester,
              id_formation: formation.id,
              order_number: semester.order_number
            })
          ))
        } else {
          semesterPromises.push(...semestersToUpdate.map(semester =>
            editSemester(semester.id, {
              ...semester,
              id_formation: formation.id,
              order_number: semester.order_number
            })
          ))
        }

        // Create the new semesters
        const newSemesters = formation.semesters.filter(s => String(s.id).startsWith('temp-'))
        for (const semester of newSemesters) {
          const tempId = semester.id
          const promise = createSemester({
            id_formation: formation.id,
            ...semester,
            order_number: semester.order_number
          }).then(response => {
            newEntities.push({
              tempId,
              newEntity: response.data,
              type: 'semester',
              formationId: formation.id
            })
            return response
          })
          semesterPromises.push(promise)
        }

        // Delete semesters
        if (formation.deletedSemesters) {
          semesterPromises.push(...formation.deletedSemesters.map(semester =>
            deleteSemester(semester.id)
          ))
        }
      }

      await Promise.all(semesterPromises)

      // Delete formations
      const deletePromises = deletedFormations.value.map(formation =>
        deleteFormation(formation.id)
      )
      await Promise.all(deletePromises)

      updateFinalState(newEntities)

      // Reset editing state
      cancelEditing()
      isEditing.value = false
      successNotify('Modifications enregistrées avec succès')
    } catch (error) {
      errorNotify('Erreur lors de l\'enregistrement des modifications')
      logger.error(error)
    }
  }

  function updateFinalState(newEntities) {
    localFormations.value = localFormations.value.map(formation => {
      const newFormationEntity = newEntities.find(
        e => e.type === 'formation' && e.tempId === formation.id
      )

      if (newFormationEntity) {
        return {
          ...newFormationEntity.newEntity,
          semesters: formation.semesters.map(semester => {
            const newSemesterEntity = newEntities.find(
              e => e.type === 'semester' &&
                e.tempId === semester.id &&
                e.formationId === formation.id
            )
            return newSemesterEntity ? newSemesterEntity.newEntity : semester
          })
        }
      }

      return {
        ...formation,
        semesters: formation.semesters.map(semester => {
          const newSemesterEntity = newEntities.find(
            e => e.type === 'semester' &&
              e.tempId === semester.id &&
              e.formationId === formation.id
          )
          return newSemesterEntity ? newSemesterEntity.newEntity : semester
        })
      }
    })

    originalFormations.value = JSON.parse(JSON.stringify(localFormations.value))
  }

  function hasModifiedSemesters(formation) {
    if (formation.deletedSemesters?.length > 0) {
      return true
    }

    const originalFormation = originalFormations.value.find(f => f.id === formation.id)
    if (!originalFormation) {
      return true
    }

    const hasOrderChanged = formation.semesters.some((semester, index) => {
      const originalIndex = originalFormation.semesters.findIndex(s => s.id === semester.id)
      return originalIndex !== index
    })

    const hasModifiedContent = formation.semesters.some(semester =>
      isSemesterModified(formation, semester)
    )

    const hasNewSemesters = formation.semesters.some(semester =>
      String(semester.id).startsWith('temp-')
    )

    return hasOrderChanged || hasModifiedContent || hasNewSemesters
  }

  function isFormationModified(formation) {
    if (String(formation.id).startsWith('temp-')) {
      return true
    }

    const original = originalFormations.value.find(f => f.id === formation.id)
    if (!original) {
      return true
    }

    const originalIndex = originalFormations.value.findIndex(f => f.id === formation.id)
    const currentIndex = localFormations.value.findIndex(f => f.id === formation.id)

    const hasPositionChanged = originalIndex !== currentIndex

    return formation.label !== original.label || hasPositionChanged
  }

  function isSemesterModified(formation, semester) {
    if (String(semester.id).startsWith('temp-')) {
      return true
    }

    const originalFormation = originalFormations.value.find(f => f.id === formation.id)
    if (!originalFormation) {
      return true
    }

    const originalSemester = originalFormation.semesters.find(s => s.id === semester.id)
    if (!originalSemester) {
      return true
    }

    const originalIndex = originalFormation.semesters.findIndex(s => s.id === semester.id)
    const currentIndex = formation.semesters.findIndex(s => s.id === semester.id)

    const hasPositionChanged = originalIndex !== currentIndex

    return semester.name !== originalSemester.name || hasPositionChanged
  }

  function moveFormation(index, direction) {
    const newIndex = index + direction
    if (newIndex >= 0 && newIndex < localFormations.value.length) {
      const item = localFormations.value.splice(index, 1)[0]
      localFormations.value.splice(newIndex, 0, item)
      updateFormationOrder()
    }
  }

  function addFormation() {
    const newFormation = {
      id: `temp-${Date.now()}`,
      label: 'Nouvelle formation',
      semesters: [],
      order_number: localFormations.value.length + 1
    }
    localFormations.value.push(newFormation)
    initializeSortable()
  }

  function removeFormation(index) {
    const formation = localFormations.value.splice(index, 1)[0]
    if (!String(formation.id).startsWith('temp-')) {
      deletedFormations.value.push(formation)
    }
  }

  function startLabelEdit(formation) {
    editingFormationId.value = formation.id
  }

  function finishLabelEdit() {
    editingFormationId.value = null
  }

  function addSemester(formation) {
    if (!formation.semesters) {
      formation.semesters = []
    }
    const newSemester = {
      id: `temp-${Date.now()}`,
      name: `Semestre ${formation.semesters.length + 1}`,
      order_number: formation.semesters.length + 1
    }
    formation.semesters.push(newSemester)
    initializeSortable()
  }

  function removeSemester(formation, index) {
    const semester = formation.semesters.splice(index, 1)[0]
    if (!String(semester.id).startsWith('temp-')) {
      formation.deletedSemesters = formation.deletedSemesters || []
      formation.deletedSemesters.push(semester)
    }
  }

  function startSemesterEdit(semester) {
    editingSemesterId.value = semester.id
  }

  function finishSemesterEdit() {
    editingSemesterId.value = null
  }

  function cancelEditing() {
    localFormations.value = JSON.parse(JSON.stringify(originalFormations.value))

    isEditing.value = false
    editingFormationId.value = null
    editingSemesterId.value = null

    deletedFormations.value = []
    modifiedFormations.value = []
    modifiedSemesters.value = []

    localFormations.value.forEach(formation => {
      formation.deletedSemesters = []
    })

    selectedFormation.value = null
    selectedSemester.value = null

    semestersListRefs.value = {}

    formationOrderChanged.value = false
    semesterOrdersChanged.value = {}

    initializeSortable()
  }

  watch(() => localFormations.value, () => {
    nextTick(() => {
      initializeSortable()
      updateFormationOrder()
    })
  }, { deep: true })

  watch(
    () => localFormations.value,
    () => {
      localFormations.value.forEach(formation => {
        updateSemesterOrder(formation)
      })
    },
    {
      deep: true,
      flush: 'post'
    }
  )

  watch(selectedFormation, () => {

  })

  onMounted(load)
</script>

<style lang="scss" scoped>
.formations-list {
  .formation-item {
    border-radius: 8px;
    overflow: hidden;

    &.selected > div.row {
      background-color: $primary-op!important;
    }
  }

  .semester-item {
    margin-left: 24px;

    &.selected > div {
      background-color: $primary-op!important;
    }
  }

  .drag-handle, .semester-drag-handle {
    cursor: move;
    opacity: 0.6;

    &:hover {
      opacity: 1;
    }
  }
}

.custom-btn {
  background-color: $primary-op;
  color: $primary;
  border: 2px dotted $primary;
  border-radius: 15px;
}
</style>
