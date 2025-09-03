<template>
  <div v-if="loading" class="full-width flex justify-center">
    <q-spinner-dots size="xl" color="primary" />
  </div>
  <template v-else-if="weeks.length > 0 && !hasLoadingError">
    <div class="row items-start q-mb-sm">
      <div class="col">
        <pedagogical-legend
          :periods="pedagogicalInterruptions"
        />
      </div>

      <div class="col-auto q-gutter-sm">
        <q-btn
          v-if="!effectiveReadOnly"
          color="grey-7"
          icon="content_copy"
          unelevated no-caps size="sm"
          label="Copier / Coller"
          @click="showCopyPasteDialog"
        />

        <q-btn
          v-if="!effectiveReadOnly"
          color="secondary"
          icon="library_add"
          unelevated no-caps size="sm"
          label="Semaine modèle"
          @click="showTemplateWeekDialog"
        />
      </div>
    </div>

    <div class="row no-wrap" style="border-radius: 15px;overflow: hidden;">
      <course-types-column
        :course-types="courseTypes"
      />

      <div style="flex: 1;">
        <assignments-table
          :weeks="weeks"
          :course-types="courseTypes"
          :assignments="assignments"
          :pedagogical-interruptions="pedagogicalInterruptions"
          :resource="resource"
          :read-only="effectiveReadOnly"
          @edit-assignment="editAssignment"
          @copy-week="handleCopyWeek"
          @paste-week="handlePasteWeek"
          @save-template-week="handleSaveTemplateWeek"
          @paste-template-week="handlePasteTemplateWeek"
        />
      </div>
    </div>

    <dialog-component
      v-model="showAssignmentDialog"
      title="Affectation de cours"
      :loading="assignmentLoading"
      size="md"
      :form-ref="formRef"
      @hide="resetForm"
    >
      <template #default>
        <assignment-form
          ref="formRef"
          :initial-data="assignmentFormData"
          :edit-mode="assignmentFormData.id !== null"
          :week="currentWeek"
          @submit="saveAssignment"
        />
      </template>

      <template #secondActions>
        <q-btn
          v-if="assignmentFormData.id"
          label="Supprimer"
          no-caps dense color="red-5"
          @click="deleteAssignment"
        />
      </template>
    </dialog-component>

    <dialog-component
      v-model="showCopyDialog"
      title="Copier/coller une semaine"
      size="md"
      :loading="copyLoading"
      :form-ref="copyFormRef"
    >
      <q-form ref="copyFormRef" class="q-gutter-md" @submit="executeCopy">
        <div v-if="isCrossResourcePaste" class="bg-yellow-1 q-pa-sm rounded-borders">
          <div class="text-subtitle2 text-weight-medium q-mb-xs">
            <q-icon name="info" color="amber" /> Copie inter-ressources
          </div>
          <div class="text-caption">
            Vous êtes en train de coller des données provenant d'une autre ressource ({{ sourceResourceName }}).
          </div>
        </div>

        <q-select
          v-if="!isCrossResourcePaste"
          v-model="sourceWeekIndex"
          :options="weekSelectOptions"
          label="Semaine source"
          filled emit-value map-options
          :rules="[val => val !== null || 'Veuillez sélectionner une semaine source']"
        />

        <q-separator />

        <div class="text-subtitle2 q-my-sm">
          Options de copie :
        </div>

        <div class="row q-gutter-md">
          <q-checkbox
            v-model="copyComments"
            label="Inclure les commentaires"
          />
          <q-checkbox
            v-model="copyTeachers"
            label="Inclure les enseignants"
          />
        </div>

        <q-separator />

        <div class="text-subtitle2 q-my-sm">
          Semaines cibles :
        </div>

        <div class="row q-mb-sm">
          <q-checkbox
            v-model="applyCopyToAll"
            label="Appliquer à toutes les semaines"
            class="q-mr-sm"
          />
        </div>

        <q-select
          v-if="!applyCopyToAll"
          v-model="targetWeeksForCopy"
          :options="filteredTargetOptions"
          label="Semaines cibles"
          filled multiple emit-value map-options
          :rules="[val => val.length > 0 || 'Veuillez sélectionner au moins une semaine cible']"
        >
          <template #selected-item="scope">
            <q-chip
              removable
              :tabindex="scope.tabindex"
              dense
              color="primary"
              text-color="white"
              @remove="scope.removeAtIndex(scope.index)"
            >
              Semaine {{ scope.opt.value + 1 }}
            </q-chip>
          </template>
        </q-select>

        <q-checkbox
          v-model="replaceExisting"
          label="Remplacer les assignations existantes"
          class="q-mt-sm"
        />
      </q-form>
    </dialog-component>

    <dialog-component
      v-model="showTemplateDialog"
      title="Gestion de la semaine modèle"
      :highlight-title-words="['semaine', 'modèle']"
      size="md"
      :loading="templateLoading"
      :form-ref="templateFormRef"
    >
      <q-form ref="templateFormRef" class="q-gutter-md" @submit="executeTemplate">
        <div class="q-mb-lg flex justify-center full-width">
          <q-btn-toggle
            v-model="templateAction"
            toggle-color="secondary"
            toggle-text-color="white"
            color="op-secondary"
            text-color="secondary"
            rounded no-caps
            :options="[
              { label: 'Enregistrer une semaine comme modèle', value: 'save' },
              { label: 'Appliquer la semaine type', value: 'apply', disable: !hasTemplateWeek }
            ]"
          />
        </div>

        <q-select
          v-if="templateAction === 'save'"
          v-model="templateSourceWeek"
          :options="weekSelectOptions"
          label="Semaine à enregistrer comme modèle"
          filled
          emit-value
          map-options
          :rules="[val => val !== null || 'Veuillez sélectionner une semaine source']"
        />

        <template v-if="templateAction === 'apply' && hasTemplateWeek">
          <div v-if="templateWeekData && templateWeekData.length > 0" class="q-pa-sm q-mb-md bg-grey-2 rounded-borders">
            <div class="text-subtitle2 q-mb-xs">
              Contenu de la semaine type :
            </div>
            <div v-for="(item, idx) in templateWeekPreview" :key="idx" class="text-caption">
              {{ item }}
            </div>
          </div>

          <div class="text-subtitle2 q-my-sm">
            Options d'application :
          </div>

          <div class="row q-gutter-md">
            <q-checkbox
              v-model="copyTemplateComments"
              label="Inclure les commentaires"
            />
            <q-checkbox
              v-model="copyTemplateTeachers"
              label="Inclure les enseignants"
            />
          </div>

          <div class="text-subtitle2 q-my-sm">
            Semaines cibles :
          </div>

          <div class="row q-mb-sm">
            <q-checkbox
              v-model="applyTemplateToAll"
              label="Appliquer à toutes les semaines"
              class="q-mr-sm"
            />
          </div>

          <q-select
            v-if="!applyTemplateToAll"
            v-model="targetWeeksForTemplate"
            :options="weekSelectOptions"
            label="Semaines cibles"
            filled
            multiple
            emit-value
            map-options
            :rules="[val => val.length > 0 || 'Veuillez sélectionner au moins une semaine cible']"
          >
            <template #selected-item="scope">
              <q-chip
                removable
                :tabindex="scope.tabindex"
                dense
                color="secondary"
                text-color="white"
                @remove="scope.removeAtIndex(scope.index)"
              >
                Semaine {{ scope.opt.value + 1 }}
              </q-chip>
            </template>
          </q-select>

          <q-checkbox
            v-model="replaceExistingTemplate"
            label="Remplacer les assignations existantes"
            class="q-mt-sm"
          />
        </template>
      </q-form>
    </dialog-component>
  </template>

  <div
    v-else-if="!hasLoadingError"
    class="flex justify-center"
  >
    <empty-data
      class="bg-red-4"
      style="width: fit-content;border-radius: 20px;"
      text-color="white"
      icon="warning"
      :message="userStore.isAdmin
        ? 'Le semestre ne contient pas de date de début ou de fin, rendez-vous dans les paramètres pour le modifier.'
        : 'Veuillez contacter un administrateur !'
      "
    />
  </div>
  <div v-else class="flex justify-center">
    <empty-data
      class="bg-red-4"
      style="width: fit-content;border-radius: 20px;"
      text-color="white"
      icon="warning"
      message="Veuillez contacter un administrateur !"
    />
  </div>
