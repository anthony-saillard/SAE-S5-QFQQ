<template>
  <q-scroll-area
    :thumb-style="{
      borderRadius: '5px',
      backgroundColor: getPaletteColor('primary'),
      height: '8px',
      opacity: '0.75'
    }"
    :bar-style="{
      borderRadius: '9px',
      backgroundColor: getPaletteColor('primary'),
      height: '9px',
      opacity: '0.2'
    }"
    style="height: 100%; width: 100%;"
  >
    <table class="table">
      <thead>
        <tr>
          <th
            v-for="(week, index) in weeks"
            :key="`week-${index}`"
            :class="[
              getWeekClasses(week),
              { 'current-week': isCurrentWeek(week) }
            ]"
            class="bg-secondary text-white"
            style="min-width: 150px;"
          >
            <div class="row items-center justify-between q-pb-xs relative-position">
              <div class="col">
                <div style="font-weight: bold; font-size: 14px;">
                  Semaine {{ index + 1 }}
                </div>
                <div style="font-size: 12px;color: #f0f0f0;">
                  Du {{ formatDateRange(week) }}
                </div>
              </div>
              <q-btn
                v-if="!readOnly"
                flat dense rounded size="sm"
                icon="more_vert"
                color="white"
                class="col-auto absolute-top-right"
              >
                <q-menu>
                  <q-list dense style="min-width: 200px">
                    <q-item v-close-popup clickable @click="copyWeek(index)">
                      <q-item-section avatar>
                        <q-icon name="content_copy" />
                      </q-item-section>
                      <q-item-section>Copier la semaine</q-item-section>
                    </q-item>

                    <q-item v-close-popup clickable :disable="!hasWeekInClipboard" @click="pasteWeek(index)">
                      <q-item-section avatar>
                        <q-icon name="content_paste" />
                      </q-item-section>
                      <q-item-section>Coller</q-item-section>
                    </q-item>

                    <q-separator />

                    <q-item v-close-popup clickable @click="saveAsTemplateWeek(index)">
                      <q-item-section avatar>
                        <q-icon name="save" />
                      </q-item-section>
                      <q-item-section>Enregistrer comme semaine modèle</q-item-section>
                    </q-item>

                    <q-item v-close-popup clickable :disable="!hasTemplateWeek" @click="pasteTemplateWeek(index)">
                      <q-item-section avatar>
                        <q-icon name="assignment" />
                      </q-item-section>
                      <q-item-section>Appliquer la semaine modèle</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </div>
          </th>
          <th class="bg-secondary text-white" style="min-width: 100px;border-left: 2px solid #999;">
            Total
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="courseType in courseTypes" :key="courseType.id">
          <assignment-cell
            v-for="(week, index) in weeks"
            :key="`${courseType.id}-${index}`"
            :is-pedagogical-period="isPedagogicalPeriod(week)"
            :period-style="getWeekStyles(week)"
            :assignment="getAssignment(courseType.id, index)"
            :read-only="readOnly"
            :is-completed="readOnly"
            @edit="edit(courseType, index)"
          />
          <td style="background-color: #f5f5f5;border-left: 2px solid #999;" class="text-weight-bold">
            {{ parseFloat(calculateTypeTotal(courseType.id).toFixed(2)) }} h
          </td>
        </tr>
        <tr class="total-row">
          <td
            v-for="(week, index) in weeks"
            :key="`total-${index}`"
            :class="getWeekClasses(week)"
            :style="getWeekStyles(week)"
            style="background-color: #f5f5f5;" class="text-weight-bold"
          >
            {{ parseFloat(calculateWeekTotal(index).toFixed(2)) }} h
          </td>
          <td style="background-color: #e0e0e0;border-left: 2px solid #999;" class="text-weight-bold">
            {{ parseFloat(calculateTotal().toFixed(2)) }} h
          </td>
        </tr>
      </tbody>
    </table>
  </q-scroll-area>
</template>

