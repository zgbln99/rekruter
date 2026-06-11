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
const duplicateOffer = useDuplicateJobOffer()
const auth = useAuthStore()
const toast = useToast()

// Duplikacja ogłoszenia → otwórz edycję kopii.
const duplicating = ref(false)
async function duplicate() {
  duplicating.value = true
  try {
    const copy = await duplicateOffer.mutateAsync(id.value)
    await router.push(`/job-offers/${copy.id}/edit`)
  } finally {
    duplicating.value = false
  }
}

// Aktywuj / wstrzymaj ogłoszenie.
async function toggleActive() {
  const next = offer.value?.status === 'open' ? 'paused' : 'open'
  await updateOffer.mutateAsync({ status: next } as any)
}

const publishing = ref(false)
async function togglePublic() {
  const next = !offer.value?.is_public
  publishing.value = true
  try {
    await updateOffer.mutateAsync({ is_public: next } as any)
    toast.success(next ? 'Oferta opublikowana na stronie.' : 'Oferta ukryta ze strony.')
  } catch {
    toast.error('Nie udało się zmienić widoczności.')
  } finally {
    publishing.value = false
  }
}

const featuring = ref(false)
async function toggleFeatured() {
  const next = !offer.value?.is_featured
  featuring.value = true
  try {
    await updateOffer.mutateAsync({ is_featured: next } as any)
    toast.success(next ? 'Oferta promowana - będzie wyżej na liście.' : 'Wyłączono promowanie oferty.')
  } catch {
    toast.error('Nie udało się zmienić promowania.')
  } finally {
    featuring.value = false
  }
}

const archiveOffer = useArchiveJobOffer(id)
const archiving = ref(false)
async function toggleArchive() {
  const isArch = !!offer.value?.archived
  if (!isArch && !confirm('Zarchiwizować ofertę? Zniknie z listy i ze strony publicznej (można ją przywrócić).')) return
  archiving.value = true
  try {
    await archiveOffer.mutateAsync(!isArch)
    toast.success(isArch ? 'Przywrócono ofertę z archiwum.' : 'Oferta zarchiwizowana.')
  } catch {
    toast.error('Nie udało się zmienić archiwizacji.')
  } finally {
    archiving.value = false
  }
}

// Zdjęcie okładkowe (europejska ciężarówka z Unsplash).
const fetchCover = useFetchOfferCover(id)
const coverLoading = ref(false)
async function randomCover() {
  coverLoading.value = true
  try {
    await fetchCover.mutateAsync()
  } finally {
    coverLoading.value = false
  }
}
async function removeCover() {
  coverLoading.value = true
  try {
    await updateOffer.mutateAsync({ cover_image_url: null } as any)
  } finally {
    coverLoading.value = false
  }
}
function copyPublicUrl() {
  if (offer.value?.public_url) navigator.clipboard?.writeText(offer.value.public_url)
}
async function removeOffer() {
  if (!confirm('Usunąć to ogłoszenie? Operacja nieodwracalna.')) return
  await deleteOffer.mutateAsync(id.value)
  await navigateTo('/job-offers')
}

// Skierowanie do pracy (PDF dla kierowcy) — najpierw modal do uzupełnienia danych.
const referralLoading = ref(false)
const showReferral = ref(false)
const REFERRAL_LANGS = [
  { value: 'pl', label: 'Polski' },
  { value: 'uk', label: 'Ukraiński' },
  { value: 'ru', label: 'Rosyjski' },
  { value: 'en', label: 'Angielski' },
  { value: 'de', label: 'Niemiecki' },
]
const referralForm = reactive<Record<string, string>>({
  candidate_name: '', arrival_at: '', lang: 'pl',
  title: '', region_base: '', country: '', work_system: '', vehicle_type: '',
  trailer_type: '', routes_info: '', cargo: '', points_per_day: '', loading_info: '',
  daily_km: '', accommodation: '', contract_type: '', salary_amount: '', currency: '',
  required_language: '', onsite_contact: '', public_description: '',
})

