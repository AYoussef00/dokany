<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Dokany monogram: simple "D" mark -->
        <path
            fill="currentColor"
            d="M12 7.5c0-.83.67-1.5 1.5-1.5h5.6a8.9 8.9 0 0 1 0 17.8h-5.6c-.83 0-1.5-.67-1.5-1.5V7.5Zm5.6 2.8H15v10.9h2.6a5.45 5.45 0 0 0 0-10.9Z"
        />
    </svg>
</template>
