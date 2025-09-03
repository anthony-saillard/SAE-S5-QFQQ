<template>
  <div class="user-resources-list">
    <q-card v-if="loading" class="q-pa-md full-height">
      <q-skeleton type="text" class="text-h6 q-mb-md" />
      <q-skeleton type="rect" height="100px" class="q-mb-md" />
    </q-card>

    <template v-else>
      <div v-if="error" class="text-negative q-mb-md">
        <q-banner class="bg-negative text-white">
          {{ error }}
          <template #action>
            <q-btn flat color="white" label="Réessayer" @click="fetchUserResources" />
          </template>
        </q-banner>
      </div>

      <q-card v-else class="full-height">
        <q-card-section>
          <div class="text-h6 q-mb-md">
            Ressources
          </div>

          <div v-if="!resources.length && !parentResources.length" class="text-center q-pa-md">
            <q-icon name="info" size="2rem" color="primary" />
            <p>Vous n'êtes responsable d'aucune ressource pour le moment.</p>
          </div>

          <q-list v-else separator>
            <q-item
              v-for="resource in resources"
              :key="`resource-${resource.id}`"
              v-ripple
              clickable
              :to="`/resource/${resource.id}`"
            >
              <q-item-section>
                <q-item-label>{{ resource.identifier }} - {{ resource.name }}</q-item-label>
              </q-item-section>

              <q-item-section side>
                <q-icon name="chevron_right" color="primary" />
              </q-item-section>
            </q-item>
          </q-list>
          <q-item
            v-for="resource in parentResources"
            :key="`subresource-parent-${resource.id}`"
            v-ripple
            clickable
            :to="`/resource/${resource.id}`"
          >
            <q-item-section>
              <q-item-label>
                {{ resource.identifier }} - {{ resource.name }}
              </q-item-label>
              <q-item-label caption>
                Sous-ressource : {{ getSubResourceNames(resource.id) }}
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <q-icon name="chevron_right" color="primary" />
            </q-item-section>
          </q-item>
        </q-card-section>
      </q-card>
    </template>
  </div>
</template>

<script setup>
  import { ref, onMounted } from 'vue'
  import { useUserStore } from 'src/utils/stores/useUserStore.js'
  import { ApiService } from 'src/services/apiService.js'
  import { logger } from 'src/utils/logger.js'

  const userStore = useUserStore()
  const userId = userStore.user?.id

  const resources = ref([])
  const subResources = ref([])
  const parentResources = ref([])
  const subResourceMapping = ref({})
  const loading = ref(true)
  const error = ref(null)

  const getSubResourceNames = (resourceId) => {
    return subResourceMapping.value[resourceId]?.map(sr => sr.name).join(', ') || 'Non spécifiée'
  }

  const fetchUserResources = async () => {
    loading.value = true
    error.value = null

    try {
      if (!userId) {
        return
      }

      resources.value = await ApiService.resources.fetchResources({ id_user: userId }, true)

      subResources.value = await ApiService.subResources.fetchSubResources({ id_user: userId })

      if (subResources.value.length > 0) {
        const existingResourceIds = new Set(resources.value.map(r => r.id))

        const parentResourceIds = [...new Set(
          subResources.value
            .map(sr => sr.id_resources)
            .filter(id => id && !existingResourceIds.has(id))
        )]

        if (parentResourceIds.length > 0) {
          const mapping = {}
          subResources.value.forEach(sr => {
            if (!mapping[sr.id_resources]) {
              mapping[sr.id_resources] = []
            }
            mapping[sr.id_resources].push(sr)
          })
          subResourceMapping.value = mapping

          const fetchedResources = await Promise.all(
            parentResourceIds.map(id => ApiService.resources.fetchResource(id))
          )
          parentResources.value = fetchedResources.filter(Boolean)
        }
      }
    } catch (err) {
      logger.error('Error loading resources', err)
      error.value = 'Erreur lors du chargement des ressources'
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    fetchUserResources()
  })
</script>
