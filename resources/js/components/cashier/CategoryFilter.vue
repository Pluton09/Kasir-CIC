<script setup>
defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    activeCategory: {
        type: Number,
        default: null,
    },
});

defineEmits(['select']);
</script>

<template>
    <div class="chips" aria-label="Filter kategori">
        <button
            type="button"
            class="chip"
            :class="{ on: activeCategory === null }"
            :aria-pressed="activeCategory === null"
            @click="$emit('select', null)"
        >
            Semua
        </button>

        <button
            v-for="category in categories"
            :key="category.id"
            type="button"
            class="chip"
            :class="{ on: activeCategory === category.id }"
            :aria-pressed="activeCategory === category.id"
            @click="$emit('select', category.id)"
        >
            {{ category.name }}
        </button>
    </div>
</template>
