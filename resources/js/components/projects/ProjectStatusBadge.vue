<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

const props = defineProps<{
    isActive: boolean;
}>();

const variant = computed(() => (props.isActive ? 'default' : 'secondary'));

const text = computed(() => (props.isActive ? 'Active' : 'Inactive'));
</script>

<template>
    <Badge :variant="variant">
        {{ text }}
    </Badge>
</template>
