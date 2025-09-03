<template>
  <div class="row items-center justify-between q-mb-md">
    <div class="text-h5">
      Groupes de classe
    </div>
    <q-btn
      color="primary" icon-right="add" no-caps
      label="Ajouter un groupe"
      @click="openEditDialog()"
    />
  </div>

  <q-tree
    v-if="treeNodes.length > 0"
    :nodes="treeNodes"
    node-key="id"
    default-expand-all
  >
    <template #default-header="prop">
      <div class="row items-center">
        <div class="text-weight-medium">
          {{ prop.node.label }}
          <q-badge color="primary" class="q-ml-sm">
            {{ getGroupTypeName(prop.node.type) }}
          </q-badge>
        </div>

        <div class="row items-center q-gutter-x-xs q-ml-xs">
          <q-btn
            flat round dense
            color="positive" icon="add_circle"
            :title="`Ajouter un sous-groupe à ${prop.node.label}`"
            @click.stop="openAddSubgroupDialog(prop.node)"
          />

          <q-btn
            v-if="!isFirstInLevel(prop.node)"
            flat round dense
            color="grey" icon="arrow_upward"
            @click.stop="moveNode(prop.node, -1)"
          />

          <q-btn
            v-if="!isLastInLevel(prop.node)"
            flat round dense
            color="grey" icon="arrow_downward"
            @click.stop="moveNode(prop.node, 1)"
          />

          <q-btn
            flat round dense
            color="primary" icon="edit"
            @click.stop="openEditDialog(prop.node)"
          />

          <q-btn
            flat round dense
            color="negative" icon="delete"
            @click.stop="confirmDelete(prop.node)"
          />
        </div>
      </div>
    </template>
  </q-tree>

  <dialog-component
    v-model="editDialog"
    :title="isNewGroup ? 'Ajouter un groupe' : 'Modifier le groupe'"
    size="md"
    :form-ref="formRef"
  >
    <q-form ref="formRef" class="q-gutter-md" @submit="saveGroup">
      <q-input
        v-model="editedGroup.name"
        label="Nom du groupe"
        filled
        :rules="[val => !!val || 'Le nom est requis']"
      />

      <q-select
        v-model="editedGroup.id_course_types"
        :options="courseTypesOptions"
        label="Type de groupe"
        filled
        :rules="[val => !!val || 'Le type est requis']"
        emit-value map-options
      />

      <q-select
        v-if="!isRootGroup"
        v-model="editedGroup.id_parent_group"
        :options="parentOptions"
        label="Groupe parent"
        filled
        :rules="[val => !isRootGroup ? (!!val || 'Le parent est requis') : true]"
        emit-value map-options
        option-label="label" option-value="value"
        use-input input-debounce="300"
        @filter="filterParents"
      >
        <template #selected>
          {{ getParentLabel(editedGroup.id_parent_group) }}
        </template>
      </q-select>

      <q-input
        v-model="editedGroup.description"
        label="Description"
        filled
        type="textarea"
      />
    </q-form>
  </dialog-component>
</template>

