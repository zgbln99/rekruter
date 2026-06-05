<script setup lang="ts">
import type {
  ContactOutcome,
  LicenseCategory,
  LookupResponse,
  QuickAddInput,
} from '~/types'

// Ekran Quick-Add — rdzeń KPI: dodanie kandydata podczas rozmowy < 60s.
const router = useRouter()
const createCandidate = useCreateCandidate()

const phone = ref('')
const firstName = ref('')
const categories = ref<Set<LicenseCategory>>(new Set())
const hasAdr = ref(false)
const hasCode95 = ref(false)
const note = ref('')
const outcome = ref<ContactOutcome | null>(null)
const nextContactAt = ref<string | null>(null)
const error = ref('')

// --- Deduplikacja w locie ---
const duplicate = ref<LookupResponse['candidate']>(null)
let lookupTimer: ReturnType<typeof setTimeout> | null = null

watch(phone, (value) => {
  duplicate.value = null
  if (lookupTimer) clearTimeout(lookupTimer)
  const digits = value.replace(/\D/g, '')
  if (digits.length < 6) return
  lookupTimer = setTimeout(async () => {
    try {
      const res = await lookupPhone(value)
      duplicate.value = res.exists ? res.candidate : null
    } catch {
      /* ignoruj błędy lookupu */
    }
  }, 350)
})

function toggleCategory(cat: LicenseCategory) {
  const set = new Set(categories.value)
  set.has(cat) ? set.delete(cat) : set.add(cat)
  categories.value = set
}

function openDuplicate() {
  if (duplicate.value) router.push(`/candidates/${duplicate.value.id}`)
}

async function save() {
  error.value = ''
  const payload: QuickAddInput = {
    phone: phone.value,
    first_name: firstName.value,
    license_categories: [...categories.value],
    has_adr: hasAdr.value,
    has_code_95: hasCode95.value,
    internal_notes: note.value || null,
  }
  if (outcome.value) {
    payload.contact = {
      channel: 'phone',
      outcome: outcome.value,
      note: note.value || null,
      next_contact_at: nextContactAt.value,
    }
  }

  try {
    const candidate = await createCandidate.mutateAsync(payload)
    await router.push(`/candidates/${candidate.id}`)
  } catch (e: any) {
    if (e?.response?.status === 409 && e.response._data?.candidate) {
      await router.push(`/candidates/${e.response._data.candidate.id}`)
      return
    }
    error.value = 'Nie udało się zapisać kandydata.'
  }
}

const canSave = computed(
  () => phone.value.trim().length > 0 && firstName.value.trim().length > 0,
)
</script>

<template>
  <section class="pb-8">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-2xl font-bold">Nowy kandydat</h1>
      <NuxtLink to="/candidates" class="text-sm text-gray-400">Anuluj</NuxtLink>
    </div>

    <form class="space-y-5" @submit.prevent="save">
      <!-- Telefon -->
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">📞 Telefon</label>
        <input
          v-model="phone"
          type="tel"
          inputmode="tel"
          autofocus
          placeholder="+48 600 000 000"
          class="input-field"
        />
        <button
          v-if="duplicate"
          type="button"
          class="mt-2 w-full rounded-xl border border-amber-300 bg-amber-50 p-3 text-left text-sm"
          @click="openDuplicate"
        >
          ⚠️ Kandydat <strong>{{ duplicate.full_name }}</strong> już istnieje —
          dotknij, aby otworzyć.
        </button>
      </div>

      <!-- Imię -->
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">👤 Imię</label>
        <input v-model="firstName" type="text" placeholder="Jan" class="input-field" />
      </div>

      <!-- Kategorie -->
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-700">🚛 Kategorie</label>
        <div class="flex flex-wrap gap-2">
          <UiChip
            v-for="cat in LICENSE_CATEGORIES"
            :key="cat"
            :active="categories.has(cat)"
            @click="toggleCategory(cat)"
          >
            {{ cat }}
          </UiChip>
          <UiChip :active="hasAdr" @click="hasAdr = !hasAdr">ADR</UiChip>
          <UiChip :active="hasCode95" @click="hasCode95 = !hasCode95">Kod 95</UiChip>
        </div>
      </div>

      <!-- Notatka -->
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">📝 Notatka</label>
        <textarea
          v-model="note"
          rows="2"
          placeholder="Czego szuka, dyspozycyjność…"
          class="input-field !h-auto py-2"
        />
      </div>

      <!-- Wynik kontaktu -->
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-700">Wynik kontaktu</label>
        <div class="flex flex-wrap gap-2">
          <UiChip
            v-for="opt in OUTCOME_OPTIONS"
            :key="opt.value"
            :active="outcome === opt.value"
            @click="outcome = outcome === opt.value ? null : opt.value"
          >
            {{ opt.label }}
          </UiChip>
        </div>
      </div>

      <!-- Następny kontakt -->
      <div v-if="outcome">
        <label class="mb-2 block text-sm font-medium text-gray-700">⏰ Oddzwonić</label>
        <div class="flex flex-wrap gap-2">
          <UiChip
            v-for="preset in nextContactPresets()"
            :key="preset.label"
            :active="nextContactAt === preset.value"
            @click="
              nextContactAt = nextContactAt === preset.value ? null : preset.value
            "
          >
            {{ preset.label }}
          </UiChip>
        </div>
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

      <button
        type="submit"
        class="btn-primary"
        :disabled="!canSave || createCandidate.isPending.value"
      >
        {{ createCandidate.isPending.value ? 'Zapisywanie…' : 'Zapisz' }}
      </button>
    </form>
  </section>
</template>
