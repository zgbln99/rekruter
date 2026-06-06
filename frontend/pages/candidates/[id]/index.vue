<script setup lang="ts">
import { useQueryClient } from '@tanstack/vue-query'
import { DOCUMENT_TYPE_OPTIONS, type CandidateDocument, type DocumentType } from '~/types'

// Szczegóły kandydata: dane, zdjęcie, dokumenty, kontakty, profil PDF.
const route = useRoute()
const id = computed(() => route.params.id as string)
const { data: candidate, isLoading } = useCandidateQuery(id)
const { data: documents } = useDocumentsQuery(id)

const uploadDocument = useUploadDocument(id)
const uploadProfilePhoto = useUploadProfilePhoto(id)
const deleteDocument = useDeleteDocument(id)
const deleteCandidate = useDeleteCandidate()
const sendProfile = useSendProfile(id)

function removeDocument(docId: string) {
  if (confirm('Usunąć ten dokument? Plik zostanie skasowany ze storage.')) {
    deleteDocument.mutate(docId)
  }
}
async function removeCandidate() {
  if (!confirm('Usunąć kandydata wraz z dokumentami? Operacja nieodwracalna.')) return
  await deleteCandidate.mutateAsync(id.value)
  await navigateTo('/candidates')
}

// Faza 5: kompletność profilu + timeline.
const { data: completeness } = useCompletenessQuery(id)
const { data: timeline } = useTimelineQuery(id)

// Przypisanie do ogłoszenia (pipeline).
const queryClient = useQueryClient()
const { data: postingsData } = useJobOffersQuery()
const postings = computed(() => postingsData.value?.data ?? [])
const addApplication = useAddApplication()
const selectedPosting = ref('')
const applyMsg = ref('')
async function addToPipeline() {
  if (!selectedPosting.value) return
  applyMsg.value = ''
  try {
    await addApplication.mutateAsync({
      candidate_id: id.value,
      job_posting_id: selectedPosting.value,
    })
    applyMsg.value = 'Dodano do rekrutacji.'
    selectedPosting.value = ''
    // Odśwież profil, aby pojawiła się sekcja „W rekrutacjach".
    await queryClient.invalidateQueries({ queryKey: ['candidate', id.value] })
  } catch (e: any) {
    applyMsg.value =
      e?.response?._data?.errors?.candidate_id?.[0] || 'Nie udało się dodać.'
  }
}

// --- Skierowania (Placement): generowane z karty kierowcy ---
const { data: placements } = useCandidatePlacements(id)
const createPlacement = useCreatePlacement(id)
const updateArrival = useUpdateArrival()
const placementForm = reactive({
  job_posting_id: '',
  arrival_at: '',
})
const placementError = ref('')
const placementLoading = ref(false)

async function generatePlacement() {
  placementError.value = ''
  if (!placementForm.job_posting_id || !placementForm.arrival_at) {
    placementError.value = 'Wybierz ogłoszenie i podaj datę przyjazdu.'
    return
  }
  placementLoading.value = true
  try {
    // Kwota jest ustalona z góry w ustawieniach — nie wysyłamy jej z formularza.
    const placement = await createPlacement.mutateAsync({
      job_posting_id: placementForm.job_posting_id,
      arrival_at: placementForm.arrival_at,
    })
    // Od razu pobierz PDF skierowania.
    await downloadReferral(placement.id)
    placementForm.job_posting_id = ''
    placementForm.arrival_at = ''
  } catch (e: any) {
    placementError.value =
      e?.response?._data?.message || 'Nie udało się utworzyć skierowania.'
  } finally {
    placementLoading.value = false
  }
}

async function downloadReferral(placementId: string) {
  const blob = await fetchBlob(`/placements/${placementId}/referral-pdf`)
  const name = (candidate.value?.full_name || 'kierowca').replace(/\s+/g, '-').toLowerCase()
  openBlob(blob, `skierowanie-${name}.pdf`)
}

async function markArrival(placementId: string, status: 'confirmed' | 'no_show' | 'pending') {
  await updateArrival.mutateAsync({ placementId, status })
}

function fmtDateTime(iso?: string | null) {
  return iso ? new Date(iso).toLocaleString('pl-PL', { dateStyle: 'medium', timeStyle: 'short' }) : ''
}
function fmtDate(d?: string | null) {
  return d ? new Date(d).toLocaleDateString('pl-PL', { dateStyle: 'medium' }) : ''
}

