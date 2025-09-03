<template>
  <div class="resource-annotations-section q-mt-lg">
    <q-card class="annotations-card" flat bordered>
      <q-card-section>
        <div class="row items-center justify-between">
          <div class="fs-110 fw-500">
            Annotations
          </div>
          <q-btn
            v-if="canAddAnnotation"
            label="Ajouter une annotation"
            color="positive"
            no-caps unelevated dense
            icon="add" class="q-px-md"
            @click="showDialog = true"
          />
        </div>

        <q-separator class="q-my-sm" />

        <div v-if="loading" class="flex justify-center q-pa-md">
          <q-spinner-dots size="lg" color="primary" />
        </div>

        <empty-data v-else-if="annotations.length === 0" />

        <div v-else class="annotations-list" style="max-height: 300px; overflow-y: auto;">
          <div
            v-for="annotation in annotations"
            :key="annotation.id"
            class="annotation-item q-py-sm"
          >
            <div class="annotation-header row items-center no-wrap">
              <span class="text-primary text-weight-bold">{{ formatUserName(annotation?.user) }}</span>
              <span class="q-mx-xs text-grey-6">-</span>
              <span class="text-grey-8">{{ formatDateShort(annotation?.created_at) }}</span>
              <q-space />
              <q-btn
                v-if="canDelete(annotation)"
                flat dense round
                size="sm"
                color="negative"
                icon="delete"
                @click="confirmDelete(annotation)"
              >
                <q-tooltip>Supprimer</q-tooltip>
              </q-btn>
            </div>
            <div class="annotation-content q-mt-xs">
              {{ annotation.description }}
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <dialog-component
      v-if="canAddAnnotation"
      v-model="showDialog"
      title="Ajouter une annotation"
      :highlight-title-words="['annotation']"
      :form-ref="formRef"
      :loading="submitting"
      size="sm"
      @hide="reset"
    >
      <annotation-form
        ref="formRef"
        :resource-id="resource?.id"
        @submit="handleAddAnnotation"
      />
    </dialog-component>
  </div>
</template>

<script setup>
  import { ref, onMounted, computed, watch } from 'vue'
  import { ApiService } from 'src/services/apiService.js'
  import { logger } from 'src/utils/logger.js'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import { formatUserName } from 'src/utils/utils.js'
  import { useUserStore } from 'src/utils/stores/useUserStore.js'
  import { confirmDialog } from 'src/utils/dialog.js'
  import DialogComponent from 'src/modules/core/components/DialogComponent.vue'
  import AnnotationForm from './AnnotationForm.vue'
  import EmptyData from 'src/modules/core/components/EmptyData.vue'

  const props = defineProps({
    resource: {
      type: Object,
      required: true
    },
    canEditResource: {
      type: Boolean,
      default: false
    }
  })

  const userStore = useUserStore()

  const loading = ref(false)
  const submitting = ref(false)
  const annotations = ref([])
  const showDialog = ref(false)
  const formRef = ref(null)

  const canAddAnnotation = computed(() => {
    const isSubResourceResponsible = props.resource?.sub_resources?.some(
      subRes => subRes.user && subRes.user.id === userStore.user?.id
    )

    return props.canEditResource || userStore.isAdmin || isSubResourceResponsible
  })

  watch(() => props.resource?.id, (newId, oldId) => {
    if (newId && newId !== oldId) {
      fetchAnnotations()
    }
  })

  onMounted(() => {
    fetchAnnotations()
  })

  async function fetchAnnotations() {
    if (!props.resource?.id) {
      return
    }

    loading.value = true
    try {
      annotations.value = await ApiService.annotations.fetchAnnotations({
        id_resources: props.resource?.id
      }, ['user'])
    } catch (error) {
      logger.error('Error loading annotations:', error)
      errorNotify('Impossible de charger les annotations')
      annotations.value = []
    } finally {
      loading.value = false
    }
  }

  function formatDateShort(dateString) {
    if (!dateString) {
      return ''
    }

    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  }

  function canDelete(annotation) {
    return userStore.isAdmin
      || props.canEditResource
      || (
        userStore.user
        && annotation.id_user === userStore.user.id
      )
  }

  function reset() {
    if (formRef.value) {
      formRef.value.reset()
    }
  }

  async function handleAddAnnotation(annotationData) {
    submitting.value = true
    try {
      const result = await ApiService.annotations.createAnnotation(annotationData)

      annotations.value.unshift({
        ...result,
        user: userStore.user
      })

      successNotify('Annotation ajoutée avec succès')
      showDialog.value = false
    } catch (error) {
      logger.error('Error adding annotation:', error)
      errorNotify('Impossible d\'ajouter l\'annotation')
    } finally {
      submitting.value = false
    }
  }

  async function confirmDelete(annotation) {
    if (!await confirmDialog('Êtes-vous sûr de vouloir supprimer cette annotation ?')) {
      return
    }

    try {
      await ApiService.annotations.deleteAnnotation(annotation.id)
      annotations.value = annotations.value.filter(a => a.id !== annotation.id)
      successNotify('Annotation supprimée avec succès')
    } catch (error) {
      logger.error('Error deleting annotation:', error)
      errorNotify('Impossible de supprimer l\'annotation')
    }
  }
</script>

<style scoped lang="scss">
  .annotations-card {
    border-radius: 15px;
  }

  .annotation-item {
    padding: 8px 12px;
    border-bottom: 1px solid $grey-3;
    position: relative;

    &:last-child {
      border-bottom: none;
    }

    &:hover {
      background-color: rgba(0, 0, 0, 0.02);
    }
  }

  .annotation-header {
    font-size: 14px;
  }

  .annotation-content {
    font-size: 14px;
    color: $grey-9;
    white-space: pre-line;
  }
</style>
