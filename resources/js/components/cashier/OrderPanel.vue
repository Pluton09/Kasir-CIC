<script setup>
import OrderLine from './OrderLine.vue';
import { rupiah } from '../../lib/format.js';

defineProps({
    groups: {
        type: Array,
        default: () => [],
    },
    lineCount: {
        type: Number,
        default: 0,
    },
    tableNo: {
        type: String,
        default: '',
    },
    customerName: {
        type: String,
        default: '',
    },
    note: {
        type: String,
        default: '',
    },
    total: {
        type: Number,
        default: 0,
    },
});

defineEmits([
    'update-table',
    'update-customer',
    'update-note',
    'increment',
    'decrement',
    'clear',
    'pay',
]);
</script>

<template>
    <aside class="order-panel" aria-label="Panel pesanan">
        <div class="order-title">
            <h2>Pesanan</h2>
            <span>{{ lineCount }} menu</span>
        </div>

        <div class="order-fields">
            <label>
                Meja
                <input
                    :value="tableNo"
                    maxlength="20"
                    placeholder="Nomor meja"
                    @input="$emit('update-table', $event.target.value)"
                >
            </label>

            <label>
                Pelanggan
                <input
                    :value="customerName"
                    maxlength="100"
                    placeholder="Nama pelanggan"
                    @input="$emit('update-customer', $event.target.value)"
                >
            </label>

            <label class="full">
                Catatan
                <input
                    :value="note"
                    maxlength="255"
                    placeholder="Catatan (opsional)"
                    @input="$emit('update-note', $event.target.value)"
                >
            </label>
        </div>

        <div class="order-lines">
            <section
                v-for="group in groups"
                :key="group.tenantId"
                class="order-group"
            >
                <h3 class="order-group-title">
                    <span>{{ group.tenantName }}</span>
                    <small>{{ rupiah(group.subtotal) }}</small>
                </h3>

                <OrderLine
                    v-for="line in group.lines"
                    :key="line.menu.id"
                    :line="line"
                    @increment="$emit('increment', $event)"
                    @decrement="$emit('decrement', $event)"
                />
            </section>

            <p v-if="groups.length === 0" class="order-empty">
                Ketuk menu untuk menambah pesanan.
            </p>
        </div>

        <div class="order-total">
            <span>Total</span>
            <strong>{{ rupiah(total) }}</strong>
        </div>

        <div class="order-actions">
            <button
                type="button"
                class="btn btn-primary"
                :disabled="lineCount === 0"
                @click="$emit('pay')"
            >
                Pembayaran
            </button>

            <button
                type="button"
                class="btn btn-ghost"
                :disabled="lineCount === 0"
                @click="$emit('clear')"
            >
                Kosongkan pesanan
            </button>
        </div>
    </aside>
</template>
