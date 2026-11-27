import { createApp } from 'vue';
import CashierApp from './components/cashier/CashierApp.vue';

const root = document.getElementById('kasir-app');

createApp(CashierApp, {
    userName: root?.dataset.user || 'Kasir',
}).mount(root);
