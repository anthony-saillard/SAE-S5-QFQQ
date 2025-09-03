<template>
  <q-select
    v-model="selectedUserModel"
    v-bind="$attrs"
    :options="filteredUsers"
    :option-value="optionValue"
    :option-label="optionLabel"
    :label="label"
    filled
    use-input
    :loading="loading"
    :dense="dense"
    @filter="filterUsers"
  >
    <template #option="scope">
      <q-item v-bind="scope.itemProps" dense class="q-px-md q-py-sm">
        <q-item-section avatar>
          <q-avatar color="primary" text-color="white" size="32px">
            {{ getInitials(scope.opt.fullName) }}
          </q-avatar>
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.fullName }}</q-item-label>
          <q-item-label caption>
            {{ scope.opt.email }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
    <template #selected>
      <div v-if="selectedUser && simpleDisplay" class="ellipsis">
        {{ selectedUser.fullName }}
      </div>
      <template v-else-if="selectedUser">
        <q-item dense class="q-py-xs">
          <q-item-section avatar>
            <q-avatar color="primary" text-color="white" size="28px">
              {{ getInitials(selectedUser.fullName) }}
            </q-avatar>
          </q-item-section>
          <q-item-section>
            <q-item-label>
              {{ selectedUser.fullName }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </template>
  </q-select>
</template>

<script setup>
  import { ref, computed, onMounted, watch } from 'vue'
  import { ApiService } from 'src/services/apiService.js'
  import { logger } from 'src/utils/logger.js'
  import { formatUserName, getInitials } from 'src/utils/utils.js'

  const props = defineProps({
    modelValue: {
      type: [String, Number, Object],
      default: null
    },
    label: {
      type: String,
      default: 'Utilisateur'
    },
    optionLabel: {
      type: String,
      default: 'fullName'
    },
    optionValue: {
      type: String,
      default: 'id'
    },
    dense: Boolean,
    simpleDisplay: Boolean
  })

  const emit = defineEmits(['update:modelValue'])

  const users = ref([])
  const filteredUsers = ref([])
  const loading = ref(false)

  const selectedUserModel = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  const selectedUser = computed(() => {
    if (!selectedUserModel.value) {
      return null
    }

    if (typeof selectedUserModel.value === 'object' && selectedUserModel.value[props.optionLabel]) {
      return selectedUserModel.value
    }

    return users.value.find(user =>
      user[props.optionValue] === (
        typeof selectedUserModel.value === 'object'
          ? selectedUserModel.value[props.optionValue]
          : selectedUserModel.value
      )
    )
  })

  onMounted(fetchUsers)

  watch(() => props.modelValue, () => {
    if (users.value.length === 0 && props.modelValue) {
      fetchUsers()
    }
  })

  async function fetchUsers() {
    loading.value = true
    try {
      const response = await ApiService.users.fetchUsers()

      users.value = response.map(user => ({
        ...user,
        fullName: formatUserName(user)
      }))

      filteredUsers.value = [...users.value]
    } catch (error) {
      logger.error('Error loading users:', error)
      users.value = []
      filteredUsers.value = []
    } finally {
      loading.value = false
    }
  }

  function filterUsers(val, update) {
    if (val === '') {
      update(() => {
        filteredUsers.value = users.value
      })
      return
    }

    update(() => {
      const needle = val.toLowerCase()
      filteredUsers.value = users.value.filter(
        v => v[props.optionLabel].toLowerCase().indexOf(needle) > -1 ||
          (v?.email && v.email.toLowerCase().indexOf(needle) > -1)
      )
    })
  }
</script>
