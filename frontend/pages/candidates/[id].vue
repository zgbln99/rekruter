<script setup lang="ts">
import { DOCUMENT_TYPE_OPTIONS, type CandidateDocument, type DocumentType } from '~/types'

// Szczegóły kandydata: dane, zdjęcie, dokumenty, kontakty, profil PDF.
const route = useRoute()
const id = computed(() => route.params.id as string)
const { data: candidate, isLoading } = useCandidateQuery(id)
const { data: documents } = useDocumentsQuery(id)

const uploadDocument = useUploadDocument(id)
const uploadProfilePhoto = useUploadProfilePhoto(id)
const sendProfile = useSendProfile(id)

// Faza 5: kompletność profilu + timeline.
const { data: completeness } = useCompletenessQuery(id)
const { data: timeline } = useTimelineQuery(id)

// Przypisanie do ogłoszenia (pipeline).
const { data: postingsData } = useJobPostingsQuery()
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
  } catch (e: any) {
    applyMsg.value =
      e?.response?._data?.errors?.candidate_id?.[0] || 'Nie udało się dodać.'
  }
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
  await loadProfilePhoto()
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
async function generatePdf() {
  const blob = await fetchBlob(`/candidates/${id.value}/profile-pdf`)
  openBlob(blob)
}

const showSend = ref(false)
const recipient = ref('')
const sendMsg = ref('')

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

  <section v-else-if="candidate" class="space-y-5 pb-8">
    <!-- Nagłówek + zdjęcie -->
    <div class="flex items-start gap-4">
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
        <a :href="`tel:${candidate.phone}`" class="mt-0.5 inline-flex items-center gap-1.5 font-medium text-brand-deep">
          <AppIcon name="phone" :size="15" /> {{ candidate.phone }}
        </a>
      </div>
      <span class="badge badge-neutral shrink-0">{{ candidate.status_label }}</span>
    </div>
    <input ref="photoInput" type="file" accept="image/*" capture="user" class="hidden" @change="onPhotoSelected" />

    <!-- Kompletność profilu + braki do wysłania -->
    <div v-if="completeness" class="card p-4">
      <div class="mb-2 flex items-center justify-between">
        <p class="text-[13px] font-medium text-steel">Kompletność profilu</p>
        <span class="text-sm font-semibold" :class="completeness.complete ? 'text-brand-deep' : 'text-amber-600'">
          {{ completeness.percent }}%
        </span>
      </div>
      <div class="mb-3 h-2 overflow-hidden rounded-full bg-surface">
        <div class="h-full rounded-full bg-brand" :style="{ width: completeness.percent + '%' }" />
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
          <NuxtLink :to="`/job-offers/${app.job_posting_id}`" class="min-w-0 truncate text-sm font-medium text-ink">
            {{ app.job_posting?.title || 'Ogłoszenie' }}
          </NuxtLink>
          <span class="badge badge-neutral shrink-0">{{ app.status_label }}</span>
        </li>
      </ul>
    </div>

    <!-- Uprawnienia -->
    <div class="card p-4">
      <p class="mb-2.5 text-[13px] font-medium text-steel">Uprawnienia</p>
      <div class="flex flex-wrap gap-2">
        <span v-for="cat in candidate.license_categories" :key="cat" class="badge badge-neutral">{{ cat }}</span>
        <span v-if="candidate.has_adr" class="badge bg-amber-50 text-amber-700">ADR</span>
        <span v-if="candidate.has_code_95" class="badge badge-accent">Kod 95</span>
        <span v-if="!candidate.license_categories.length && !candidate.has_adr" class="text-sm text-muted">Brak danych</span>
      </div>
    </div>

    <!-- Profil PDF / wysyłka -->
    <div class="flex gap-2.5">
      <button class="inline-flex h-11 flex-1 items-center justify-center gap-1.5 rounded-full bg-ink text-sm font-semibold text-white transition active:scale-[0.98]" @click="generatePdf">
        <AppIcon name="pdf" :size="18" /> Generuj PDF
      </button>
      <button class="inline-flex h-11 flex-1 items-center justify-center gap-1.5 rounded-full border border-hairline bg-canvas text-sm font-semibold text-ink transition active:bg-surface" @click="showSend = !showSend">
        <AppIcon name="mail" :size="18" /> Wyślij profil
      </button>
    </div>
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
          <button class="flex h-9 w-9 items-center justify-center rounded-full text-steel transition active:bg-surface" @click="download(doc)">
            <AppIcon name="download" :size="18" />
          </button>
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

    <!-- RODO -->
    <div class="card p-4">
      <p class="mb-2.5 inline-flex items-center gap-1.5 text-[13px] font-medium text-steel">
        <AppIcon name="shield" :size="15" /> RODO
      </p>
      <div class="flex flex-wrap gap-2">
        <button class="inline-flex items-center gap-1.5 rounded-full border border-hairline px-3.5 py-2 text-sm font-medium text-ink transition active:bg-surface" @click="exportData">
          <AppIcon name="download" :size="16" /> Eksport danych
        </button>
        <button
          v-if="auth.isAdmin"
          class="inline-flex items-center gap-1.5 rounded-full border border-red-200 px-3.5 py-2 text-sm font-medium text-red-600 transition active:bg-red-50"
          @click="forget"
        >
          <AppIcon name="x" :size="16" /> Usuń trwale
        </button>
      </div>
    </div>

    <!-- Timeline -->
    <div v-if="timeline?.length">
      <h2 class="mb-3 text-lg font-semibold text-ink">Historia (timeline)</h2>
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

    <!-- Cropper -->
    <CropperModal
      v-if="cropSrc"
      :src="cropSrc"
      @cropped="onCropped"
      @close="cropSrc = null"
    />
  </section>
</template>
