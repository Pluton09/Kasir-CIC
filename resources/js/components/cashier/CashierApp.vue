<script setup>
import { computed, onMounted, ref } from 'vue';
import CategoryFilter from './CategoryFilter.vue';
import MenuGrid from './MenuGrid.vue';
import OrderPanel from './OrderPanel.vue';
import PaymentDialog from './PaymentDialog.vue';
import TenantSidebar from './TenantSidebar.vue';
import { apiFetch, csrfToken } from '../../lib/http.js';

defineProps({
    userName: {
        type: String,
        default: 'Kasir',
    },
});

const tenants = ref([]);
const activeTenantId = ref(null);
const loading = ref(true);
const error = ref('');
const search = ref('');
const activeCategory = ref(null);
const cart = ref({});
const tableNo = ref('');
const customerName = ref('');
const note = ref('');
const paymentOpen = ref(false);
const token = csrfToken();

const activeTenant = computed(() => {
    return tenants.value.find((tenant) => tenant.id === activeTenantId.value) ?? null;
});

const activeCategories = computed(() => activeTenant.value?.categories ?? []);

const allMenus = computed(() => {
    return tenants.value.flatMap((tenant) => {
        return (tenant.categories ?? []).flatMap((category) => {
            return (category.menus ?? []).map((menu) => ({
                ...menu,
                category_id: menu.category_id ?? category.id,
                tenant_id: tenant.id,
                tenant_name: tenant.name,
            }));
        });
    });
});

const menuMap = computed(() => new Map(allMenus.value.map((menu) => [menu.id, menu])));

const filteredMenus = computed(() => {
    const query = search.value.trim().toLowerCase();

    return allMenus.value.filter((menu) => {
        const matchesTenant = menu.tenant_id === activeTenantId.value;
        const matchesCategory = activeCategory.value === null || menu.category_id === activeCategory.value;
        const matchesSearch = query === '' || menu.name.toLowerCase().includes(query);

        return matchesTenant && matchesCategory && matchesSearch;
    });
});

const orderLines = computed(() => {
    return Object.entries(cart.value)
        .map(([menuId, qty]) => {
            const menu = menuMap.value.get(Number(menuId));

            if (!menu || qty < 1) {
                return null;
            }

            return {
                menu,
                qty,
                subtotal: menu.price * qty,
            };
        })
        .filter(Boolean);
});

const orderGroups = computed(() => {
    return tenants.value
        .map((tenant) => {
            const lines = orderLines.value.filter((line) => line.menu.tenant_id === tenant.id);

            return {
                tenantId: tenant.id,
                tenantName: tenant.name,
                lines,
                subtotal: lines.reduce((sum, line) => sum + line.subtotal, 0),
            };
        })
        .filter((group) => group.lines.length > 0);
});

const total = computed(() => {
    return orderLines.value.reduce((sum, line) => sum + line.subtotal, 0);
});

const lineCount = computed(() => orderLines.value.length);

const emptyMenuMessage = computed(() => {
    const isFiltering = search.value.trim() !== '' || activeCategory.value !== null;

    return isFiltering ? 'Menu tidak ditemukan.' : 'Tenant ini belum punya menu.';
});

async function loadMenus() {
    loading.value = true;
    error.value = '';

    try {
        const response = await apiFetch('/api/menus');

        if (!response.ok) {
            throw new Error('Gagal memuat menu.');
        }

        const data = await response.json();

        tenants.value = Array.isArray(data.tenants) ? data.tenants : [];

        if (!tenants.value.some((tenant) => tenant.id === activeTenantId.value)) {
            activeTenantId.value = tenants.value[0]?.id ?? null;
        }

        if (
            activeCategory.value !== null
            && !activeCategories.value.some((category) => category.id === activeCategory.value)
        ) {
            activeCategory.value = null;
        }
    } catch {
        error.value = 'Gagal memuat menu. Periksa koneksi lalu coba lagi.';
    } finally {
        loading.value = false;
    }
}

