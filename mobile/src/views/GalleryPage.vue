<template>
  <ion-page>
    <ion-header translucent><ion-toolbar><ion-title>Galleria</ion-title><ion-buttons slot="end"><ion-button @click="capturePhoto"><ion-icon :icon="cameraOutline"/></ion-button></ion-buttons></ion-toolbar></ion-header>
    <ion-content :fullscreen="true">
      <div class="pm-page">
        <CloudStatus />
        <div class="pm-section-title">Foto tecniche</div>
        <div v-if="photos.length" class="pm-photo-grid">
          <article v-for="photo in photos" :key="photo.id" class="pm-card pm-photo">
            <img :src="photo.preview" :alt="photo.title" />
            <div class="pm-photo-copy"><strong>{{ photo.title }}</strong><small class="pm-muted">{{ photo.description || 'Nessuna descrizione' }}</small><div style="margin-top:8px"><span class="pm-pill">{{ photo.syncState === 'synced' ? 'Cloud' : 'Sul dispositivo' }}</span></div></div>
          </article>
        </div>
        <div v-else class="pm-card"><strong>Nessuna foto</strong><p class="pm-muted">Fotografa pneumatici, danni, setup o dettagli del veicolo. Le immagini vengono salvate prima sul dispositivo.</p><ion-button @click="capturePhoto"><ion-icon slot="start" :icon="cameraOutline"/>Aggiungi foto</ion-button></div>
      </div>

      <ion-modal :is-open="draft !== null" @did-dismiss="draft = null">
        <ion-header><ion-toolbar><ion-title>Nuova foto</ion-title><ion-buttons slot="end"><ion-button @click="draft = null">Chiudi</ion-button></ion-buttons></ion-toolbar></ion-header>
        <ion-content><div class="pm-page" v-if="draft"><img :src="draft.preview" style="width:100%;border-radius:18px;aspect-ratio:4/3;object-fit:cover" alt="Anteprima"/><div class="pm-card" style="margin-top:14px"><ion-item><ion-input v-model="title" label="Titolo" label-placement="stacked" placeholder="Es. Usura anteriore sinistra"/></ion-item><ion-item><ion-textarea v-model="description" label="Descrizione" label-placement="stacked" auto-grow/></ion-item><ion-button expand="block" size="large" @click="saveDraft">Salva in galleria</ion-button></div></div></ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';
import { Capacitor } from '@capacitor/core';
import { Directory, Filesystem } from '@capacitor/filesystem';
import { IonButton, IonButtons, IonContent, IonHeader, IonIcon, IonInput, IonItem, IonModal, IonPage, IonTextarea, IonTitle, IonToolbar } from '@ionic/vue';
import { cameraOutline } from 'ionicons/icons';
import { onMounted, ref } from 'vue';
import CloudStatus from '../components/CloudStatus.vue';
import { getGalleryPhotos, saveGalleryPhoto } from '../services/local-db';
import { refreshPendingCount, syncNow } from '../services/sync';
import type { LocalGalleryPhoto } from '../types';

type VisiblePhoto = LocalGalleryPhoto & { preview: string };
type PhotoDraft = { id: string; filePath: string; preview: string; mimeType: string };
const photos = ref<VisiblePhoto[]>([]); const draft = ref<PhotoDraft|null>(null); const title = ref(''); const description = ref('');

async function load() {
  const stored = await getGalleryPhotos();
  photos.value = await Promise.all(stored.sort((a,b) => b.takenAt.localeCompare(a.takenAt)).map(async (photo) => {
    const uri = await Filesystem.getUri({ path: photo.filePath, directory: Directory.Data });
    return { ...photo, preview: Capacitor.convertFileSrc(uri.uri) };
  }));
}

async function capturePhoto() {
  const image = await Camera.getPhoto({ quality: 82, resultType: CameraResultType.Base64, source: CameraSource.Prompt, correctOrientation: true });
  if (!image.base64String) return;
  const id = crypto.randomUUID(); const filePath = `gallery/${id}.jpeg`;
  await Filesystem.writeFile({ path: filePath, data: image.base64String, directory: Directory.Data, recursive: true });
  const uri = await Filesystem.getUri({ path: filePath, directory: Directory.Data });
  draft.value = { id, filePath, preview: Capacitor.convertFileSrc(uri.uri), mimeType: `image/${image.format || 'jpeg'}` };
  title.value = ''; description.value = '';
}

async function saveDraft() {
  if (!draft.value || !title.value.trim()) return;
  await saveGalleryPhoto({ id: draft.value.id, title: title.value.trim(), description: description.value.trim(), filePath: draft.value.filePath, mimeType: draft.value.mimeType, takenAt: new Date().toISOString(), syncState: 'pending' });
  draft.value = null; await load(); await refreshPendingCount(); void syncNow();
}

onMounted(load);
</script>
