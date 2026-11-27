<script setup>
import { computed } from 'vue';
import { colorFromName, initials, rupiah } from '../../lib/format.js';

const props = defineProps({
    menu: {
        type: Object,
        required: true,
    },
});

defineEmits(['add']);

const hasPrice = computed(() => props.menu.price !== null && props.menu.price !== undefined);

const label = computed(() => (hasPrice.value
    ? `Tambah ${props.menu.name} ke pesanan`
    : `${props.menu.name} belum ada harga`));
</script>

<template>
    <button
        type="button"
        class="menu-card"
        :class="{ unpriced: !hasPrice }"
        :aria-label="label"
        :disabled="!hasPrice"
        @click="$emit('add', menu)"
    >
        <span
            class="menu-thumb"
            :style="{ backgroundColor: colorFromName(menu.name) }"
        >
            {{ initials(menu.name) }}
        </span>

        <span class="menu-body">
            <span class="menu-name">{{ menu.name }}</span>
            <span class="menu-price">
                {{ hasPrice ? rupiah(menu.price) : 'Harga belum diisi' }}
            </span>
        </span>
    </button>
</template>
