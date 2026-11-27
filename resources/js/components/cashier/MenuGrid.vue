<script setup>
import MenuCard from './MenuCard.vue';

defineProps({
    menus: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
    emptyMessage: {
        type: String,
        default: 'Menu tidak ditemukan.',
    },
});

defineEmits(['add', 'retry']);
</script>

<template>
    <div v-if="loading" class="state" aria-live="polite">
        Memuat menu...
    </div>

    <div v-else-if="error" class="state error-box" role="alert">
        <p>{{ error }}</p>
        <button type="button" class="btn" @click="$emit('retry')">Coba lagi</button>
    </div>

    <div v-else-if="menus.length === 0" class="state">
        {{ emptyMessage }}
    </div>

    <div v-else class="menu-grid">
        <MenuCard
            v-for="menu in menus"
            :key="menu.id"
            :menu="menu"
            @add="$emit('add', $event)"
        />
    </div>
</template>