function selectTenant(tenantId) {
    if (activeTenantId.value === tenantId) {
        return;
    }

    activeTenantId.value = tenantId;
    activeCategory.value = null;
}

function addMenu(menu) {
    if (menu.price === null || menu.price === undefined) {
        return;
    }

    const currentQty = cart.value[menu.id] ?? 0;

    if (currentQty >= 99) {
        return;
    }

    cart.value = {
        ...cart.value,
        [menu.id]: currentQty + 1,
    };
}

function increment(line) {
    if (line.qty >= 99) {
        return;
    }

    cart.value = {
        ...cart.value,
        [line.menu.id]: line.qty + 1,
    };
}

function decrement(line) {
    const nextQty = line.qty - 1;
    const nextCart = { ...cart.value };

    if (nextQty < 1) {
        delete nextCart[line.menu.id];
    } else {
        nextCart[line.menu.id] = nextQty;
    }

    cart.value = nextCart;
}

function clearOrder() {
    cart.value = {};
}

function openPayment() {
    if (orderLines.value.length > 0) {
        paymentOpen.value = true;
    }
}

function closePayment() {
    paymentOpen.value = false;
}

function handleCompleted() {
    cart.value = {};
    tableNo.value = '';
    customerName.value = '';
    note.value = '';
}

onMounted(loadMenus);
</script>

<template>
    <div class="cashier-page">
        <header class="topbar">
            <div class="brand">
                <span class="brand-mark">K</span>
                <span class="brand-text">
                    <strong>Kasir</strong>
                    <small>Kantin makan di tempat</small>
                </span>
            </div>

            <nav class="topbar-nav" aria-label="Navigasi">
                <a href="/riwayat">Riwayat</a>
                <a href="/kelola/menu">Harga Menu</a>
                <a href="/kelola">Kelola</a>
            </nav>

            <div class="search-wrap">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Cari nama menu"
                    aria-label="Cari menu"
                >
            </div>

            <div class="topbar-actions">
                <span class="user-chip">{{ userName }}</span>
                <form method="POST" action="/logout" class="logout-form">
                    <input type="hidden" name="_token" :value="token">
                    <button type="submit" class="btn">Keluar</button>
                </form>
            </div>
        </header>

        <main class="cashier-main">
            <TenantSidebar
                :tenants="tenants"
                :active-tenant-id="activeTenantId"
                @select="selectTenant"
            />

            <section class="menu-section" aria-label="Daftar menu">
                <h2 v-if="activeTenant" class="menu-tenant-title">
                    {{ activeTenant.name }}
                </h2>

                <CategoryFilter
                    :categories="activeCategories"
                    :active-category="activeCategory"
                    @select="activeCategory = $event"
                />

                <div v-if="!loading && !error && tenants.length === 0" class="state">
                    Belum ada tenant aktif. Tambahkan tenant lewat halaman Kelola.
                </div>

                <MenuGrid
                    v-else
                    :menus="filteredMenus"
                    :loading="loading"
                    :error="error"
                    :empty-message="emptyMenuMessage"
                    @add="addMenu"
                    @retry="loadMenus"
                />
            </section>

            <OrderPanel
                :groups="orderGroups"
                :line-count="lineCount"
                :table-no="tableNo"
                :customer-name="customerName"
                :note="note"
                :total="total"
                @update-table="tableNo = $event"
                @update-customer="customerName = $event"
                @update-note="note = $event"
                @increment="increment"
                @decrement="decrement"
                @clear="clearOrder"
                @pay="openPayment"
            />
        </main>

        <PaymentDialog
            :open="paymentOpen"
            :groups="orderGroups"
            :total="total"
            :table-no="tableNo"
            :customer-name="customerName"
            :note="note"
            @close="closePayment"
            @completed="handleCompleted"
        />
    </div>
</template>
