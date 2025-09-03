<template>
  <div>
    <div class="row q-mb-sm q-mx-lg items-center">
      <p class="q-pl-lg col-grow fw-500 fs-200">
        Liste des ressources
      </p>
      <q-btn
        v-if="userStore.isAdmin"
        color="positive" icon-right="add"
        label="Nouvelle ressource"
        no-caps class="q-mr-sm"
        @click="resourceDialogRef.createResource(String(semesterId))"
      />
    </div>

    <resources-table
      v-if="isActive"
      ref="resourcesTableRef"
      :semester-id="String(semesterId)"
      @edit="resourceDialogRef.editResource($event)"
    />

    <resource-dialog
      v-if="userStore.isAdmin"
      ref="resourceDialogRef"
      :semester-id="String(semesterId)"
      @save-success="handleSaveSuccess"
    />
  </div>
</template>

<script setup>
  import ResourcesTable from 'src/modules/resources/components/ResourcesTable.vue'
  import ResourceDialog from 'src/modules/resources/components/dialog/ResourceDialog.vue'
  import { ref, onMounted, watch } from 'vue'
  import { useUserStore } from 'src/utils/stores/useUserStore.js'

  const props = defineProps({
    semesterId: {
      type: [String, Number],
      required: true
    }
  })

  defineEmits(['loaded'])

  const userStore = useUserStore()
  const resourcesTableRef = ref(null)
  const resourceDialogRef = ref(null)
  const isActive = ref(false)

  const handleSaveSuccess = () => {
    if (resourcesTableRef.value) {
      resourcesTableRef.value.load()
    }
    if (resourceDialogRef.value) {
      resourceDialogRef.value.close()
    }
  }

  watch(() => props.semesterId, (newId) => {
    if (newId) {
      isActive.value = true
    }
  }, { immediate: true })

  onMounted(() => {
    isActive.value = true
  })
</script>
