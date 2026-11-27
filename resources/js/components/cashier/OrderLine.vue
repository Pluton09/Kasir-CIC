<script setup>
import { rupiah } from '../../lib/format.js';

defineProps({
    line: {
        type: Object,
        required: true,
    },
});

defineEmits(['increment', 'decrement']);
</script>

<template>
    <div class="order-line">
        <div class="line-info">
            <strong>{{ line.menu.name }}</strong>
            <small>{{ rupiah(line.menu.price) }} × {{ line.qty }}</small>
        </div>

        <div class="qty">
            <button
                type="button"
                aria-label="Kurangi jumlah"
                @click="$emit('decrement', line)"
            >
                −
            </button>
            <span>{{ line.qty }}</span>
            <button
                type="button"
                aria-label="Tambah jumlah"
                @click="$emit('increment', line)"
            >
                +
            </button>
        </div>
    </div>
</template>
