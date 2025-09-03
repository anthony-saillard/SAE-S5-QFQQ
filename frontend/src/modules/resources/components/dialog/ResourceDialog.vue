<template>
  <dialog-component
    v-model="dialogModel"
    :title="dialogTitle"
    :highlight-title-words="['ressource']"
    size="md"
    persistent
    :tabs="dialogTabs"
    :initial-view="'general'"
    :loading="loading"
    auto-sync-tabs
    @hide="onHide"
    @save="onSave"
    @tab-registered="onTabRegistered"
  />
</template>

<script setup>
  import { computed, ref, watch } from 'vue'
  import DialogComponent from 'src/modules/core/components/DialogComponent.vue'
  import ResourcesTabGeneral from 'src/modules/resources/components/dialog/ResourcesTabGeneral.vue'
  import ResourcesTabTeachers from 'src/modules/resources/components/dialog/ResourcesTabTeachers.vue'
  import { ApiService } from 'src/services/apiService.js'
  import { logger } from 'src/utils/logger.js'
  import { errorNotify, successNotify } from 'src/utils/notify.js'

  const loading = ref(false)
  const dialogShow = ref(false)
  const isEdit = ref(false)
  const dialogTitle = ref('')

  const viewRefs = ref({})

  const props = defineProps({
    semesterId: {
      type: String,
      required: true
    }
  })

  const emit = defineEmits(['save-success'])

  const dialogModel = computed({
    get: () => dialogShow.value,
    set: (value) => {
      dialogShow.value = value
    }
  })

  const dialogData = ref({
    resource: {
      id: null,
      identifier: '',
      name: '',
      description: '',
      id_semesters: null,
      id_users: null,
      user: null,
      total_hours: 0
    },
    subResources: []
  })

  const dialogTabs = computed(() => [
    {
      name: 'general',
      label: 'Général',
      component: ResourcesTabGeneral,
      props: {
        resource: dialogData.value.resource,
        semesterId: props.semesterId
      }
    },
    {
      name: 'teachers',
      label: dialogData.value.subResources.length > 1 ? 'Sous-ressources' : 'Enseignants',
      component: ResourcesTabTeachers,
      props: {
        subResources: dialogData.value.subResources,
        resourceId: dialogData.value.resource.id
      }
    }
  ])

  watch(() => dialogShow.value,
        async (isOpen) => {
          if (isOpen && isEdit.value && dialogData.value.resource.id) {
            await loadSubResources()
          }
        }
  )

  function onTabRegistered(name, ref) {
    viewRefs.value[name] = ref
  }

  async function loadSubResources() {
    try {
      if (!dialogData.value.resource.id) {
        return
      }

      const fetchedSubResources = await ApiService.subResources.getSubResourcesByResource(
        dialogData.value.resource.id,
        ['user'],
        true
      )

      if (!fetchedSubResources || fetchedSubResources.length === 0) {
        dialogData.value.subResources = [
          {
            id: null,
            name: '',
            user: dialogData.value.resource.user,
            id_users: dialogData.value.resource.id_users,
            teachers: []
          }
        ]
        return
      }

      dialogData.value.subResources = await Promise.all(
        fetchedSubResources.map(async (subResource) => {
          let teachers = []
          try {
            teachers = await ApiService.teachers.fetchCourseTeachers({
              id_sub_resource: subResource.id
            }, ['user', 'group'])
          } catch (error) {
            logger.error(`Error loading teachers for the sub-resource ${subResource.id}:`, error)
          }

          if (fetchedSubResources.length === 1 && !subResource.user && !subResource.id_users) {
            return {
              id: subResource.id,
              name: subResource.name,
              user: dialogData.value.resource.user,
              id_users: dialogData.value.resource.user?.id,
              teachers: teachers || []
            }
          }

          return {
            id: subResource.id,
            name: subResource.name,
            user: subResource.user,
            id_users: subResource.user?.id,
            teachers: teachers || []
          }
        })
      )
    } catch (error) {
      logger.error('Error loading sub-resources:', error)
      errorNotify('Impossible de charger les sous-resources')
      dialogData.value.subResources = [
        {
          id: null,
          name: '',
          user: null,
          id_users: null,
          teachers: []
        }
      ]
    }
  }

  function onHide() {
    dialogShow.value = false
  }

  async function onSave() {
    loading.value = true

    try {
      const generalTabRef = viewRefs.value['general']

      if (!generalTabRef) {
        errorNotify('Erreur interne, veuillez recharger la page !')
        logger.error('Technical error: reference to general tab missing')
        loading.value = false
        return
      }

      let isValid = await generalTabRef.validate()

      if (!isValid) {
        errorNotify('Veuillez corriger les erreurs dans le formulaire.')
        loading.value = false
        return
      }

      const formData = generalTabRef.getData()

      if (!formData.identifier || !formData.name) {
        errorNotify('Veuillez remplir les champs obligatoires : Identifiant et Nom')
        loading.value = false
        return
      }

      const userId = formData.id_users || (formData.user ? formData.user.id : null)

      if (!userId) {
        errorNotify('Veuillez sélectionner un responsable de ressource')
        loading.value = false
        return
      }

      const resourcePayload = {
        identifier: formData.identifier,
        name: formData.name,
        description: formData.description || '',
        id_semesters: props.semesterId,
        id_users: userId,
        total_hours: formData.total_hours || 0
      }

      let teachersData = { subResources: dialogData.value.subResources }
      if (viewRefs.value['teachers'] && viewRefs.value['teachers'].getData) {
        teachersData = viewRefs.value['teachers'].getData()
        dialogData.value.subResources = teachersData.subResources
      }

      let resourceId

      if (isEdit.value && dialogData.value.resource.id) {
        await ApiService.resources.updateResource(dialogData.value.resource.id, resourcePayload)
        resourceId = dialogData.value.resource.id
        successNotify('Ressource mise à jour avec succès')
      } else {
        const response = await ApiService.resources.createResource(resourcePayload)
        resourceId = response.id
        successNotify('Ressource créée avec succès')
      }

      if (resourceId && teachersData.subResources) {
        try {
          const currentSubResources = await ApiService.subResources.getSubResourcesByResource(resourceId)
          const existingSubResourcesMap = new Map(currentSubResources.map((sr) => [sr.id, sr]))

          for (const newSubResource of teachersData.subResources) {
            let subResourceUserId = newSubResource.id_users ||
              (newSubResource.user ? newSubResource.user.id : null)

            if (teachersData.subResources.length === 1) {
              subResourceUserId = userId
            }

            const subResourcePayload = {
              name: newSubResource.name,
              id_users: subResourceUserId,
              id_resources: resourceId
            }

            let subResourceId
            if (newSubResource.id && existingSubResourcesMap.has(newSubResource.id)) {
              await ApiService.subResources.updateSubResource(newSubResource.id, subResourcePayload)
              subResourceId = newSubResource.id
              existingSubResourcesMap.delete(newSubResource.id)
            } else {
              const response = await ApiService.subResources.createSubResource(subResourcePayload)
              subResourceId = response.id
            }

            if (subResourceId && newSubResource.teachers) {
              try {
                const existingTeachers = await ApiService.teachers.fetchCourseTeachers({
                  id_sub_resource: subResourceId
                }, ['user', 'group'], true)

                if (existingTeachers.length > 0 && newSubResource.teachers.length === 0) {
                  await ApiService.teachers.deleteCourseTeachersBySubResource(subResourceId)
                } else {
                  const keepTeacherIds = []

                  for (const teacher of newSubResource.teachers) {
                    try {
                      const userId = teacher.id_user || teacher.id
                      const groupId = teacher.id_group ||
                        (teacher.group ? (typeof teacher.group === 'object' ? teacher.group.id : teacher.group) : null)

                      const existingTeacher = existingTeachers.find(et =>
                        (et.id_user === userId || et.id === userId) &&
                        (et.id_group === groupId || (et.group && et.group.id === groupId))
                      )

                      if (existingTeacher) {
                        keepTeacherIds.push(existingTeacher.id)
                      } else {
                        await ApiService.teachers.createCourseTeacher({
                          id_sub_resource: subResourceId,
                          id_user: userId,
                          id_group: groupId
                        })
                      }
                    } catch (error) {
                      logger.error(`Error adding a teacher for the sub-resource ${subResourceId}:`, error)
                    }
                  }

                  for (const teacher of existingTeachers) {
                    if (!keepTeacherIds.includes(teacher.id)) {
                      try {
                        await ApiService.teachers.deleteCourseTeacher(teacher.id)
                      } catch (error) {
                        logger.error(`Error deleting teacher ID:${teacher.id}:`, error)
                      }
                    }
                  }
                }
              } catch (error) {
                logger.error(`Error when managing teachers for the sub-resource ${subResourceId}:`, error)
              }
            }
          }

          for (const [subResourceId] of existingSubResourcesMap.entries()) {
            await ApiService.subResources.deleteSubResource(subResourceId)
          }
        } catch (error) {
          logger.error('Error when managing sub-resources:', error)
          errorNotify('Des erreurs sont survenues lors de la gestion des sous-ressources')
          loading.value = false
          return
        }
      } else if (resourceId && !isEdit.value) {
        try {
          const defaultSubResourcePayload = {
            name: formData.name || 'Défaut',
            id_resources: resourceId,
            id_users: userId
          }
          await ApiService.subResources.createSubResource(defaultSubResourcePayload)
        } catch (error) {
          logger.error('Error when creating the default sub-resource:', error)
          errorNotify('Impossible de créer la sous-ressource par défaut')
          loading.value = false
          return
        }
      }

      emit('save-success')
      dialogShow.value = false
    } catch (error) {
      logger.error('Error when registering a resource:', error)
      errorNotify('Impossible d\'enregistrer la ressource')
    } finally {
      loading.value = false
    }
  }

  /**
   * Initializes the dialog to create a new resource
   * @param {String|null} semesterId - Semester ID for the new resource  (optional if given in the component)
   */
  function createResource(semesterId = null) {
    isEdit.value = false

    dialogData.value.resource = {
      id: null,
      identifier: '',
      name: '',
      description: '',
      id_semesters: semesterId || props.semesterId,
      id_users: null,
      user: null,
      total_hours: 0
    }

    dialogData.value.subResources = [
      {
        id: null,
        name: '',
        user: null,
        id_users: null,
        teachers: []
      }
    ]

    dialogTitle.value = 'Créer une ressource'
    dialogShow.value = true
  }

  /**
   * Initializes the dialog to modify an existing resource
   * @param {String|Number} resourceId - ID of the resource to modify
   */
  async function editResource(resourceId) {
    try {
      loading.value = true
      isEdit.value = true

      const resource = await ApiService.resources.fetchResource(resourceId, true, true)

      dialogData.value.resource = {
        id: resource.id,
        identifier: resource.identifier,
        name: resource.name,
        description: resource.description,
        id_semesters: resource.id_semesters || props.semesterId,
        id_users: resource.id_users,
        user: resource.user,
        total_hours: resource.total_hours
      }

      ApiService.subResources.clearCache()
      await loadSubResources()

      dialogTitle.value = `Modifier la ressource ${resource.identifier}`
      dialogShow.value = true
    } catch (error) {
      logger.error(`Error loading resource ID: ${resourceId}`, error)
      errorNotify('Impossible de charger la ressource')
    } finally {
      loading.value = false
    }
  }

  defineExpose({
    createResource,
    editResource,
    close: onHide
  })
</script>
