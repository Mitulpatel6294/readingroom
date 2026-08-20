<template>
  <AuthenticatedLayout>
    <div class="settings-container">
      <!-- Settings Panel -->
      <div class="card-panel" style="max-width: 600px;">
        <h2><i class="fa-solid fa-gears"></i> System Configuration</h2>
        
        <form @submit.prevent="submit" id="settings-form">
          <!-- Site Name -->
          <div class="form-group">
            <label for="site-name"><i class="fa-solid fa-building"></i> Reading Room Name</label>
            <input 
              type="text" 
              id="site-name" 
              required 
              placeholder="e.g. Clever's Reading Room"
              v-model="form.site_name"
            >
            <div v-if="form.errors.site_name" class="error-text" style="color: var(--danger); margin-top: 4px; font-size: 0.85rem;">{{ form.errors.site_name }}</div>
            <small style="color: var(--text-muted); margin-top: 4px; display: block;">
              This name appears in the sidebar and dashboard
            </small>
          </div>

          <!-- Monthly Fee -->
          <div class="form-group">
            <label for="monthly-fee"><i class="fa-solid fa-indian-rupee-sign"></i> Monthly Membership Fee</label>
            <div class="input-with-prefix">
              <span class="input-prefix">₹</span>
              <input 
                type="number" 
                id="monthly-fee" 
                required 
                min="0" 
                step="any"
                placeholder="e.g. 1500"
                v-model="form.monthly_fee"
              >
            </div>
            <div v-if="form.errors.monthly_fee" class="error-text" style="color: var(--danger); margin-top: 4px; font-size: 0.85rem;">{{ form.errors.monthly_fee }}</div>
            <small style="color: var(--text-muted); margin-top: 4px; display: block;">
              Amount charged per month for membership renewal
            </small>
          </div>

          <!-- Total Seats -->
          <div class="form-group">
            <label for="total-seats"><i class="fa-solid fa-chair"></i> Total Number of Seats</label>
            <input 
              type="number" 
              id="total-seats" 
              required 
              min="1" 
              max="500"
              placeholder="e.g. 80"
              v-model="form.total_seats"
            >
            <div v-if="form.errors.total_seats" class="error-text" style="color: var(--danger); margin-top: 4px; font-size: 0.85rem;">{{ form.errors.total_seats }}</div>
            <small style="color: var(--text-muted); margin-top: 4px; display: block;">
              Maximum number of seats available in the reading room
            </small>
          </div>

          <!-- Action Buttons -->
          <div style="display: flex; gap: 12px; margin-top: 32px;">
            <button type="submit" class="btn btn-primary" style="flex: 1;" :disabled="form.processing">
              <i class="fa-solid fa-save"></i> Save Settings
            </button>
            <button type="button" class="btn btn-secondary" @click="resetForm" :disabled="form.processing">
              <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
          </div>
        </form>
      </div>

      <!-- Information Panel -->
      <div class="card-panel" style="max-width: 600px; margin-top: 24px;">
        <h2><i class="fa-solid fa-info-circle"></i> System Information</h2>
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Current Site Name</span>
            <span class="info-value">{{ settings.site_name }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Monthly Fee</span>
            <span class="info-value">{{ formatCurrency(settings.monthly_fee) }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Total Seats</span>
            <span class="info-value">{{ settings.total_seats }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">System Version</span>
            <span class="info-value">1.0.0</span>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';

const props = defineProps({
  settings: Object
});

const form = useForm({
  site_name: props.settings.site_name || '',
  monthly_fee: props.settings.monthly_fee || 0,
  total_seats: props.settings.total_seats || 0,
});

const submit = () => {
  form.post('/settings', {
    preserveScroll: true,
  });
};

const resetForm = () => {
  form.reset();
  form.clearErrors();
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR',
    minimumFractionDigits: 0
  }).format(amount);
};
</script>
