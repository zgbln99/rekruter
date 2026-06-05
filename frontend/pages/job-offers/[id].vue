<script setup lang="ts">
import { REQUIREMENT_OPTIONS } from '~/utils/options'

// Szczegóły ogłoszenia — centrum pracy: wymagania, skrypt rozmowy, gotowy opis,
// szybkie tworzenie kandydata z ogłoszenia.
const route = useRoute()
const router = useRouter()
const id = computed(() => route.params.id as string)
const { data: offer, isLoading } = useJobOfferQuery(id)
const createCandidate = useCreateCandidateFromOffer(id)

// Aktywne wymagania (z mapy requirements).
const activeRequirements = computed(() =>
  REQUIREMENT_OPTIONS.filter((r) => offer.value?.requirements?.[r.key]),
)

// Skrypt rozmowy — lokalne odhaczanie w trakcie rozmowy.
const checked = ref<Set<number>>(new Set())
function toggleCheck(i: number) {
  const s = new Set(checked.value)
  s.has(i) ? s.delete(i) : s.add(i)
  checked.value = s
}

// Kopiowanie gotowego opisu.
const copied = ref(false)
async function copyDescription() {
  if (!offer.value?.public_description) return
  await navigator.clipboard.writeText(offer.value.public_description)
  copied.value = true
  setTimeout(() => (copied.value = false), 2000)
}

// Szybki kandydat z ogłoszenia (krok 1).
const showQuick = ref(false)
const quick = reactive({ first_name: '', last_name: '', phone: '' })
const quickError = ref('')
async function saveQuick() {
  quickError.value = ''
  if (!quick.first_name || !quick.phone) {
    quickError.value = 'Podaj imię i telefon.'
    return
  }
  try {
    const candidate = await createCandidate.mutateAsync({ ...quick })
    await router.push(`/candidates/${candidate.id}`)
  } catch {
    quickError.value = 'Nie udało się zapisać kandydata.'
  }
}
</script>

<template>
  <section v-if="isLoading" class="py-10 text-center text-muted">Ładowanie…</section>

  <section v-else-if="offer" class="space-y-5 pb-8">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-ink">{{ offer.title }}</h1>
      <p class="text-sm text-stone">{{ offer.company?.name }} · {{ offer.status_label }}</p>
    </div>

    <!-- Główna akcja: nowy kandydat z ogłoszenia -->
    <button class="btn-primary" @click="showQuick = !showQuick">
      <AppIcon name="plus" :size="18" /> Nowy kandydat z tego ogłoszenia
    </button>
    <div v-if="showQuick" class="card space-y-2.5 p-4">
      <div class="grid grid-cols-2 gap-2">
        <input v-model="quick.first_name" placeholder="Imię" class="input-field" autofocus />
        <input v-model="quick.last_name" placeholder="Nazwisko" class="input-field" />
      </div>
      <input v-model="quick.phone" type="tel" inputmode="tel" placeholder="+48 600 000 000" class="input-field" />
      <p v-if="quickError" class="text-sm text-red-600">{{ quickError }}</p>
      <button class="btn-primary" :disabled="createCandidate.isPending.value" @click="saveQuick">
        {{ createCandidate.isPending.value ? 'Zapisywanie…' : 'Zapisz i otwórz profil' }}
      </button>
    </div>

    <NuxtLink :to="`/pipeline/${offer.id}`" class="btn-outline">
      <AppIcon name="board" :size="18" /> Otwórz pipeline kandydatów
    </NuxtLink>

    <!-- Szczegóły oferty -->
    <div class="card p-4">
      <p class="mb-2.5 text-[13px] font-medium text-steel">Szczegóły</p>
      <dl class="grid grid-cols-2 gap-y-2 text-sm">
        <template v-for="row in [
          ['Typ kierowcy', offer.driver_type],
          ['Typ naczepy', offer.trailer_type],
          ['Kraj', offer.country],
          ['Region / baza', offer.region_base],
          ['System pracy', offer.work_system],
          ['Wynagrodzenie', offer.salary_amount ? `${offer.salary_amount} ${offer.currency || ''}` : null],
          ['Język', offer.required_language],
          ['Doświadczenie', offer.required_experience],
          ['Start', offer.start_date],
        ]" :key="row[0]">
          <template v-if="row[1]">
            <dt class="text-stone">{{ row[0] }}</dt>
            <dd class="font-medium text-ink">{{ row[1] }}</dd>
          </template>
        </template>
      </dl>
    </div>

    <!-- Wymagania -->
    <div v-if="activeRequirements.length || offer.required_categories.length" class="card p-4">
      <p class="mb-2.5 text-[13px] font-medium text-steel">Wymagania</p>
      <div class="flex flex-wrap gap-2">
        <span v-for="cat in offer.required_categories" :key="cat" class="badge badge-neutral">{{ cat }}</span>
        <span v-for="r in activeRequirements" :key="r.key" class="badge badge-accent">{{ r.label }}</span>
      </div>
    </div>

    <!-- Skrypt rozmowy -->
    <div v-if="offer.call_script.length" class="card p-4">
      <p class="mb-2.5 text-[13px] font-medium text-steel">Skrypt rozmowy</p>
      <ul class="space-y-2">
        <li v-for="(q, i) in offer.call_script" :key="i">
          <button class="flex w-full items-start gap-3 text-left" @click="toggleCheck(i)">
            <span
              class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-md border"
              :class="checked.has(i) ? 'border-brand bg-brand text-white' : 'border-hairline'"
            >
              <AppIcon v-if="checked.has(i)" name="check" :size="14" />
            </span>
            <span class="text-sm" :class="checked.has(i) ? 'text-muted line-through' : 'text-ink'">{{ q }}</span>
          </button>
        </li>
      </ul>
    </div>

    <!-- Gotowy opis + kopiuj -->
    <div v-if="offer.public_description" class="card p-4">
      <div class="mb-2 flex items-center justify-between">
        <p class="text-[13px] font-medium text-steel">Gotowy opis ogłoszenia</p>
        <button class="btn-sm" @click="copyDescription">
          <AppIcon name="document" :size="15" /> {{ copied ? 'Skopiowano' : 'Kopiuj opis' }}
        </button>
      </div>
      <p class="whitespace-pre-line text-sm text-charcoal">{{ offer.public_description }}</p>
    </div>

    <!-- Notatka rekruterki (wewnętrzna) -->
    <div v-if="offer.recruiter_notes" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
      <p class="mb-1 text-[13px] font-medium text-amber-800">Notatka dla rekruterki (wewnętrzna)</p>
      <p class="whitespace-pre-line text-sm text-amber-900">{{ offer.recruiter_notes }}</p>
    </div>
  </section>
</template>