// --- Upload dokumentu (plik / aparat) ---
const docType = ref<DocumentType>('cv')
const fileInput = ref<HTMLInputElement | null>(null)
const cameraInput = ref<HTMLInputElement | null>(null)

function pickDocument() {
  fileInput.value?.click()
}
function pickDocumentCamera() {
  cameraInput.value?.click()
}
async function onDocumentSelected(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) await uploadDocument.mutateAsync({ file, type: docType.value })
  ;(e.target as HTMLInputElement).value = ''
}

async function download(doc: CandidateDocument) {
  const blob = await fetchBlob(
    `/candidates/${id.value}/documents/${doc.id}/download`,
  )
  openBlob(blob, doc.original_name || 'dokument')
}

// --- Zdjęcie profilowe (Cropper) ---
const photoInput = ref<HTMLInputElement | null>(null)
const cropSrc = ref<string | null>(null)

function pickPhoto() {
  photoInput.value?.click()
}
function onPhotoSelected(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = () => (cropSrc.value = reader.result as string)
  reader.readAsDataURL(file)
  if (photoInput.value) photoInput.value.value = ''
}
async function onCropped(blob: Blob) {
  await uploadProfilePhoto.mutateAsync(blob)
  cropSrc.value = null
  // Natychmiastowy podgląd z właśnie wyciętego obrazu (bez czekania na refetch).
  photoUrl.value = URL.createObjectURL(blob)
}

// Podgląd aktualnego zdjęcia profilowego.
const photoUrl = ref<string | null>(null)
async function loadProfilePhoto() {
  const photo = documents.value?.find((d) => d.is_profile_photo)
  if (!photo) {
    photoUrl.value = null
    return
  }
  const blob = await fetchBlob(
    `/candidates/${id.value}/documents/${photo.id}/download`,
  )
  photoUrl.value = URL.createObjectURL(blob)
}
watch(documents, () => loadProfilePhoto())

// --- Profil PDF + wysyłka ---
const pdfLoading = ref(false)
const pdfError = ref('')
async function generatePdf() {
  pdfError.value = ''
  pdfLoading.value = true
  try {
    const blob = await fetchBlob(`/candidates/${id.value}/profile-pdf`)
    const name = (candidate.value?.full_name || 'profil').replace(/\s+/g, '-').toLowerCase()
    openBlob(blob, `profil-${name}.pdf`)
  } catch {
    pdfError.value = 'Nie udało się wygenerować PDF.'
  } finally {
    pdfLoading.value = false
  }
}

const showSend = ref(false)
const recipient = ref('')
const sendMsg = ref('')

// --- WhatsApp + szablony wiadomości ---
const { data: settings } = useSettingsQuery()
const templates = computed(() => settings.value?.message_templates ?? [])
const showWa = ref(false)
function openWhatsApp(body: string) {
  const c = candidate.value
  const text = fillTemplate(body, {
    imie: c?.first_name,
    nazwisko: c?.last_name,
    telefon: c?.phone,
    agencja: auth.user?.agency_name,
  })
  window.open(waLink(c?.phone, text), '_blank')
  showWa.value = false
}

// --- Łączenie duplikatów ---
const showMerge = ref(false)
const mergeQuery = ref('')
const { data: mergeResults } = useSearchQuery(mergeQuery)
const mergeCandidate = useMergeCandidate(id)
const mergeError = ref('')
const mergeCandidates = computed(() =>
  (mergeResults.value?.candidates ?? []).filter((c) => c.id !== id.value),
)
async function doMerge(sourceId: string, name: string) {
  if (!confirm(`Połączyć „${name}" z tym kandydatem? Dane i powiązania duplikatu przejdą tutaj, a duplikat zostanie usunięty.`)) return
  mergeError.value = ''
  try {
    await mergeCandidate.mutateAsync(sourceId)
    showMerge.value = false
    mergeQuery.value = ''
  } catch {
    mergeError.value = 'Nie udało się połączyć kandydatów.'
  }
}

// --- RODO ---
const auth = useAuthStore()
const api = useApi()
const router = useRouter()

