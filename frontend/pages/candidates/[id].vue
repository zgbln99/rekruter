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

// --- Upload dokumentu ---
const docType = ref<DocumentType>('cv')
const fileInput = ref<HTMLInputElement | null>(null)

function pickDocument() {
  fileInput.value?.click()
}
async function onDocumentSelected(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) await uploadDocument.mutateAsync({ file, type: docType.value })
  if (fileInput.value) fileInput.value.value = ''
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
  <section v-if="isLoading" class="py-8 text-center text-gray-400">Ładowanie…</section>

  <section v-else-if="candidate" class="space-y-5 pb-8">
    <!-- Nagłówek + zdjęcie -->
    <div class="flex items-start gap-4">
      <button
        class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand text-2xl font-bold text-white"
        @click="pickPhoto"
      >
        <img v-if="photoUrl" :src="photoUrl" class="h-full w-full object-cover" alt="" />
        <span v-else>{{ candidate.first_name.charAt(0) }}</span>
      </button>
      <div class="flex-1">
        <h1 class="text-2xl font-bold">{{ candidate.full_name }}</h1>
        <a :href="`tel:${candidate.phone}`" class="text-brand">{{ candidate.phone }}</a>
        <p class="text-xs text-gray-400">Dotknij zdjęcia, aby ustawić / wyciąć</p>
      </div>
      <span class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600">
        {{ candidate.status_label }}
      </span>
    </div>
    <input ref="photoInput" type="file" accept="image/*" class="hidden" @change="onPhotoSelected" />

    <!-- Uprawnienia -->
    <div class="rounded-xl border border-gray-200 bg-white p-4">
      <p class="mb-2 text-sm font-medium text-gray-700">Uprawnienia</p>
      <div class="flex flex-wrap gap-2">
        <span v-for="cat in candidate.license_categories" :key="cat" class="rounded-full bg-gray-100 px-3 py-1 text-sm">{{ cat }}</span>
        <span v-if="candidate.has_adr" class="rounded-full bg-amber-100 px-3 py-1 text-sm text-amber-700">ADR</span>
        <span v-if="candidate.has_code_95" class="rounded-full bg-emerald-100 px-3 py-1 text-sm text-emerald-700">Kod 95</span>
      </div>
    </div>

    <!-- Profil PDF -->
    <div class="flex gap-2">
      <button class="flex-1 rounded-xl bg-brand py-3 text-sm font-semibold text-white active:bg-brand-dark" @click="generatePdf">
        📄 Generuj PDF
      </button>
      <button class="flex-1 rounded-xl border border-brand py-3 text-sm font-semibold text-brand" @click="showSend = !showSend">
        ✉️ Wyślij profil
      </button>
    </div>
    <div v-if="showSend" class="rounded-xl border border-gray-200 bg-white p-4">
      <input v-model="recipient" type="email" placeholder="email@klienta.pl" class="input-field mb-2" />
      <button class="btn-primary" :disabled="sendProfile.isPending.value" @click="doSend">
        {{ sendProfile.isPending.value ? 'Wysyłanie…' : 'Wyślij' }}
      </button>
    </div>
    <p v-if="sendMsg" class="text-sm text-brand">{{ sendMsg }}</p>

    <!-- Dokumenty -->
    <div>
      <div class="mb-2 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Dokumenty</h2>
        <div class="flex items-center gap-2">
          <select v-model="docType" class="rounded-lg border border-gray-300 px-2 py-1 text-sm">
            <option v-for="opt in DOCUMENT_TYPE_OPTIONS" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
          <button class="rounded-lg bg-brand px-3 py-1 text-sm text-white" @click="pickDocument">+ Dodaj</button>
        </div>
      </div>
      <input ref="fileInput" type="file" accept="image/*,application/pdf" class="hidden" @change="onDocumentSelected" />

      <ul v-if="documents?.length" class="space-y-2">
        <li v-for="doc in documents" :key="doc.id" class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-3 text-sm">
          <div>
            <span class="font-medium">{{ doc.type_label }}</span>
            <span class="ml-2 text-gray-400">{{ doc.original_name }}</span>
          </div>
          <button class="text-brand" @click="download(doc)">Pobierz</button>
        </li>
      </ul>
      <p v-else class="text-sm text-gray-400">Brak dokumentów.</p>
    </div>

    <!-- Historia kontaktów -->
    <div>
      <h2 class="mb-2 text-lg font-semibold">Historia kontaktów</h2>
      <ul v-if="candidate.contact_logs?.length" class="space-y-2">
        <li v-for="log in candidate.contact_logs" :key="log.id" class="rounded-xl border border-gray-200 bg-white p-3 text-sm">
          <div class="flex justify-between">
            <span class="font-medium">{{ log.outcome_label }}</span>
            <span class="text-gray-400">{{ log.channel_label }}</span>
          </div>
          <p v-if="log.note" class="mt-1 text-gray-600">{{ log.note }}</p>
        </li>
      </ul>
      <p v-else class="text-sm text-gray-400">Brak zapisanych kontaktów.</p>
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
