<template>
  <q-select
    v-model="selectedSubResourceModel"
    v-bind="$attrs"
    :options="filteredSubResources"
    option-value="id"
    option-label="name"
    :label="label"
    filled
    style="min-width: 250px;"
    :loading="loading"
    @filter="filterSubResources"
  >
    <template #selected>
      <div v-if="selectedSubResourceModel">
        {{ selectedSubResourceModel?.name }}
      </div>
    </template>
  </q-select>
</template>

<script setup>
  import { computed, onMounted, ref, watch } from 'vue'
  import { ApiService } from 'src/services/apiService.js'
  import { logger } from 'src/utils/logger.js'

  const props = defineProps({
    modelValue: {
      type: [String, Number, Object],
      default: null
    },
    label: {
      type: String,
      default: 'Sous-ressources'
    },
    filters: {
      type: Object,
      default: null
    },
    enrich: {
      type: Array,
      default: null
    }
  })

  const emit = defineEmits(['update:modelValue'])

  const subResources = ref([])
  const filteredSubResources = ref([])
  const loading = ref(false)

  const isUpdatingModel = ref(false)

  const selectedSubResourceModel = computed({
    get: () => {
      if (props.modelValue && typeof props.modelValue === 'object') {
        return props.modelValue
      }

      if (props.modelValue && (typeof props.modelValue === 'string' || typeof props.modelValue === 'number')) {
        const found = subResources.value.find(sr => sr.id == props.modelValue)
        return found || props.modelValue
      }

      return props.modelValue
    },
    set: (value) => emit('update:modelValue', value)
  })

  onMounted(fetchSubResources)

  watch(() => props.modelValue, (newValue) => {
    if (isUpdatingModel.value) {
      return
    }

    if (newValue && (typeof newValue === 'string' || typeof newValue === 'number')) {
      if (subResources.value.length > 0) {
        const foundSubResource = subResources.value.find(sr => sr.id == newValue)
        if (foundSubResource) {
          isUpdatingModel.value = true
          emit('update:modelValue', foundSubResource)
          isUpdatingModel.value = false
        }
      } else {
        fetchSubResources()
      }
    }
  })

  watch(() => props.filters, () => {
    fetchSubResources()
  })

  watch(() => subResources.value, (newSubResources) => {
    if (props.modelValue && (typeof props.modelValue === 'string' || typeof props.modelValue === 'number') && newSubResources.length > 0) {
      const foundSubResource = newSubResources.find(sr => sr.id == props.modelValue)
      if (foundSubResource) {
        isUpdatingModel.value = true
        emit('update:modelValue', foundSubResource)
        isUpdatingModel.value = false
      }
    }
  })

  async function fetchSubResources() {
    loading.value = true
    try {
      subResources.value = await ApiService.subResources.fetchSubResources(props.filters, props.enrich)
      filteredSubResources.value = [...subResources.value]

      if (props.modelValue && (typeof props.modelValue === 'string' || typeof props.modelValue === 'number')) {
        const foundSubResource = subResources.value.find(sr => sr.id == props.modelValue)
        if (foundSubResource) {
          isUpdatingModel.value = true
          emit('update:modelValue', foundSubResource)
          isUpdatingModel.value = false
        }
      }
    } catch (error) {
      logger.error('Error loading sub-resources:', error)
      subResources.value = []
      filteredSubResources.value = []
    } finally {
      loading.value = false
    }
  }

  function filterSubResources(val, update) {
    if (val === '') {
      update(() => {
        filteredSubResources.value = subResources.value
      })
      return
    }

    update(() => {
      const needle = val.toLowerCase()
      filteredSubResources.value = subResources.value.filter(
        (v) => v.name.toLowerCase().indexOf(needle) > -1
      )
    })
  }
</script>
