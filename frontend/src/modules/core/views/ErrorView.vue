<template>
  <div class="container bg-white nowrap shadow-4 column flex justify-center content-center q-px-lg q-py-xl">
    <p class="fs-200 fw-700 text-center text-primary q-mb-lg">
      Erreur {{ errorType }} - {{ errors[errorType].title }}
    </p>
    <p class="fs-110 op-70 text-center">
      {{ errors[errorType].message }}
    </p>
    <div class="flex justify-center">
      <q-btn
        label="Retour à la page principal"
        color="primary"
        unelevated
        rounded outline type="submit"
        style="width: fit-content"
        class="q-mt-lg"
        @click.stop="redirect('home')"
      />
    </div>
  </div>
</template>

<script setup>
  import { useRoute } from 'vue-router'
  import {useRedirect} from 'src/router/useRedirect.js'

  const { redirect } = useRedirect()

  const errors = {
    '404': {
      title: 'Page introuvable',
      message: 'La page que vous tentez d\'accéder n\'a malheureusement pas été trouvée.'
    },
    '403': {
      title: 'Accès restreint',
      message: 'Vous n\'avez pas le droit d\'accéder à cette page.'
    },
    '500': {
      title: 'Erreur interne',
      message: 'Une erreur inattendue s\'est produite, veuillez contacter le support technique.'
    }
  }

  const route = useRoute()
  const errorType =  errors[route.params.errorType] ? route.params.errorType : '500'
</script>

<style lang="scss" scoped>
.container {
  border-radius: 20px;
  max-width: 700px;
  width: fit-content;

  @media (max-width: $breakpoint-xs-max) {
    height: 100vh;
    max-width: unset;
    width: 100vw;
  }
}
</style>
