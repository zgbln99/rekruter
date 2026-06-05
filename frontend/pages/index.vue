<script setup lang="ts">
// Ekran „Dziś" — punkt startowy aplikacji. W Fazie 1 wypełni go lista
// zadań follow-up na dziś (z Call Log). Teraz: szkielet + dane użytkownika.
const auth = useAuthStore()
</script>

<template>
  <section>
    <h1 class="mb-4 text-2xl font-bold">Dziś</h1>

    <div class="rounded-xl border border-gray-200 bg-white p-4">
      <p class="text-sm text-gray-500">Zalogowano jako</p>
      <p class="text-lg font-semibold">{{ auth.user?.name }}</p>
      <p class="text-sm text-gray-500">{{ auth.user?.role_label }}</p>
    </div>

    <div
      class="mt-6 rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center"
    >
      <p class="text-gray-500">
        Tu pojawi się lista zadań follow-up na dziś.
      </p>
      <p class="mt-1 text-xs text-gray-400">Faza 1 — rdzeń KPI</p>
    </div>

    <button class="mt-8 text-sm text-gray-400 underline" @click="auth.logout()">
      Wyloguj się
    </button>
  </section>
</template>
