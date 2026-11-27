<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { apiFetch } from '../../lib/http.js';
import { rupiah } from '../../lib/format.js';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    groups: {
        type: Array,
        default: () => [],
    },
    total: {
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
});

const emit = defineEmits(['close', 'completed']);

const methods = ['Tunai', 'QRIS', 'Transfer'];
const method = ref('Tunai');
const cash = ref('');
const submitting = ref(false);
const error = ref('');
const receipt = ref(null);

const paidAmount = computed(() => Number(cash.value) || 0);

const change = computed(() => Math.max(0, paidAmount.value - props.total));

const quickAmounts = computed(() => {
    if (props.total < 1) {
        return [];
    }

    const values = new Set([props.total]);
    const rounded = Math.ceil(props.total / 10000) * 10000;

    [rounded, 20000, 50000, 100000].forEach((amount) => {
        if (amount >= props.total) {
            values.add(amount);
        }
    });

    return [...values].sort((a, b) => a - b).slice(0, 5);
});

const canSubmit = computed(() => {
    if (receipt.value || submitting.value || props.total < 1) {
        return false;
    }

    return method.value !== 'Tunai' || paidAmount.value >= props.total;
});

const receiptGroups = computed(() => {
    if (!receipt.value) {
        return [];
    }

    const groups = new Map();

    for (const item of receipt.value.items ?? []) {
        const key = item.tenant_id ?? item.tenant_name ?? 'tanpa-tenant';

        if (!groups.has(key)) {
            groups.set(key, {
                tenantId: key,
                tenantName: item.tenant_name || 'Tanpa tenant',
                lines: [],
                subtotal: 0,
            });
        }

        const group = groups.get(key);
        group.lines.push(item);
        group.subtotal += item.subtotal;
    }

    return [...groups.values()];
});

const receiptTime = computed(() => {
    if (!receipt.value?.created_at) {
        return '';
    }

    return new Date(receipt.value.created_at).toLocaleTimeString('id-ID', {
        timeZone: 'Asia/Jakarta',
        hour: '2-digit',
        minute: '2-digit',
    });
});

function resetForm() {
    method.value = 'Tunai';
    cash.value = '';
    submitting.value = false;
    error.value = '';
    receipt.value = null;
}

function close() {
    if (submitting.value) {
        return;
    }

    emit('close');
}

function firstError(payload) {
    const errors = payload?.errors ?? {};

    for (const messages of Object.values(errors)) {
        if (Array.isArray(messages) && messages.length > 0) {
            return messages[0];
        }
    }

    return payload?.message || 'Transaksi gagal disimpan.';
}

async function submit() {
    if (!canSubmit.value) {
        return;
    }

    submitting.value = true;
    error.value = '';

    const payload = {
        table_no: props.tableNo || null,
        customer_name: props.customerName || null,
        note: props.note || null,
        payment_method: method.value,
        items: props.groups.flatMap((group) => {
            return group.lines.map((line) => ({
                menu_id: line.menu.id,
                qty: line.qty,
            }));
        }),
    };

    if (method.value === 'Tunai') {
        payload.paid_amount = paidAmount.value;
    }

    try {
        const response = await apiFetch('/api/transactions', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            error.value = firstError(data);
            return;
        }

        receipt.value = data;
        emit('completed', data);
    } catch {
        error.value = 'Gagal menyimpan transaksi. Periksa koneksi lalu coba lagi.';
    } finally {
        submitting.value = false;
    }
}

function onKeydown(event) {
    if (event.key === 'Escape' && props.open) {
        close();
    }
}

watch(
    () => props.open,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';

        if (open) {
            resetForm();
        }
    },
);

onMounted(() => window.addEventListener('keydown', onKeydown));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="modal-overlay" @click.self="close">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="payment-title">
                <template v-if="!receipt">
                    <div class="modal-head">
                        <h3 id="payment-title">Pembayaran</h3>
                        <button
                            type="button"
                            class="modal-close"
                            aria-label="Tutup"
                            :disabled="submitting"
                            @click="close"
                        >
                            ×
                        </button>
                    </div>

                    <div class="pay-total">{{ rupiah(total) }}</div>

                    <div class="chips pay-methods" aria-label="Metode pembayaran">
                        <button
                            v-for="item in methods"
                            :key="item"
                            type="button"
                            class="chip"
                            :class="{ on: method === item }"
                            :aria-pressed="method === item"
                            @click="method = item"
                        >
                            {{ item }}
                        </button>
                    </div>

                    <template v-if="method === 'Tunai'">
                        <label class="pay-label">
                            Uang diterima
                            <input
                                v-model="cash"
                                type="number"
                                inputmode="numeric"
                                min="0"
                                step="500"
                                placeholder="Uang diterima"
                            >
                        </label>

                        <div class="chips quick-amounts">
                            <button
                                v-for="amount in quickAmounts"
                                :key="amount"
                                type="button"
                                class="chip"
                                @click="cash = String(amount)"
                            >
                                {{ amount === total ? 'Uang pas' : rupiah(amount) }}
                            </button>
                        </div>

                        <p class="pay-change">
                            Kembalian: <strong>{{ rupiah(change) }}</strong>
                        </p>
                    </template>

                    <p v-else class="pay-note">
                        {{ method === 'QRIS'
                            ? 'Tunjukkan QR ke pelanggan, lalu konfirmasi setelah lunas.'
                            : 'Konfirmasi setelah transfer diterima.' }}
                    </p>

                    <p v-if="error" class="error" role="alert">{{ error }}</p>

                    <div class="modal-actions">
                        <button type="button" :disabled="submitting" @click="close">
                            Batal
                        </button>
                        <button
                            type="button"
                            class="btn-primary"
                            :disabled="!canSubmit"
                            @click="submit"
                        >
                            {{ submitting ? 'Menyimpan...' : 'Selesaikan' }}
                        </button>
                    </div>
                </template>

                <template v-else>
                    <div class="modal-head">
                        <h3>Transaksi berhasil</h3>
                    </div>

                    <div class="receipt">
                        <div class="receipt-row">
                            <strong>{{ receipt.code }}</strong>
                            <span>{{ receiptTime }} WIB</span>
                        </div>

                        <div v-if="receipt.table_no || receipt.customer_name || receipt.note" class="receipt-meta">
                            <span v-if="receipt.table_no">Meja {{ receipt.table_no }}</span>
                            <span v-if="receipt.customer_name">{{ receipt.customer_name }}</span>
                            <span v-if="receipt.note">Catatan: {{ receipt.note }}</span>
                        </div>

                        <div
                            v-for="group in receiptGroups"
                            :key="group.tenantId"
                            class="receipt-group"
                        >
                            <h4>{{ group.tenantName }}</h4>
                            <div
                                v-for="(item, index) in group.lines"
                                :key="index"
                                class="receipt-row"
                            >
                                <span>{{ item.qty }} × {{ item.menu_name }}</span>
                                <span>{{ rupiah(item.subtotal) }}</span>
                            </div>
                        </div>

                        <div class="receipt-total">
                            <strong>Total</strong>
                            <strong>{{ rupiah(receipt.total) }}</strong>
                        </div>

                        <div class="receipt-row">
                            <span>{{ receipt.payment_method }}</span>
                            <span>{{ rupiah(receipt.paid_amount) }}</span>
                        </div>
                        <div class="receipt-row">
                            <span>Kembalian</span>
                            <span>{{ rupiah(receipt.change_amount) }}</span>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-primary" @click="close">
                            Pesanan baru
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </Teleport>
</template>
