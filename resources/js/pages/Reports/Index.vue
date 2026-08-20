<template>
  <AuthenticatedLayout>
    <div>
      <!-- Period Filter Buttons -->
      <div class="period-filter-buttons" style="margin-bottom: 20px; display: flex; gap: 8px;">
        <button class="btn period-btn" :class="currentPeriod === 'all' ? 'btn-primary' : 'btn-secondary'" @click="currentPeriod = 'all'">
          <i class="fa-solid fa-calendar"></i> All Time
        </button>
        <button class="btn period-btn" :class="currentPeriod === 'current-month' ? 'btn-primary' : 'btn-secondary'" @click="currentPeriod = 'current-month'">
          <i class="fa-solid fa-calendar-days"></i> Current Month
        </button>
      </div>

      <!-- Report Tabs -->
      <div class="tabs-container">
        <div class="tab-item" :class="{ active: currentTab === 'summary' }" @click="currentTab = 'summary'">
          <i class="fa-solid fa-chart-pie"></i> Summary
        </div>
        <div class="tab-item" :class="{ active: currentTab === 'payments' }" @click="currentTab = 'payments'">
          <i class="fa-solid fa-money-bill"></i> Payments History
        </div>
        <div class="tab-item" :class="{ active: currentTab === 'financials' }" @click="currentTab = 'financials'">
          <i class="fa-solid fa-chart-line"></i> Monthly Financials
        </div>
      </div>

      <!-- Tab Content Container -->
      <div id="reports-content">
        <!-- Summary Tab -->
        <div v-show="currentTab === 'summary'" class="tab-content active">
          <div class="metrics-grid">
            <div class="metric-card primary-glow">
              <div class="metric-info">
                <h3>Occupancy Rate</h3>
                <div class="value">{{ summary.total_seats > 0 ? Math.round((summary.occupied_seats / summary.total_seats) * 100) : 0 }}%</div>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                  {{ summary.occupied_seats }} / {{ summary.total_seats }} seats
                </p>
              </div>
              <div class="metric-icon"><i class="fa-solid fa-chart-pie"></i></div>
            </div>

            <div class="metric-card success-glow">
              <div class="metric-info">
                <h3>Total Revenue</h3>
                <div class="value">{{ formatCurrency(summary.revenue.total) }}</div>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                  Cash: {{ formatCurrency(summary.revenue.cash) }} | UPI: {{ formatCurrency(summary.revenue.upi) }}
                </p>
              </div>
              <div class="metric-icon"><i class="fa-solid fa-wallet"></i></div>
            </div>

            <div class="metric-card danger-glow">
              <div class="metric-info">
                <h3>Total Expenses</h3>
                <div class="value">{{ formatCurrency(summary.expenses) }}</div>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                  Operational costs
                </p>
              </div>
              <div class="metric-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
            </div>

            <div class="metric-card" :class="summary.net_profit >= 0 ? 'success-glow' : 'danger-glow'">
              <div class="metric-info">
                <h3>Net Profit</h3>
                <div class="value" :style="{ color: summary.net_profit >= 0 ? 'var(--success)' : 'var(--danger)' }">
                  {{ formatCurrency(summary.net_profit) }}
                </div>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                  Revenue - Expenses
                </p>
              </div>
              <div class="metric-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
            </div>
          </div>

          <!-- Summary Details Grid -->
          <div class="summary-details-grid">
            <div class="card-panel">
              <h2><i class="fa-solid fa-user-check"></i> Member Status</h2>
              <div class="recent-list">
                <div class="recent-item">
                  <div class="item-main">
                    <span class="item-title">Active Members</span>
                    <span class="item-subtitle">Currently occupied seats</span>
                  </div>
                  <div class="item-meta">{{ summary.occupied_seats }}</div>
                </div>
                <div class="recent-item">
                  <div class="item-main">
                    <span class="item-title">Expired Memberships</span>
                    <span class="item-subtitle">Require renewal or vacate</span>
                  </div>
                  <div class="item-meta" style="background-color: var(--danger-glow); color: var(--danger); padding: 4px 8px; border-radius: 4px; font-weight: 700;">
                    {{ summary.expired_count }}
                  </div>
                </div>
                <div class="recent-item">
                  <div class="item-main">
                    <span class="item-title">Due Within 5 Days</span>
                    <span class="item-subtitle">Renewal reminders pending</span>
                  </div>
                  <div class="item-meta" style="background-color: var(--warning-glow); color: var(--warning); padding: 4px 8px; border-radius: 4px; font-weight: 700;">
                    {{ summary.due_soon_count }}
                  </div>
                </div>
              </div>
            </div>

            <div class="card-panel">
              <h2><i class="fa-solid fa-users"></i> Waiting Queue</h2>
              <div class="recent-list">
                <div class="recent-item">
                  <div class="item-main">
                    <span class="item-title">Clients Waiting</span>
                    <span class="item-subtitle">Available seats: {{ summary.available_seats }}</span>
                  </div>
                  <div class="item-meta">{{ summary.waiting_count }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payments History Tab -->
        <div v-show="currentTab === 'payments'" class="tab-content active">
          <div class="card-panel">
            <h2><i class="fa-solid fa-receipt"></i> Payment Transactions</h2>
            <p style="color: var(--text-muted); margin-bottom: 16px; font-size: 0.9rem;">
              Total of <strong>{{ payments.length }}</strong> payment transactions recorded
            </p>
            
            <div v-if="payments.length === 0" class="empty-state">
              <i class="fa-solid fa-inbox"></i>
              <h3>No payment transactions</h3>
              <p>Payment history will appear here.</p>
            </div>
            
            <div v-else class="table-container">
              <table class="app-table">
                <thead>
                  <tr>
                    <th>Member Name</th>
                    <th style="text-align: center;">Seat</th>
                    <th>Cash</th>
                    <th>UPI</th>
                    <th>Total</th>
                    <th>Duration</th>
                    <th>Payment Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in payments" :key="p.id">
                    <td><strong>{{ p.member_name || 'N/A' }}</strong></td>
                    <td style="text-align: center; color: var(--primary); font-weight: 700;">#{{ p.seat_number || 'N/A' }}</td>
                    <td style="color: var(--success); font-weight: 600;">{{ formatCurrency(p.amount_cash || 0) }}</td>
                    <td style="color: var(--primary); font-weight: 600;">{{ formatCurrency(p.amount_upi || 0) }}</td>
                    <td style="font-weight: 700;">{{ formatCurrency((parseFloat(p.amount_cash) || 0) + (parseFloat(p.amount_upi) || 0)) }}</td>
                    <td><span class="badge badge-success">{{ p.months_paid }} Month{{ p.months_paid > 1 ? 's' : '' }}</span></td>
                    <td>{{ formatDate(p.payment_date) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Monthly Financials Tab -->
        <div v-show="currentTab === 'financials'" class="tab-content active">
          <div class="card-panel">
            <h2><i class="fa-solid fa-chart-line"></i> Monthly Financial Summary</h2>
            <p style="color: var(--text-muted); margin-bottom: 16px; font-size: 0.9rem;">
              Revenue vs Expenses breakdown by month
            </p>
            
            <div v-if="monthlyFinancials.length === 0" class="empty-state">
              <i class="fa-solid fa-inbox"></i>
              <h3>No financial data</h3>
              <p>Monthly financials will appear here.</p>
            </div>
            
            <div v-else class="table-container">
              <table class="app-table">
                <thead>
                  <tr>
                    <th style="width: 120px;">Month</th>
                    <th style="text-align: right;">Revenue</th>
                    <th style="text-align: right; color: var(--success);">Cash</th>
                    <th style="text-align: right; color: var(--primary);">UPI</th>
                    <th style="text-align: right;">Expenses</th>
                    <th style="text-align: right;">Net Profit</th>
                    <th style="text-align: right; width: 180px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="f in monthlyFinancials" :key="f.month">
                    <td style="font-weight: 700;">{{ formatMonthYear(f.month) }}</td>
                    <td style="text-align: right; color: var(--success); font-weight: 600;">{{ formatCurrency(f.revenue) }}</td>
                    <td style="text-align: right; font-weight: 600; color: var(--success);">{{ formatCurrency(f.cash_revenue) }}</td>
                    <td style="text-align: right; font-weight: 600; color: var(--primary);">{{ formatCurrency(f.upi_revenue) }}</td>
                    <td style="text-align: right; color: var(--danger); font-weight: 600;">{{ formatCurrency(f.expenses) }}</td>
                    <td style="text-align: right; font-weight: 700;" :style="{ color: f.profit >= 0 ? 'var(--success)' : 'var(--danger)' }">
                      {{ formatCurrency(f.profit) }}
                    </td>
                    <td style="text-align: right;">
                      <div class="table-action-buttons" style="justify-content:flex-end;">
                        <button type="button" class="btn btn-secondary btn-small" @click="openMonthHistory(f.month)">History</button>
                        <button type="button" class="btn btn-primary btn-small" @click="exportMonthCsv(f.month)">Excel</button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Month History Modal -->
    <div v-if="showMonthModal" class="modal active" @click.self="showMonthModal = false">
      <div class="modal-content" style="max-width: 1000px;">
        <div class="modal-header">
          <h3>Transaction History - {{ formatMonthYear(selectedMonth) }}</h3>
          <button class="modal-close" @click="showMonthModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <div class="table-container" style="margin-bottom: 16px;">
            <table class="app-table">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Description</th>
                  <th>Member</th>
                  <th style="text-align:center;">Seat</th>
                  <th style="text-align:right;">Cash</th>
                  <th style="text-align:right;">UPI</th>
                  <th style="text-align:right;">Expense</th>
                  <th style="text-align:right;">Total</th>
                  <th style="text-align:right;">Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="monthTransactions.length === 0">
                  <td colspan="9" style="text-align:center; color: var(--text-muted); padding: 24px;">
                    No transactions for {{ formatMonthYear(selectedMonth) }}
                  </td>
                </tr>
                <tr v-for="(item, index) in monthTransactions" :key="index">
                  <td>{{ item.type }}</td>
                  <td>{{ item.description }}</td>
                  <td>{{ item.member_name }}</td>
                  <td style="text-align:center;">{{ item.seat_number }}</td>
                  <td style="color: var(--success); font-weight: 600; text-align:right;">{{ item.cash }}</td>
                  <td style="color: var(--primary); font-weight: 600; text-align:right;">{{ item.upi }}</td>
                  <td style="color: var(--danger); font-weight: 600; text-align:right;">{{ item.expense }}</td>
                  <td style="font-weight:700; text-align:right;">{{ item.total }}</td>
                  <td style="text-align:right;">{{ item.date }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer" style="justify-content: space-between;">
          <button type="button" class="btn btn-secondary" @click="showMonthModal = false">Close</button>
          <button type="button" class="btn btn-primary" @click="exportMonthCsv(selectedMonth)">Download Excel</button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';

const props = defineProps({
  summaryAll: Object,
  summaryMonth: Object,
  payments: Array,
  expenses: Array,
  monthlyFinancials: Array,
  isSubAdmin: Boolean,
});

const currentPeriod = ref('all');
const currentTab = ref('summary');

const summary = computed(() => {
  return currentPeriod.value === 'all' ? props.summaryAll : props.summaryMonth;
});

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', minimumFractionDigits: 0 }).format(amount);
};

const formatMonthYear = (monthStr) => {
  if (!monthStr) return '';
  const [year, month] = monthStr.split('-');
  const date = new Date(year, parseInt(month) - 1);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
};

// --- History Modal Logic ---
const showMonthModal = ref(false);
const selectedMonth = ref('');

const monthTransactions = computed(() => {
  return buildMonthTransactions(selectedMonth.value);
});

const buildMonthTransactions = (month) => {
  if (!month) return [];
  
  const monthPayments = props.payments.filter(p => p.payment_date?.startsWith(month));
  const monthExpenses = props.expenses.filter(e => e.date?.startsWith(month));
  
  const paymentRows = monthPayments.map(p => {
    const cashValue = parseFloat(p.amount_cash || 0);
    const upiValue = parseFloat(p.amount_upi || 0);
    const totalValue = cashValue + upiValue;
    return {
      type: 'Payment',
      description: `${p.months_paid || 1} Month${p.months_paid > 1 ? 's' : ''}`,
      member_name: p.member_name || 'N/A',
      seat_number: p.seat_number || 'N/A',
      cash: cashValue.toFixed(2),
      upi: upiValue.toFixed(2),
      expense: '',
      total: totalValue.toFixed(2),
      date: formatDate(p.payment_date),
      rawDate: new Date(p.payment_date)
    };
  });

  const expenseRows = monthExpenses.map(expense => ({
    type: 'Expense',
    description: expense.label || 'Expense',
    member_name: '-',
    seat_number: '-',
    cash: '',
    upi: '',
    expense: parseFloat(expense.amount || 0).toFixed(2),
    total: parseFloat(expense.amount || 0).toFixed(2),
    date: formatDate(expense.date),
    rawDate: new Date(expense.date)
  }));

  return [...paymentRows, ...expenseRows].sort((a, b) => a.rawDate - b.rawDate);
};

const openMonthHistory = (month) => {
  selectedMonth.value = month;
  showMonthModal.value = true;
};

// --- CSV Export Logic ---
const exportMonthCsv = (month) => {
  const transactions = buildMonthTransactions(month);
  
  if (transactions.length === 0) {
    alert('No transactions available for this month');
    return;
  }
  
  const rows = transactions.map(item => ({
    'Type': item.type,
    'Description': item.description,
    'Member': item.member_name,
    'Seat Number': item.seat_number,
    'Cash Amount': item.cash,
    'UPI Amount': item.upi,
    'Expense Amount': item.expense,
    'Total Amount': item.total,
    'Date': item.date
  }));

  const filename = `transactions-${month}.csv`;
  downloadCsvFile(filename, rows);
};

const downloadCsvFile = (filename, dataArray) => {
  if (!dataArray || dataArray.length === 0) return;
  const headers = Object.keys(dataArray[0]);
  const csvRows = [headers.join(',')];

  dataArray.forEach(row => {
    const values = headers.map(header => {
      let val = row[header] === null || row[header] === undefined ? '' : row[header];
      const strVal = String(val);
      if (strVal.includes(',') || strVal.includes('"') || strVal.includes('\n')) {
        return `"${strVal.replace(/"/g, '""')}"`;
      }
      return strVal;
    });
    csvRows.push(values.join(','));
  });

  const csvData = csvRows.join('\n');
  const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
  
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
</script>

<style scoped>
.period-btn {
  padding: 8px 16px;
  border-radius: var(--radius-sm);
  font-weight: 600;
}
.summary-details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
  margin-top: 24px;
}
.recent-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.recent-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: var(--background);
  border-radius: var(--radius-sm);
}
.item-title {
  display: block;
  font-weight: 600;
  color: var(--text-main);
}
.item-subtitle {
  display: block;
  font-size: 0.8rem;
  color: var(--text-muted);
}
.item-meta {
  font-weight: 700;
  color: var(--text-main);
}
</style>
