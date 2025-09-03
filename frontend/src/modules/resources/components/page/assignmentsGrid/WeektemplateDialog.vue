<template>
  <q-dialog
    v-model="showDialog"
    persistent
  >
    <q-card style="min-width: 400px; max-width: 600px">
      <q-card-section class="row items-center">
        <div class="text-h6">
          Appliquer à plusieurs semaines
        </div>
        <q-space />
        <q-btn
          v-close-popup icon="close"
          flat round dense
        />
      </q-card-section>

      <q-card-section>
        <div class="q-mb-md">
          <q-radio
            v-model="useTemplateWeek"
            :val="true"
            label="Utiliser la semaine type enregistrée"
            :disable="!hasTemplateWeek"
          />
          <q-radio
            v-model="useTemplateWeek"
            :val="false"
            label="Utiliser la semaine sélectionnée (Semaine {{ sourceIndex + 1 }})"
          />
        </div>

        <div class="text-subtitle2 q-mb-sm">
          Sélectionnez les semaines cibles :
        </div>

        <div class="row q-mb-md">
          <q-btn
            label="Tout sélectionner"
            color="primary"
            dense flat no-caps
            class="q-mr-sm"
            @click="selectAllWeeks"
          />
          <q-btn
            label="Tout désélectionner"
            color="grey"
            dense flat no-caps
            @click="deselectAllWeeks"
          />
        </div>

        <div style="max-height: 300px; overflow-y: auto;">
          <q-list bordered separator>
            <q-item
              v-for="(week, index) in weeks"
              :key="`week-select-${index}`"
              v-ripple
              tag="label"
            >
              <q-item-section avatar>
                <q-checkbox v-model="selectedWeeks" :val="index" :disable="index === sourceIndex" />
              </q-item-section>

              <q-item-section>
                <q-item-label>
                  Semaine {{ index + 1 }}
                </q-item-label>
                <q-item-label caption>
                  {{ formatDateRange(week) }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </div>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn v-close-popup flat label="Annuler" color="negative" />
        <q-btn
          flat
          label="Appliquer"
          color="positive"
          :disable="selectedWeeks.length === 0"
          @click="applyTemplate"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
  import { ref, watch, computed } from 'vue'
  import { logger } from 'src/utils/logger.js'

  const props = defineProps({
    modelValue: {
      type: Boolean,
      default: false
    },
    sourceIndex: {
      type: Number,
      default: null
    },
    weeks: {
      type: Array,
      required: true
    }
  })

  const emit = defineEmits(['update:modelValue', 'apply'])

  const hasTemplateWeek = ref(false)

  if (typeof localStorage !== 'undefined') {
    try {
      const resourceId = window.location.pathname.split('/').pop()
      hasTemplateWeek.value = !!localStorage.getItem(`template_week_${resourceId}`)
    } catch (e) {
      logger.error('Error checking template week', e)
    }
  }

  const showDialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  const selectedWeeks = ref([])
  const useTemplateWeek = ref(hasTemplateWeek.value)

  watch(() => props.modelValue, (newVal) => {
    if (newVal) {
      selectedWeeks.value = []
      useTemplateWeek.value = hasTemplateWeek.value
    }
  })

  function selectAllWeeks() {
    selectedWeeks.value = props.weeks
      .map((_, index) => index)
      .filter(index => index !== props.sourceIndex)
  }

  function deselectAllWeeks() {
    selectedWeeks.value = []
  }

  function applyTemplate() {
    emit('apply', {
      sourceWeek: props.sourceIndex,
      targetWeeks: selectedWeeks.value,
      useTemplateWeek: useTemplateWeek.value
    })

    showDialog.value = false
  }

  function formatDateRange(week) {
    if (!week) {
      return ''
    }

    const formatDay = (date) => {
      if (!(date instanceof Date)) {
        date = new Date(date)
      }
      return date.getDate().toString().padStart(2, '0') + '/' +
        (date.getMonth() + 1).toString().padStart(2, '0')
    }

    return formatDay(week.start_date) + ' au ' + formatDay(week.end_date)
  }
</script>