<script setup>
  import { computed, onMounted, ref, watch } from 'vue'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import DialogComponent from 'src/modules/core/components/DialogComponent.vue'
  import { confirmDialog } from 'src/utils/dialog.js'
  import { logger } from 'src/utils/logger.js'
  import { ApiService } from 'src/services/apiService.js'

  const props = defineProps({
    formationId: {
      type: Number,
      default: null
    }
  })

  const groups = ref([])
  const courseTypes = ref([])
  const formations = ref([])
  const editDialog = ref(false)
  const editedGroup = ref({})
  const isNewGroup = ref(true)
  const isAddingSubgroup = ref(false)
  const loading = ref(false)

  const formRef = ref(null)

  const isRootGroup = computed(() => {
    return !editedGroup.value.id_parent_group
  })

  const treeNodes = computed(() => {
    const buildNode = (group) => {
      const children = groups.value
        .filter(g => g.id_parent_group === group.id)
        .sort((a, b) => a.order_number - b.order_number)
        .map(buildNode)

      return {
        id: group.id,
        label: group.name,
        type: group.id_course_types,
        parent: group.id_parent_group,
        order: group.order_number,
        description: group.description,
        id_formation: group.id_formation,
        children: children.length ? children : undefined
      }
    }

    return groups.value
      .filter(g => !g.id_parent_group)
      .sort((a, b) => a.order_number - b.order_number)
      .map(buildNode)
  })

  const courseTypesOptions = computed(() => {
    return courseTypes.value.map(type => ({
      label: type.name,
      value: type.id
    }))
  })

  const parentOptions = ref([])

  const getParentLabel = (parentId) => {
    if (!parentId) {
      return ''
    }
    const parent = groups.value.find(g => g.id === parentId)
    return parent ? parent.name : ''
  }

  const fetchData = async () => {
    loading.value = true
    try {
      const filters = {}
      if (props.formationId) {
        filters.id_formation = props.formationId
      }

      const [groupsResponse, typesResponse, formationsResponse] = await Promise.all([
        ApiService.groups.fetchGroups(filters),
        ApiService.courseTypes.fetchCourseTypes(false, true),
        ApiService.formations.fetchFormations({}, false, true)
      ])

      groups.value = groupsResponse
      courseTypes.value = typesResponse
      formations.value = formationsResponse
    } catch (error) {
      logger.error('Error loading data:', error)
      errorNotify('Erreur lors du chargement des données')
    } finally {
      loading.value = false
    }
  }

  const getGroupTypeName = (typeId) => {
    const type = courseTypes.value.find(t => t.id === typeId)
    return type ? type.name : 'Non défini'
  }

  const getNextOrder = (parentId = null) => {
    const siblings = groups.value.filter(g => g.id_parent_group === parentId)
    return siblings.length ? Math.max(...siblings.map(g => g.order_number || 0)) + 1 : 1
  }

  const isFirstInLevel = (node) => {
    const siblings = groups.value.filter(g => g.id_parent_group === node.parent)
    return siblings.sort((a, b) => a.order_number - b.order_number)[0]?.id === node.id
  }

  const isLastInLevel = (node) => {
    const siblings = groups.value.filter(g => g.id_parent_group === node.parent)
    return siblings.sort((a, b) => a.order_number - b.order_number).pop()?.id === node.id
  }

  const openAddSubgroupDialog = async (parentNode) => {
    try {
      if (courseTypes.value.length === 0) {
        const typesResponse = await ApiService.courseTypes.fetchCourseTypes(false, true)
        courseTypes.value = typesResponse.data
      }
    } catch (error) {
      logger.error('Error loading course types:', error)
      errorNotify('Erreur lors du chargement des types de cours')
      return
    }

    isNewGroup.value = true
    isAddingSubgroup.value = true

    editedGroup.value = {
      name: '',
      description: '',
      order_number: getNextOrder(parentNode.id),
      id_parent_group: parentNode.id,
      id_course_types: null,
      id_formation: parentNode.id_formation
    }

    updateAvailableParents()

    editDialog.value = true
  }

  const moveNode = async (node, direction) => {
    const siblings = groups.value.filter(g => g.id_parent_group === node.parent)
    siblings.sort((a, b) => a.order_number - b.order_number)

    const currentIndex = siblings.findIndex(g => g.id === node.id)
    const targetIndex = currentIndex + direction

    if (targetIndex >= 0 && targetIndex < siblings.length) {
      const currentGroup = { ...siblings[currentIndex] }
      const targetGroup = { ...siblings[targetIndex] }

      const tempOrder = currentGroup.order_number
      currentGroup.order_number = targetGroup.order_number
      targetGroup.order_number = tempOrder

      try {
        await ApiService.groups.updateGroup(currentGroup.id, {
          order_number: currentGroup.order_number
        })
        await ApiService.groups.updateGroup(targetGroup.id, {
          order_number: targetGroup.order_number
        })

        const currentGroupIndex = groups.value.findIndex(g => g.id === currentGroup.id)
        const targetGroupIndex = groups.value.findIndex(g => g.id === targetGroup.id)

        if (currentGroupIndex !== -1) {
          groups.value[currentGroupIndex].order_number = currentGroup.order_number
        }

        if (targetGroupIndex !== -1) {
          groups.value[targetGroupIndex].order_number = targetGroup.order_number
        }

        successNotify('Ordre mis à jour avec succès')
      } catch (error) {
        logger.error('Error when moving:', error)
        errorNotify('Erreur lors du déplacement')
      }
    }
  }

  const openEditDialog = (node = null) => {
    isNewGroup.value = !node
    isAddingSubgroup.value = false

    if (node) {
      editedGroup.value = {
        id: node.id,
        name: node.label,
        description: node.description,
        order_number: node.order,
        id_parent_group: node.parent,
        id_course_types: node.type,
        id_formation: node.id_formation
      }
    } else {
      editedGroup.value = {
        name: '',
        description: '',
        order_number: getNextOrder(),
        id_parent_group: null,
        id_course_types: null,
        id_formation: props.formationId || null
      }
    }

    updateAvailableParents()

    editDialog.value = true
  }

  const updateAvailableParents = () => {
    const possibleParents = groups.value.filter(g => {
      return !(editedGroup.value.id && (g.id === editedGroup.value.id || isDescendant(g.id, editedGroup.value.id)))
    })

    parentOptions.value = possibleParents.map(g => ({
      label: g.name,
      value: g.id
    }))
  }

  const isDescendant = (groupId, potentialAncestorId) => {
    const group = groups.value.find(g => g.id === groupId)
    if (!group) {
      return false
    }
    if (group.id_parent_group === potentialAncestorId) {
      return true
    }
    if (group.id_parent_group) {
      return isDescendant(group.id_parent_group, potentialAncestorId)
    }
    return false
  }

  const saveGroup = async () => {
    try {
      let savedGroup

      if (isNewGroup.value) {
        savedGroup = await ApiService.groups.createGroup(editedGroup.value)
        groups.value.push(savedGroup)
        successNotify('Groupe créé avec succès')
      } else {
        await ApiService.groups.updateGroup(editedGroup.value.id, editedGroup.value)
        await fetchData()

        successNotify('Groupe modifié avec succès')
      }

      editDialog.value = false
      isAddingSubgroup.value = false
    } catch (error) {
      logger.error('Backup error:', error)
      errorNotify('Erreur lors de la sauvegarde')
    }
  }

  const deleteRecursive = async (groupId) => {
    try {
      const allGroups = [...groups.value]

      const idsToDelete = getGroupAndDescendantIds(groupId, allGroups)

      for (const id of idsToDelete) {
        try {
          await ApiService.groups.deleteGroup(id)

          const index = groups.value.findIndex(g => g.id === id)
          if (index !== -1) {
            groups.value.splice(index, 1)
          }
        } catch (error) {
          logger.error(`Error deleting group ${id}:`, error)
        }
      }

      successNotify('Groupe supprimé avec succès')
    } catch (error) {
      errorNotify('Erreur lors de la suppression')
      logger.error('Deletion error', error)
    }
  }

  const getGroupAndDescendantIds = (groupId, allGroups) => {
    const result = []

    const collectDescendants = (id) => {
      const children = allGroups.filter(g => g.id_parent_group === id)

      for (const child of children) {
        collectDescendants(child.id)
      }

      result.push(id)
    }

    collectDescendants(groupId)

    return result.reverse()
  }

  const confirmDelete = async (node) => {
    try {
      const message = `Voulez-vous supprimer le groupe "${node.label}" et tous ses sous-groupes ?`
      const options = {
        title: 'Confirmation de suppression',
        okLabel: 'Supprimer',
        cancelLabel: 'Annuler',
        cancelColor: 'grey',
        persistent: true
      }

      const confirmed = await confirmDialog(message, options)

      if (confirmed) {
        await deleteRecursive(node.id)
      } else {
        logger.log(`Cancelled for: ${node.label}`)
      }
    } catch (error) {
      logger.error('Error when requesting confirmation', error)
    }
  }

  const filterParents = (val, update) => {
    if (val === '') {
      updateAvailableParents()
      update()
      return
    }

    update(() => {
      const needle = val.toLowerCase()
      parentOptions.value = parentOptions.value.filter(option =>
        option.label.toLowerCase().includes(needle)
      )
    })
  }

  watch(() => props.formationId, (newVal, oldVal) => {
    if (newVal !== oldVal) {
      fetchData()
    }
  }, { immediate: false })

  onMounted(fetchData)
</script>