<script setup>
  import AssignmentCell from 'src/modules/resources/components/page/assignmentsGrid/AssignmentCell.vue'
  import { usePeriods } from 'src/modules/resources/composables/usePeriods.js'
  import { colors } from 'quasar'
  import { onMounted, ref } from 'vue'
  import { errorNotify } from 'src/utils/notify.js'
  import { logger } from 'src/utils/logger.js'

  const props = defineProps({
    weeks: {
      type: Array,
      required: true
    },
    courseTypes: {
      type: Array,
      required: true
    },
    assignments: {
      type: Array,
      required: true
    },
    pedagogicalInterruptions: {
      type: Array,
      default: () => []
    },
    resource: {
      type: Object,
      required: true
    },
    readOnly: {
      type: Boolean,
      default: false
    }
  })

  const emit = defineEmits([
    'edit-assignment',
    'copy-paste-week',
    'copy-week',
    'paste-week',
    'save-template-week',
    'paste-template-week'
  ])

  const { getPaletteColor } = colors
  const {
    getTransparentPeriodColor,
    registerUsedPeriod
  } = usePeriods()

  const hasWeekInClipboard = ref(false)
  const hasTemplateWeek = ref(false)

  onMounted(() => {
    try {
      const localTemplate = localStorage.getItem(`template_week_${props.resource.id}`)
      const globalTemplate = localStorage.getItem('global_template_week')

      hasTemplateWeek.value = !!(localTemplate || globalTemplate)

      const sessionCopy = sessionStorage.getItem('copied_week')
      const crossResourceCopy = localStorage.getItem('cross_resource_copy')

      hasWeekInClipboard.value = !!(sessionCopy || crossResourceCopy)
    } catch (e) {
      logger.error('Error checking storage data', e)
    }
  })

  function copyWeek(weekIndex) {
    const weekAssignments = props.assignments.filter(a => a.week === weekIndex)
    if (weekAssignments.length === 0) {
      errorNotify('Cette semaine ne contient aucune affectation à copier')
      return
    }

    emit('copy-week', weekIndex)
  }

  function pasteWeek(targetWeekIndex) {
    if (!hasWeekInClipboard.value) {
      errorNotify('Aucune semaine à coller')
      return
    }

    emit('paste-week', targetWeekIndex)
  }

  function saveAsTemplateWeek(weekIndex) {
    const weekAssignments = props.assignments.filter(a => a.week === weekIndex)
    if (weekAssignments.length === 0) {
      errorNotify('Cette semaine ne contient aucune affectation à enregistrer comme modèle')
      return
    }

    emit('save-template-week', weekIndex)
  }

  function pasteTemplateWeek(targetWeekIndex) {
    if (!hasTemplateWeek.value) {
      errorNotify('Aucune semaine type disponible')
      return
    }

    emit('paste-template-week', targetWeekIndex)
  }

  function isPedagogicalPeriod(week) {
    if (!week || !props.pedagogicalInterruptions || props.pedagogicalInterruptions.length === 0) {
      return false
    }

    let hasPeriod = false

    props.pedagogicalInterruptions.forEach((period, index) => {
      const periodStartDate = new Date(period.start_date)
      const periodEndDate = new Date(period.end_date)

      const startDay = periodStartDate.getDay()
      let overlap

      if (startDay === 0 || startDay === 6) {
        const daysToAdd = startDay === 0 ? 1 : 2
        const adjustedStartDate = new Date(periodStartDate)
        adjustedStartDate.setDate(adjustedStartDate.getDate() + daysToAdd)

        overlap = hasOverlap(week, adjustedStartDate, periodEndDate)
      } else {
        overlap = hasOverlap(week, periodStartDate, periodEndDate)
      }

      if (overlap) {
        registerUsedPeriod(index)
        hasPeriod = true
      }
    })

    return hasPeriod
  }

  function hasOverlap(week, periodStart, periodEnd) {
    const weekStartDate = new Date(week.start_date)
    const weekEndDate = new Date(week.end_date)

    return weekStartDate <= periodEnd && weekEndDate >= periodStart
  }

  function getPeriodIndex(week) {
    if (!week || !props.pedagogicalInterruptions || props.pedagogicalInterruptions.length === 0) {
      return -1
    }

    for (let i = 0; i < props.pedagogicalInterruptions.length; i++) {
      const period = props.pedagogicalInterruptions[i]

      const periodStartDate = new Date(period.start_date)
      const periodEndDate = new Date(period.end_date)

      const startDay = periodStartDate.getDay()

      if (startDay === 0 || startDay === 6) {
        const daysToAdd = startDay === 0 ? 1 : 2
        const adjustedStartDate = new Date(periodStartDate)
        adjustedStartDate.setDate(adjustedStartDate.getDate() + daysToAdd)

        if (hasOverlap(week, adjustedStartDate, periodEndDate)) {
          return i
        }
      } else if (hasOverlap(week, periodStartDate, periodEndDate)) {
        return i
      }
    }

    return -1
  }

  function getWeekClasses(week) {
    return {
      'highlighted-period': isPedagogicalPeriod(week)
    }
  }

  function getWeekStyles(week) {
    const periodIndex = getPeriodIndex(week)
    if (periodIndex === -1) {
      return {}
    }

    return {
      backgroundColor: `${getTransparentPeriodColor(periodIndex)} !important`
    }
  }

  function isCurrentWeek(week) {
    const today = new Date()
    const weekStartDate = new Date(week.start_date)
    const weekEndDate = new Date(week.end_date)

    return today >= weekStartDate && today <= weekEndDate
  }

  function getAssignment(courseTypeId, weekIndex) {
    if (weekIndex < 0) {
      return null
    }

    const typedCourseTypeId = Number(courseTypeId)
    const typedWeekIndex = Number(weekIndex)

    return props.assignments.find(a => {
      if (a.week < 0) {
        return false
      }

      return a.id_course_type === typedCourseTypeId && a.week === typedWeekIndex
    })
  }

  function calculateTypeTotal(courseTypeId) {
    return props.assignments
      .filter(a => a.id_course_type === courseTypeId)
      .reduce((total, a) => total + Number(a.allocated_hours || 0), 0)
  }

  function calculateWeekTotal(weekIndex) {
    return props.assignments
      .filter(a => a.week === weekIndex)
      .reduce((total, a) => total + Number(a.allocated_hours || 0), 0)
  }

  function calculateTotal() {
    return props.assignments
      .reduce((total, a) => total + Number(a.allocated_hours || 0), 0)
  }

  function formatDateRange(week) {
    if (!week) {
      return ''
    }

    const formatDay = (date) => {
      return date.getDate().toString().padStart(2, '0') + '/' +
        (date.getMonth() + 1).toString().padStart(2, '0')
    }

    return formatDay(week.start_date) + ' au ' + formatDay(week.end_date)
  }

  function edit(courseType, index) {
    if (!props.readOnly) {
      emit('edit-assignment', courseType, index)
    }
  }
</script>

<style scoped lang="scss">
.table {
  border-collapse: collapse;
  min-width: calc(100% - 70px);

  th, td {
    padding: 8px;
    text-align: center;
    height: 60px;
  }

  .total-row {
    border-top: 2px solid #999;
  }

  .current-week {
    opacity: 0.7;
  }
}
</style>
