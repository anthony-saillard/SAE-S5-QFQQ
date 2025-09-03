<template>
  <div class="container bg-white row shadow-4">
    <div class="left-side col flex justify-center content-center full-height">
      <img
        :src="logoIut"
        alt="IUT Valence Logo"
        width="70%"
        style="max-width: 400px"
      >
    </div>
    <q-form
      class="right-side col bg-secondary text-white q-pa-lg col flex column justify-around"
      @submit.prevent="onSubmit"
    >
      <p style="font-weight: bold; font-size: 35px; width: 100%; text-align: center; letter-spacing: 1px">
        Connexion
      </p>

      <div class="q-mx-lg">
        <q-input
          v-model="login"
          label="Identifiant"
          bg-color="white"
          rounded outlined
          class="q-mb-md"
          :rules="[rules.required]"
        />
        <q-input
          v-model="password"
          label="Mot de passe"
          bg-color="white"
          type="password"
          rounded outlined
          :rules="[rules.required]"
        />
        <q-checkbox
          v-model="rememberMe"
          label="Rester connecté"
          dark
          class="q-mt-md"
        />
      </div>

      <div class="flex justify-center">
        <q-btn
          label="Se connecter"
          color="primary"
          unelevated rounded
          type="submit"
          style="width: fit-content"
          class="q-px-xl q-mb-sm"
        />
      </div>
    </q-form>
  </div>
</template>

<script setup>
  import { ref } from 'vue'
  import { useUserStore } from 'src/utils/stores/useUserStore.js'
  import logoIut from 'assets/logo-iut.jpg'
  import { errorNotify } from 'src/utils/notify.js'
  import { rules } from 'src/utils/rules.js'
  import { useRedirect } from 'src/router/useRedirect.js'
  import { logger } from 'src/utils/logger.js'

  const login = ref('')
  const password = ref('')
  const rememberMe = ref(false)

  const { redirect } = useRedirect()
  const userStore = useUserStore()

  const onSubmit = async () => {
    try {
      await userStore.login(login.value, password.value, rememberMe.value)
      await redirect('home')
    } catch (error) {
      if (error.response.status) {
        errorNotify('Identifiant et/ou mot de passe incorrectes !')
      } else {
        logger.error(error)
        errorNotify('Nous ne pouvons malheureusement pas vous connectez pour le moment !')
      }
    }
  }
</script>

<style lang="scss" scoped>
.container {
  border-radius: 20px;
  max-height: 400px;
  height: 100%;
  max-width: 800px;
  width: 100%;

  @media (max-width: $breakpoint-xs-max) {
    max-height: unset;
    height: 100vh;
    max-width: unset;
    width: 100vw;
    flex-direction: column;
    > .left-side {
      height: 100px !important;
    }
    > .right-side {
      border-radius: unset;
    }
  }
}

.right-side {
  border-radius: 20px;
}
</style>
