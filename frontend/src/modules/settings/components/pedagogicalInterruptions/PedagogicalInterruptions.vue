<template>
  <section-component class="row q-pa-md" :class="$attrs.class">
    <div class="col-auto column justify-between">
      <q-select
        v-model="selectedFormation"
        :options="formationOptions"
        option-label="label"
        label="Groupe à afficher"
        filled
        emit-value
        map-options
      >
        <template #selected>
          {{ getSelectedFormationLabel }}
        </template>
      </q-select>

      <div>
        <q-btn
          label="Ajouter une interruption pédagogique"
          color="primary"
          icon="add"
          no-caps
          @click.stop="addInterruption"
        />
      </div>
    </div>

    <div class="col-grow q-pl-xl">
      <q-table
        :rows="filteredInterruptions"
        :columns="columns"
        :row-key="row => row.id"
        flat
        bordered
        hide-pagination
        :pagination="{ rowsPerPage: 0 }"
        class="fixed-header-table"
      >
        <template #body-cell-formations="props">
          <q-td :props="props">
            {{ getFormationLabels(props.row.formations) }}
          </q-td>
        </template>
        <template #body-cell-actions="props">
          <q-td :props="props" class="q-gutter-sm">
            <q-btn
              flat
              round
              color="primary"
              icon="edit"
              size="sm"
              @click="editInterruption(props.row)"
            />
            <q-btn
              flat
              round
              color="negative"
              icon="delete"
              size="sm"
              @click="deleteInterruption(props.row)"
            />
          </q-td>
        </template>
      </q-table>
    </div>
  </section-component>

  <dialog-component
    v-model="dialogShow"
    :title="isEditMode ? 'Modifier une interruption' : 'Ajouter une interruption'"
    :loading="loading"
    :form-ref="formRef"
    size="md"
  >
    <pedagogical-interruptions-form
      ref="formRef"
      :edit-mode="isEditMode"
      :initial-data="selectedInterruption"
      :formations="formations"
      @submit="onDialogSubmit"
    />
  </dialog-component>
</template>

<script setup>
  import { computed, onMounted, ref } from 'vue'
  import { logger } from 'src/utils/logger.js'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import { confirmDialog } from 'src/utils/dialog.js'
  import SectionComponent from 'src/modules/core/components/SectionComponent.vue'
  import DialogComponent from 'src/modules/core/components/DialogComponent.vue'
  import PedagogicalInterruptionsForm from 'src/modules/settings/components/pedagogicalInterruptions/PedagogicalInterruptionsForm.vue'
  import { ApiService } from 'src/services/apiService.js'

  const formations = ref([])
  const interruptions = ref([])
  const selectedFormation = ref(null)
  const loading = ref(false)
  const dialogShow = ref(false)
  const isEditMode = ref(false)
  const formRef = ref(null)
  const selectedInterruption = ref(null)

  const formationOptions = computed(() => {
    return [
      { label: 'Toutes les formations', value: null },
      ...formations.value.map(f => ({
        label: f.label,
        value: f.id
      }))
    ]
  })

  const getSelectedFormationLabel = computed(() => {
    const selected = formationOptions.value.find(f => f.value === selectedFormation.value)
    return selected?.label || 'Toutes les formations'
  })

  const groupedInterruptions = computed(() => {
    const groups = {}

    interruptions.value.forEach(interruption => {
      const key = `${interruption.name}_${interruption.start_date}_${interruption.end_date}`
      if (!groups[key]) {
        groups[key] = {
          ...interruption,
          formations: [interruption.formation_id],
          originalIds: [interruption.id]
        }
      } else {
        groups[key].formations.push(interruption.formation_id)
        groups[key].originalIds.push(interruption.id)
      }
    })

    return Object.values(groups)
  })

  const filteredInterruptions = computed(() => {
    if (!selectedFormation.value) {
      return groupedInterruptions.value
    }
    return groupedInterruptions.value.filter(i =>
      i.formations.includes(selectedFormation.value)
    )
  })

  const getFormationLabels = (formationIds) => {
    if (!Array.isArray(formationIds)) {
      formationIds = [formationIds]
    }

    if (formationIds.includes('all')) {
      return 'Toutes les formations'
    }

    const formationLabels = formationIds.map(id => {
      const formation = formations.value.find(f => f.id === id)
      return formation ? formation.label : ''
    })

    return formationLabels.join(', ')
  }

  const closeDialog = () => {
    dialogShow.value = false
    selectedInterruption.value = null
    isEditMode.value = false
  }

  const onDialogSubmit = async () => {
    closeDialog()
    await loadInterruptions()
  }

  const addInterruption = () => {
    isEditMode.value = false
    dialogShow.value = true
  }

  const editInterruption = (interruption) => {
    isEditMode.value = true
    selectedInterruption.value = {
      ...interruption,
      isGrouped: interruption.originalIds && interruption.originalIds.length > 1,
      originalIds: interruption.originalIds || [interruption.id]
    }
    dialogShow.value = true
  }

  const columns = [
    {
      name: 'name',
      label: 'Type',
      field: 'name',
      align: 'left',
      sortable: true
    },
    {
      name: 'start_date',
      label: 'Date de début',
      field: 'start_date',
      align: 'left',
      sortable: true,
      format: val => new Date(val).toLocaleDateString('fr-FR')
    },
    {
      name: 'end_date',
      label: 'Date de fin',
      field: 'end_date',
      align: 'left',
      sortable: true,
      format: val => new Date(val).toLocaleDateString('fr-FR')
    },
    {
      name: 'formations',
      label: 'Formations',
      field: 'formations',
      align: 'left',
      sortable: false
    },
    {
      name: 'actions',
      label: '',
      field: 'actions',
      align: 'center'
    }
  ]

  const loadFormations = async () => {
    try {
      formations.value = await ApiService.formations.fetchFormations({}, false, true)
    } catch (error) {
      errorNotify('Erreur lors du chargement des formations')
      logger.error(error)
    }
  }

  const loadInterruptions = async () => {
    try {
      interruptions.value = await ApiService.pedagogicalInterruptions.fetchPedagogicalInterruptions({}, false, true)
    } catch (error) {
      errorNotify('Erreur lors du chargement des interruptions')
      logger.error(error)
    }
  }

  const deleteInterruption = async (interruption) => {
    const isGrouped = interruption.originalIds && interruption.originalIds.length > 1
    const confirmMessage = isGrouped
      ? `Voulez-vous vraiment supprimer cette interruption pour toutes les formations (${interruption.originalIds.length} formations) ?`
      : 'Voulez-vous vraiment supprimer cette interruption ?'

    if (!await confirmDialog(confirmMessage)) {
      return
    }

    loading.value = true
    try {
      if (isGrouped) {
        const promises = interruption.originalIds.map(id =>
          ApiService.pedagogicalInterruptions.deletePedagogicalInterruption(id)
        )
        await Promise.all(promises)
        successNotify('Toutes les interruptions ont été supprimées avec succès')
      } else {
        await ApiService.pedagogicalInterruptions.deletePedagogicalInterruption(interruption.id || interruption.originalIds[0])
        successNotify('Interruption supprimée avec succès')
      }
      await loadInterruptions()
    } catch (error) {
      errorNotify('Erreur lors de la suppression')
      logger.error(error)
    } finally {
      loading.value = false
    }
  }

  onMounted(async () => {
    loading.value = true
    try {
      await loadFormations()
      await loadInterruptions()
    } finally {
      loading.value = false
    }
  })
</script>

<style lang="scss">
.fixed-header-table {
  max-height: 300px;
}
</style>
