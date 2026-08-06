<script lang="ts" setup>
import type { ToasterProps } from "vue-sonner"
import { CircleCheckIcon, InfoIcon, Loader2Icon, OctagonXIcon, TriangleAlertIcon, XIcon } from "@lucide/vue"
import { Toaster as Sonner } from "vue-sonner"
import { cn } from "@/lib/utils"

import 'vue-sonner/style.css';

const props = defineProps<ToasterProps>()
</script>

<template>
  <Sonner
    :class="cn('toaster group', props.class)"
    :duration="5000"
    rich-colors
    :style="{
      // Solid, saturated palette so toasts stand out in both themes:
      // blue for default/info, green for success, red for error,
      // orange for warning. White text throughout.
      '--normal-bg': '#2563eb',
      '--normal-text': '#ffffff',
      '--normal-border': '#2563eb',
      '--info-bg': '#2563eb',
      '--info-text': '#ffffff',
      '--info-border': '#2563eb',
      '--success-bg': '#16a34a',
      '--success-text': '#ffffff',
      '--success-border': '#16a34a',
      '--error-bg': '#dc2626',
      '--error-text': '#ffffff',
      '--error-border': '#dc2626',
      '--warning-bg': '#ea580c',
      '--warning-text': '#ffffff',
      '--warning-border': '#ea580c',
      '--border-radius': 'var(--radius)',
    }"
    v-bind="props"
  >
    <template #success-icon>
      <CircleCheckIcon class="size-4" />
    </template>
    <template #info-icon>
      <InfoIcon class="size-4" />
    </template>
    <template #warning-icon>
      <TriangleAlertIcon class="size-4" />
    </template>
    <template #error-icon>
      <OctagonXIcon class="size-4" />
    </template>
    <template #loading-icon>
      <div>
        <Loader2Icon class="size-4 animate-spin" />
      </div>
    </template>
    <template #close-icon>
      <XIcon class="size-4" />
    </template>
  </Sonner>
</template>
