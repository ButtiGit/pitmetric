<template>
  <ion-tabs>
    <ion-router-outlet />
    <ion-tab-bar slot="bottom">
      <ion-tab-button tab="home" href="/app/home"><ion-icon :icon="homeOutline"/><ion-label>Home</ion-label></ion-tab-button>
      <ion-tab-button tab="pit" href="/app/pit"><ion-icon :icon="flashOutline"/><ion-label>Pit</ion-label></ion-tab-button>
      <ion-tab-button tab="gallery" href="/app/gallery"><ion-icon :icon="imagesOutline"/><ion-label>Galleria</ion-label></ion-tab-button>
      <ion-tab-button tab="settings" href="/app/settings"><ion-icon :icon="settingsOutline"/><ion-label>Altro</ion-label></ion-tab-button>
    </ion-tab-bar>
  </ion-tabs>
</template>

<script setup lang="ts">
import { IonIcon, IonLabel, IonRouterOutlet, IonTabBar, IonTabButton, IonTabs } from '@ionic/vue';
import { flashOutline, homeOutline, imagesOutline, settingsOutline } from 'ionicons/icons';
</script>
