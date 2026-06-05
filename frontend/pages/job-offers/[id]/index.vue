<script setup lang="ts">
import { REQUIREMENT_OPTIONS } from '~/utils/options'

// Szczegóły ogłoszenia — centrum pracy: wymagania, skrypt rozmowy, gotowy opis,
// szybkie tworzenie kandydata z ogłoszenia.
const route = useRoute()
const router = useRouter()
const id = computed(() => route.params.id as string)
const { data: offer, isLoading } = useJobOfferQuery(id)
const createCandidate = useCreateCandidateFromOffer(id)
const updateOffer = useUpdateJobOffer(id)
const deleteOffer = useDeleteJobOffer()
const auth = useAuthStore()

// Aktywuj / wstrzymaj ogłoszenie.
async function toggleActive() {
  const next = offer.value?.status === 'open' ? 'paused' : 'open'
  await updateOffer.mutateAsync({ status: next } as any)
}
async function removeOffer() {
  if (!confirm('Usunąć to ogłoszenie? Operacja nieodwracalna.')) return
  await deleteOffer.mutateAsync(id.value)
  await navigateTo('/job-offers')
}

// Skierowanie do pracy (PDF dla kierowcy).
const referralLoading = ref(false)
async function generateReferral() {
  referralLoading.value = true
  try {
    const blob = await fetchBlob(`/job-offers/${id.value}/referral-pdf`)
    openBlob(blob, `skierowanie-${(offer.value?.title || 'oferta').replace(/\s+/g, '-').toLowerCase()}.pdf`)
  } finally {
    referralLoading.value = false
  }
}

