<script setup lang="ts">
import type { User } from '~/types'

// Zarządzanie użytkownikami — dostęp egzekwuje backend (admin). Link w nawigacji
// pokazywany jest tylko adminom; nie-admin dostanie tu komunikat o braku dostępu.
const auth = useAuthStore()

const { data: users, isLoading, isError } = useUsersQuery()
const createUser = useCreateUser()
const updateUser = useUpdateUser()
const deleteUser = useDeleteUser()

// --- Dodawanie ---
const showAdd = ref(false)
const form = reactive({ name: '', email: '', password: '', role: 'recruiter', phone: '' })
const error = ref('')

async function add() {
  error.value = ''
  if (!form.name || !form.email || form.password.length < 8) {
    error.value = 'Podaj imię, e-mail i hasło (min. 8 znaków).'
    return
  }
  try {
    await createUser.mutateAsync({ ...form })
    Object.assign(form, { name: '', email: '', password: '', role: 'recruiter', phone: '' })
    showAdd.value = false
  } catch (e: any) {
    error.value = e?.response?._data?.errors?.email?.[0] || 'Nie udało się dodać użytkownika.'
  }
}

// --- Edycja ---
const editing = ref<User | null>(null)
const editForm = reactive({ name: '', email: '', role: 'recruiter', password: '' })
const editError = ref('')

function openEdit(u: User) {
  editing.value = u
  editError.value = ''
  Object.assign(editForm, { name: u.name, email: u.email, role: u.role, password: '' })
}

async function saveEdit() {
  if (!editing.value) return
  editError.value = ''
  const payload: Record<string, any> = {
    name: editForm.name,
    email: editForm.email,
    role: editForm.role,
  }
  if (editForm.password) payload.password = editForm.password
  try {
    await updateUser.mutateAsync({ id: editing.value.id, ...payload })
    editing.value = null
  } catch (e: any) {
    editError.value = e?.response?._data?.errors?.email?.[0] || 'Nie udało się zapisać zmian.'
  }
}

function remove(u: User) {
  if (confirm(`Usunąć użytkownika ${u.name}?`)) deleteUser.mutate(u.id)
}
</script>

<template>
  <section>
    <header class="mb-4 flex items-center justify-between">
      <h1 class="text-[26px] font-bold tracking-tight text-ink">Użytkownicy</h1>
      <button class="btn-sm" @click="showAdd = !showAdd">
        <AppIcon name="plus" :size="16" /> Dodaj
      </button>
    </header>

    <div v-if="showAdd" class="card mb-4 max-w-3xl space-y-2.5 p-4">
      <div class="grid gap-2.5 sm:grid-cols-2">
        <input v-model="form.name" placeholder="Imię i nazwisko" class="input-field" />
        <input v-model="form.email" type="email" placeholder="E-mail" class="input-field" />
        <input v-model="form.password" type="password" placeholder="Hasło (min. 8)" class="input-field" />
        <select v-model="form.role" class="input-field">
          <option value="recruiter">Rekruter</option>
          <option value="admin">Administrator</option>
        </select>
      </div>
      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <button class="btn-primary" :disabled="createUser.isPending.value" @click="add">
        Zapisz użytkownika
      </button>
    </div>

    <UiSkeletonList v-if="isLoading" :count="3" />

    <div v-else-if="isError" class="card p-6 text-center text-stone">
      Brak uprawnień do zarządzania użytkownikami (tylko administrator).
    </div>

    <div v-else-if="!users?.length" class="card flex flex-col items-center px-6 py-12 text-center">
      <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface text-stone">
        <AppIcon name="users" :size="24" />
      </div>
      <p class="font-semibold text-ink">Brak użytkowników</p>
      <p class="mt-1 text-sm text-stone">Dodaj pierwszego przyciskiem „Dodaj".</p>
    </div>

    <ul v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
      <li v-for="u in users" :key="u.id" class="card flex items-center gap-3 p-4">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-ink text-sm font-semibold text-white">
          {{ u.name.charAt(0) }}
        </div>
        <div class="min-w-0 flex-1">
          <p class="truncate font-semibold text-ink">{{ u.name }}</p>
          <p class="truncate text-sm text-stone">{{ u.email }}</p>
        </div>
        <span class="badge shrink-0" :class="u.role === 'admin' ? 'badge-accent' : 'badge-neutral'">
          {{ u.role_label }}
        </span>
        <button
          class="shrink-0 rounded-lg border border-hairline px-3 py-1.5 text-sm font-medium text-ink transition hover:bg-surface"
          @click="openEdit(u)"
        >
          Edytuj
        </button>
        <button
          v-if="u.id !== auth.user?.id"
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-red-500 transition hover:bg-red-50"
          title="Usuń"
          @click="remove(u)"
        >
          <AppIcon name="x" :size="18" />
        </button>
      </li>
    </ul>

    <!-- Modal edycji -->
    <div
      v-if="editing"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      @click.self="editing = null"
    >
      <div class="card w-full max-w-md space-y-3 p-5">
        <h2 class="text-lg font-semibold text-ink">Edycja użytkownika</h2>
        <div>
          <label class="field-label">Imię i nazwisko</label>
          <input v-model="editForm.name" class="input-field" />
        </div>
        <div>
          <label class="field-label">E-mail</label>
          <input v-model="editForm.email" type="email" class="input-field" />
        </div>
        <div>
          <label class="field-label">Rola</label>
          <select v-model="editForm.role" class="input-field">
            <option value="recruiter">Rekruter</option>
            <option value="admin">Administrator</option>
          </select>
        </div>
        <div>
          <label class="field-label">Nowe hasło (opcjonalnie)</label>
          <input v-model="editForm.password" type="password" placeholder="min. 8 znaków" class="input-field" />
        </div>
        <p v-if="editError" class="text-sm text-red-600">{{ editError }}</p>
        <div class="flex gap-2">
          <button class="btn-primary" :disabled="updateUser.isPending.value" @click="saveEdit">
            {{ updateUser.isPending.value ? 'Zapisywanie…' : 'Zapisz' }}
          </button>
          <button class="btn-outline" @click="editing = null">Anuluj</button>
        </div>
      </div>
    </div>
  </section>
</template>
