<template>
  <AuthenticatedLayout>
    <div>
      <!-- Dashboard Metrics -->
      <div class="metrics-grid">
        <div class="metric-card primary-glow">
          <div class="metric-info">
            <h3>Occupancy</h3>
            <div class="value">{{ summary.occupied_seats }} / {{ summary.total_seats }}</div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
              {{ summary.available_seats }} seats available
            </p>
          </div>
          <div class="metric-icon"><i class="fa-solid fa-chair"></i></div>
        </div>

        <div v-if="!isSubAdmin" class="metric-card success-glow">
          <div class="metric-info">
            <h3>Total Revenue</h3>
            <div class="value">{{ formatCurrency(summary.revenue.total) }}</div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
              Cash: {{ formatCurrency(summary.revenue.cash) }} | UPI: {{ formatCurrency(summary.revenue.upi) }}
            </p>
          </div>
          <div class="metric-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        </div>

        <div v-if="!isSubAdmin" class="metric-card danger-glow">
          <div class="metric-info">
            <h3>Expenses</h3>
            <div class="value">{{ formatCurrency(summary.expenses) }}</div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
              Rent, bills & maintenance
            </p>
          </div>
          <div class="metric-icon"><i class="fa-solid fa-wallet"></i></div>
        </div>

        <div v-if="!isSubAdmin" class="metric-card" :class="summary.net_profit >= 0 ? 'success-glow' : 'danger-glow'">
          <div class="metric-info">
            <h3>Net Profit</h3>
            <div class="value" :style="{ color: summary.net_profit >= 0 ? 'var(--success)' : 'var(--danger)' }">
              {{ formatCurrency(summary.net_profit) }}
            </div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
              Revenue minus expenses
            </p>
          </div>
          <div class="metric-icon"><i class="fa-solid fa-chart-line"></i></div>
        </div>
      </div>

      <!-- Dashboard Layout Columns -->
      <div class="dashboard-actions-grid">
        <!-- Quick Actions Panel -->
        <div class="card-panel">
          <h2><i class="fa-solid fa-bolt"></i> Quick Actions</h2>
          <div class="quick-actions-list">
            <button class="action-btn" @click="router.visit('/seats')">
              <i class="fa-solid fa-chair"></i> Book / Assign a Seat
            </button>
            <button class="action-btn" @click="router.visit('/members')">
              <i class="fa-solid fa-user-plus"></i> Add New Member
            </button>
            <button class="action-btn" @click="router.visit('/waitlist')">
              <i class="fa-solid fa-clock-rotate-left"></i> Add to Waiting List
            </button>
            <button class="action-btn" @click="router.visit('/expenses')">
              <i class="fa-solid fa-money-bill-transfer"></i> Log an Expense
            </button>
          </div>
        </div>

        <!-- Alerts / System Health -->
        <div class="card-panel">
          <h2><i class="fa-solid fa-bell"></i> Alerts & Status</h2>
          <div class="recent-list">
            <div class="recent-item" style="cursor: pointer;" @click="router.visit('/members')">
              <div class="item-main">
                <span class="item-title" style="color: var(--expired);">
                  <i class="fa-solid fa-exclamation-circle"></i> Expired Memberships
                </span>
                <span class="item-subtitle">Action required (renew or vacate)</span>
              </div>
              <div class="item-meta">
                <span class="badge" style="background-color: var(--danger-glow); color: var(--danger); border: 1px solid var(--danger);">
                  {{ summary.expired_count }}
                </span>
              </div>
            </div>
            <div class="recent-item" style="cursor: pointer;" @click="router.visit('/members')">
              <div class="item-main">
                <span class="item-title" style="color: var(--warning);">
                  <i class="fa-solid fa-clock"></i> Due within 5 days
                </span>
                <span class="item-subtitle">Renewal reminders pending</span>
              </div>
              <div class="item-meta">
                <span class="badge" style="background-color: var(--warning-glow); color: var(--warning); border: 1px solid var(--warning);">
                  {{ summary.due_soon_count }}
                </span>
              </div>
            </div>
            <div class="recent-item" style="cursor: pointer;" @click="router.visit('/waitlist')">
              <div class="item-main">
                <span class="item-title" style="color: var(--success);">
                  <i class="fa-solid fa-users"></i> Waiting Queue
                </span>
                <span class="item-subtitle">Clients waiting for seat vacancy</span>
              </div>
              <div class="item-meta">
                <span class="badge" style="background-color: var(--success-glow); color: var(--success); border: 1px solid var(--success);">
                  {{ summary.waiting_count }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';

const props = defineProps({
  summary: Object,
  isSubAdmin: Boolean,
});

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', minimumFractionDigits: 0 }).format(amount);
};
</script>

<style scoped>
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}
.metric-card {
  background: white;
  border-radius: var(--radius-md);
  padding: 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border);
  position: relative;
  overflow: hidden;
}
.metric-info h3 {
  color: var(--text-muted);
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.metric-info .value {
  font-size: 1.8rem;
  font-family: 'Outfit', sans-serif;
  font-weight: 700;
  color: var(--text-main);
}
.metric-icon {
  font-size: 2.5rem;
  opacity: 0.2;
}
.dashboard-actions-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}
.quick-actions-list {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-top: 16px;
}
.action-btn {
  background-color: var(--primary-light);
  color: var(--primary);
  border: 1px solid rgba(59, 130, 246, 0.2);
  padding: 16px;
  border-radius: var(--radius-sm);
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  transition: all 0.2s ease;
}
.action-btn:hover {
  background-color: var(--primary);
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
.recent-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 16px;
}
.recent-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background-color: var(--background);
  border-radius: var(--radius-sm);
  border-left: 4px solid transparent;
  transition: transform 0.2s;
}
.recent-item:hover {
  transform: translateX(4px);
  background-color: #f1f5f9;
}
.item-main {
  display: flex;
  flex-direction: column;
}
.item-title {
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}
.item-subtitle {
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-top: 4px;
}
@media (max-width: 768px) {
  .dashboard-actions-grid {
    grid-template-columns: 1fr;
  }
}
</style>
