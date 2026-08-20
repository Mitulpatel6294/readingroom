<template>
  <AuthenticatedLayout>
    <div>
      <!-- Top Actions Bar -->
      <div class="table-actions">
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="member-search" v-model="searchQuery" placeholder="Search members by name, phone, seat...">
        </div>
        <button id="add-member-btn" class="btn btn-primary" @click="showAddModal = true" :disabled="vacantSeats.length === 0">
          <i class="fa-solid fa-user-plus"></i> Add Member
        </button>
      </div>

      <!-- Members Table -->
      <div id="members-list-container">
        <div v-if="filteredMembers.length === 0" class="empty-state">
          <i class="fa-solid fa-users-slash"></i>
          <h3>No members found</h3>
          <p>Try searching for a different query or add a member.</p>
        </div>

        <div v-else class="table-container">
          <table class="app-table">
            <thead>
              <tr>
                <th style="width: 80px; text-align: center;">Seat</th>
                <th>Member Details</th>
                <th>Registration ID</th>
                <th>Dates</th>
                <th>Days Left</th>
                <th style="width: 150px; text-align: center;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="member in filteredMembers" :key="member.member_id">
                <td style="text-align: center; font-weight: 700; color: var(--primary);">#{{ member.seat_number }}</td>
                <td>
                  <div class="table-avatar-cell">
                    <div class="table-avatar">{{ member.name.charAt(0).toUpperCase() }}</div>
                    <div>
                      <div style="font-weight: 600;">{{ member.name }}</div>
                      <div style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-solid fa-phone"></i> {{ member.phone }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <code style="font-size: 0.85rem; background: var(--background); padding:2px 6px; border-radius:4px;">{{ member.registration_number || 'N/A' }}</code>
                </td>
                <td>
                  <div style="font-size: 0.85rem;">Join: <strong>{{ formatDate(member.join_date) }}</strong></div>
                  <div style="font-size: 0.85rem; color: var(--text-muted);">Due: <strong>{{ formatDate(member.due_date) }}</strong></div>
                </td>
                <td>
                  <span class="badge" :class="getBadgeClass(member)" :style="getBadgeStyle(member)">
                    <i class="fa-solid" :class="getStatusIcon(member)"></i> {{ getStatusLabel(member) }}
                  </span>
                </td>
                <td style="text-align: center;">
                  <div class="table-actions-cell" style="justify-content: center;">
                    <button class="btn-icon edit-member-btn" @click="openEditModal(member)" title="Edit Profile">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="btn-icon view-payments-btn" @click="openPaymentsModal(member)" title="View Payments">
                      <i class="fa-solid fa-receipt"></i>
                    </button>
                    <button class="btn-icon renew-member-btn" @click="openRenewModal(member)" title="Renew Membership">
                      <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                    <button class="btn-icon btn-icon-danger vacate-member-btn" @click="confirmVacate(member)" title="Vacate Seat">
                      <i class="fa-solid fa-user-xmark"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add Member Modal -->
    <div v-if="showAddModal" class="modal active" @click.self="showAddModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Register New Member</h3>
          <button class="modal-close" @click="showAddModal = false">&times;</button>
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

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap:16px;">
              <div class="form-group">
                <label for="add-reg">Registration/ID Card No. (Optional)</label>
                <input type="text" id="add-reg" placeholder="e.g. REG1020" v-model="addForm.registration_number">
                <div v-if="addForm.errors.registration_number" class="field-error">{{ addForm.errors.registration_number }}</div>
              </div>
              <div class="form-group">
                <label for="add-seat">Select Vacant Seat*</label>
                <select id="add-seat" required v-model="addForm.seat_number">
                  <option v-for="s in vacantSeats" :key="s" :value="s">Seat #{{ s }}</option>
                </select>
                <div v-if="addForm.errors.seat_number" class="field-error">{{ addForm.errors.seat_number }}</div>
              </div>
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
                </div>
                <div>
                  <label for="add-upi" style="font-size: 0.8rem; font-weight: normal; margin-bottom: 2px;">UPI Amount (₹)</label>
                  <input type="number" id="add-upi" min="0" step="any" v-model.number="addForm.amount_upi" @input="syncAddSplit('upi')">
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showAddModal = false">Cancel</button>
            <button type="submit" class="btn btn-success" :disabled="addForm.processing"><i class="fa-solid fa-cash-register"></i> Register & Book</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Member Modal -->
    <div v-if="showEditModal" class="modal active" @click.self="showEditModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Edit Member Details</h3>
          <button class="modal-close" @click="showEditModal = false">&times;</button>
        </div>
        <form @submit.prevent="submitEdit">
          <div class="modal-body">
            <div class="form-group">
              <label for="edit-name">Full Name*</label>
              <input type="text" id="edit-name" required v-model="editForm.name">
              <div v-if="editForm.errors.name" class="field-error">{{ editForm.errors.name }}</div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap:16px;">
              <div class="form-group">
                <label for="edit-phone">Phone Number*</label>
                <input type="tel" id="edit-phone" required v-model="editForm.phone">
                <div v-if="editForm.errors.phone" class="field-error">{{ editForm.errors.phone }}</div>
              </div>
              <div class="form-group">
                <label for="edit-age">Age (Optional)</label>
                <input type="number" id="edit-age" min="1" max="120" v-model="editForm.age">
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap:16px;">
              <div class="form-group">
                <label for="edit-reg">Registration/ID Card No. (Optional)</label>
                <input type="text" id="edit-reg" v-model="editForm.registration_number">
                <div v-if="editForm.errors.registration_number" class="field-error">{{ editForm.errors.registration_number }}</div>
              </div>
              <div class="form-group">
                <label for="edit-seat">Seat Number*</label>
                <select id="edit-seat" required v-model.number="editForm.seat_number">
                  <option v-for="s in editAvailableSeats" :key="s" :value="s">Seat #{{ s }}</option>
                </select>
                <div v-if="editForm.errors.seat_number" class="field-error">{{ editForm.errors.seat_number }}</div>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap:16px;">
              <div class="form-group">
                <label for="edit-join">Join Date*</label>
                <input type="date" id="edit-join" required v-model="editForm.join_date" :max="todayStr">
              </div>
              <div class="form-group">
                <label for="edit-due">Membership Due Date*</label>
                <input type="date" id="edit-due" required v-model="editForm.due_date">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showEditModal = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="editForm.processing"><i class="fa-solid fa-save"></i> Save Changes</button>
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

    <!-- Payment Records Modal -->
    <div v-if="showPaymentsModal" class="modal active" @click.self="showPaymentsModal = false">
      <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
          <h3>Payment Records - {{ paymentsTarget?.name }} (Seat #{{ paymentsTarget?.seat_number }})</h3>
          <button class="modal-close" @click="showPaymentsModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <div v-if="loadingPayments" class="spinner-container">
            <div class="spinner"></div>
          </div>
          <div v-else-if="paymentRecords.length === 0" class="empty-state" style="padding: 24px;">
            <p>No payment records found.</p>
          </div>
          <div v-else class="table-container" style="margin-bottom: 0;">
            <table class="app-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Duration</th>
                  <th style="text-align: right;">Cash</th>
                  <th style="text-align: right;">UPI</th>
                  <th style="text-align: right;">Total</th>
                  <th style="text-align: center;">Receipt</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="payment in paymentRecords" :key="payment.id">
                  <td>{{ formatDate(payment.payment_date) }}</td>
                  <td>{{ payment.months_paid }} Month(s)</td>
                  <td style="text-align: right;">{{ formatCurrency(payment.amount_cash) }}</td>
                  <td style="text-align: right;">{{ formatCurrency(payment.amount_upi) }}</td>
                  <td style="text-align: right; font-weight: 600;">{{ formatCurrency(payment.total) }}</td>
                  <td style="text-align: center;">
                    <button class="btn-icon" @click="downloadReceipt(payment)" title="Download Receipt">
                      <i class="fa-solid fa-download"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" @click="showPaymentsModal = false">Close</button>
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
  members: Array,
  vacantSeats: Array,
  settings: Object,
});

