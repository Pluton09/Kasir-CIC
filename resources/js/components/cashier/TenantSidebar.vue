<script setup>
defineProps({
    tenants: {
        type: Array,
        default: () => [],
    },
    activeTenantId: {
        type: Number,
        default: null,
    },
});

defineEmits(['select']);

function menuCount(tenant) {
    return (tenant.categories ?? []).reduce((count, category) => {
        return count + (category.menus?.length ?? 0);
    }, 0);
}
</script>

<template>
    <nav class="tenant-sidebar" aria-label="Pilih tenant">
        <p class="tenant-sidebar-title">Tenant</p>

        <button
            v-for="tenant in tenants"
            :key="tenant.id"
            type="button"
            class="tenant-btn"
            :class="{ on: tenant.id === activeTenantId }"
            :aria-pressed="tenant.id === activeTenantId"
            @click="$emit('select', tenant.id)"
        >
            <span class="tenant-btn-name">{{ tenant.name }}</span>
            <small>{{ menuCount(tenant) }} menu</small>
        </button>

        <p v-if="tenants.length === 0" class="tenant-sidebar-empty">
            Belum ada tenant.
        </p>
    </nav>
</template>