function openReferral() {
  // Prefill z ogłoszenia — recepcjonistka tylko uzupełnia/poprawia.
  const o: any = offer.value || {}
  for (const k of Object.keys(referralForm)) {
    if (['candidate_name', 'arrival_at', 'lang'].includes(k)) continue
    referralForm[k] = (o[k] ?? '') as string
  }
  referralForm.candidate_name = ''
  referralForm.arrival_at = ''
  showReferral.value = true
}

async function generateReferral() {
  referralLoading.value = true
  try {
    const body: Record<string, string> = {}
    for (const [k, v] of Object.entries(referralForm)) {
      if (v != null && String(v).trim() !== '') body[k] = v
    }
    const blob = await fetchBlob(`/job-offers/${id.value}/referral-pdf`, {
      method: 'POST',
      body,
    })
    openBlob(blob, `skierowanie-${(offer.value?.title || 'oferta').replace(/\s+/g, '-').toLowerCase()}.pdf`)
    showReferral.value = false
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
  await navigator.clipboard.writeText(htmlToText(offer.value.public_description))
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
  <UiSkeletonDetail v-if="isLoading" />

  <section v-else-if="offer" class="mx-auto space-y-5 pb-8">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <span v-if="offer.internal_ref" class="mb-1 inline-block rounded-md bg-surface px-2 py-0.5 font-mono text-xs text-steel">{{ offer.internal_ref }}</span>
        <h1 class="text-2xl font-bold tracking-tight text-ink">{{ offer.title }}</h1>
        <p class="text-sm text-stone">
          {{ offer.company?.name }} ·
          <span :class="offer.status === 'open' ? 'text-brand-deep font-medium' : 'text-amber-600'">{{ offer.status_label }}</span>
          <span v-if="offer.archived" class="ml-1.5 rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700">Zarchiwizowana</span>
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-ink px-3.5 text-sm font-semibold text-white transition hover:bg-charcoal disabled:opacity-50" :disabled="referralLoading" @click="openReferral">
          <AppIcon name="pdf" :size="16" /> Skierowanie PDF
        </button>
        <button class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface disabled:opacity-50" :disabled="!!posterLoading" @click="generatePoster('feed')">
          <AppIcon name="camera" :size="16" /> {{ posterLoading === 'feed' ? 'Generuję…' : 'Grafika (post)' }}
        </button>
        <button class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface disabled:opacity-50" :disabled="!!posterLoading" @click="generatePoster('reels')">
          {{ posterLoading === 'reels' ? 'Generuję…' : 'Grafika (reels)' }}
        </button>
        <button class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface disabled:opacity-50" :disabled="!!posterLoading" title="Wygeneruj nowe tło przez AI" @click="generatePoster('feed', true)">
          {{ posterLoading === 'refresh' ? 'Odświeżam tło…' : 'Odśwież tło (AI)' }}
        </button>
        <NuxtLink :to="`/job-offers/${id}/edit`" class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface">
          Edytuj
        </NuxtLink>
        <button class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface disabled:opacity-50" :disabled="duplicating" @click="duplicate">
          <AppIcon name="document" :size="16" /> {{ duplicating ? 'Duplikuję…' : 'Duplikuj' }}
        </button>
        <button class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface" @click="toggleActive">
          {{ offer.status === 'open' ? 'Wstrzymaj' : 'Aktywuj' }}
        </button>
        <button class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface disabled:opacity-50" :disabled="archiving" @click="toggleArchive">
          {{ offer.archived ? 'Przywróć z archiwum' : 'Archiwizuj' }}
        </button>
        <button v-if="auth.isAdmin" class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-red-200 px-3.5 text-sm font-medium text-red-600 transition hover:bg-red-50" @click="removeOffer">
          Usuń
        </button>
      </div>
    </div>

    <!-- Publikacja na stronie kariery -->
    <div class="card flex flex-wrap items-center gap-3 p-4">
      <span
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
        :class="offer.is_public ? 'bg-emerald-50 text-emerald-600' : 'bg-surface text-stone'"
      >
        <AppIcon name="document" :size="20" />
      </span>
      <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold text-ink">
          {{ offer.is_public ? 'Opublikowane na stronie kariery' : 'Niewidoczne publicznie' }}
        </p>
        <p v-if="offer.is_public && offer.public_url" class="truncate text-xs text-stone">
          <a :href="offer.public_url" target="_blank" class="text-brand-deep hover:underline">{{ offer.public_url }}</a>
        </p>
        <p v-else class="text-xs text-stone">Włącz, aby kierowcy zobaczyli tę ofertę na publicznej stronie.</p>
      </div>

      <div class="flex items-center gap-2">
        <a
          v-if="offer.is_public && offer.public_url"
          :href="offer.public_url"
          target="_blank"
          class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface"
        >
          Podgląd ↗
        </a>
        <button
          v-if="offer.is_public && offer.public_url"
          class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface"
          @click="copyPublicUrl"
        >
          Kopiuj link
        </button>
        <!-- Przełącznik -->
        <button
          class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition disabled:opacity-50"
          :class="offer.is_public ? 'bg-emerald-500' : 'bg-hairline'"
          :disabled="publishing"
          :title="offer.is_public ? 'Wyłącz publikację' : 'Opublikuj'"
          @click="togglePublic"
        >
          <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition" :class="offer.is_public ? 'translate-x-6' : 'translate-x-1'" />
        </button>
      </div>
    </div>

    <!-- Promowanie (oferta wyżej na stronie głównej; bez etykiety publicznie) -->
    <div class="card flex flex-wrap items-center gap-3 p-4">
      <span
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
        :class="offer.is_featured ? 'bg-amber-50 text-amber-500' : 'bg-surface text-stone'"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" :fill="offer.is_featured ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M12 2l2.9 6 6.6.6-5 4.3 1.5 6.5L12 16.9 5.9 19.4 7.4 12.9 2.4 8.6 9 8z"/></svg>
      </span>
      <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold text-ink">{{ offer.is_featured ? 'Oferta promowana' : 'Oferta zwykła' }}</p>
        <p class="text-xs text-stone">Promowane oferty wyświetlają się wyżej na stronie głównej (bez żadnej etykiety dla kierowców).</p>
      </div>
      <button
        class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition disabled:opacity-50"
        :class="offer.is_featured ? 'bg-amber-500' : 'bg-hairline'"
        :disabled="featuring"
        :title="offer.is_featured ? 'Wyłącz promowanie' : 'Promuj ofertę'"
        @click="toggleFeatured"
      >
        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition" :class="offer.is_featured ? 'translate-x-6' : 'translate-x-1'" />
      </button>
    </div>

    <!-- Zdjęcie ogłoszenia (okładka na stronie kariery) -->
    <div class="card overflow-hidden">
      <div
        class="relative h-40 bg-gradient-to-br from-slate-800 to-ink"
        :style="offer.cover_image_url ? `background-image:url('${offer.cover_image_url}');background-size:cover;background-position:center` : ''"
      >
        <div v-if="!offer.cover_image_url" class="flex h-full items-center justify-center text-white/60">
          <div class="text-center">
            <AppIcon name="truck" :size="28" class="mx-auto" />
            <p class="mt-1.5 text-xs">Brak zdjęcia — użyte zostanie domyślne</p>
          </div>
        </div>
      </div>
      <div class="flex flex-wrap items-center justify-between gap-2 p-4">
        <div class="min-w-0">
          <p class="text-sm font-semibold text-ink">Zdjęcie ogłoszenia</p>
          <p class="text-xs text-stone">Europejska ciężarówka z Unsplash — pokazywana na stronie kariery.</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-ink px-3.5 text-sm font-semibold text-white transition hover:bg-charcoal disabled:opacity-50"
            :disabled="coverLoading"
            @click="randomCover"
          >
            <AppIcon name="camera" :size="16" /> {{ coverLoading ? 'Pobieram…' : (offer.cover_image_url ? 'Zmień zdjęcie' : 'Pobierz zdjęcie') }}
          </button>
          <button
            v-if="offer.cover_image_url"
            class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface disabled:opacity-50"
            :disabled="coverLoading"
            @click="removeCover"
          >
            Usuń
          </button>
        </div>
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

    <!-- Karty z danymi — 2 kolumny na desktopie, pełna szerokość -->
    <div class="lg:columns-2 lg:gap-5 [&>*]:mb-5 [&>*]:break-inside-avoid">
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
      <p class="mb-2.5 text-[13px] font-medium text-steel">Opis stanowiska</p>
      <UiRichText :text="offer.description" />
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
      <UiRichText :text="offer.public_description" />
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
      <UiRichText :text="offer.recruiter_notes" size="sm" />
    </div>
    </div>

    <!-- Modal: uzupełnienie danych przed wygenerowaniem skierowania -->
    <div v-if="showReferral" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @click.self="showReferral = false">
      <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-canvas shadow-xl sm:rounded-2xl">
        <div class="flex items-center justify-between border-b border-hairline px-5 py-3.5">
          <h2 class="text-base font-semibold text-ink">Skierowanie do pracy — uzupełnij dane</h2>
          <button class="flex h-8 w-8 items-center justify-center rounded-full text-stone hover:bg-surface" @click="showReferral = false">
            <AppIcon name="x" :size="18" />
          </button>
        </div>

        <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
          <div class="grid gap-3 sm:grid-cols-2">
            <div>
              <label class="field-label">Imię i nazwisko kierowcy</label>
              <input v-model="referralForm.candidate_name" placeholder="np. Jan Kowalski" class="input-field" />
            </div>
            <div>
              <label class="field-label">Data i godzina przyjazdu</label>
              <input v-model="referralForm.arrival_at" type="datetime-local" class="input-field" />
            </div>
            <div class="sm:col-span-2">
              <label class="field-label">Język dokumentu</label>
              <select v-model="referralForm.lang" class="input-field">
                <option v-for="l in REFERRAL_LANGS" :key="l.value" :value="l.value">{{ l.label }}</option>
              </select>
            </div>
            <div class="sm:col-span-2">
              <label class="field-label">Stanowisko</label>
              <input v-model="referralForm.title" class="input-field" />
            </div>
            <div><label class="field-label">Kraj</label><input v-model="referralForm.country" class="input-field" /></div>
            <div><label class="field-label">Region / baza</label><input v-model="referralForm.region_base" class="input-field" /></div>
            <div><label class="field-label">System pracy</label><input v-model="referralForm.work_system" class="input-field" /></div>
            <div><label class="field-label">Typ auta</label><input v-model="referralForm.vehicle_type" class="input-field" /></div>
            <div><label class="field-label">Rodzaj umowy</label><input v-model="referralForm.contract_type" class="input-field" /></div>
            <div class="grid grid-cols-2 gap-2">
              <div><label class="field-label">Wynagrodzenie</label><input v-model="referralForm.salary_amount" class="input-field" /></div>
              <div><label class="field-label">Waluta</label><input v-model="referralForm.currency" class="input-field" /></div>
            </div>
            <div><label class="field-label">Przewożony towar</label><input v-model="referralForm.cargo" class="input-field" /></div>
            <div><label class="field-label">Punktów dziennie</label><input v-model="referralForm.points_per_day" class="input-field" /></div>
            <div><label class="field-label">Średni przebieg</label><input v-model="referralForm.daily_km" class="input-field" /></div>
            <div><label class="field-label">Załadunek / rozładunek</label><input v-model="referralForm.loading_info" class="input-field" /></div>
            <div><label class="field-label">Wymagany język</label><input v-model="referralForm.required_language" class="input-field" /></div>
          </div>
          <div><label class="field-label">Trasy</label><textarea v-model="referralForm.routes_info" rows="2" class="input-field !h-auto py-2.5" /></div>
          <div><label class="field-label">Zakwaterowanie</label><textarea v-model="referralForm.accommodation" rows="2" class="input-field !h-auto py-2.5" /></div>
          <div><label class="field-label">Osoba kontaktowa na miejscu</label><textarea v-model="referralForm.onsite_contact" rows="2" class="input-field !h-auto py-2.5" /></div>
          <div><label class="field-label">Dodatkowe informacje</label><textarea v-model="referralForm.public_description" rows="3" class="input-field !h-auto py-2.5" /></div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-hairline px-5 py-3.5">
          <button class="rounded-xl border border-hairline px-4 py-2 text-sm font-medium text-ink hover:bg-surface" @click="showReferral = false">Anuluj</button>
          <button class="btn-primary !w-auto" :disabled="referralLoading" @click="generateReferral">
            <AppIcon name="pdf" :size="18" /> {{ referralLoading ? 'Generowanie…' : 'Generuj PDF' }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