const todayStr = new Date().toISOString().split('T')[0];
const searchQuery = ref('');

// --- Filtered Members ---
const filteredMembers = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  if (!query) return props.members;
  return props.members.filter(m =>
    m.name.toLowerCase().includes(query) ||
    m.phone.includes(query) ||
    m.seat_number.toString() === query ||
    (m.registration_number && m.registration_number.toLowerCase().includes(query))
  );
});

// --- Formatting helpers ---
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', minimumFractionDigits: 0 }).format(amount);
};

// --- Status helpers (matching original constants.js logic) ---
const STATUS_CONFIG = {
  active: { color: 'var(--danger)', icon: 'fa-check-circle', label: 'Active' },
  due_soon: { color: 'var(--warning)', icon: 'fa-clock', label: 'Due Soon' },
  expired: { color: 'var(--expired)', icon: 'fa-exclamation-circle', label: 'Expired' },
};

const getBadgeClass = (member) => {
  if (member.days_left <= 0) return 'badge-danger';
  if (member.days_left <= 3) return 'badge-warning';
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

// --- ADD MEMBER ---
const showAddModal = ref(false);

const addForm = useForm({
  name: '',
  phone: '',
  age: null,
  registration_number: '',
  seat_number: props.vacantSeats.length > 0 ? props.vacantSeats[0] : null,
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
      // Calculate due date for receipt
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

      showAddModal.value = false;
      addForm.reset();
      addForm.seat_number = props.vacantSeats.length > 0 ? props.vacantSeats[0] : null;
      addForm.amount_cash = props.settings.monthly_fee;
      addForm.amount_upi = 0;
    },
  });
};