</template>

<script setup>
  import { ref, onMounted, reactive, watch, computed } from 'vue'
  import { ApiService } from 'src/services/apiService.js'
  import { logger } from 'src/utils/logger.js'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import CourseTypesColumn from 'src/modules/resources/components/page/assignmentsGrid/CourseTypesColumn.vue'
  import AssignmentsTable from 'src/modules/resources/components/page/assignmentsGrid/AssignmentsTable.vue'
  import DialogComponent from 'src/modules/core/components/DialogComponent.vue'
  import AssignmentForm from 'src/modules/resources/components/page/assignmentsGrid/AssignmentForm.vue'
  import PedagogicalLegend from 'src/modules/resources/components/page/PeriodPedagogicalLegend.vue'
  import EmptyData from 'src/modules/core/components/EmptyData.vue'
  import { useUserStore } from 'src/utils/stores/useUserStore.js'
  import { usePeriods } from 'src/modules/resources/composables/usePeriods.js'
  import { confirmDialog } from 'src/utils/dialog.js'

  const props = defineProps({
    resource: {
      type: Object,
      required: true
    },
    subResourceId: {
      type: Number,
      required: true
    },
    readOnly: {
      type: Boolean,
      default: false
    }
  })

  const userStore = useUserStore()
  const { resetUsedPeriods } = usePeriods()

  const loading = ref(false)
  const weeks = ref([])
  const pedagogicalInterruptions = ref([])
  const courseTypes = ref([])
  const assignments = ref([])
  const hasLoadingError = ref(false)

  const assignmentLoading = ref(false)
  const showAssignmentDialog = ref(false)
  const formRef = ref(null)

  const assignmentFormData = reactive({
    id: null,
    allocated_hours: 0.5,
    annotation: '',
    id_users: null,
    id_course_type: null,
    id_sub_resources: props.subResourceId || null,
    weekIndex: -1,
    assignment_date: null
  })
  const currentWeek = ref(null)

  const copyLoading = ref(false)
  const showCopyDialog = ref(false)
  const copyFormRef = ref(null)
  const sourceWeekIndex = ref(null)
  const targetWeeksForCopy = ref([])
  const applyCopyToAll = ref(false)
  const replaceExisting = ref(true)
  const copyComments = ref(true)
  const copyTeachers = ref(true)

  const templateLoading = ref(false)
  const showTemplateDialog = ref(false)
  const templateFormRef = ref(null)
  const templateAction = ref('save')
  const templateSourceWeek = ref(null)
  const targetWeeksForTemplate = ref([])
  const applyTemplateToAll = ref(false)
  const replaceExistingTemplate = ref(true)
  const hasTemplateWeek = ref(false)
  const copyTemplateComments = ref(true)
  const copyTemplateTeachers = ref(true)
  const templateWeekData = ref(null)

  const isCrossResourcePaste = ref(false)
  const sourceResourceName = ref('')

  const emit = defineEmits([
    'edit-assignment',
    'copy-paste-week',
    'copy-week',
    'paste-week',
    'save-template-week',
    'paste-template-week',
    'sub-resource-status-updated'
  ])

  const templateWeekPreview = computed(() => {
    if (!templateWeekData.value || templateWeekData.value.length === 0) {
      return []
    }

    return templateWeekData.value.map(item => {
      const courseType = courseTypes.value.find(ct => ct.id === item.id_course_type)
      return `${courseType?.name || 'Type inconnu'}: ${item.allocated_hours}h ${item.id_users ? '(avec enseignant)' : ''}`
    })
  })

  const weekSelectOptions = computed(() => {
    return weeks.value.map((week, index) => ({
      label: `Semaine ${index + 1} (${formatDateRange(week)})`,
      value: index
    }))
  })

  const filteredTargetOptions = computed(() => {
    if (sourceWeekIndex.value === null) {
      return weekSelectOptions.value
    }
    return weekSelectOptions.value.filter(option => option.value !== sourceWeekIndex.value)
  })

  const isSubResourceCompleted = computed(() => {
    return props.subResourceId &&
      props.resource?.sub_resources?.some(sr =>
        sr.id === props.subResourceId &&
        sr.status === 'COMPLETED'
      )
  })

  const effectiveReadOnly = computed(() => {
    return props.readOnly || isSubResourceCompleted.value
  })

  watch(() => props.subResourceId, async (newId, oldId) => {
    if (newId && newId !== oldId) {
      await fetchAssignments()
    }
  }, { immediate: false })

  onMounted(async () => {
    try {
      loading.value = true
      hasLoadingError.value = false

      resetUsedPeriods()
      await fetchCourseTypes()
      generateWeeks()
      loadTemplateWeekData()
      checkExistingData()

      pedagogicalInterruptions.value = props.resource?.semester?.pedagogical_interruptions || []

      await fetchAssignments()
    } catch (error) {
      logger.error('Error initializing assignments grid:', error)
      errorNotify('Erreur lors du chargement des affectations. Veuillez réessayer plus tard.')
      hasLoadingError.value = true
    } finally {
      loading.value = false
    }
  })

  async function fetchCourseTypes() {
    try {
      courseTypes.value = await ApiService.courseTypes.fetchCourseTypes()
    } catch (error) {
      logger.error('Error fetching courses types:', error)
      errorNotify('Erreur lors du chargement des types de cours')
    }
  }

  async function fetchAssignments() {
    try {
      const rawAssignments = await ApiService.assignments.fetchAssignments({
        id_sub_resource: props.subResourceId
      }, ['user'])

      const processedAssignments = []
      const processedKeys = new Set()

      for (const assignment of rawAssignments) {
        let weekIndex = -1

        if (assignment.assignment_date) {
          const assignmentDate = new Date(assignment.assignment_date)

          for (let i = 0; i < weeks.value.length; i++) {
            const week = weeks.value[i]
            const weekStart = new Date(week.start_date)
            weekStart.setHours(0, 0, 0, 0)
            const weekEnd = new Date(week.end_date)
            weekEnd.setHours(23, 59, 59, 999)

            weekEnd.setHours(23, 59, 59, 999)

            if (assignmentDate >= weekStart && assignmentDate <= weekEnd) {
              weekIndex = i
              break
            }
          }
        }

        if (weekIndex >= 0) {
          const key = `${assignment.id_course_type}-${weekIndex}`

          if (!processedKeys.has(key)) {
            processedKeys.add(key)

            processedAssignments.push({
              ...assignment,
              week: weekIndex,
              allocated_hours: Number(assignment.allocated_hours || 0)
            })
          }
        }
      }
      assignments.value = processedAssignments
    } catch (error) {
      logger.error('Error fetching assignments:', error)
      errorNotify('Erreur lors du chargement des affectations')
      assignments.value = []
      throw error
    }
  }

  function loadTemplateWeekData() {
    try {
      const savedTemplate = localStorage.getItem(`template_week_${props.resource.id}`)
      if (savedTemplate) {
        templateWeekData.value = JSON.parse(savedTemplate)
        hasTemplateWeek.value = true
      } else {
        const globalTemplate = localStorage.getItem('global_template_week')
        if (globalTemplate) {
          templateWeekData.value = JSON.parse(globalTemplate)
          hasTemplateWeek.value = true
        }
      }
    } catch (e) {
      logger.error('Error loading template week data', e)
      templateWeekData.value = null
    }
  }

  function generateWeeks() {
    if (!props.resource?.semester?.start_date || !props.resource?.semester?.end_date) {
      weeks.value = []
      errorNotify(userStore.isAdmin
        ? 'Le semestre ne contient pas de date de début ou de fin, rendez-vous dans les paramètres pour le modifier.\n'
        : 'Veuillez contacter un administrateur !'
      )
      return
    }

    const result = []
    const startDate = new Date(props.resource.semester.start_date)
    const endDate = new Date(props.resource.semester.end_date)

    let currentWeekStart = new Date(startDate)

    const dayOfWeek = currentWeekStart.getDay()
    if (dayOfWeek !== 1) {
      const daysToSubtract = dayOfWeek === 0 ? 6 : dayOfWeek - 1
      currentWeekStart.setDate(currentWeekStart.getDate() - daysToSubtract)
    }

    let index = 0
    while (currentWeekStart <= endDate) {
      const weekEnd = new Date(currentWeekStart)
      weekEnd.setDate(weekEnd.getDate() + 6)

      result.push({
        index: index,
        start_date: new Date(currentWeekStart),
        end_date: new Date(weekEnd)
      })

      currentWeekStart.setDate(currentWeekStart.getDate() + 7)
      index++
    }

    weeks.value = result
  }

  function editAssignment(courseType, weekIndex) {
    if (effectiveReadOnly.value) {
      return
    }

    if (weekIndex < 0 || weekIndex >= weeks.value.length) {
      errorNotify('Semaine invalide sélectionnée')
      return
    }

    const existingAssignment = assignments.value.find(a =>
      a.id_course_type === courseType.id && a.week === weekIndex
    )

    currentWeek.value = weeks.value[weekIndex]

    Object.assign(assignmentFormData, {
      id: existingAssignment?.id ?? null,
      allocated_hours: existingAssignment?.allocated_hours ?? 0,
      annotation: existingAssignment?.annotation ?? '',
      id_users: existingAssignment?.id_users ?? null,
      id_course_type: courseType.id,
      id_sub_resources: props.subResourceId,
      weekIndex: weekIndex,
      assignment_date: currentWeek.value?.start_date.toISOString().split('T')[0]
    })

    showAssignmentDialog.value = true
  }

  function resetForm() {
    if (formRef.value) {
      formRef.value.reset()
    }
  }

  async function saveAssignment(formData) {
    if (effectiveReadOnly.value) {
      errorNotify('Vous n\'avez pas les droits pour modifier cette affectation ou la sous-ressource est terminée')
      return
    }

    if (!formData.id_course_type) {
      errorNotify('Veuillez sélectionner un type de cours')
      return
    }

    if (!formData.id_sub_resources) {
      errorNotify('Sous-ressource non spécifiée')
      return
    }

    if (!formData.assignment_date) {
      errorNotify('Date d\'affectation non spécifiée')
      return
    }

    if (formData.allocated_hours <= 0) {
      errorNotify('Veuillez spécifier un nombre d\'heures valide (supérieur à 0)')
      return
    }

    try {
      assignmentLoading.value = true
      const weekIndex = assignmentFormData.weekIndex

      const weekStartDate = new Date(weeks.value[weekIndex].start_date)
      weekStartDate.setHours(12, 0, 0, 0)
      const formattedDate = weekStartDate.toISOString().split('T')[0]

      const assignmentData = {
        assignment_date: formattedDate,
        id_course_type: formData.id_course_type,
        id_sub_resources: formData.id_sub_resources,
        id_users: formData.id_users || null,
        allocated_hours: formData.allocated_hours,
        annotation: formData.annotation || ''
      }

      let updateId = formData.id
      const existingIndex = assignments.value.findIndex(a =>
        a.id_course_type === formData.id_course_type &&
        a.week === weekIndex
      )

      let tempIndex = -1

      if (existingIndex !== -1) {
        updateId = assignments.value[existingIndex].id
        assignments.value[existingIndex] = {
          ...assignments.value[existingIndex],
          ...assignmentData,
          id_users: formData.id_users || null,
          allocated_hours: formData.allocated_hours
        }
      } else {
        const tempId = Date.now()
        assignments.value.push({
          ...assignmentData,
          id: tempId,
          week: weekIndex,
          tempId: true
        })
        tempIndex = assignments.value.length - 1
      }

      try {
        if (updateId && typeof updateId === 'number' && !Number.isNaN(updateId) && !assignments.value.find(a => a.id === updateId)?.tempId) {
          await ApiService.assignments.updateAssignment(updateId, assignmentData)
        } else {
          const response = await ApiService.assignments.createAssignment(assignmentData)
          if (tempIndex !== -1 && response && response.id) {
            assignments.value[tempIndex].id = response.id
            assignments.value[tempIndex].tempId = false
          }

          if (formData.id_sub_resources) {
            const wasUpdated = await ApiService.subResources.checkAndUpdateStatusForNewAssignment(formData.id_sub_resources)

            if (wasUpdated) {
              emit('sub-resource-status-updated', {
                id: formData.id_sub_resources,
                status: 'IN_PROGRESS'
              })
            }
          }
        }

        successNotify('Affectation enregistrée avec succès')
        showAssignmentDialog.value = false
      } catch (apiError) {
        if (tempIndex !== -1) {
          assignments.value.splice(tempIndex, 1)
        }

        if (updateId && typeof updateId === 'number' && !Number.isNaN(updateId)) {
          errorNotify('Erreur lors de la mise à jour: les données locales pourraient être désynchronisées')
        } else {
          errorNotify('Erreur lors de la création: les données locales pourraient être désynchronisées')
        }
        logger.error(apiError)
      }
    } catch (error) {
      errorNotify('Erreur lors de l\'enregistrement de l\'affectation')
      logger.error(error)
    } finally {
      assignmentLoading.value = false
    }

    try {
      if (formData.id_sub_resources) {
        await ApiService.subResources.checkAndUpdateStatusForNewAssignment(formData.id_sub_resources)
      }
    } catch (error) {
      logger.error('Error updating sub-resource status:', error)
    }
  }

  async function deleteAssignment() {
    if (effectiveReadOnly.value) {
      errorNotify('Vous n\'avez pas les droits pour supprimer cette affectation')
      return
    }

    if (!assignmentFormData.id) {
      return
    }

    try {
      assignmentLoading.value = true

      await ApiService.assignments.deleteAssignment(assignmentFormData.id)

      const assignmentIndex = assignments.value.findIndex(a => a.id === assignmentFormData.id)
      if (assignmentIndex !== -1) {
        assignments.value.splice(assignmentIndex, 1)
      }

      successNotify('Affectation supprimée avec succès')
      showAssignmentDialog.value = false
    } catch (error) {
      logger.error('Error deleting assignment:', error)
      errorNotify('Erreur lors de la suppression de l\'affectation')
    } finally {
      assignmentLoading.value = false
    }
  }

  async function executeCopy() {
    if (!await copyFormRef.value.validate()) {
      return
    }

    if (effectiveReadOnly.value) {
      errorNotify('Vous n\'avez pas les droits pour modifier les affectations')
      return
    }

    const allTargetWeeks = applyCopyToAll.value
      ? weeks.value.map((_, idx) => idx).filter(idx => idx !== sourceWeekIndex.value)
      : targetWeeksForCopy.value

    if (allTargetWeeks.length > 3) {
      const confirmed = await confirmDialog(
        `Vous allez appliquer cette semaine à ${allTargetWeeks.length} semaines. Voulez-vous continuer ?`
      )
      if (!confirmed) {
        return
      }
    }

    try {
      copyLoading.value = true

      let sourceAssignments

      if (isCrossResourcePaste.value) {
        const copiedData = localStorage.getItem('cross_resource_copy')
        if (!copiedData) {
          errorNotify('Données de copie inter-ressources non disponibles')
          copyLoading.value = false
          return
        }

        const parsedData = JSON.parse(copiedData)
        sourceAssignments = parsedData.assignments
      } else {
        sourceAssignments = assignments.value
          .filter(a => a.week === sourceWeekIndex.value)
          .map(a => ({
            id_course_type: a.id_course_type,
            allocated_hours: a.allocated_hours,
            annotation: copyComments.value ? (a.annotation || '') : '',
            id_users: copyTeachers.value ? a.id_users : null
          }))
      }

      if (sourceAssignments.length === 0) {
        errorNotify('La semaine source ne contient aucune affectation')
        return
      }

      let successCount = 0
      let errorCount = 0

      for (const targetWeekIndex of allTargetWeeks) {
        try {
          if (replaceExisting.value) {
            const existingAssignments = assignments.value.filter(a => a.week === targetWeekIndex)

            for (const assignment of existingAssignments) {
              if (assignment.id && !assignment.tempId) {
                try {
                  await ApiService.assignments.deleteAssignment(assignment.id)
                  const idx = assignments.value.findIndex(a => a.id === assignment.id)
                  if (idx !== -1) {
                    assignments.value.splice(idx, 1)
                  }
                } catch (error) {
                  logger.error('Error deleting assignment:', error)
                }
              }
            }
          }

          const targetWeek = weeks.value[targetWeekIndex]
          if (!targetWeek) {
            errorNotify('Semaine cible invalide')
            continue
          }

          const targetDate = new Date(targetWeek.start_date)
          targetDate.setHours(12, 0, 0, 0)
          const formattedDate = targetDate.toISOString().split('T')[0]

          let weekSuccess = 0

          for (const source of sourceAssignments) {
            const existingIdx = assignments.value.findIndex(a =>
              a.week === targetWeekIndex &&
              a.id_course_type === source.id_course_type
            )

            if (existingIdx !== -1 && !replaceExisting.value) {
              continue
            }

            const assignmentData = {
              assignment_date: formattedDate,
              id_course_type: source.id_course_type,
              id_sub_resources: props.subResourceId,
              id_users: copyTeachers.value ? source.id_users : null,
              allocated_hours: source.allocated_hours,
              annotation: copyComments.value ? (source.annotation || '') : ''
            }

            try {
              if (existingIdx !== -1) {
                const existingAssignment = assignments.value[existingIdx]
                await ApiService.assignments.updateAssignment(existingAssignment.id, assignmentData)

                assignments.value[existingIdx] = {
                  ...existingAssignment,
                  ...assignmentData
                }
              } else {
                const response = await ApiService.assignments.createAssignment(assignmentData)

                if (response && response.id) {
                  assignments.value.push({
                    ...assignmentData,
                    id: response.id,
                    week: targetWeekIndex
                  })
                }
              }

              weekSuccess++
            } catch (error) {
              errorCount++
              logger.error('Error creating/updating assignment:', error)
            }
          }

          if (weekSuccess > 0) {
            successCount++
          }
        } catch (error) {
          logger.error(`Error processing week ${targetWeekIndex}:`, error)
        }
      }

      if (successCount > 0) {
        successNotify(`Semaine copiée avec succès sur ${successCount} semaine(s)`)
        showCopyDialog.value = false

        if (errorCount > 0) {
          errorNotify(`${errorCount} assignation(s) n'ont pas pu être copiées`)
        }
      } else {
        errorNotify('Aucune assignation n\'a pu être copiée')
      }

      await fetchAssignments()
    } catch (error) {
      logger.error('Error in copy operation:', error)
      errorNotify('Erreur lors de la copie de semaine')
    } finally {
      copyLoading.value = false

      if (props.subResourceId) {
        const wasUpdated = await ApiService.subResources.checkAndUpdateStatusForNewAssignment(props.subResourceId)
        if (wasUpdated) {
          emit('sub-resource-status-updated', {
            id: props.subResourceId,
            status: 'IN_PROGRESS'
          })
        }
      }
    }
  }

  async function executeTemplate() {
    if (!await templateFormRef.value.validate()) {
      return
    }

    if (effectiveReadOnly.value) {
      errorNotify('Vous n\'avez pas les droits pour modifier les affectations')
      return
    }

    templateLoading.value = true

    try {
      if (templateAction.value === 'save') {
        const templateSource = assignments.value
          .filter(a => a.week === templateSourceWeek.value)
          .map(a => ({
            id_course_type: a.id_course_type,
            allocated_hours: a.allocated_hours,
            annotation: a.annotation || '',
            id_users: a.id_users
          }))

        if (templateSource.length === 0) {
          errorNotify('La semaine sélectionnée ne contient aucune affectation')
          return
        }

        if (typeof localStorage !== 'undefined') {
          try {
            localStorage.setItem(`template_week_${props.resource.id}`, JSON.stringify(templateSource))

            localStorage.setItem('global_template_week', JSON.stringify(templateSource))

            templateWeekData.value = templateSource
            hasTemplateWeek.value = true
            successNotify('Semaine type enregistrée avec succès')
            showTemplateDialog.value = false
          } catch (e) {
            logger.error('Error saving template week:', e)
            errorNotify('Erreur lors de l\'enregistrement de la semaine type')
          }
        }
      } else if (templateAction.value === 'apply') {
        if (typeof localStorage === 'undefined') {
          errorNotify('Stockage local non disponible')
          return
        }

        try {
          let templateData = localStorage.getItem(`template_week_${props.resource.id}`)

          if (!templateData) {
            templateData = localStorage.getItem('global_template_week')
          }

          if (!templateData) {
            errorNotify('Aucune semaine type enregistrée')
            return
          }

          const templateSource = JSON.parse(templateData)
          if (!Array.isArray(templateSource) || templateSource.length === 0) {
            errorNotify('Semaine type invalide')
            return
          }

          const allTargetWeeks = applyTemplateToAll.value
            ? weeks.value.map((_, idx) => idx)
            : targetWeeksForTemplate.value

          if (allTargetWeeks.length > 3) {
            const confirmed = await confirmDialog(
              `Vous allez appliquer la semaine type à ${allTargetWeeks.length} semaines. Voulez-vous continuer ?`
            )
            if (!confirmed) {
              return
            }
          }

          let successCount = 0
          let errorCount = 0

          for (const targetWeekIndex of allTargetWeeks) {
            try {
              if (replaceExistingTemplate.value) {
                const existingAssignments = assignments.value.filter(a => a.week === targetWeekIndex)

                for (const assignment of existingAssignments) {
                  if (assignment.id && !assignment.tempId) {
                    try {
                      await ApiService.assignments.deleteAssignment(assignment.id)
                      const idx = assignments.value.findIndex(a => a.id === assignment.id)
                      if (idx !== -1) {
                        assignments.value.splice(idx, 1)
                      }
                    } catch (error) {
                      logger.error('Error deleting assignment:', error)
                    }
                  }
                }
              }

              const targetWeek = weeks.value[targetWeekIndex]
              if (!targetWeek) {
                continue
              }

              const targetDate = new Date(targetWeek.start_date)
              targetDate.setHours(12, 0, 0, 0)
              const formattedDate = targetDate.toISOString().split('T')[0]

              let weekSuccess = 0

              for (const source of templateSource) {
                const assignmentData = {
                  assignment_date: formattedDate,
                  id_course_type: source.id_course_type,
                  id_sub_resources: props.subResourceId,
                  id_users: copyTemplateTeachers.value ? source.id_users : null,
                  allocated_hours: source.allocated_hours,
                  annotation: copyTemplateComments.value ? (source.annotation || '') : ''
                }

                try {
                  const response = await ApiService.assignments.createAssignment(assignmentData)

                  if (response && response.id) {
                    assignments.value.push({
                      ...assignmentData,
                      id: response.id,
                      week: targetWeekIndex
                    })
                  }

                  weekSuccess++
                } catch (error) {
                  errorCount++
                  logger.error('Error creating/updating assignment from template:', error)
                }
              }

              if (weekSuccess > 0) {
                successCount++
              }
            } catch (error) {
              logger.error(`Error processing week ${targetWeekIndex} with template:`, error)
            }
          }

          if (successCount > 0) {
            successNotify(`Semaine type appliquée avec succès à ${successCount} semaine(s)`)
            showTemplateDialog.value = false

            if (errorCount > 0) {
              errorNotify(`${errorCount} assignation(s) n'ont pas pu être appliquées`)
            }
          } else {
            errorNotify('Aucune assignation n\'a pu être appliquée')
          }

          await fetchAssignments()
        } catch (e) {
          logger.error('Error applying template week:', e)
          errorNotify('Erreur lors de l\'application de la semaine type')
        }
      }
    } finally {
      templateLoading.value = false
    }
  }

  function formatDateRange(week) {
    if (!week) {
      return ''
    }

    const formatDay = (date) => {
      if (!(date instanceof Date)) {
        date = new Date(date)
      }
      return date.getDate().toString().padStart(2, '0') + '/' +
        (date.getMonth() + 1).toString().padStart(2, '0')
    }

    return formatDay(week.start_date) + ' au ' + formatDay(week.end_date)
  }

  function handleCopyWeek(weekIndex) {
    sourceWeekIndex.value = weekIndex

    const weekAssignments = assignments.value
      .filter(a => a.week === weekIndex)
      .map(a => ({
        id_course_type: a.id_course_type,
        allocated_hours: a.allocated_hours,
        annotation: a.annotation || '',
        id_users: a.id_users
      }))

    if (weekAssignments.length === 0) {
      errorNotify('La semaine ne contient aucune affectation')
      return
    }

    if (typeof sessionStorage !== 'undefined') {
      try {
        sessionStorage.setItem('copied_week', JSON.stringify({
          assignments: weekAssignments,
          timestamp: new Date().getTime(),
          resourceId: props.resource.identifier,
          weekIndex: weekIndex
        }))

        localStorage.setItem('cross_resource_copy', JSON.stringify({
          assignments: weekAssignments,
          timestamp: new Date().getTime(),
          resourceId: props.resource.id,
          weekIndex: weekIndex
        }))

        successNotify('Semaine copiée avec succès')
      } catch (e) {
        logger.error('Error saving to storage:', e)
        errorNotify('Erreur lors de la copie')
      }
    }
  }

  async function handlePasteWeek(targetWeekIndex) {
    if (effectiveReadOnly.value) {
      errorNotify('Vous n\'avez pas les droits pour modifier les affectations')
      return
    }

    try {
      let copiedData = sessionStorage.getItem('copied_week')

      if (!copiedData) {
        copiedData = localStorage.getItem('cross_resource_copy')
        if (!copiedData) {
          errorNotify('Aucune semaine en mémoire')
          return
        }
      }

      const parsedData = JSON.parse(copiedData)
      const { assignments: sourceAssignments, resourceId: sourceResourceId } = parsedData

      if (!sourceAssignments || sourceAssignments.length === 0) {
        errorNotify('Aucune affectation à coller')
        return
      }

      copyComments.value = true
      copyTeachers.value = true

      isCrossResourcePaste.value = sourceResourceId !== props.resource.identifier
      sourceResourceName.value = isCrossResourcePaste.value ?
        `Ressource ${sourceResourceId}` : 'Ressource actuelle'

      targetWeeksForCopy.value = [targetWeekIndex]
      replaceExisting.value = true
      showCopyDialog.value = true
    } catch (error) {
      logger.error('Error in paste operation:', error)
      errorNotify('Erreur lors du collage')
    }
  }

  function handleSaveTemplateWeek(weekIndex) {
    templateSourceWeek.value = weekIndex
    templateAction.value = 'save'
    showTemplateDialog.value = true
  }

  function handlePasteTemplateWeek(targetWeekIndex) {
    templateAction.value = 'apply'
    targetWeeksForTemplate.value = [targetWeekIndex]
    applyTemplateToAll.value = false
    replaceExistingTemplate.value = true
    showTemplateDialog.value = true
  }

  function showCopyPasteDialog() {
    isCrossResourcePaste.value = false
    sourceWeekIndex.value = null
    showCopyDialog.value = true
  }

  function showTemplateWeekDialog({ sourceIndex }) {
    templateSourceWeek.value = sourceIndex

    templateAction.value = hasTemplateWeek.value ? 'apply' : 'save'

    showTemplateDialog.value = true
  }

  function checkExistingData() {
    try {
      const localTemplate = localStorage.getItem(`template_week_${props.resource.id}`)
      const globalTemplate = localStorage.getItem('global_template_week')

      hasTemplateWeek.value = !!(localTemplate || globalTemplate)

      if (localTemplate) {
        templateWeekData.value = JSON.parse(localTemplate)
      } else if (globalTemplate) {
        templateWeekData.value = JSON.parse(globalTemplate)
      }
    } catch (e) {
      logger.error('Error checking template data', e)
    }

    try {
      const crossResourceCopy = localStorage.getItem('cross_resource_copy')

      if (crossResourceCopy) {
        const parsedData = JSON.parse(crossResourceCopy)
        if (parsedData && parsedData.resourceId) {
          sourceResourceName.value = `Ressource ${parsedData.resourceId}`
          isCrossResourcePaste.value = parsedData.resourceId !== props.resource.identifier
        }
      }
    } catch (e) {
      logger.error('Error checking copied data', e)
    }
  }

  function refresh() {
    const currentReadOnly = effectiveReadOnly.value
    fetchAssignments()

    if (isSubResourceCompleted.value !== currentReadOnly) {
      logger.info('Sub-resource status change detected, refreshing UI')
    }
  }

  defineExpose({
    load: fetchAssignments(),
    refresh
  })
</script>
