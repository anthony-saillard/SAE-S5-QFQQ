<template>
  <q-dialog
    v-model="dialogModel"
    v-bind="$attrs"
    :maximized="size === 'xl' && $q.screen.lt.md"
    :full-width="size === 'xl' && $q.screen.lt.md"
    :position="position"
  >
    <q-card :class="cardClass" style="border-radius: 13px; max-height: 90vh; display: flex; flex-direction: column;">
      <!-- Header -->
      <q-card-section class="flex justify-between items-center text-white bg-secondary q-pa-md">
        <slot name="header">
          <p class="fs-140 fw-700 q-ma-none">
            <template v-for="(part, index) in titleParts" :key="index">
              <span :class="{ 'text-primary': part.highlight }">{{ part.text }}</span>
            </template>
          </p>
        </slot>
        <q-btn
          icon="close"
          size="sm"
          flat round dense
          color="blue-grey-3"
          class="q-ml-md"
          @click="handleClose"
        />
      </q-card-section>

      <div class="content-container overflow-auto">
        <!-- Content -->
        <template v-if="hasSteps">
          <!-- Steps Mode -->
          <q-stepper
            ref="stepperRef"
            v-model="currentStep"
            alternative-labels
            :contracted="$q.screen.lt.md"
            class="bg-secondary"
          >
            <q-step
              v-for="(step, index) in steps"
              :key="index"
              :name="index"
              :icon="step.icon || 'check_circle'"
              :title="step.label"
              :done="currentStep > index"
            >
              <component
                :is="step.component"
                v-bind="step.props || {}"
                :ref="(el) => registerViewRef(el, `step-${index}`)"
                @cancel="handlePrevious"
              />
            </q-step>
          </q-stepper>
        </template>

        <template v-else>
          <q-card-section>
            <!-- Tabs Mode -->
            <div v-if="hasTabs" class="bg-white rounded-top-md">
              <div class="flex justify-center q-py-sm row">
                <q-btn-group style="border-radius: 15px" class="col-8">
                  <q-btn
                    v-for="view in tabs"
                    :key="view.name"
                    :label="view.label"
                    no-caps
                    class="col-grow"
                    :class="[
                      currentTab === view.name
                        ? 'bg-secondary text-white'
                        : 'bg-op-secondary text-secondary',
                    ]"
                    @click="handleViewChange(view.name)"
                  />
                </q-btn-group>
              </div>
              <div v-for="tab in tabs" :key="tab.name" :style="{ display: currentTab === tab.name ? 'block' : 'none' }">
                <component
                  :is="tab.component"
                  v-bind="tab.props || {}"
                  ref="componentRefs"
                  @cancel="handlePrevious"
                />
              </div>
            </div>

            <!-- Default slot -->
            <slot v-else />
          </q-card-section>
        </template>
      </div>

      <!-- Actions -->
      <q-card-actions class="text-white bg-secondary q-px-md q-py-sm flex justify-between">
        <div class="q-gutter-sm">
          <slot name="secondActions" />
        </div>
        <div class="q-gutter-sm">
          <slot name="actions">
            <template v-if="hasSteps">
              <q-btn
                v-if="currentStep > 0"
                label="Précédent"
                no-caps
                dense
                class="bg-op-white q-mr-sm"
                @click="handlePrevious"
              />
              <q-btn
                v-else label="Fermer" no-caps dense
                class="bg-op-white" @click="handleClose"
              />
              <q-btn
                v-if="!isLastStep"
                label="Suivant"
                no-caps
                dense
                :loading="loading"
                class="bg-primary"
                @click="handleNext"
              />
              <q-btn
                v-else
                label="Terminer"
                no-caps
                dense
                :loading="loading"
                class="bg-positive"
                @click="handleFinish"
              />
            </template>
            <template v-else>
              <q-btn
                label="Fermer"
                no-caps dense
                class="bg-op-white"
                @click="handleClose"
              />
              <q-btn
                v-if="hasForm"
                label="Enregistrer"
                no-caps
                dense
                :loading="loading"
                class="bg-positive"
                @click="handleSave"
              />
            </template>
          </slot>
        </div>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
  import { computed, onMounted, ref, watch } from 'vue'
  import { useQuasar } from 'quasar'
  import { logger } from 'src/utils/logger.js'
  import {errorNotify} from 'src/utils/notify.js'

  const $q = useQuasar()

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true
    },
    title: String,
    highlightTitleWords: Array,
    formRef: Object,
    size: {
      type: String,
      default: 'md',
      validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    loading: Boolean,
    steps: {
      type: Array,
      default: () => [],
      validator: (steps) =>
        steps.every(
          (step) => step.label && step.component && (!step.props || typeof step.props === 'object')
        )
    },
    tabs: {
      type: Array,
      default: () => [],
      validator: (tabs) =>
        tabs.every(
          (tab) =>
            tab.name && tab.label && tab.component && (!tab.props || typeof tab.props === 'object')
        )
    },
    initialView: String
  })

  const emit = defineEmits([
    'update:modelValue',
    'hide',
    'step-complete',
    'steps-finished',
    'tab-change',
    'tab-validated',
    'save',
    'tab-registered'
  ])

  const stepperRef = ref(null)
  const currentStep = ref(0)
  const currentTab = ref(null)
  const viewRefs = ref({})
  const stepData = ref([])
  const componentRefs = ref([])

  const dialogModel = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  const hasSteps = computed(() => props.steps?.length > 0)
  const hasTabs = computed(() => props.tabs?.length > 0)
  const hasForm = computed(() => props.formRef || Object.keys(viewRefs.value).length > 0)
  const isLastStep = computed(() => currentStep.value === props.steps.length - 1)

  const titleParts = computed(
    () =>
      props.title?.split(' ').map((word) => ({
        text: word + ' ',
        highlight: props.highlightTitleWords?.includes(word) || false
      })) || []
  )

  const cardClass = computed(() => ({
    'dialog-xs': props.size === 'xs',
    'dialog-sm': props.size === 'sm',
    'dialog-md': props.size === 'md',
    'dialog-lg': props.size === 'lg',
    'dialog-xl': props.size === 'xl'
  }))

  const position = computed(() => (props.size === 'xl' && $q.screen.lt.md ? 'bottom' : 'standard'))

  onMounted(() => {
    if (
      !hasSteps.value &&
      props.initialView &&
      props.tabs.some((v) => v.name === props.initialView)
    ) {
      currentTab.value = props.initialView
    } else if (props.tabs.length > 0) {
      currentTab.value = props.tabs[0].name
    }
  })

  watch(componentRefs, (newRefs) => {
    if (newRefs && newRefs.length) {
      props.tabs.forEach((tab, index) => {
        if (newRefs[index]) {
          viewRefs.value[tab.name] = newRefs[index]
          emit('tab-registered', tab.name, newRefs[index])
        }
      })
    }
  }, { immediate: true, deep: true })

  function registerViewRef(el, key) {
    if (el) {
      viewRefs.value[key] = el
    }
  }

  function getCurrentViewRef() {
    if (props.formRef) {
      return props.formRef
    }
    if (hasSteps.value) {
      return viewRefs.value[`step-${currentStep.value}`]
    }
    return viewRefs.value[currentTab.value]
  }

  async function validateCurrentView() {
    const currentRef = getCurrentViewRef()
    if (!currentRef?.validate) {
      return true
    }
    return await currentRef.validate()
  }

  const handleSave = async () => {
    if (props.formRef) {
      try {
        const isValid = await props.formRef.validate()
        if (isValid) {
          props.formRef.submit()
        }
      } catch {
        errorNotify('Veuillez remplir tous les champs obligatoires.')
      }
      return
    }

    if (hasSteps.value) {
      await handleFinish()
      return
    }

    if (hasTabs.value) {
      try {
        let isValid = true
        const data = {}

        for (const tab of props.tabs) {
          const ref = viewRefs.value[tab.name]
          if (ref?.validate) {
            const tabIsValid = await ref.validate()
            isValid = isValid && tabIsValid
          }

          if (ref?.getData) {
            const tabData = ref.getData()
            Object.assign(data, tabData)
          }
        }

        if (!isValid) {
          return
        }

        emit('save', data)
      } catch (error) {
        logger.error('Error during validation/saving:', error)
      }
      return
    }

    if (await validateCurrentView()) {
      const currentRef = getCurrentViewRef()
      const data = currentRef?.getData?.() || {}
      emit('save', data)
    }
  }

  function handleClose() {
    if (props.formRef && props.formRef.reset) {
      props.formRef.reset()
    }

    Object.values(viewRefs.value).forEach((ref) => {
      if (ref?.reset) {
        ref.reset()
      }
    })

    stepData.value = []
    currentStep.value = 0
    emit('hide')
    dialogModel.value = false
  }

  async function handleViewChange(viewName) {
    const oldTab = currentTab.value
    currentTab.value = viewName
    emit('tab-change', { newView: currentTab.value, oldView: oldTab })
  }

  function handlePrevious() {
    if (hasSteps.value && currentStep.value > 0) {
      currentStep.value--
    }
  }

  async function handleNext() {
    if (!hasSteps.value || currentStep.value >= props.steps.length - 1) {
      return
    }

    if (await validateCurrentView()) {
      const currentRef = getCurrentViewRef()
      const data = currentRef?.getData?.() || {}
      stepData.value[currentStep.value] = data
      emit('step-complete', { step: currentStep.value, data })
      currentStep.value++
    }
  }

  async function handleFinish() {
    if (await validateCurrentView()) {
      const currentRef = getCurrentViewRef()
      stepData.value[currentStep.value] = currentRef?.getData?.() || {}
      const finalData = stepData.value.reduce((acc, curr) => ({ ...acc, ...curr }), {})
      emit('steps-finished', finalData)
    }
  }

  defineExpose({
    close: handleClose
  })
</script>

<style lang="scss" scoped>
.dialog-xs {
  width: 300px;
}

.dialog-sm {
  width: 400px;
}

.dialog-md {
  width: 600px;
}

.dialog-lg {
  width: 800px;
}

.dialog-xl {
  width: 100%;
  max-width: 1200px;
}

@media (max-width: 600px) {
  .dialog-xs,
  .dialog-sm,
  .dialog-md,
  .dialog-lg,
  .dialog-xl {
    width: 100%;
    max-width: 100%;
  }
}

.content-container {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}
</style>
