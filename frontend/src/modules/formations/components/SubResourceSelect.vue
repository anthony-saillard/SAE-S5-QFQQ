<template>
  <q-select
    v-model="internalValue"
    :options="transformedOptions"
    :label="label"
    :hint="hint"
    :rules="rules"
    :disable="disable"
    :loading="loading"
    emit-value
    map-options
    option-value="id"
    option-label="name"
    filled
    @update:model-value="onChange"
  >
    <template v-if="$slots.prepend" #prepend>
      <slot name="prepend" />
    </template>

    <template v-if="$slots.append" #append>
      <slot name="append" />
    </template>

    <template v-if="loading" #after>
      <q-spinner color="primary" size="24px" />
    </template>
  </q-select>
</template>

<script setup>
  import { computed, ref, watch } from 'vue'

  const props = defineProps({
    modelValue: {
      type: [String, Number, Object],
      default: null
    },
    options: {
      type: Array,
      default: () => []
    },
    label: {
      type: String,
      default: 'Sous-ressource'
    },
    hint: {
      type: String,
      default: ''
    },
    rules: {
      type: Array,
      default: () => []
    },
    disable: {
      type: Boolean,
      default: false
    },
    loading: {
      type: Boolean,
      default: false
    }
  })

  const emit = defineEmits(['update:modelValue', 'change'])

  const internalValue = ref(null)

  const transformedOptions = computed(() => {
    return props.options.map(option => {
      if (typeof option === 'object' && option !== null) {
        return {
          id: option.id,
          name: option.name || `Sous-ressource ${option.id}`,
          description: option.description || '',
          ...option
        }
      }
      return {
        id: option,
        name: `Sous-ressource ${option}`
      }
    })
  })

  watch(() => props.modelValue, (newValue) => {
    if (newValue === internalValue.value) {
      return
    }

    // If modelValue is an object, extract the ID
    if (typeof newValue === 'object' && newValue !== null && 'id' in newValue) {
      internalValue.value = newValue.id
    } else {
      internalValue.value = newValue
    }
  }, { immediate: true })

  watch(() => props.options, () => {
    // If model value is not in options, select the first option
    if (internalValue.value !== null &&
      transformedOptions.value.length > 0 &&
      !transformedOptions.value.some(opt => opt.id === internalValue.value)) {
      internalValue.value = transformedOptions.value[0].id
      emit('update:modelValue', internalValue.value)
      emit('change', internalValue.value)
    }
  })

  function onChange(value) {
    emit('update:modelValue', value)
    emit('change', value)
  }
</script>
