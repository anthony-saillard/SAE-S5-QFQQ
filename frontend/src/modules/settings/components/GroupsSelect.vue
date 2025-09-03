<template>
  <q-select
    v-model="selectedGroup"
    v-bind="$attrs"
    :options="groups"
    :label="label"
    filled
    :loading="loading"
    :option-value="optionValue"
    :option-label="optionLabel"
    :option-disable="disableOptionCheck"
    @filter="filterGroups"
  />
</template>

<script setup>
  import { ref, computed, onMounted, watch } from 'vue'
  import { ApiService } from 'src/services/apiService.js'
  import { logger } from 'src/utils/logger.js'
  import { errorNotify } from 'src/utils/notify.js'

  const props = defineProps({
    modelValue: {
      type: [String, Number, Object],
      default: null
    },
    label: {
      type: String,
      default: 'Groupe'
    },
    disableOptions: {
      type: Function,
      default: () => false
    },
    optionValue: {
      type: String,
      default: 'id'
    },
    optionLabel: {
      type: String,
      default: 'name'
    }
  })

  const emit = defineEmits(['update:modelValue'])

  const groups = ref([])
  const filteredGroups = ref([])
  const loading = ref(false)

  const selectedGroup = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  onMounted(loadGroups)

  watch(() => props.modelValue, () => {
    if (groups.value.length === 0 && props.modelValue) {
      loadGroups()
    }
  })

  async function loadGroups() {
    loading.value = true
    try {
      const response = await ApiService.groups.fetchGroups()

      if (response && response.length && typeof response[0] === 'object') {
        groups.value = response
      } else if (response && response.length) {
        groups.value = response.map(value => ({
          id: value,
          name: value
        }))
      } else {
        groups.value = []
      }

      filteredGroups.value = [...groups.value]
    } catch (error) {
      logger.error('Error loading groups:', error)
      errorNotify('Nous n\'avons pas pu récupérer la liste des groupes.')
      groups.value = []
      filteredGroups.value = []
    } finally {
      loading.value = false
    }
  }

  function filterGroups(val, update) {
    if (val === '') {
      update(() => {
        filteredGroups.value = groups.value
      })
      return
    }

    update(() => {
      const needle = val.toLowerCase()
      filteredGroups.value = groups.value.filter(
        v => getDisplayValue(v).toLowerCase().indexOf(needle) > -1
      )
    })
  }

  function getDisplayValue(item) {
    if (!item) {
      return ''
    }

    if (typeof item === 'string') {
      return item
    }

    if (typeof item === 'object') {
      return item[props.optionLabel] || item.toString()
    }

    return item.toString()
  }

  function disableOptionCheck(option) {
    return props.disableOptions(option)
  }
</script>
