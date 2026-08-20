<template>
  <AuthenticatedLayout>
    <div>
      <div class="table-actions">
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" v-model="searchQuery" placeholder="Search waiting list by name or phone...">
        </div>
        <button class="btn btn-primary" @click="showAddModal = true">
          <i class="fa-solid fa-plus"></i> Add to Waitlist
        </button>
      </div>

      <div id="waitlist-container">
        <div v-if="filteredWaitlist.length === 0" class="empty-state">
          <i class="fa-solid fa-clock-rotate-left" style="color: #cbd5e1;"></i>
          <h3>Waiting list is empty</h3>
          <p>Add people here when all seats are full.</p>
        </div>

        <div v-else class="table-container">
          <table class="app-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Phone Number</th>
                <th>Age</th>
                <th>Registration ID</th>
                <th>Added On</th>
                <th style="width: 150px; text-align: center;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in filteredWaitlist" :key="entry.id">
                <td><strong>{{ entry.name }}</strong></td>
                <td><a :href="'tel:' + entry.phone" style="color: var(--primary); font-weight:600;"><i class="fa-solid fa-phone"></i> {{ entry.phone }}</a></td>
                <td>{{ entry.age || 'N/A' }}</td>
                <td>
                  <code v-if="entry.registration_number" style="font-size: 0.85rem; background: var(--background); padding:2px 6px; border-radius:4px;">{{ entry.registration_number }}</code>
                  <span v-else>N/A</span>
                </td>
                <td>{{ formatDate(entry.added_on) }}</td>
                <td style="text-align: center;">
                  <div class="table-actions-cell" style="justify-content: center;">
                    <button class="btn-icon promote-btn" :disabled="vacantSeats.length === 0" @click="openPromoteModal(entry)" title="Promote to Active Seat">
                      <i class="fa-solid fa-user-check" style="color: var(--success);"></i>
                    </button>
                    <button class="btn-icon btn-icon-danger delete-waitlist-btn" @click="confirmDelete(entry)" title="Remove Entry">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add to Waitlist Modal -->
    <div v-if="showAddModal" class="modal active" @click.self="showAddModal = false">
      <div class="modal-content" style="max-width: 440px;">
        <div class="modal-header">
          <h3>Add to Waiting List</h3>
          <button class="modal-close" @click="showAddModal = false">&times;</button>
        </div>
        <form @submit.prevent="submitAdd">
          <div class="modal-body">
            <div class="form-group">
              <label for="wait-name">Full Name*</label>
              <input type="text" id="wait-name" required placeholder="Enter full name" v-model="addForm.name">
              <div v-if="addForm.errors.name" class="field-error">{{ addForm.errors.name }}</div>
            </div>
            
            <div class="form-group">
              <label for="wait-phone">Phone Number*</label>
              <input type="tel" id="wait-phone" required placeholder="10-digit mobile" v-model="addForm.phone">
              <div v-if="addForm.errors.phone" class="field-error">{{ addForm.errors.phone }}</div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap:16px;">
              <div class="form-group">
                <label for="wait-age">Age (Optional)</label>
                <input type="number" id="wait-age" min="1" max="120" placeholder="e.g. 22" v-model="addForm.age">
              </div>
              <div class="form-group">
                <label for="wait-reg">ID Card/Reg ID (Optional)</label>
                <input type="text" id="wait-reg" placeholder="e.g. REG1040" v-model="addForm.registration_number">
              </div>
            </div>

            <div class="form-group">
              <label for="wait-date">Added Date*</label>
              <input type="date" id="wait-date" required :min="todayStr" v-model="addForm.added_on">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showAddModal = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="addForm.processing"><i class="fa-solid fa-plus"></i> Add to Queue</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Promote to Seat Modal -->
    <div v-if="showPromoteModal" class="modal active" @click.self="showPromoteModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Promote {{ promoteTarget?.name }} to Seat</h3>
          <button class="modal-close" @click="showPromoteModal = false">&times;</button>
        </div>
        <form @submit.prevent="submitPromote">
          <div class="modal-body">
            <div style="background-color: var(--primary-light); color: var(--primary); padding:12px; border-radius:var(--radius-sm); margin-bottom:16px; font-size:0.85rem; font-weight:500;">
              Promoting client from waiting list. Details will be synced automatically.
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap:16px;">
              <div class="form-group">
                <label for="promote-seat">Choose Seat*</label>
                <select id="promote-seat" required v-model="promoteForm.seat_number">
                  <option v-for="s in vacantSeats" :key="s" :value="s">Seat #{{ s }}</option>
                </select>
                <div v-if="promoteForm.errors.seat_number" class="field-error">{{ promoteForm.errors.seat_number }}</div>
              </div>
              <div class="form-group">
                <label for="promote-join">Join Date*</label>
                <input type="date" id="promote-join" required :max="todayStr" v-model="promoteForm.join_date">
              </div>
            </div>

            <div class="form-group">
              <label for="promote-months">Duration (Months)*</label>
              <select id="promote-months" required v-model.number="promoteForm.months_paid" @change="resetPromoteSplit">
                <option :value="1">1 Month</option>
                <option :value="2">2 Months</option>
                <option :value="3">3 Months</option>
                <option :value="6">6 Months</option>
                <option :value="12">12 Months</option>
              </select>
            </div>

            <!-- Payment Calculation -->
            <div style="background-color: var(--background); padding:16px; border-radius:var(--radius-sm); margin-bottom:16px; border:1px solid var(--border);">
              <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span>Monthly Fee:</span>
                <strong>{{ formatCurrency(settings.monthly_fee) }}</strong>
              </div>
              <div style="display:flex; justify-content:space-between; margin-bottom:12px; padding-bottom:8px; border-bottom:1px dashed var(--border);">
                <span>Duration:</span>
                <strong>{{ promoteForm.months_paid }} Month{{ promoteForm.months_paid > 1 ? 's' : '' }}</strong>
              </div>
              <div style="display:flex; justify-content:space-between; font-weight:700; font-size:1rem; color:var(--text-main);">
                <span>Total Amount Due:</span>
                <span>{{ formatCurrency(promoteTotalDue) }}</span>
              </div>
            </div>

            <div class="form-group">
              <label>Payment Split</label>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:6px;">
                <div>
                  <label for="promote-cash" style="font-size: 0.8rem; font-weight: normal; margin-bottom: 2px;">Cash Amount (₹)</label>
                  <input type="number" id="promote-cash" min="0" step="any" v-model.number="promoteForm.amount_cash" @input="syncPromoteSplit('cash')">
                  <div v-if="promoteForm.errors.amount_cash" class="field-error">{{ promoteForm.errors.amount_cash }}</div>
                </div>
                <div>
                  <label for="promote-upi" style="font-size: 0.8rem; font-weight: normal; margin-bottom: 2px;">UPI Amount (₹)</label>
                  <input type="number" id="promote-upi" min="0" step="any" v-model.number="promoteForm.amount_upi" @input="syncPromoteSplit('upi')">
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showPromoteModal = false">Cancel</button>
            <button type="submit" class="btn btn-success" :disabled="promoteForm.processing"><i class="fa-solid fa-user-check"></i> Assign Seat & Remove from Waitlist</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Remove Modal -->
    <div v-if="showDeleteConfirm" class="modal active" @click.self="showDeleteConfirm = false">
      <div class="modal-content confirm-modal-content">
        <div class="confirm-icon"><i class="fa-solid fa-circle-question"></i></div>
        <h3>Remove from Waiting List?</h3>
        <p>Are you sure you want to remove {{ deleteTarget?.name }} from the waiting list?</p>
        <div class="confirm-buttons">
          <button class="btn btn-secondary" @click="showDeleteConfirm = false">No</button>
          <button class="btn btn-danger" @click="executeDelete" :disabled="deleteProcessing">Yes</button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { generateReceiptPdf } from '../../utils/pdf';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';

