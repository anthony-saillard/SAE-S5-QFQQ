<template>
  <q-page class="q-pa-xl">
    <h4 class="text-center q-mb-xl fs-230">
      Bonjour, <span class="fw-500">{{ formatUserName(userStore.user) }}</span>
    </h4>

    <div class="row q-col-gutter-md justify-around q-mt-xl">
      <div class="col-12 col-md-5">
        <UserResourcesList :key="schoolYearStore.effectiveYearId" />
      </div>

      <div class="col-12 col-md-5">
        <WeeklyAssignements :key="schoolYearStore.effectiveYearId" />
      </div>
    </div>
  </q-page>
</template>

<script setup>
  import { useUserStore } from 'src/utils/stores/useUserStore.js'
  import UserResourcesList from 'src/modules/resources/components/indexPage/UserResourcesList.vue'
  import WeeklyAssignements from 'src/modules/resources/components/indexPage/WeeklyAssignements.vue'
  import { formatUserName } from 'src/utils/utils.js'
  import { useSchoolYearStore } from 'src/utils/stores/useSchoolYearStore.js'

  const userStore = useUserStore()
  const schoolYearStore = useSchoolYearStore()
</script>
