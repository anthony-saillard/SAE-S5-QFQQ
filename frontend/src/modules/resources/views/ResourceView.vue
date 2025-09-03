<template>
  <div class="q-pa-md">
    <div v-if="loading" class="full-width flex justify-center">
      <q-spinner-dots size="xl" color="primary" />
    </div>
    <div v-else-if="resource">
      <div class="row q-mb-md items-center">
        <div class="row col-grow items-center">
          <q-chip
            dense square size="lg"
            color="primary" text-color="white"
            class="q-mr-sm q-my-sm q-pa-sm fw-500"
          >
            {{ resource.identifier }}
          </q-chip>
          <div class="fs-230 fw-500 q-ml-sm">
            {{ resource.name }}
          </div>
        </div>
        <div class="col-auto">
          <min-card
            title="Responsable de la ressource"
            :text="formatUserName(resource.user)"
          />
        </div>
      </div>

      <div v-if="showSubResourceSection" class="row q-mb-md items-center">
        <div class="row col-grow items-center">
          <sub-resource-select
            v-if="hasMultipleSubResources"
            v-model="selectedSubResource"
            :filters="{
              id_resource: resourceId
            }"
            :enrich="['user']"
            label="Choisissez la sous-ressource"
            class="q-mr-md" bg-color="white"
            style="border-radius: 15px!important;overflow: hidden;"
          />

          <min-card
            v-if="hasMultipleSubResources && selectedSubResource && selectedSubResource.user"
            title="Responsable de la sous-ressource"
            :text="formatUserName(selectedSubResource.user)"
            class="q-mr-md"
          />

          <div class="row items-center">
            <sub-resource-status-badge
              v-if="selectedSubResource"
              :status="selectedSubResource.status"
            />

            <q-btn
              v-if="canChangeStatus && selectedSubResource && selectedSubResource.status !== 'COMPLETED'"
              color="positive"
              icon="check"
              unelevated round
              class="q-ml-sm" size="xs"
              @click="confirmCompleteSubResource"
            >
              <q-tooltip>
                Marquer comme terminer
              </q-tooltip>
            </q-btn>

            <q-btn
              v-if="userStore.isAdmin && selectedSubResource && selectedSubResource.status === 'COMPLETED'"
              color="secondary"
              icon="restart_alt"
              unelevated round
              class="q-ml-sm" size="xs"
              @click="confirmReopenSubResource"
            >
              <q-tooltip>
                Marquer comme en cours
              </q-tooltip>
            </q-btn>
          </div>
        </div>
        <div class="col-auto">
          <q-btn
            v-if="canEditResource"
            icon-right="settings" label="Modifier les paramètres"
            color="primary" no-caps unelevated
            @click="openSettingsDialog"
          />
        </div>
      </div>

      <template v-if="selectedSubResource && selectedSubResource.id">
        <resource-assignments-grid
          v-if="!assignmentsLoading"
          ref="assignmentsGridRef"
          :resource="resourceData"
          :sub-resource-id="selectedSubResource.id"
          :read-only="!canEditSubResource || (selectedSubResource && selectedSubResource.status === 'completed')"
          @sub-resource-status-updated="handleSubResourceStatusUpdate"
        />
        <div v-else class="full-width flex justify-center q-py-lg">
          <q-spinner-dots size="lg" color="primary" />
        </div>
      </template>
      <empty-data v-else-if="empty(resource?.sub_resources)" message="Aucune sous-ressource existante" />
      <empty-data v-else message="Aucune sous-ressource sélectionnée" />

      <resource-annotations-section
        :resource="resource"
        :can-edit-resource="canEditResource"
      />
    </div>
    <div v-else class="text-center q-pa-xl fs-130 fw-500 text-grey-7">
      Ressource non trouvée
    </div>
  </div>

  <resource-dialog
    ref="resourceDialogRef"
    :semester-id="resource?.semester?.id ? String(resource.semester.id) : ''"
    @save-success="fetchResource"
  />
</template>

