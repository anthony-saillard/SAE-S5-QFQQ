<template>
  <td
    :class="{
      'has-assignment': !empty(assignment),
      'has-comment': hasComment,
      'empty-cell': empty(assignment) || assignment.allocated_hours <= 0,
      'read-only': readOnly,
    }"
    :style="isPedagogicalPeriod ? (isHover && !readOnly ? {} : periodStyle) : {}"
    class="assignment-cell"
    @mouseover="isHover = true" @mouseleave="isHover = false"
    @click.stop="$emit('edit')"
  >
    <div v-if="!empty(assignment) && assignment.allocated_hours > 0" class="assignment-content">
      <div class="fs-100 text-weight-medium">
        {{ assignment?.allocated_hours }} h
      </div>

      <q-icon
        v-if="isHover && !readOnly"
        name="edit" size="xs" color="primary"
        class="q-ml-sm"
      />

      <q-icon
        v-if="hasComment"
        name="comment"
        size="xs" color="secondary"
        class="absolute-top-right"
      >
        <q-tooltip class="fs-80 bg-secondary text-center">
          <div style="max-width: 300px;">
            {{ assignment.annotation }}
          </div>
        </q-tooltip>
      </q-icon>
    </div>
    <div v-else class="flex justify-center items-center">
      <q-icon
        v-if="isHover && !readOnly"
        name="add" size="xs" color="primary"
        class="cursor-pointer"
      />
    </div>
  </td>
</template>

<script setup>
  import { computed, ref } from 'vue'
  import { empty } from 'src/utils/utils.js'

  const props = defineProps({
    assignment: Object,
    isPedagogicalPeriod: Boolean,
    periodStyle: {
      type: Object,
      default: () => ({})
    },
    readOnly: {
      type: Boolean,
      default: false
    }
  })

  defineEmits(['edit'])

  const hasComment = computed(() =>
    props.assignment?.annotation && props.assignment.annotation.trim().length > 0
  )

  const isHover = ref(false)
</script>

<style scoped lang="scss">
  .assignment-cell {
    position: relative;
    vertical-align: middle;
    transition: background-color 0.2s;
    cursor: pointer;

    &:not(.read-only):hover {
      background-color: $primary-op !important;
    }

    .assignment-content {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100%;
      position: relative;
    }

    .assignment-add {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100%;
    }

    &.has-assignment {
      background-color: $grey-3;
    }

    &.read-only {
      cursor: default;
    }
  }
</style>