const props = defineProps({
  waitlist: Array,
  vacantSeats: Array,
  settings: Object,
});

const todayStr = new Date().toISOString().split('T')[0];
const searchQuery = ref('');

const filteredWaitlist = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  if (!query) return props.waitlist;
  return props.waitlist.filter(w => 
    w.name.toLowerCase().includes(query) || 
    w.phone.includes(query)
  );
});

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', minimumFractionDigits: 0 }).format(amount);
};

// --- ADD TO WAITLIST ---
const showAddModal = ref(false);

const addForm = useForm({
  name: '',
  phone: '',
  age: null,
  registration_number: '',
  added_on: todayStr,
});

const submitAdd = () => {
  addForm.post('/waitlist', {
    preserveScroll: true,
    onSuccess: () => {
      showAddModal.value = false;
      addForm.reset();
    },
  });
};

// --- PROMOTE TO SEAT ---
const showPromoteModal = ref(false);
const promoteTarget = ref(null);

const promoteForm = useForm({
  waitlist_id: null,
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

const promoteTotalDue = computed(() => props.settings.monthly_fee * promoteForm.months_paid);

const openPromoteModal = (entry) => {
  promoteTarget.value = entry;
  promoteForm.waitlist_id = entry.id;
  promoteForm.name = entry.name;
  promoteForm.phone = entry.phone;
  promoteForm.age = entry.age;
  promoteForm.registration_number = entry.registration_number;
  promoteForm.seat_number = props.vacantSeats.length > 0 ? props.vacantSeats[0] : null;
  promoteForm.join_date = todayStr;
  promoteForm.months_paid = 1;
  promoteForm.amount_cash = props.settings.monthly_fee;
  promoteForm.amount_upi = 0;
  promoteForm.payment_date = todayStr;
  showPromoteModal.value = true;
};

const resetPromoteSplit = () => {
  promoteForm.amount_cash = promoteTotalDue.value;
  promoteForm.amount_upi = 0;
};

const syncPromoteSplit = (changedField) => {
  const total = promoteTotalDue.value;
  if (changedField === 'cash') {
    if (promoteForm.amount_cash > total) promoteForm.amount_cash = total;
    promoteForm.amount_upi = Math.max(0, total - (promoteForm.amount_cash || 0));
  } else {
    if (promoteForm.amount_upi > total) promoteForm.amount_upi = total;
    promoteForm.amount_cash = Math.max(0, total - (promoteForm.amount_upi || 0));
  }
};

const submitPromote = () => {
  promoteForm.post('/members', {
    preserveScroll: true,
    onSuccess: () => {
      // Generate receipt automatically on promotion to match Seat booking logic
      let d = new Date(promoteForm.join_date);
      d.setMonth(d.getMonth() + promoteForm.months_paid);
      
      generateReceiptPdf({
        receiptTitle: 'Booking Receipt',
        receiptType: 'Seat Booking Payment',
        seat_number: promoteForm.seat_number,
        member_name: promoteForm.name,
        phone: promoteForm.phone,
        registration_number: promoteForm.registration_number,
        payment_date: promoteForm.payment_date,
        months_paid: promoteForm.months_paid,
        total_amount: promoteTotalDue.value,
        amount_cash: promoteForm.amount_cash,
        amount_upi: promoteForm.amount_upi,
        due_date: d.toISOString().split('T')[0]
      });

      showPromoteModal.value = false;
      promoteForm.reset();
    },
  });
};

// --- REMOVE ENTRY ---
const showDeleteConfirm = ref(false);
const deleteTarget = ref(null);
const deleteProcessing = ref(false);

const confirmDelete = (entry) => {
  deleteTarget.value = entry;
  showDeleteConfirm.value = true;
};

const executeDelete = () => {
  deleteProcessing.value = true;
  router.delete(`/waitlist/${deleteTarget.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteConfirm.value = false;
      deleteTarget.value = null;
    },
    onFinish: () => {
      deleteProcessing.value = false;
    },
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