<script setup>
  import { onMounted, ref, computed, watch, nextTick } from 'vue'
  import { useRoute } from 'vue-router'
  import ResourceAssignmentsGrid from 'src/modules/resources/components/page/assignmentsGrid/AssignmentsGrid.vue'
  import { ApiService } from 'src/services/apiService.js'
  import { logger } from 'src/utils/logger.js'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import { useRedirect } from 'src/router/useRedirect.js'
  import SubResourceSelect from 'src/modules/resources/components/SubResourceSelect.vue'
  import { empty, formatUserName } from 'src/utils/utils.js'
  import MinCard from 'src/modules/core/components/MinCard.vue'
  import { useUserStore } from 'src/utils/stores/useUserStore.js'
  import ResourceDialog from 'src/modules/resources/components/dialog/ResourceDialog.vue'
  import ResourceAnnotationsSection from 'src/modules/resources/components/page/ResourceAnnotationsSection.vue'
  import EmptyData from 'src/modules/core/components/EmptyData.vue'
  import { useSchoolYearStore } from 'src/utils/stores/useSchoolYearStore.js'
  import SubResourceStatusBadge from 'src/modules/resources/components/page/SubResourceStatusBadge.vue'
  import { confirmDialog } from 'src/utils/dialog.js'

  const route = useRoute()
  const { redirect } = useRedirect()
  const userStore = useUserStore()
  const schoolYearStore = useSchoolYearStore()

  const resourceDialogRef = ref(null)

  const loading = ref(false)
  const assignmentsLoading = ref(false)
  const assignmentsGridRef = ref(null)

  const resource = ref({})
  const resourceData = ref({})
  const selectedSubResource = ref(null)

  const resourceId = computed(() => route.params?.id)

  const canEditResource = computed(() => {
    return userStore.isAdmin
      || (
        userStore.user
        && resource.value?.user
        && userStore.user.id === resource.value.user.id
      )
  })

  const canEditSubResource = computed(() => {
    if (canEditResource.value) {
      return true
    }

    if (
      !selectedSubResource.value
      || !userStore.user
      || !resource.value?.sub_resources
    ) {
      return false
    }

    const subResourceId = selectedSubResource.value?.id || selectedSubResource.value

    const subRes = resource.value.sub_resources.find(
      sr => sr.id === subResourceId
    )

    return subRes && subRes.user && subRes.user.id === userStore.user.id
  })

  const canChangeStatus = computed(() => {
    return canEditSubResource.value || userStore.isAdmin
  })

  const hasMultipleSubResources = computed(() => {
    return resource.value?.sub_resources && resource.value.sub_resources.length > 1
  })

  const showSubResourceSection = computed(() => {
    return resource.value?.sub_resources && resource.value.sub_resources.length > 0
  })

  watch(resourceId, async (newId, oldId) => {
    if (newId && newId !== oldId) {
      await fetchResource()
    }
  })

  watch(() => schoolYearStore.lastUpdate, async () => {
    await redirect('home')
  })

  watch(() => selectedSubResource.value, async (newVal, oldVal) => {
    if (newVal && newVal.id && (!oldVal || newVal.id !== oldVal.id)) {
      await loadAssignmentsData()
    }
  })

  onMounted(async () => {
    await fetchResource()
  })

  async function fetchResource() {
    if (!resourceId.value) {
      await redirect('error', { errorType: 404 })
      return
    }

    loading.value = true
    try {
      resource.value = await ApiService.resources.fetchResource(
        resourceId.value,
        ['user', 'semester', 'groups', 'pedagogical_interruptions', 'sub_resources_user'],
        true
      )

      resourceData.value = JSON.parse(JSON.stringify(resource.value))

      if (resource.value?.sub_resources?.length > 0) {
        selectedSubResource.value = resource.value.sub_resources[0]
        await loadAssignmentsData()
      }
    } catch (error) {
      logger.error('Error fetching resource:', error)
      errorNotify('Erreur lors du chargement de la ressource')
      await redirect('error', { errorType: 404 })
    } finally {
      loading.value = false
    }
  }

  async function loadAssignmentsData() {
    if (!selectedSubResource.value || !selectedSubResource.value.id) {
      return
    }

    assignmentsLoading.value = true
    try {
      resourceData.value = JSON.parse(JSON.stringify(resource.value))

      const subResourceWithAssignments = await ApiService.subResources.fetchSubResource(
        selectedSubResource.value.id,
        ['assignments'],
        true
      )

      if (typeof selectedSubResource.value === 'object') {
        selectedSubResource.value = {
          ...selectedSubResource.value,
          assignments: subResourceWithAssignments.assignments || []
        }
      }
    } catch (error) {
      logger.error('Error loading assignments data:', error)
      errorNotify('Erreur lors du chargement des affectations')
    } finally {
      assignmentsLoading.value = false
    }
  }

  function openSettingsDialog() {
    resourceDialogRef.value?.editResource(resource.value.id)
  }

  async function confirmCompleteSubResource() {
    if (!selectedSubResource.value || !selectedSubResource.value.id) {
      return
    }

    const confirmed = await confirmDialog(
      'Êtes-vous sûr de vouloir marquer cette sous-ressource comme terminée ? ' +
        'Cela empêchera toute modification ultérieure des affectations.'
    )

    if (confirmed) {
      try {
        await ApiService.subResources.updateSubResourceStatus(
          selectedSubResource.value.id,
          'COMPLETED'
        )

        if (typeof selectedSubResource.value === 'object') {
          selectedSubResource.value = {
            ...selectedSubResource.value,
            status: 'COMPLETED'
          }

          if (resource.value && resource.value.sub_resources) {
            const subResourceIndex = resource.value.sub_resources.findIndex(
              sr => sr.id === selectedSubResource.value.id
            )

            if (subResourceIndex !== -1) {
              const updatedSubResources = [...resource.value.sub_resources]
              updatedSubResources[subResourceIndex] = {
                ...updatedSubResources[subResourceIndex],
                status: 'COMPLETED'
              }

              resource.value = {
                ...resource.value,
                sub_resources: updatedSubResources
              }

              resourceData.value = JSON.parse(JSON.stringify(resource.value))

              await nextTick(() => {
                if (assignmentsGridRef.value && typeof assignmentsGridRef.value.refresh === 'function') {
                  assignmentsGridRef.value.refresh()
                }
              })
            }
          }
        } else {
          await fetchResource()
        }

        successNotify('Sous-ressource marquée comme terminée')
      } catch (error) {
        logger.error('Error completing sub-resource:', error)
        errorNotify('Erreur lors de la mise à jour du statut')
      }
    }
  }

  async function confirmReopenSubResource() {
    if (!userStore.isAdmin || !selectedSubResource.value || !selectedSubResource.value.id) {
      return
    }

    const confirmed = await confirmDialog(
      'Êtes-vous sûr de vouloir rouvrir cette sous-ressource? ' +
        'Cela permettra de modifier à nouveau les affectations.'
    )

    if (confirmed) {
      try {
        await ApiService.subResources.updateSubResourceStatus(
          selectedSubResource.value.id,
          'IN_PROGRESS'
        )

        if (typeof selectedSubResource.value === 'object') {
          selectedSubResource.value = {
            ...selectedSubResource.value,
            status: 'IN_PROGRESS'
          }

          if (resource.value && resource.value.sub_resources) {
            const subResourceIndex = resource.value.sub_resources.findIndex(
              sr => sr.id === selectedSubResource.value.id
            )

            if (subResourceIndex !== -1) {
              const updatedSubResources = [...resource.value.sub_resources]
              updatedSubResources[subResourceIndex] = {
                ...updatedSubResources[subResourceIndex],
                status: 'IN_PROGRESS'
              }

              resource.value = {
                ...resource.value,
                sub_resources: updatedSubResources
              }

              resourceData.value = JSON.parse(JSON.stringify(resource.value))

              await nextTick(() => {
                if (assignmentsGridRef.value && typeof assignmentsGridRef.value.refresh === 'function') {
                  assignmentsGridRef.value.refresh()
                }
              })
            }
          }
        } else {
          await fetchResource()
        }

        successNotify('Sous-ressource rouverte avec succès')
      } catch (error) {
        logger.error('Error reopening sub-resource:', error)
        errorNotify('Erreur lors de la mise à jour du statut')
      }
    }
  }

  function handleSubResourceStatusUpdate(data) {
    if (!data || !data.id || !data.status) {
      return
    }

    if (selectedSubResource.value && selectedSubResource.value.id === data.id) {
      selectedSubResource.value = {
        ...selectedSubResource.value,
        status: data.status
      }
    }

    if (resource.value && resource.value.sub_resources) {
      const subResourceIndex = resource.value.sub_resources.findIndex(sr => sr.id === data.id)
      if (subResourceIndex !== -1) {
        const updatedSubResources = [...resource.value.sub_resources]
        updatedSubResources[subResourceIndex] = {
          ...updatedSubResources[subResourceIndex],
          status: data.status
        }

        resource.value = {
          ...resource.value,
          sub_resources: updatedSubResources
        }

        resourceData.value = JSON.parse(JSON.stringify(resource.value))
      }
    }
  }
</script>