// Grafika ogłoszenia (PNG na social media). Tło z AI generujemy raz i reużywamy;
// ponowne wywołanie AI tylko przy „Odśwież tło" (refresh=1).
const posterLoading = ref('')
const posterError = ref('')
async function generatePoster(format: 'feed' | 'reels', refresh = false) {
  posterError.value = ''
  posterLoading.value = refresh ? 'refresh' : format
  try {
    const q = `format=${format}${refresh ? '&refresh=1' : ''}`
    const blob = await fetchBlob(`/job-offers/${id.value}/poster?${q}`)
    openBlob(blob, `oferta-${format}-${(offer.value?.title || '').replace(/\s+/g, '-').toLowerCase()}.png`)
  } catch (e: any) {
    // Odpowiedź błędu przychodzi jako Blob (responseType: blob) — spróbuj odczytać treść.
    let msg = 'Nie udało się wygenerować grafiki.'
    try {
      const body = e?.response?._data
      const text = body instanceof Blob ? await body.text() : ''
      const json = text ? JSON.parse(text) : null
      msg = json?.errors?.openai?.[0] || json?.message || msg
    } catch {}
    posterError.value = msg + ' (Odświeżenie tła przez AI może potrwać kilkanaście sekund — wymaga klucza OpenAI i usługi Gotenberg.)'
  } finally {
    posterLoading.value = ''
  }
}

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

  <section v-else-if="offer" class="mx-auto max-w-4xl space-y-5 pb-8">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">{{ offer.title }}</h1>
        <p class="text-sm text-stone">
          {{ offer.company?.name }} ·
          <span :class="offer.status === 'open' ? 'text-brand-deep font-medium' : 'text-amber-600'">{{ offer.status_label }}</span>
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button class="inline-flex h-9 items-center gap-1.5 rounded-full bg-ink px-3.5 text-sm font-semibold text-white transition hover:bg-charcoal disabled:opacity-50" :disabled="referralLoading" @click="generateReferral">
          <AppIcon name="pdf" :size="16" /> {{ referralLoading ? 'Generowanie…' : 'Skierowanie PDF' }}
        </button>
        <button class="inline-flex h-9 items-center gap-1.5 rounded-full border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface disabled:opacity-50" :disabled="!!posterLoading" @click="generatePoster('feed')">
          <AppIcon name="camera" :size="16" /> {{ posterLoading === 'feed' ? 'Generuję…' : 'Grafika (post)' }}
        </button>
        <button class="inline-flex h-9 items-center gap-1.5 rounded-full border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface disabled:opacity-50" :disabled="!!posterLoading" @click="generatePoster('reels')">
          {{ posterLoading === 'reels' ? 'Generuję…' : 'Grafika (reels)' }}
        </button>
        <button class="inline-flex h-9 items-center gap-1.5 rounded-full border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface disabled:opacity-50" :disabled="!!posterLoading" title="Wygeneruj nowe tło przez AI" @click="generatePoster('feed', true)">
          {{ posterLoading === 'refresh' ? 'Odświeżam tło…' : 'Odśwież tło (AI)' }}
        </button>
        <NuxtLink :to="`/job-offers/${id}/edit`" class="inline-flex h-9 items-center gap-1.5 rounded-full border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface">
          Edytuj
        </NuxtLink>
        <button class="inline-flex h-9 items-center gap-1.5 rounded-full border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface" @click="toggleActive">
          {{ offer.status === 'open' ? 'Wstrzymaj' : 'Aktywuj' }}
        </button>
        <button v-if="auth.isAdmin" class="inline-flex h-9 items-center gap-1.5 rounded-full border border-red-200 px-3.5 text-sm font-medium text-red-600 transition hover:bg-red-50" @click="removeOffer">
          Usuń
        </button>
      </div>
    </div>

    <p v-if="posterError" class="-mt-2 mb-2 text-sm text-red-600">{{ posterError }}</p>

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
          ['Rodzaj umowy', offer.contract_type],
          ['Typ auta', offer.vehicle_type],
          ['Punkty dziennie', offer.points_per_day],
          ['Załadunek / rozładunek', offer.loading_info],
          ['Średni przebieg', offer.daily_km],
          ['Zakwaterowanie', offer.accommodation],
          ['Wynagrodzenie', offer.salary_amount ? `${offer.salary_amount} ${offer.currency || ''}` : null],
          ['Język', offer.required_language],
          ['Doświadczenie', offer.required_experience],
          ['Data przyjazdu', offer.arrival_info],
          ['Start', offer.start_date],
        ]" :key="row[0]">
          <template v-if="row[1]">
            <dt class="text-stone">{{ row[0] }}</dt>
            <dd class="font-medium text-ink">{{ row[1] }}</dd>
          </template>
        </template>
      </dl>
      <a v-if="offer.pdf_url" :href="offer.pdf_url" target="_blank" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-brand-deep">
        <AppIcon name="pdf" :size="15" /> PDF ogłoszenia
      </a>
    </div>

    <!-- Opis stanowiska -->
    <div v-if="offer.description" class="card p-4">
      <p class="mb-2 text-[13px] font-medium text-steel">Opis stanowiska</p>
      <p class="whitespace-pre-line text-sm text-charcoal">{{ offer.description }}</p>
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

    <!-- FAQ dla rekruterki -->
    <div v-if="offer.faq?.length" class="card p-4">
      <p class="mb-3 text-[13px] font-medium text-steel">FAQ — najczęstsze pytania kierowców</p>
      <ul class="space-y-3">
        <li v-for="(item, i) in offer.faq" :key="i">
          <p class="text-sm font-semibold text-ink">{{ item.q }}</p>
          <p v-if="item.a" class="text-sm text-steel">{{ item.a }}</p>
          <p v-else class="text-sm italic text-muted">— brak odpowiedzi —</p>
        </li>
      </ul>
    </div>

    <!-- Notatka rekruterki (wewnętrzna) -->
    <div v-if="offer.recruiter_notes" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
      <p class="mb-1 text-[13px] font-medium text-amber-800">Notatka dla rekruterki (wewnętrzna)</p>
      <p class="whitespace-pre-line text-sm text-amber-900">{{ offer.recruiter_notes }}</p>
    </div>
  </section>
</template>
