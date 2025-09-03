<template>
  <q-layout view="lhh LpR lFf">
    <q-header reveal class="bg-background text-default">
      <q-toolbar>
        <q-btn
          dense flat round icon="menu"
          @click="toggleLeftDrawer"
        />
        <q-toolbar-title>QFQQ</q-toolbar-title>
        <div v-if="userStore.isAdmin && schoolYearStore.isSchoolYearSelected">
          <school-years-select-admin
            :key="'admin-year-select-' + schoolYearStore.lastUpdate"
          />
        </div>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      :width="280"
      bordered
      class="bg-white"
    >
      <div class="drawer-content">
        <div class="scroll-area">
          <div class="flex justify-center q-pa-md">
            <img :src="logoIut" style="width: 200px" alt="IUT Valence Logo">
          </div>

          <q-list
            :key="schoolYearStore.effectiveYearId"
            padding class="text-dark"
          >
            <q-item
              v-ripple clickable
              :to="{ name: 'home' }"
              active-class="active-link"
              class="q-pa-sm q-mb-sm q-mx-sm"
            >
              <q-item-section avatar>
                <q-icon name="home" size="22px" />
              </q-item-section>
              <q-item-section class="q-pa-none">
                Accueil
              </q-item-section>
            </q-item>

            <!-- User resources -->
            <q-item
              v-if="schoolYearStore.isSchoolYearSelected && userResources.length > 0"
              v-ripple clickable
              class="q-pa-sm q-mb-sm q-mx-sm"
              @click="expandResources = !expandResources"
            >
              <q-item-section avatar>
                <q-icon name="book" size="22px" />
              </q-item-section>
              <q-item-section class="q-pa-none">
                Mes ressources
              </q-item-section>
              <q-item-section side>
                <q-icon name="expand_more" :class="expandResources ? 'rotate-180' : ''" />
              </q-item-section>
            </q-item>

            <q-slide-transition>
              <div v-show="expandResources">
                <q-list class="q-pl-md">
                  <q-item
                    v-for="resource in userResources"
                    :key="resource.id"
                    v-ripple clickable
                    active-class="active-link"
                    :to="{ name: 'resource', params: { id: resource.id } }"
                    class="q-pa-sm q-mb-sm q-mx-sm ellipsis-container"
                  >
                    <q-item-section class="q-pl-md ellipsis-text">
                      {{ resource.name }}
                    </q-item-section>
                  </q-item>
                  <q-item v-if="userResources.length === 0" class="q-pa-sm q-mb-sm q-mx-sm">
                    <q-item-section class="q-pl-md text-grey-7">
                      Aucune ressource
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>
            </q-slide-transition>

            <!-- Formations -->
            <q-item
              v-if="schoolYearStore.isSchoolYearSelected && formations.length > 0"
              v-ripple clickable
              class="q-px-sm q-py-sm q-mb-sm q-mx-sm"
              @click="expandFormations = !expandFormations"
            >
              <q-item-section avatar>
                <q-icon name="school" size="22px" />
              </q-item-section>
              <q-item-section class="q-pa-none">
                Formations
              </q-item-section>
              <q-item-section side>
                <q-icon name="expand_more" :class="expandFormations ? 'rotate-180' : ''" />
              </q-item-section>
            </q-item>

            <q-slide-transition>
              <div v-show="expandFormations">
                <q-list class="q-pl-md">
                  <template
                    v-for="formation in formations"
                    :key="formation.id"
                  >
                    <q-item
                      v-ripple clickable
                      class="q-pa-sm q-mb-sm q-mx-sm"
                      @click="formation.expanded = !formation.expanded"
                    >
                      <q-item-section class="q-pl-md">
                        {{ formation.label }}
                      </q-item-section>
                      <q-item-section v-if="formation.semesters && formation.semesters.length" side>
                        <q-icon
                          name="expand_more"
                          :class="formation.expanded ? 'rotate-180' : ''"
                        />
                      </q-item-section>
                    </q-item>

                    <q-slide-transition>
                      <q-list v-show="formation.expanded" class="q-pl-xl q-pr-sm">
                        <q-item
                          v-for="semester in formation.semesters"
                          :key="semester"
                          v-ripple clickable
                          :to="{ name: 'semester', params: { id: semester.id } }"
                          active-class="active-link"
                          class="q-pa-sm q-mb-sm"
                        >
                          <q-item-section class="q-pl-md">
                            {{ semester.name }}
                          </q-item-section>
                        </q-item>
                      </q-list>
                    </q-slide-transition>
                  </template>
                </q-list>
              </div>
            </q-slide-transition>

            <q-item
              v-if="userStore.isAdmin"
              v-ripple clickable
              :to="{ name: 'users' }"
              active-class="text-primary active-link"
              class="q-pa-sm q-mb-sm q-mx-sm"
            >
              <q-item-section avatar>
                <q-icon name="people" />
              </q-item-section>
              <q-item-section>Utilisateurs</q-item-section>
            </q-item>

            <q-item
              v-if="userStore.isAdmin && schoolYearStore.isSchoolYearSelected"
              v-ripple clickable
              :to="{ name: 'stats' }"
              active-class="text-primary active-link"
              class="q-pa-sm q-mb-sm q-mx-sm"
            >
              <q-item-section avatar>
                <q-icon name="trending_up" />
              </q-item-section>
              <q-item-section>Statistiques</q-item-section>
            </q-item>
          </q-list>
        </div>

        <div class="fixed-bottom-section">
          <q-separator />
          <q-list padding class="text-dark">
            <q-item
              v-if="userStore.isAdmin"
              v-ripple clickable
              :to="{ name: 'settings' }"
              active-class="active-link"
              class="q-pa-sm q-mb-sm q-mx-sm"
            >
              <q-item-section avatar>
                <q-icon
                  name="settings"
                />
              </q-item-section>
              <q-item-section>
                Paramètres
              </q-item-section>
            </q-item>

            <q-item
              v-ripple clickable
              class="q-pa-sm q-mb-sm q-mx-sm"
              @click="logout"
            >
              <q-item-section avatar>
                <q-icon name="logout" />
              </q-item-section>
              <q-item-section>Se déconnecter</q-item-section>
            </q-item>

            <q-item class="q-mt-md">
              <q-item-section avatar>
                <q-avatar color="grey-5" text-color="white" size="32px">
                  {{ getInitials(user) }}
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium">
                  {{ formatUserName(user) }}
                </q-item-label>
                <q-item-label caption>
                  {{ user?.email ?? '' }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </div>
      </div>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>

    <q-footer
      reveal class="bg-transparent text-grey-7 fs-80 row q-pa-sm"
    >
      © create by
      <a href="https://www.linkedin.com/in/anthonysaillard/" target="_blank">Saillard Anthony</a>,
      <a href="https://www.linkedin.com/in/theoriotte/" target="_blank">Riotte Théo</a>,
      Dumarey Lucy and
      <a href="https://www.linkedin.com/in/kaan-topkaya-493552293/" target="_blank">Topkaya Kaan</a>
    </q-footer>
  </q-layout>
</template>

<script setup>
  import { useUserStore } from 'src/utils/stores/useUserStore.js'
  import { useSchoolYearStore } from 'src/utils/stores/useSchoolYearStore.js'
  import { onMounted, ref, watch } from 'vue'
  import { useRedirect } from 'src/router/useRedirect.js'
  import logoIut from 'src/assets/logo-iut.jpg'
  import { errorNotify } from 'src/utils/notify.js'
  import { logger } from 'src/utils/logger.js'
  import { ApiService } from 'src/services/apiService.js'
  import { formatUserName, getInitials } from 'src/utils/utils.js'
  import SchoolYearsSelectAdmin from 'src/modules/settings/components/schoolYear/SchoolYearsSelectAdmin.vue'

  const userStore = useUserStore()
  const schoolYearStore = useSchoolYearStore()

  const { redirect } = useRedirect()

  const leftDrawerOpen = ref(false)

  const formations = ref([])
  const expandFormations = ref(false)

  const userResources = ref([])
  const expandResources = ref(false)

  const user = ref(userStore.user)

  watch(() => schoolYearStore.lastUpdate, async () => {
    try {
      if (schoolYearStore.isSchoolYearSelected) {
        await fetchFormations()

        await new Promise(resolve => setTimeout(resolve, 50))

        await fetchUserResources()
      }
    } catch (error) {
      logger.error('Error when updating data following a change of year:', error)
    }
  })

  async function logout() {
    userStore.clearAuth()
    schoolYearStore.clearCurrentYear()
    await redirect('login')
  }

  function toggleLeftDrawer() {
    leftDrawerOpen.value = !leftDrawerOpen.value
  }

  async function fetchFormations() {
    try {
      if (!schoolYearStore.isSchoolYearSelected) {
        formations.value = []
        return
      }

      const response = await ApiService.formations.fetchFormations()
      if (!response) {
        formations.value = []
        return
      }

      formations.value = (response).map(formation => ({
        ...formation,
        expanded: false,
        semesters: []
      }))

      const validFormations = formations.value.filter(f => f && f.id)

      try {
        const semestersPromises = validFormations.map(f =>
          ApiService.semesters.fetchSemesters({id_formation: f.id}, false, true)
            .catch(error => {
              logger.error(`Error fetching semesters for formation ${f.id}:`, error)
              return { data: [] }
            })
        )

        const semestersResponses = await Promise.allSettled(semestersPromises)

        let successIndex = 0
        for (let i = 0; i < formations.value.length; i++) {
          if (formations.value[i].id) {
            const result = semestersResponses[successIndex]
            if (result.status === 'fulfilled' && result.value) {
              formations.value[i].semesters = result.value || []

              formations.value[i].semesters.sort((a, b) => {
                if (a.order_number !== undefined && b.order_number !== undefined) {
                  return a.order_number - b.order_number
                }
                return a.name.localeCompare(b.name)
              })
            }
            successIndex++
          }
        }
      } catch (error) {
        logger.error('Error fetching formation semesters:', error)
      }
    } catch (error) {
      logger.error('Error fetching formations:', error)
      errorNotify('Un problème est survenu lors du chargement de la barre de navigation !')
      formations.value = []
    }
  }

  async function fetchUserResources() {
    try {
      if (!userStore.user || !userStore.user.id) {
        logger.info('Skip fetching user resources: no user logged in')
        return
      }

      if (!schoolYearStore.isSchoolYearSelected) {
        logger.info('Skip fetching user resources: no school year selected')
        return
      }

      if (!ApiService?.resources?.fetchResources || !ApiService?.subResources?.fetchSubResources) {
        logger.warn('ApiService resources or subResources not available')
        return
      }

      const resourcesAsResponsible = await ApiService.resources.fetchResources({
        id_user: userStore.user.id
      })

      if (!resourcesAsResponsible) {
        logger.warn('fetchResources returned undefined')
        userResources.value = []
        return
      }

      try {
        const subResourcesAsResponsible = await ApiService.subResources.fetchSubResources({
          id_user: userStore.user.id
        }) || []

        const addedResourceIds = new Set(resourcesAsResponsible.map(r => r.id))

        if (subResourcesAsResponsible.length > 0) {
          const validSubResources = subResourcesAsResponsible
            .filter(subRes => subRes && subRes.id_resources && !addedResourceIds.has(subRes.id_resources))

          const parentResourcePromises = validSubResources
            .map(subRes => ApiService.resources.fetchResource(subRes.id_resources)
              .catch(error => {
                logger.error(`Error fetching parent resource ${subRes.id_resources}`, error)
                return null
              })
            )

          const parentResourceResults = await Promise.allSettled(parentResourcePromises)
          const parentResources = parentResourceResults
            .filter(result => result.status === 'fulfilled' && result.value)
            .map(result => result.value)

          userResources.value = [
            ...resourcesAsResponsible.map(r => ({ ...r, isResourceResponsible: true })),
            ...parentResources.map(r => ({ ...r, isSubResourceResponsible: true }))
          ].filter(Boolean)
        } else {
          userResources.value = resourcesAsResponsible.map(r => ({ ...r, isResourceResponsible: true }))
        }
      } catch (error) {
        logger.error('Error fetching sub-resources:', error)
        userResources.value = resourcesAsResponsible.map(r => ({ ...r, isResourceResponsible: true }))
      }
    } catch (error) {
      logger.error('Error fetching user resources:', error)
      errorNotify('Un problème est survenu lors du chargement des ressources !')
      userResources.value = []
    }
  }

  onMounted(async () => {
    await schoolYearStore.fetchCurrentSchoolYear()
    await fetchFormations()
    await fetchUserResources()
  })
</script>

<style lang="scss" scoped>
.q-drawer {
  .drawer-content {
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  .scroll-area {
    flex-grow: 1;
    overflow-y: auto;
    overflow-x: hidden;
  }

  .fixed-bottom-section {
    flex-shrink: 0;
    background: white;
  }

  .q-item,
  .q-expansion-item {
    border-radius: 8px;
    min-height: auto;

    &.active-link {
      color: $primary;
      background: $primary-op;
    }

    .q-item__section--avatar {
      min-width: auto;
    }
  }
}

.q-footer > a {
  color: $grey-7;
  margin-left: 5px;
}

.rotate-180 {
  transform: rotate(180deg);
  transition: transform 0.3s;
}
</style>
<style scoped>
  .ellipsis-container {
    min-width: 0;
    max-width: 100%;
  }

  .ellipsis-text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
    display: block;
  }
</style>