async function exportData() {
  const data = await api(`/candidates/${id.value}/export`)
  const blob = new Blob([JSON.stringify(data, null, 2)], {
    type: 'application/json',
  })
  openBlob(blob, `rodo-${id.value}.json`)
}

async function forget() {
  if (!confirm('Trwale usunąć wszystkie dane tego kandydata? Operacja nieodwracalna.'))
    return
  await api(`/candidates/${id.value}/forget`, { method: 'DELETE' })
  await router.push('/candidates')
}
async function doSend() {
  sendMsg.value = ''
  try {
    await sendProfile.mutateAsync(recipient.value)
    sendMsg.value = 'Profil wysłany do ' + recipient.value
    showSend.value = false
    recipient.value = ''
  } catch {
    sendMsg.value = 'Nie udało się wysłać profilu.'
  }
}
</script>

<template>
  <section v-if="isLoading" class="py-10 text-center text-muted">Ładowanie…</section>

  <section v-else-if="candidate" class="mx-auto max-w-7xl pb-8">
    <!-- Nagłówek + zdjęcie -->
    <div class="mb-6 flex items-start gap-4">
      <button
        class="relative flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-ink text-xl font-bold text-white shadow-subtle"
        @click="pickPhoto"
      >
        <img v-if="photoUrl" :src="photoUrl" class="h-full w-full object-cover" alt="" />
        <span v-else>{{ candidate.first_name.charAt(0) }}</span>
        <span class="absolute -bottom-0 -right-0 flex h-6 w-6 items-center justify-center rounded-full bg-brand text-white ring-2 ring-canvas">
          <AppIcon name="camera" :size="13" />
        </span>
      </button>
      <div class="min-w-0 flex-1">
        <h1 class="truncate text-2xl font-bold tracking-tight text-ink">{{ candidate.full_name }}</h1>
        <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1">
          <a :href="`tel:${candidate.phone}`" class="inline-flex items-center gap-1.5 font-medium text-brand-deep">
            <AppIcon name="phone" :size="15" /> {{ candidate.phone }}
          </a>
          <!-- WhatsApp + szablony -->
          <div class="relative">
            <button
              class="inline-flex h-8 items-center gap-1.5 rounded-full bg-emerald-600 px-3 text-xs font-semibold text-white transition hover:bg-emerald-700"
              @click="showWa = !showWa"
            >
              <AppIcon name="chat" :size="15" /> WhatsApp
            </button>
            <div v-if="showWa" class="absolute left-0 top-9 z-30 w-72 overflow-hidden rounded-xl border border-hairline bg-canvas shadow-lg">
              <button
                class="block w-full px-3.5 py-2.5 text-left text-sm text-ink transition hover:bg-surface"
                @click="openWhatsApp('')"
              >
                Otwórz czat (bez treści)
              </button>
              <div class="border-t border-hairline" />
              <button
                v-for="(t, i) in templates"
                :key="i"
                class="block w-full px-3.5 py-2.5 text-left transition hover:bg-surface"
                @click="openWhatsApp(t.body)"
              >
                <span class="block text-sm font-medium text-ink">{{ t.name || 'Szablon' }}</span>
                <span class="line-clamp-2 block text-xs text-stone">{{ t.body }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
      <span class="badge badge-neutral shrink-0">{{ candidate.status_label }}</span>
      <NuxtLink
        :to="`/candidates/${id}/edit`"
        class="inline-flex h-9 items-center gap-1.5 rounded-full border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface"
      >
        Edytuj
      </NuxtLink>
    </div>
    <input ref="photoInput" type="file" accept="image/*" capture="user" class="hidden" @change="onPhotoSelected" />

    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
      <!-- LEWA KOLUMNA -->
      <div class="space-y-5 lg:col-span-2">
        <!-- Dane osobowe -->
        <div class="card p-4">
          <p class="mb-2.5 text-[13px] font-medium text-steel">Dane osobowe</p>
          <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <template v-for="row in [
              ['E-mail', candidate.email],
              ['Adres', candidate.address],
              ['Data urodzenia', candidate.date_of_birth],
              ['Narodowość', candidate.nationality],
              ['Miejscowość', candidate.city],
              ['Kraj', candidate.country],
              ['Dostępność od', candidate.availability_from],
            ]" :key="row[0]">
              <template v-if="row[1]">
                <dt class="text-stone">{{ row[0] }}</dt>
                <dd class="font-medium text-ink">{{ row[1] }}</dd>
              </template>
            </template>
          </dl>
        </div>

        <!-- Uprawnienia i doświadczenie -->
        <div class="card p-4">
          <p class="mb-2.5 text-[13px] font-medium text-steel">Uprawnienia i doświadczenie</p>
          <div class="flex flex-wrap gap-2">
            <span v-for="cat in candidate.license_categories" :key="cat" class="badge badge-neutral">{{ cat }}</span>
            <span v-if="candidate.has_adr" class="badge bg-amber-50 text-amber-700">ADR</span>
            <span v-if="candidate.has_code_95" class="badge badge-accent">Kod 95</span>
            <span v-if="candidate.has_hds" class="badge badge-neutral">HDS</span>
            <span v-if="candidate.exp_reefer" class="badge badge-neutral">chłodnia</span>
            <span v-if="candidate.exp_tilt" class="badge badge-neutral">plandeka</span>
            <span v-if="candidate.exp_international" class="badge badge-neutral">międzynarodowe</span>
            <span v-if="candidate.lang_de" class="badge badge-neutral">DE</span>
            <span v-if="candidate.lang_en" class="badge badge-neutral">EN</span>
          </div>
          <p v-if="candidate.experience_notes" class="mt-2 text-sm text-steel">{{ candidate.experience_notes }}</p>
        </div>

        <!-- Historia pracy -->
        <div v-if="candidate.work_history?.length" class="card p-4">
          <p class="mb-2.5 text-[13px] font-medium text-steel">Historia pracy</p>
          <ul class="space-y-2.5">
            <li v-for="(job, i) in candidate.work_history" :key="i" class="border-l-2 border-hairline pl-3">
              <p class="text-sm font-semibold text-ink">{{ job.employer }}<span v-if="job.position"> — {{ job.position }}</span></p>
              <p class="text-xs text-stone">{{ job.period }}<span v-if="job.description"> · {{ job.description }}</span></p>
            </li>
          </ul>
        </div>

        <!-- Profil PDF / wysyłka -->
        <div class="flex gap-2.5">
          <button class="inline-flex h-11 flex-1 items-center justify-center gap-1.5 rounded-full bg-ink text-sm font-semibold text-white transition active:scale-[0.98] disabled:opacity-50" :disabled="pdfLoading" @click="generatePdf">
            <AppIcon name="pdf" :size="18" /> {{ pdfLoading ? 'Generowanie…' : 'Generuj PDF' }}
          </button>
          <button class="inline-flex h-11 flex-1 items-center justify-center gap-1.5 rounded-full border border-hairline bg-canvas text-sm font-semibold text-ink transition active:bg-surface" @click="showSend = !showSend">
            <AppIcon name="mail" :size="18" /> Wyślij profil
          </button>
        </div>
        <p v-if="pdfError" class="text-sm text-red-600">{{ pdfError }}</p>
        <div v-if="showSend" class="card p-4">
          <input v-model="recipient" type="email" placeholder="email@klienta.pl" class="input-field mb-2.5" />
          <button class="btn-primary" :disabled="sendProfile.isPending.value" @click="doSend">
            {{ sendProfile.isPending.value ? 'Wysyłanie…' : 'Wyślij' }}
          </button>
        </div>
        <p v-if="sendMsg" class="text-sm text-brand-deep">{{ sendMsg }}</p>

        <!-- Rekrutacje (pipeline) -->
        <div v-if="postings.length" class="card p-4">
          <p class="mb-2.5 text-[13px] font-medium text-steel">Dodaj do rekrutacji</p>
          <div class="flex gap-2">
            <select v-model="selectedPosting" class="input-field flex-1">
              <option value="">Wybierz ogłoszenie…</option>
              <option v-for="p in postings" :key="p.id" :value="p.id">
                {{ p.title }} — {{ p.company?.name }}
              </option>
            </select>
            <button
              class="btn-sm shrink-0 px-5"
              :disabled="!selectedPosting || addApplication.isPending.value"
              @click="addToPipeline"
            >
              Dodaj
            </button>
          </div>
          <p v-if="applyMsg" class="mt-2 text-sm text-brand-deep">{{ applyMsg }}</p>
        </div>

        <!-- Skierowania do pracy -->
        <div class="card p-4">
          <div class="mb-3 flex items-center gap-2">
            <AppIcon name="calendar" :size="18" class="text-brand-deep" />
            <h2 class="text-lg font-semibold text-ink">Skierowania do pracy</h2>
          </div>

          <!-- Formularz generowania -->
          <div class="rounded-xl border border-hairline bg-surface-soft p-3.5">
            <div class="grid gap-3 sm:grid-cols-2">
              <div class="sm:col-span-2">
                <label class="field-label">Ogłoszenie</label>
                <select v-model="placementForm.job_posting_id" class="input-field">
                  <option value="">Wybierz ogłoszenie…</option>
                  <option v-for="p in postings" :key="p.id" :value="p.id">
                    {{ p.title }} — {{ p.company?.name }}
                  </option>
                </select>
              </div>
              <div class="sm:col-span-2">
                <label class="field-label">Data i godzina przyjazdu</label>
                <input v-model="placementForm.arrival_at" type="datetime-local" class="input-field" />
              </div>
            </div>
            <p class="mt-2 text-xs text-stone">
              Termin przyjazdu trafi do kalendarza, gdzie potwierdzisz, czy kierowca dotarł.<template v-if="auth.isAdmin"> Rozliczenie (stała kwota z ustawień) zostanie podzielone na 2 raty: +14 i +28 dni od przyjazdu.</template>
            </p>
            <button
              class="btn-primary mt-3 w-full"
              :disabled="placementLoading"
              @click="generatePlacement"
            >
              <AppIcon name="pdf" :size="18" />
              {{ placementLoading ? 'Generowanie…' : 'Generuj skierowanie (PDF)' }}
            </button>
            <p v-if="placementError" class="mt-2 text-sm text-red-600">{{ placementError }}</p>
          </div>

          <!-- Lista skierowań -->
          <ul v-if="placements?.length" class="mt-3 space-y-2.5">
            <li v-for="pl in placements" :key="pl.id" class="rounded-xl border border-hairline p-3.5">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-ink">{{ pl.job_posting?.title || 'Ogłoszenie' }}</p>
                  <p class="text-xs text-stone">
                    Przyjazd: <span class="font-medium text-ink">{{ fmtDateTime(pl.arrival_at) }}</span>
                  </p>
                </div>
                <span
                  class="badge shrink-0"
                  :style="{ backgroundColor: pl.arrival_status_color + '1a', color: pl.arrival_status_color }"
                >{{ pl.arrival_status_label }}</span>
              </div>

              <!-- Raty rozliczenia (tylko administrator) -->
              <div v-if="auth.isAdmin && pl.installments?.length" class="mt-2.5 flex flex-wrap gap-1.5">
                <span
                  v-for="inst in pl.installments"
                  :key="inst.id"
                  class="inline-flex items-center gap-1 rounded-full border border-hairline px-2.5 py-1 text-xs"
                  :style="{ color: inst.status_color }"
                >
                  <AppIcon name="cash" :size="13" />
                  Rata {{ inst.sequence }}/2 · {{ fmtDate(inst.due_date) }}<template v-if="inst.amount"> · {{ inst.amount }} {{ pl.currency }}</template>
                </span>
              </div>

              <!-- Akcje -->
              <div class="mt-3 flex flex-wrap items-center gap-2">
                <button
                  class="inline-flex h-8 items-center gap-1 rounded-full bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                  @click="markArrival(pl.id, 'confirmed')"
                >
                  <AppIcon name="check" :size="14" /> Dotarł
                </button>
                <button
                  class="inline-flex h-8 items-center gap-1 rounded-full bg-red-50 px-3 text-xs font-semibold text-red-600 transition hover:bg-red-100"
                  @click="markArrival(pl.id, 'no_show')"
                >
                  <AppIcon name="x" :size="14" /> Nie dotarł
                </button>
                <button
                  class="ml-auto inline-flex h-8 items-center gap-1 rounded-full border border-hairline px-3 text-xs font-medium text-ink transition hover:bg-surface"
                  @click="downloadReferral(pl.id)"
                >
                  <AppIcon name="download" :size="14" /> PDF
                </button>
              </div>
            </li>
          </ul>
          <p v-else class="mt-3 text-sm text-muted">Brak skierowań. Wygeneruj pierwsze powyżej.</p>
        </div>

        <!-- Dokumenty -->
        <div>
          <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-ink">Dokumenty</h2>
            <div class="flex items-center gap-2">
              <select v-model="docType" class="h-9 rounded-md border border-hairline bg-canvas px-2 text-sm text-ink">
                <option v-for="opt in DOCUMENT_TYPE_OPTIONS" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
              <button class="btn-sm" @click="pickDocument">
                <AppIcon name="plus" :size="16" /> Plik
              </button>
              <button
                class="inline-flex h-9 items-center justify-center gap-1 rounded-full border border-hairline px-3 text-sm font-medium text-ink"
                @click="pickDocumentCamera"
              >
                <AppIcon name="camera" :size="16" /> Aparat
              </button>
            </div>
          </div>
          <input ref="fileInput" type="file" accept="image/*,application/pdf" class="hidden" @change="onDocumentSelected" />
          <input ref="cameraInput" type="file" accept="image/*" capture="environment" class="hidden" @change="onDocumentSelected" />

          <ul v-if="documents?.length" class="space-y-2">
            <li v-for="doc in documents" :key="doc.id" class="card flex items-center justify-between p-3.5">
              <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-md bg-surface text-stone">
                  <AppIcon name="document" :size="18" />
                </span>
                <div class="min-w-0">
                  <p class="text-sm font-medium text-ink">{{ doc.type_label }}</p>
                  <p class="truncate text-xs text-stone">{{ doc.original_name }}</p>
                </div>
              </div>
              <div class="flex shrink-0 items-center gap-1">
                <button class="flex h-9 w-9 items-center justify-center rounded-full text-steel transition hover:bg-surface" title="Pobierz" @click="download(doc)">
                  <AppIcon name="download" :size="18" />
                </button>
                <button class="flex h-9 w-9 items-center justify-center rounded-full text-red-500 transition hover:bg-red-50" title="Usuń" @click="removeDocument(doc.id)">
                  <AppIcon name="x" :size="18" />
                </button>
              </div>
            </li>
          </ul>
          <p v-else class="text-sm text-muted">Brak dokumentów.</p>
        </div>

        <!-- Historia kontaktów -->
        <div>
          <h2 class="mb-3 text-lg font-semibold text-ink">Historia kontaktów</h2>
          <ul v-if="candidate.contact_logs?.length" class="space-y-2">
            <li v-for="log in candidate.contact_logs" :key="log.id" class="card p-3.5">
              <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-ink">{{ log.outcome_label }}</span>
                <span class="badge badge-neutral">{{ log.channel_label }}</span>
              </div>
              <p v-if="log.note" class="mt-1.5 text-sm text-steel">{{ log.note }}</p>
            </li>
          </ul>
          <p v-else class="text-sm text-muted">Brak zapisanych kontaktów.</p>
        </div>
      </div>

      <!-- PRAWA KOLUMNA (panel) -->
      <div class="space-y-5 lg:sticky lg:top-20">
        <!-- Kompletność profilu -->
        <div v-if="completeness" class="card p-4">
          <div class="mb-2 flex items-center justify-between">
            <p class="text-[13px] font-medium text-steel">Kompletność profilu</p>
            <span class="text-sm font-semibold" :class="completeness.complete ? 'text-brand-deep' : 'text-amber-600'">
              {{ completeness.percent }}%
            </span>
          </div>
          <div class="mb-3 h-2 overflow-hidden rounded-full bg-surface">
            <div class="h-full rounded-full bg-brand transition-all duration-500" :style="{ width: completeness.percent + '%' }" />
          </div>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="item in completeness.items"
              :key="item.key"
              class="badge"
              :class="item.done ? 'badge-accent' : 'bg-surface text-stone'"
            >{{ item.label }}</span>
          </div>
          <p v-if="completeness.missing.length" class="mt-2 text-sm text-amber-700">
            Braki do wysłania: {{ completeness.missing.join(', ') }}
          </p>
        </div>

        <!-- Status w ogłoszeniach -->
        <div v-if="candidate.applications?.length" class="card p-4">
          <p class="mb-2.5 text-[13px] font-medium text-steel">W rekrutacjach</p>
          <ul class="space-y-2">
            <li v-for="app in candidate.applications" :key="app.id" class="flex items-center justify-between gap-2">
              <NuxtLink :to="`/job-offers/${app.job_posting_id}`" class="min-w-0 truncate text-sm font-medium text-ink hover:text-brand-deep">
                {{ app.job_posting?.title || 'Ogłoszenie' }}
              </NuxtLink>
              <span class="badge badge-neutral shrink-0">{{ app.status_label }}</span>
            </li>
          </ul>
        </div>

        <!-- Timeline -->
        <div v-if="timeline?.length" class="card p-4">
          <p class="mb-3 text-[13px] font-medium text-steel">Historia (timeline)</p>
          <ul class="space-y-3 border-l border-hairline pl-4">
            <li v-for="(ev, i) in timeline" :key="i" class="relative">
              <span class="absolute -left-[1.32rem] top-1.5 h-2 w-2 rounded-full bg-brand" />
              <p class="text-sm font-medium text-ink">{{ ev.label }}</p>
              <p class="text-xs text-stone">
                {{ ev.at ? new Date(ev.at).toLocaleString('pl-PL') : '' }}<span v-if="ev.by"> · {{ ev.by }}</span>
              </p>
            </li>
          </ul>
        </div>

        <!-- Łączenie duplikatów -->
        <div class="card p-4">
          <div class="flex items-center justify-between">
            <p class="inline-flex items-center gap-1.5 text-[13px] font-medium text-steel">
              <AppIcon name="merge" :size="15" /> Duplikat
            </p>
            <button class="text-sm font-medium text-brand-deep" @click="showMerge = !showMerge">
              {{ showMerge ? 'Anuluj' : 'Połącz duplikat' }}
            </button>
          </div>
          <div v-if="showMerge" class="mt-3">
            <p class="mb-2 text-xs text-stone">Znajdź drugą kartę tego samego kierowcy — jej dane i powiązania przejdą tutaj, a duplikat zostanie usunięty.</p>
            <input v-model="mergeQuery" placeholder="Szukaj po nazwisku / telefonie…" class="input-field" />
            <ul v-if="mergeCandidates.length" class="mt-2 space-y-1">
              <li v-for="c in mergeCandidates" :key="c.id">
                <button
                  class="flex w-full items-center justify-between gap-2 rounded-lg border border-hairline px-3 py-2 text-left text-sm transition hover:bg-surface"
                  :disabled="mergeCandidate.isPending.value"
                  @click="doMerge(c.id, c.full_name)"
                >
                  <span class="min-w-0">
                    <span class="block truncate font-medium text-ink">{{ c.full_name }}</span>
                    <span class="block truncate text-xs text-stone">{{ c.phone }}</span>
                  </span>
                  <AppIcon name="merge" :size="16" class="shrink-0 text-stone" />
                </button>
              </li>
            </ul>
            <p v-else-if="mergeQuery.trim().length >= 2" class="mt-2 text-sm text-muted">Brak innych kandydatów.</p>
            <p v-if="mergeError" class="mt-2 text-sm text-red-600">{{ mergeError }}</p>
          </div>
        </div>

        <!-- RODO -->
        <div class="card p-4">
          <p class="mb-2.5 inline-flex items-center gap-1.5 text-[13px] font-medium text-steel">
            <AppIcon name="shield" :size="15" /> RODO
          </p>
          <div class="flex flex-wrap gap-2">
            <button class="inline-flex items-center gap-1.5 rounded-full border border-hairline px-3.5 py-2 text-sm font-medium text-ink transition hover:bg-surface" @click="exportData">
              <AppIcon name="download" :size="16" /> Eksport
            </button>
            <button
              class="inline-flex items-center gap-1.5 rounded-full border border-red-200 px-3.5 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"
              @click="removeCandidate"
            >
              <AppIcon name="x" :size="16" /> Usuń kandydata
            </button>
            <button
              v-if="auth.isAdmin"
              class="inline-flex items-center gap-1.5 rounded-full border border-red-300 px-3.5 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50"
              @click="forget"
            >
              Usuń trwale (RODO)
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cropper -->
    <CropperModal
      v-if="cropSrc"
      :src="cropSrc"
      @cropped="onCropped"
      @close="cropSrc = null"
    />
  </section>
</template>
