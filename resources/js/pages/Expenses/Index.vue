<template>
  <Head title="Expenses" />
  <AuthenticatedLayout>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
      
      <!-- Expenses Log List -->
      <div>
        <div class="table-actions" style="margin-bottom:16px;">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" v-model="searchQuery" placeholder="Search expenses by description...">
          </div>
          <div style="font-size: 1rem; font-weight: 700; color: var(--text-main); background: white; border: 1px solid var(--border); padding: 8px 16px; border-radius: var(--radius-sm); box-shadow: var(--shadow-sm);">
            Total Outflow: <span style="color: var(--danger); font-family: 'Outfit';">{{ formatCurrency(totalExpensesSum) }}</span>
          </div>
        </div>

        <div id="expenses-container">
          <div v-if="filteredExpenses.length === 0" class="empty-state">
            <i class="fa-solid fa-wallet"></i>
            <h3>No expenses logged</h3>
            <p>Use the panel to log operational costs like electricity, rent, or repairs.</p>
          </div>

          <div v-else class="table-container">
            <table class="app-table">
              <thead>
                <tr>
                  <th>Expense Description</th>
                  <th>Amount</th>
                  <th>Expense Date</th>
                  <th style="width: 100px; text-align: center;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="exp in filteredExpenses" :key="exp.id">
                  <td><strong>{{ exp.label }}</strong></td>
                  <td style="color: var(--danger); font-weight: 700;">{{ formatCurrency(exp.amount) }}</td>
                  <td>{{ formatDate(exp.date) }}</td>
                  <td style="text-align: center; display: flex; justify-content: center; gap: 10px;">
                    <button class="btn-icon btn-icon-secondary edit-expense-btn" @click="openEditModal(exp)" title="Edit Expense">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="btn-icon btn-icon-danger delete-expense-btn" @click="confirmDelete(exp)" title="Delete Expense">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Add Expense Panel -->
      <div class="card-panel">
        <h2><i class="fa-solid fa-wallet"></i> Log New Expense</h2>
        <form @submit.prevent="submitAdd">
          <div class="form-group">
            <label for="exp-label">Description / Label*</label>
            <input type="text" id="exp-label" required placeholder="e.g. Electricity Bill, Rent" v-model="addForm.label">
            <div v-if="addForm.errors.label" class="field-error">{{ addForm.errors.label }}</div>
          </div>
          <div class="form-group">
            <label for="exp-amount">Amount (₹)*</label>
            <input type="number" id="exp-amount" required min="1" step="any" placeholder="Enter amount in ₹" v-model.number="addForm.amount">
            <div v-if="addForm.errors.amount" class="field-error">{{ addForm.errors.amount }}</div>
          </div>
          <div class="form-group">
            <label for="exp-date">Date*</label>
            <input type="date" id="exp-date" required :max="todayStr" v-model="addForm.date">
            <div v-if="addForm.errors.date" class="field-error">{{ addForm.errors.date }}</div>
          </div>
          <button type="submit" class="btn btn-danger btn-block" :disabled="addForm.processing"><i class="fa-solid fa-save"></i> Log Expense</button>
        </form>
      </div>

    </div>

    <!-- Edit Expense Modal -->
    <div v-if="showEditModal" class="modal active" @click.self="showEditModal = false">
      <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
          <h3>Edit Expense</h3>
          <button class="modal-close" @click="showEditModal = false">&times;</button>
        </div>
        <form @submit.prevent="submitEdit">
          <div class="modal-body">
            <div class="form-group">
              <label for="edit-exp-label">Description / Label*</label>
              <input type="text" id="edit-exp-label" required v-model="editForm.label">
              <div v-if="editForm.errors.label" class="field-error">{{ editForm.errors.label }}</div>
            </div>
            <div class="form-group">
              <label for="edit-exp-amount">Amount (₹)*</label>
              <input type="number" id="edit-exp-amount" required min="1" step="any" v-model.number="editForm.amount">
              <div v-if="editForm.errors.amount" class="field-error">{{ editForm.errors.amount }}</div>
            </div>
            <div class="form-group">
              <label for="edit-exp-date">Date*</label>
              <input type="date" id="edit-exp-date" required :max="todayStr" v-model="editForm.date">
              <div v-if="editForm.errors.date" class="field-error">{{ editForm.errors.date }}</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showEditModal = false">Cancel</button>
            <button type="submit" class="btn btn-success" :disabled="editForm.processing"><i class="fa-solid fa-save"></i> Save Changes</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Remove Modal -->
    <div v-if="showDeleteConfirm" class="modal active" @click.self="showDeleteConfirm = false">
      <div class="modal-content confirm-modal-content">
        <div class="confirm-icon"><i class="fa-solid fa-circle-question"></i></div>
        <h3>Delete Expense?</h3>
        <p>Are you sure you want to delete the expense: "{{ deleteTarget?.label }}" of value {{ formatCurrency(deleteTarget?.amount) }}?</p>
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
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';

const props = defineProps({
  expenses: Array,
});

const todayStr = new Date().toISOString().split('T')[0];
const searchQuery = ref('');

const filteredExpenses = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  if (!query) return props.expenses;
  return props.expenses.filter(exp => 
    exp.label.toLowerCase().includes(query)
  );
});

const totalExpensesSum = computed(() => {
  return props.expenses.reduce((sum, exp) => sum + parseFloat(exp.amount), 0);
});

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', minimumFractionDigits: 0 }).format(amount);
};

// --- ADD EXPENSE ---
const addForm = useForm({
  label: '',
  amount: '',
  date: todayStr,
});

const submitAdd = () => {
  addForm.post('/expenses', {
    preserveScroll: true,
    onSuccess: () => {
      addForm.reset();
    },
  });
};

// --- EDIT EXPENSE ---
const showEditModal = ref(false);
const editingExpense = ref(null);

const editForm = useForm({
  label: '',
  amount: '',
  date: '',
});

const openEditModal = (exp) => {
  editingExpense.value = exp;
  editForm.label = exp.label;
  editForm.amount = exp.amount;
  editForm.date = exp.date;
  showEditModal.value = true;
};

const submitEdit = () => {
  editForm.put(`/expenses/${editingExpense.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
    },
  });
};

// --- DELETE EXPENSE ---
const showDeleteConfirm = ref(false);
const deleteTarget = ref(null);
const deleteProcessing = ref(false);

const confirmDelete = (exp) => {
  deleteTarget.value = exp;
  showDeleteConfirm.value = true;
};

const executeDelete = () => {
  deleteProcessing.value = true;
  router.delete(`/expenses/${deleteTarget.value.id}`, {
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
.btn-block {
  display: block;
  width: 100%;
}
</style>
