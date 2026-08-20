<template>
  <AuthenticatedLayout>
    <div>
      <!-- Seat Grid Legend -->
      <div class="seat-legend">
        <div class="legend-item"><span class="legend-color color-available"></span> Available ({{ summary.available_count }})</div>
        <div class="legend-item"><span class="legend-color color-occupied"></span> Occupied/Active</div>
        <div class="legend-item"><span class="legend-color color-due"></span> Due Soon (≤ 5 Days)</div>
        <div class="legend-item"><span class="legend-color color-expired"></span> Expired</div>
      </div>

      <!-- Seat Grid -->
      <div class="seat-grid-container" id="seats-grid">
        <div v-for="seat in seats" :key="seat.seat_number" 
             class="seat-card" 
             :class="getSeatClass(seat)"
             :title="getSeatTitle(seat)"
             @click="handleSeatClick(seat)">
          <span class="seat-number">{{ seat.seat_number }}</span>
          <span class="seat-status-label">{{ getSeatLabel(seat) }}</span>
        </div>
      </div>
    </div>

    <!-- Seat Details Modal -->
    <div v-if="showDetailsModal" class="modal active" @click.self="showDetailsModal = false">
      <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
          <h3>Seat #{{ selectedSeat?.seat_number }} Details</h3>
          <button class="modal-close" @click="showDetailsModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <div class="info-row">
            <span class="info-label">Member Name</span>
            <span class="info-value">{{ selectedSeat?.name }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Phone Number</span>
            <span class="info-value">
              <a :href="'tel:' + selectedSeat?.phone" style="color: var(--primary); font-weight:600;">
                <i class="fa-solid fa-phone"></i> {{ selectedSeat?.phone }}
              </a>
            </span>
          </div>
          <div class="info-row">
            <span class="info-label">Age</span>
            <span class="info-value">{{ selectedSeat?.age || 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Registration ID</span>
            <span class="info-value">{{ selectedSeat?.registration_number || 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Join Date</span>
            <span class="info-value">{{ formatDate(selectedSeat?.join_date) }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Membership Valid To</span>
            <span class="info-value">{{ formatDate(selectedSeat?.due_date) }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Last Paid Date</span>
            <span class="info-value">{{ formatDate(selectedSeat?.last_paid_on) }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Days Left</span>
            <span class="info-value">
              <span class="badge" :class="getBadgeClass(selectedSeat)" :style="getBadgeStyle(selectedSeat)">
                <i class="fa-solid" :class="getStatusIcon(selectedSeat)"></i> {{ getStatusLabel(selectedSeat) }}
              </span>
            </span>
          </div>
        </div>
        <div class="modal-footer" style="justify-content: space-between; flex-wrap: wrap; gap: 10px;">
          <div style="display:flex; gap:10px; flex-wrap: wrap;">
            <button class="btn btn-info" @click="downloadLatestReceipt(selectedSeat)"><i class="fa-solid fa-file-arrow-down"></i> Download Receipt</button>
            <button class="btn btn-danger" @click="confirmVacate(selectedSeat)"><i class="fa-solid fa-user-minus"></i> Vacate Seat</button>
          </div>
          <div style="display:flex; gap:10px;">
            <button class="btn btn-secondary" @click="openRenewModal(selectedSeat)"><i class="fa-solid fa-arrows-rotate"></i> Renew</button>
            <button class="btn btn-primary" @click="showDetailsModal = false">Done</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Book Seat Modal (Add Member) -->
    <div v-if="showBookModal" class="modal active" @click.self="showBookModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Book Seat #{{ addForm.seat_number }}</h3>
          <button class="modal-close" @click="showBookModal = false">&times;</button>
        </div>
        <form @submit.prevent="submitAdd">
          <div class="modal-body">
            <div class="form-group">
              <label for="add-name">Member Name*</label>
              <input type="text" id="add-name" required placeholder="Enter full name" v-model="addForm.name">
              <div v-if="addForm.errors.name" class="field-error">{{ addForm.errors.name }}</div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap:16px;">
              <div class="form-group">
                <label for="add-phone">Phone Number*</label>
                <input type="tel" id="add-phone" required placeholder="10-digit mobile" v-model="addForm.phone">
                <div v-if="addForm.errors.phone" class="field-error">{{ addForm.errors.phone }}</div>
              </div>
              <div class="form-group">
                <label for="add-age">Age (Optional)</label>
                <input type="number" id="add-age" min="1" max="120" placeholder="e.g. 21" v-model="addForm.age">
              </div>
            </div>

            <div class="form-group">
              <label for="add-reg">Registration/ID Card No. (Optional)</label>
              <input type="text" id="add-reg" placeholder="e.g. REG1020" v-model="addForm.registration_number">
              <div v-if="addForm.errors.registration_number" class="field-error">{{ addForm.errors.registration_number }}</div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap:16px;">
              <div class="form-group">
                <label for="add-join">Join Date*</label>
                <input type="date" id="add-join" required v-model="addForm.join_date" :max="todayStr">
              </div>
              <div class="form-group">
                <label for="add-months">Duration (Months)*</label>
                <select id="add-months" required v-model.number="addForm.months_paid" @change="resetAddSplit">
                  <option :value="1">1 Month</option>
                  <option :value="2">2 Months</option>
                  <option :value="3">3 Months</option>
                  <option :value="6">6 Months</option>
                  <option :value="12">12 Months</option>
                </select>
              </div>
            </div>

            <!-- Payment Calculation -->
            <div style="background-color: var(--background); padding:16px; border-radius:var(--radius-sm); margin-bottom:16px; border:1px solid var(--border);">
              <h4 style="font-size:0.9rem; margin-bottom:10px; color:var(--text-muted);">Payment Breakdown</h4>
              <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span>Monthly Fee:</span>
                <strong>{{ formatCurrency(settings.monthly_fee) }}</strong>
              </div>
              <div style="display:flex; justify-content:space-between; margin-bottom:12px; padding-bottom:8px; border-bottom:1px dashed var(--border);">
                <span>Total Months:</span>
                <strong>{{ addForm.months_paid }} Month{{ addForm.months_paid > 1 ? 's' : '' }}</strong>
              </div>
              <div style="display:flex; justify-content:space-between; font-weight:700; font-size:1rem; color:var(--text-main);">
                <span>Total Amount Due:</span>
                <span>{{ formatCurrency(addTotalDue) }}</span>
              </div>
            </div>

            <div class="form-group">
              <label>Payment Split</label>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:6px;">
                <div>
                  <label for="add-cash" style="font-size: 0.8rem; font-weight: normal; margin-bottom: 2px;">Cash Amount (₹)</label>
                  <input type="number" id="add-cash" min="0" step="any" v-model.number="addForm.amount_cash" @input="syncAddSplit('cash')">
                  <div v-if="addForm.errors.amount_cash" class="field-error">{{ addForm.errors.amount_cash }}</div>
                </div>
                <div>
                  <label for="add-upi" style="font-size: 0.8rem; font-weight: normal; margin-bottom: 2px;">UPI Amount (₹)</label>
                  <input type="number" id="add-upi" min="0" step="any" v-model.number="addForm.amount_upi" @input="syncAddSplit('upi')">
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showBookModal = false">Cancel</button>
            <button type="submit" class="btn btn-success" :disabled="addForm.processing"><i class="fa-solid fa-cash-register"></i> Book Seat</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Renew Membership Modal -->
    <div v-if="showRenewModal" class="modal active" @click.self="showRenewModal = false">
      <div class="modal-content" style="max-width: 440px;">
        <div class="modal-header">
          <h3>Renew Membership - Seat #{{ renewTarget?.seat_number }}</h3>
          <button class="modal-close" @click="showRenewModal = false">&times;</button>
        </div>
        <form @submit.prevent="submitRenew">
          <div class="modal-body">
            <div style="background-color: var(--primary-light); color: var(--primary); padding:12px; border-radius:var(--radius-sm); margin-bottom:16px; font-size:0.85rem; font-weight:500;">
              Member: <strong>{{ renewTarget?.name }}</strong><br>
              Current Expiry: <strong>{{ formatDate(renewTarget?.due_date) }}</strong>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap:16px;">
              <div class="form-group">
                <label for="renew-date">Payment Date*</label>
                <input type="date" id="renew-date" required v-model="renewForm.payment_date">
              </div>
              <div class="form-group">
                <label for="renew-months">Extend by (Months)*</label>
                <select id="renew-months" required v-model.number="renewForm.months_paid" @change="resetRenewSplit">
                  <option :value="1">1 Month</option>
                  <option :value="2">2 Months</option>
                  <option :value="3">3 Months</option>
                  <option :value="6">6 Months</option>
                  <option :value="12">12 Months</option>
                </select>
              </div>
            </div>

            <!-- Payment Calculation -->
            <div style="background-color: var(--background); padding:16px; border-radius:var(--radius-sm); margin-bottom:16px; border:1px solid var(--border);">
              <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span>Monthly Fee:</span>
                <strong>{{ formatCurrency(settings.monthly_fee) }}</strong>
              </div>
              <div style="display:flex; justify-content:space-between; margin-bottom:12px; padding-bottom:8px; border-bottom:1px dashed var(--border);">
                <span>Extension:</span>
                <strong>{{ renewForm.months_paid }} Month{{ renewForm.months_paid > 1 ? 's' : '' }}</strong>
              </div>
              <div style="display:flex; justify-content:space-between; font-weight:700; font-size:1rem; color:var(--text-main);">
                <span>Total Renewal Fee:</span>
                <span>{{ formatCurrency(renewTotalDue) }}</span>
              </div>
            </div>

            <div class="form-group">
              <label>Payment Split</label>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:6px;">
                <div>
                  <label for="renew-cash" style="font-size: 0.8rem; font-weight: normal; margin-bottom: 2px;">Cash Amount (₹)</label>
                  <input type="number" id="renew-cash" min="0" step="any" v-model.number="renewForm.amount_cash" @input="syncRenewSplit('cash')">
                  <div v-if="renewForm.errors.amount_cash" class="field-error">{{ renewForm.errors.amount_cash }}</div>
                </div>
                <div>
                  <label for="renew-upi" style="font-size: 0.8rem; font-weight: normal; margin-bottom: 2px;">UPI Amount (₹)</label>
                  <input type="number" id="renew-upi" min="0" step="any" v-model.number="renewForm.amount_upi" @input="syncRenewSplit('upi')">
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showRenewModal = false">Cancel</button>
            <button type="submit" class="btn btn-success" :disabled="renewForm.processing"><i class="fa-solid fa-arrows-rotate"></i> Process Renewal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Vacate Modal -->
    <div v-if="showVacateConfirm" class="modal active" @click.self="showVacateConfirm = false">
      <div class="modal-content confirm-modal-content">
        <div class="confirm-icon"><i class="fa-solid fa-circle-question"></i></div>
        <h3>Vacate Seat?</h3>
        <p>Are you sure you want to vacate Seat #{{ vacateTarget?.seat_number }} for member {{ vacateTarget?.name }}? This will remove the member.</p>
        <div class="confirm-buttons">
          <button class="btn btn-secondary" @click="showVacateConfirm = false">No</button>
          <button class="btn btn-danger" @click="executeVacate" :disabled="vacateProcessing">Yes</button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import { generateReceiptPdf } from '../../utils/pdf';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';

const props = defineProps({
  seats: Array,
  summary: Object,
  settings: Object,
});

const todayStr = new Date().toISOString().split('T')[0];

// --- Helpers ---
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', minimumFractionDigits: 0 }).format(amount);
};

const STATUS_CONFIG = {
  active: { color: 'var(--danger)', icon: 'fa-check-circle', label: 'Active' },
  due_soon: { color: 'var(--warning)', icon: 'fa-clock', label: 'Due Soon' },
  expired: { color: 'var(--expired)', icon: 'fa-exclamation-circle', label: 'Expired' },
};

const getSeatClass = (seat) => {
  if (!seat.is_occupied) return 'seat-available';
  if (seat.days_left <= 0) return 'seat-expired';
  if (seat.days_left <= 5) return 'seat-due_soon';
  return 'seat-occupied'; // Matches active
};

const getSeatTitle = (seat) => {
  if (!seat.is_occupied) return 'VACANT';
  return seat.name;
};

const getSeatLabel = (seat) => {
  if (!seat.is_occupied) return 'VACANT';
  return seat.name.length > 9 ? seat.name.substring(0, 8) + '..' : seat.name;
};

const getBadgeClass = (member) => {
  if (member.days_left <= 0) return 'badge-danger';
  if (member.days_left <= 3) return 'badge-warning'; // Note: uses 3 for badge color, matching original MemberManager.js
  return 'badge-success';
};

const getBadgeStyle = (member) => {
  const config = STATUS_CONFIG[member.status] || STATUS_CONFIG.active;
  return `background-color: ${config.color}14; color: ${config.color}; border: 1px solid ${config.color}33;`;
};

const getStatusIcon = (member) => {
  return (STATUS_CONFIG[member.status] || STATUS_CONFIG.active).icon;
};

const getStatusLabel = (member) => {
  if (member.days_left <= 0) return 'Expired';
  if (member.days_left <= 3) return 'Due soon';
  return `${member.days_left} days left`;
};

// --- Modals State ---
const showDetailsModal = ref(false);
const selectedSeat = ref(null);

const handleSeatClick = (seat) => {
  if (seat.is_occupied) {
    selectedSeat.value = seat;
    showDetailsModal.value = true;
  } else {
    // Open Book Seat Modal
    addForm.seat_number = seat.seat_number;
    addForm.name = '';
    addForm.phone = '';
    addForm.age = null;
    addForm.registration_number = '';
    addForm.join_date = todayStr;
    addForm.months_paid = 1;
    addForm.amount_cash = props.settings.monthly_fee;
    addForm.amount_upi = 0;
    addForm.payment_date = todayStr;
    showBookModal.value = true;
  }
};

// --- BOOK SEAT (ADD MEMBER) ---
const showBookModal = ref(false);

const addForm = useForm({
  name: '',
  phone: '',
  age: null,
  registration_number: '',
  seat_number: null,
  join_date: todayStr,
  months_paid: 1,
  amount_cash: props.settings.monthly_fee,
  amount_upi: 0,
  payment_date: todayStr,
});

const addTotalDue = computed(() => props.settings.monthly_fee * addForm.months_paid);

const resetAddSplit = () => {
  addForm.amount_cash = addTotalDue.value;
  addForm.amount_upi = 0;
};

const syncAddSplit = (changedField) => {
  const total = addTotalDue.value;
  if (changedField === 'cash') {
    if (addForm.amount_cash > total) addForm.amount_cash = total;
    addForm.amount_upi = Math.max(0, total - (addForm.amount_cash || 0));
  } else {
    if (addForm.amount_upi > total) addForm.amount_upi = total;
    addForm.amount_cash = Math.max(0, total - (addForm.amount_upi || 0));
  }
};

const submitAdd = () => {
  addForm.post('/members', {
    preserveScroll: true,
    onSuccess: () => {
      let d = new Date(addForm.join_date);
      d.setMonth(d.getMonth() + addForm.months_paid);
      
      generateReceiptPdf({
        receiptTitle: 'Booking Receipt',
        receiptType: 'Seat Booking Payment',
        seat_number: addForm.seat_number,
        member_name: addForm.name,
        phone: addForm.phone,
        registration_number: addForm.registration_number,
        payment_date: addForm.payment_date,
        months_paid: addForm.months_paid,
        total_amount: addTotalDue.value,
        amount_cash: addForm.amount_cash,
        amount_upi: addForm.amount_upi,
        due_date: d.toISOString().split('T')[0]
      });

      showBookModal.value = false;
      addForm.reset();
    },
  });
};

// --- RENEW SEAT ---
const showRenewModal = ref(false);
const renewTarget = ref(null);

const renewForm = useForm({
  months_paid: 1,
  amount_cash: 0,
  amount_upi: 0,
  payment_date: todayStr,
});

const renewTotalDue = computed(() => props.settings.monthly_fee * renewForm.months_paid);

const openRenewModal = (seat) => {
  renewTarget.value = seat;
  renewForm.months_paid = 1;
  renewForm.amount_cash = props.settings.monthly_fee;
  renewForm.amount_upi = 0;
  renewForm.payment_date = todayStr;
  
  showDetailsModal.value = false;
  showRenewModal.value = true;
};

const resetRenewSplit = () => {
  renewForm.amount_cash = renewTotalDue.value;
  renewForm.amount_upi = 0;
};

const syncRenewSplit = (changedField) => {
  const total = renewTotalDue.value;
  if (changedField === 'cash') {
    if (renewForm.amount_cash > total) renewForm.amount_cash = total;
    renewForm.amount_upi = Math.max(0, total - (renewForm.amount_cash || 0));
  } else {
    if (renewForm.amount_upi > total) renewForm.amount_upi = total;
    renewForm.amount_cash = Math.max(0, total - (renewForm.amount_upi || 0));
  }
};

const submitRenew = () => {
  renewForm.post(`/members/${renewTarget.value.member_id}/renew`, {
    preserveScroll: true,
    onSuccess: () => {
      let currentDue = new Date(renewTarget.value.due_date);
      let today = new Date();
      let baseDate = currentDue < today ? today : currentDue;
      baseDate.setMonth(baseDate.getMonth() + renewForm.months_paid);

      generateReceiptPdf({
        receiptTitle: 'Renewal Receipt',
        receiptType: 'Membership Renewal Payment',
        seat_number: renewTarget.value.seat_number,
        member_name: renewTarget.value.name,
        phone: renewTarget.value.phone,
        registration_number: renewTarget.value.registration_number,
        payment_date: renewForm.payment_date,
        months_paid: renewForm.months_paid,
        total_amount: renewTotalDue.value,
        amount_cash: renewForm.amount_cash,
        amount_upi: renewForm.amount_upi,
        due_date: baseDate.toISOString().split('T')[0]
      });

      showRenewModal.value = false;
    },
  });
};

// --- VACATE SEAT ---
const showVacateConfirm = ref(false);
const vacateTarget = ref(null);
const vacateProcessing = ref(false);

const confirmVacate = (seat) => {
  vacateTarget.value = seat;
  showDetailsModal.value = false;
  showVacateConfirm.value = true;
};

const executeVacate = () => {
  vacateProcessing.value = true;
  router.delete(`/members/${vacateTarget.value.member_id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showVacateConfirm.value = false;
      vacateTarget.value = null;
    },
    onFinish: () => {
      vacateProcessing.value = false;
    },
  });
};

// --- LATEST RECEIPT ---
const downloadLatestReceipt = async (seat) => {
  try {
    const response = await axios.get(`/members/${seat.member_id}/payments`);
    const payments = response.data.payments;
    if (payments && payments.length > 0) {
      const latest = payments[0];
      generateReceiptPdf({
        receiptTitle: 'Payment Receipt',
        receiptType: 'Latest Payment Receipt',
        seat_number: seat.seat_number,
        member_name: seat.name,
        phone: seat.phone,
        registration_number: seat.registration_number,
        payment_date: latest.payment_date,
        months_paid: latest.months_paid,
        total_amount: latest.total,
        amount_cash: latest.amount_cash,
        amount_upi: latest.amount_upi,
        due_date: seat.due_date,
      });
    } else {
      const showToast = document.getElementById('toast-container')?.__vueParentComponent?.ctx?.showToast;
      if (showToast) showToast('No payments found for this member.', 'warning');
    }
  } catch (error) {
    const showToast = document.getElementById('toast-container')?.__vueParentComponent?.ctx?.showToast;
    if (showToast) showToast('Failed to load payment details', 'danger');
  }
};
</script>

<style scoped>
.field-error {
  color: var(--danger);
  font-size: 0.8rem;
  margin-top: 4px;
}
.btn-info {
  background-color: var(--primary);
  color: white;
}
.btn-info:hover {
  background-color: var(--primary-hover);
}
</style>