// --- EDIT MEMBER ---
const showEditModal = ref(false);
const editingMember = ref(null);

const editForm = useForm({
  name: '',
  phone: '',
  age: null,
  registration_number: '',
  seat_number: null,
  join_date: '',
  due_date: '',
});

const editAvailableSeats = computed(() => {
  if (!editingMember.value) return props.vacantSeats;
  return [editingMember.value.seat_number, ...props.vacantSeats].sort((a, b) => a - b);
});

const openEditModal = (member) => {
  editingMember.value = member;
  editForm.name = member.name;
  editForm.phone = member.phone;
  editForm.age = member.age;
  editForm.registration_number = member.registration_number || '';
  editForm.seat_number = member.seat_number;
  editForm.join_date = member.join_date;
  editForm.due_date = member.due_date;
  showEditModal.value = true;
};

const submitEdit = () => {
  editForm.put(`/members/${editingMember.value.member_id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
    },
  });
};

// --- RENEW MEMBER ---
const showRenewModal = ref(false);
const renewTarget = ref(null);

const renewForm = useForm({
  months_paid: 1,
  amount_cash: 0,
  amount_upi: 0,
  payment_date: todayStr,
});

const renewTotalDue = computed(() => props.settings.monthly_fee * renewForm.months_paid);

const openRenewModal = (member) => {
  renewTarget.value = member;
  renewForm.months_paid = 1;
  renewForm.amount_cash = props.settings.monthly_fee;
  renewForm.amount_upi = 0;
  renewForm.payment_date = todayStr;
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

// --- VACATE MEMBER ---
const showVacateConfirm = ref(false);
const vacateTarget = ref(null);
const vacateProcessing = ref(false);

const confirmVacate = (member) => {
  vacateTarget.value = member;
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

// --- PAYMENTS & RECEIPTS ---
const showPaymentsModal = ref(false);
const paymentsTarget = ref(null);
const paymentRecords = ref([]);
const loadingPayments = ref(false);

const openPaymentsModal = async (member) => {
  paymentsTarget.value = member;
  showPaymentsModal.value = true;
  loadingPayments.value = true;
  paymentRecords.value = [];
  
  try {
    const response = await axios.get(`/members/${member.member_id}/payments`);
    paymentRecords.value = response.data.payments;
  } catch (error) {
    console.error("Failed to load payment records", error);
  } finally {
    loadingPayments.value = false;
  }
};

const downloadReceipt = (payment) => {
  if (!paymentsTarget.value) return;
  generateReceiptPdf({
    receiptTitle: 'Payment Receipt',
    receiptType: 'Membership Payment',
    seat_number: paymentsTarget.value.seat_number,
    member_name: paymentsTarget.value.name,
    phone: paymentsTarget.value.phone,
    registration_number: paymentsTarget.value.registration_number,
    payment_date: payment.payment_date,
    months_paid: payment.months_paid,
    total_amount: payment.total,
    amount_cash: payment.amount_cash,
    amount_upi: payment.amount_upi,
    due_date: paymentsTarget.value.due_date,
  });
};
</script>

<style scoped>
.field-error {
  color: var(--danger);
  font-size: 0.8rem;
  margin-top: 4px;
}
</style>
