<template>
  <div class="q-pa-md">
    <div class="row q-mb-sm">
      <div class="col">
        <div class="text-subtitle1">
          {{ subResourcesInternal.length > 1 ? 'Sous-ressources' : 'Activités et groupes' }}
        </div>
      </div>
      <div class="col-auto">
        <q-btn
          :color="subResourcesInternal.length > 1 ? 'primary' : 'secondary'"
          :label="subResourcesInternal.length > 1 ? 'Ajouter une sous-ressource' : 'Activer les sous-ressources'"
          :icon="subResourcesInternal.length > 1 ? 'add' : 'check'"
          size="sm"
          class="q-mb-md"
          :flat="subResourcesInternal.length <= 1"
          @click="addNewSubResource"
        />
      </div>
    </div>

    <q-select
      v-if="subResourcesInternal.length > 1"
      v-model="selectedSubResourceIndex"
      :options="subResourceOptions"
      label="Choisissez la sous-ressource"
      filled
      class="q-mb-md"
      emit-value
      map-options
    />

    <q-separator class="q-my-md" />

    <div>
      <div v-for="(subResource, index) in subResourcesInternal" v-show="selectedSubResourceIndex === index" :key="index" class="q-mb-lg">
        <div v-if="subResourcesInternal.length > 1" class="row items-center q-mb-xs">
          <div class="col">
            <div class="text-weight-bold">
              {{ subResource.name }}
            </div>
          </div>
          <div class="col-auto">
            <q-btn
              flat round dense
              color="negative"
              icon="delete"
              @click="removeSubResource(index)"
            />
          </div>
        </div>

        <div class="q-pa-sm q-gutter-y-md">
          <q-input
            v-if="subResourcesInternal.length > 1"
            v-model="subResource.name"
            label="Nom de la sous-ressource"
            :rules="[rules.required, rules.maxLength(50)]"
            filled
          />

          <user-select
            v-if="subResourcesInternal.length > 1"
            v-model="subResource.user"
            label="Responsable de la sous-ressource"
            clearable simple-display
          />

          <div v-if="subResourcesInternal.length > 1" class="text-subtitle2 q-mt-md q-mb-xs">
            Enseignants
          </div>

          <!-- Affichage de tous les groupes (activités) -->
          <div class="q-mb-md">
            <div v-if="loading" class="text-center q-pa-md">
              <q-spinner color="primary" size="2em" />
              <div class="q-mt-sm">
                Chargement des groupes...
              </div>
            </div>

            <q-list bordered separator>
              <q-item v-for="(group, groupIndex) in groups" :key="groupIndex" class="q-py-md">
                <q-item-section>
                  <q-item-label class="text-weight-bold">
                    {{ getGroupDisplayName(group) }}
                  </q-item-label>
                  <q-item-label v-if="group.course_type" caption>
                    {{ group.course_type.name }}
                  </q-item-label>
                </q-item-section>

                <q-item-section side style="min-width: 300px">
                  <!-- Sélecteur multiple d'enseignants -->
                  <q-select
                    filled
                    dense
                    multiple
                    use-chips
                    :model-value="getTeachersForGroup(subResource, group.id)"
                    :options="users"
                    option-value="id"
                    option-label="fullName"
                    label="Enseignants"
                    class="full-width"
                    @update:model-value="updateTeachersForGroup(subResource, group.id, $event)"
                  >
                    <template #no-option>
                      <q-item>
                        <q-item-section class="text-grey">
                          Aucun utilisateur disponible
                        </q-item-section>
                      </q-item>
                    </template>
                    <template #selected-item="scope">
                      <q-chip
                        removable
                        :tabindex="scope.tabindex"
                        dense
                        class="q-ma-xs"
                        @remove="scope.removeAtIndex(scope.index)"
                      >
                        {{ scope.opt.fullName }}
                      </q-chip>
                    </template>
                    <template #option="scope">
                      <q-item v-bind="scope.itemProps" dense>
                        <q-item-section avatar>
                          <q-avatar color="primary" text-color="white" size="28px">
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
                  </q-select>
                </q-item-section>
              </q-item>

              <q-item v-if="!groups.length && !loading">
                <q-item-section>
                  <q-item-label class="text-center text-grey">
                    Aucun groupe disponible
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </div>
        </div>
      </div>
    </div>
  </div>

  <q-dialog v-model="addSubResourceDialog" persistent>
    <q-card style="width: 350px;">
      <q-card-section>
        <div class="text-h6">
          {{ isFirstSubResourceCreation ? 'Activer les sous-ressources' : 'Ajouter une sous-ressource' }}
        </div>
      </q-card-section>

      <q-card-section v-if="isFirstSubResourceCreation" style="overflow-y: auto;">
        <div class="row q-gutter-xs">
          <q-icon name="info" size="xs" color="primary">
            <q-tooltip class="fs-80">
              Nom de la sous-ressources qui contiendra les attributions précédemment ajouter à la ressource.
            </q-tooltip>
          </q-icon>
          <div class="fs-80 op-70 q-mb-xs" style="overflow-wrap: break-word; word-break: break-word; max-width: 100%;">
            Informations
          </div>
        </div>
        <q-input
          v-model="existingSubResourceName"
          label="Nom de l'ancienne sous-ressources"
          filled autofocus
        />
      </q-card-section>

      <q-card-section>
        <q-input
          v-model="newSubResourceName"
          :label="isFirstSubResourceCreation ? 'Nom de la nouvelle sous-ressources' : 'Nom de la sous-ressource'"
          filled
          :autofocus="!isFirstSubResourceCreation"
        />
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Annuler" color="primary" @click="() => { addSubResourceDialog = false; }" />
        <q-btn
          flat
          label="Confirmer" color="primary"
          @click="confirmAddSubResource"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
  import { ref, watch, computed, onMounted } from 'vue'
  import { rules } from 'src/utils/rules.js'
  import UserSelect from 'src/modules/users/components/UserSelect.vue'
  import { warningNotify } from 'src/utils/notify.js'
  import { confirmDialog } from 'src/utils/dialog.js'
  import { formatUserName, getInitials } from 'src/utils/utils.js'
  import { ApiService } from 'src/services/apiService.js'
  import { logger } from 'src/utils/logger.js'

  const props = defineProps({
    subResources: {
      type: Array,
      default: () => []
    },
    resourceId: [Number, String]
  })

  defineEmits(['cancel'])

  const loading = ref(false)
  const subResourcesInternal = ref([])
  const selectedSubResourceIndex = ref(0)
  const addSubResourceDialog = ref(false)
  const newSubResourceName = ref('')
  const existingSubResourceName = ref('')
  const isFirstSubResourceCreation = ref(false)
  const groups = ref([])
  const users = ref([])

  const subResourceOptions = computed(() => {
    return subResourcesInternal.value.map((sr, index) => ({
      label: sr.name,
      value: index
    }))
  })

  watch(() => props.subResources, (newVal) => {
    if (newVal && newVal.length) {
      subResourcesInternal.value = newVal.map(sr => ({
        id: sr.id,
        name: sr.name,
        user: sr.user ?? null,
        teachers: normalizeTeachers(sr.teachers || [])
      }))
      selectedSubResourceIndex.value = 0
    } else {
      subResourcesInternal.value = [{
        id: null,
        name: '',
        user: null,
        teachers: []
      }]
      selectedSubResourceIndex.value = 0
    }
  }, { immediate: true })

  onMounted(async () => {
    await fetchGroups()
    await fetchUsers()
  })

  async function fetchGroups() {
    loading.value = true
    try {
      const response = await ApiService.groups.fetchGroups({}, ['course_type'])
      groups.value = response
    } catch (error) {
      logger.error('Error loading groups:', error)
      warningNotify('Impossible de charger les groupes')
      groups.value = []
    } finally {
      loading.value = false
    }
  }

  async function fetchUsers() {
    try {
      const response = await ApiService.users.fetchUsers()
      users.value = response.map(user => ({
        ...user,
        fullName: formatUserName(user)
      }))
    } catch (error) {
      logger.error('Error loading users:', error)
      warningNotify('Impossible de charger les utilisateurs')
      users.value = []
    }
  }

  function getGroupDisplayName(group) {
    let displayName = group.name || `Groupe ${group.id}`
    if (group.course_type) {
      displayName = `${displayName} (${group.course_type.code || group.course_type.name})`
    }
    return displayName
  }

  function getTeachersForGroup(subResource, groupId) {
    return subResource.teachers
      .filter(teacher => teacher.id_group === groupId ||
        (teacher.group && (teacher.group.id === groupId || teacher.group === groupId)))
      .map(teacher => {
        const userId = teacher.id_user || teacher.id
        return users.value.find(u => u.id === userId) || {
          id: userId,
          fullName: teacher.fullName || 'Utilisateur inconnu'
        }
      })
  }

  function updateTeachersForGroup(subResource, groupId, selectedTeachers) {
    // Supprimer les enseignants existants pour ce groupe
    subResource.teachers = subResource.teachers.filter(
      teacher => !(teacher.id_group === groupId ||
        (teacher.group && (teacher.group.id === groupId || teacher.group === groupId)))
    )

    // Ajouter les nouveaux enseignants sélectionnés
    selectedTeachers.forEach(teacher => {
      subResource.teachers.push({
        id: null,
        id_user: teacher.id,
        id_group: groupId,
        fullName: teacher.fullName,
        email: teacher.email,
        group: {
          id: groupId,
          name: groups.value.find(g => g.id === groupId)?.name || `Groupe ${groupId}`
        }
      })
    })
  }

  function normalizeTeachers(teachers) {
    return teachers.map(teacher => {
      const normalizedTeacher = { ...teacher }

      if (!normalizedTeacher.fullName) {
        normalizedTeacher.fullName = teacher.user_name || teacher.name || 'Utilisateur'
      }

      if (!normalizedTeacher.email) {
        normalizedTeacher.email = teacher.email || ''
      }

      if (teacher.group && typeof teacher.group === 'object') {
        normalizedTeacher.group = {
          ...teacher.group,
          name: teacher.group.name || teacher.group_name
        }
        normalizedTeacher.id_group = teacher.group.id
      } else if (teacher.id_group) {
        normalizedTeacher.group = {
          id: teacher.id_group,
          name: teacher.group_name || `Groupe ${teacher.id_group}`
        }
        normalizedTeacher.id_group = teacher.id_group
      } else if (teacher.group && (typeof teacher.group === 'string' || typeof teacher.group === 'number')) {
        normalizedTeacher.group = {
          id: teacher.group,
          name: teacher.group_name || `Groupe ${teacher.group}`
        }
        normalizedTeacher.id_group = teacher.group
      }

      return normalizedTeacher
    })
  }

  function addNewSubResource() {
    if (subResourcesInternal.value.length === 1 && subResourcesInternal.value[0].name === '') {
      isFirstSubResourceCreation.value = true
      existingSubResourceName.value = ''
      newSubResourceName.value = ''
    } else {
      isFirstSubResourceCreation.value = false
      newSubResourceName.value = `Sous-ressource ${subResourcesInternal.value.length + 1}`
    }
    addSubResourceDialog.value = true
  }

  function confirmAddSubResource() {
    if (isFirstSubResourceCreation.value) {
      if (existingSubResourceName.value.trim() === '') {
        warningNotify('Le nom de la première sous-ressource ne peut pas être vide')
        return
      }
      subResourcesInternal.value[0].name = existingSubResourceName.value
    }

    if (newSubResourceName.value.trim() === '') {
      warningNotify('Le nom de la sous-ressource ne peut pas être vide')
      return
    }

    subResourcesInternal.value.push({
      id: null,
      name: newSubResourceName.value,
      user: null,
      teachers: []
    })

    selectedSubResourceIndex.value = subResourcesInternal.value.length - 1
    newSubResourceName.value = ''
    existingSubResourceName.value = ''
    isFirstSubResourceCreation.value = false
    addSubResourceDialog.value = false
  }

  async function removeSubResource(index) {
    if (!await confirmDialog(
      subResourcesInternal.value.length > 1
        ? 'Êtes-vous sûr de vouloir supprimer cette sous-ressource ?'
        : 'Êtes-vous sûr de vouloir réinitialiser les attributions ?'
    )) {
      return
    }

    if (subResourcesInternal.value.length > 1) {
      subResourcesInternal.value.splice(index, 1)

      if (subResourcesInternal.value.length === 1) {
        subResourcesInternal.value[0].name = ''
      }

      if (selectedSubResourceIndex.value >= subResourcesInternal.value.length) {
        selectedSubResourceIndex.value = Math.max(0, subResourcesInternal.value.length - 1)
      }
    } else {
      subResourcesInternal.value[0] = {
        id: null,
        name: '',
        user: null,
        teachers: []
      }
    }
  }

  function validate() {
    if (subResourcesInternal.value.length > 1) {
      return !subResourcesInternal.value.some(sr => !sr.name.trim())
    }
    return true
  }

  function getData() {
    return {
      subResources: subResourcesInternal.value.map(sr => {
        const teachers = sr.teachers.map(teacher => {
          const groupId = teacher.group ?
            (typeof teacher.group === 'object' ? teacher.group.id : teacher.group) :
            (teacher.id_group || null)

          return {
            ...teacher,
            id: teacher.id,
            id_group: groupId
          }
        })

        return {
          id: sr.id,
          name: sr.name || '',
          id_users: sr?.user?.id,
          teachers
        }
      })
    }
  }

  function reset() {
    if (props.subResources && props.subResources.length) {
      subResourcesInternal.value = props.subResources.map(sr => ({
        id: sr.id,
        name: sr.name,
        user: sr.user,
        teachers: normalizeTeachers(sr.teachers || [])
      }))
    } else {
      subResourcesInternal.value = [{
        id: null,
        name: '',
        user: null,
        teachers: []
      }]
    }

    selectedSubResourceIndex.value = 0
    fetchGroups()
    fetchUsers()
  }

  defineExpose({
    validate,
    getData,
    reset
  })
</script>
