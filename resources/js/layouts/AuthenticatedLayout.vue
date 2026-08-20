<template>
  <div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="sidebar" :class="{ 'collapsed': isSidebarCollapsed }">
      <div class="sidebar-brand">
        <i class="fa-solid fa-book-open-reader"></i>
        <span id="site-title-brand">{{ $page.props.siteName || 'Clever\'s Room' }}</span>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="nav-item" :class="{ active: $page.component === 'Dashboard' }">
            <Link href="/dashboard"><i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span></Link>
          </li>
          <li class="nav-item" :class="{ active: $page.component?.startsWith('Seats') }">
            <Link href="/seats"><i class="fa-solid fa-chair"></i> <span>Seats Grid</span></Link>
          </li>
          <li class="nav-item" :class="{ active: $page.component?.startsWith('Members') }">
            <Link href="/members"><i class="fa-solid fa-users"></i> <span>Members</span></Link>
          </li>
          <li class="nav-item" :class="{ active: $page.component === 'Settings' }">
            <Link href="/settings"><i class="fa-solid fa-gears"></i> <span>Settings</span></Link>
          </li>
        </ul>
      </nav>
      <div class="sidebar-footer">
        <Link href="/logout" method="post" as="button" class="btn btn-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Sign Out</span></Link>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content">
      <!-- Topbar Header -->
      <header class="topbar">
        <div class="topbar-left">
          <button @click="toggleSidebar" class="sidebar-toggle"><i class="fa-solid fa-bars"></i></button>
          <h1>{{ pageTitle }}</h1>
        </div>
        <div class="topbar-right">
          <div class="current-date"><i class="fa-regular fa-calendar"></i> <span>{{ currentDate }}</span></div>
          <div class="user-profile">
            <div class="user-avatar"><i class="fa-solid fa-user-tie"></i></div>
            <div class="user-info" v-if="$page.props.auth && $page.props.auth.user">
              <span class="user-name">{{ $page.props.auth.user.username }}</span>
              <span class="user-role">{{ $page.props.auth.user.username === 'subclever' ? 'Subadmin' : 'Administrator' }}</span>
            </div>
          </div>
        </div>
      </header>

      <!-- Dynamic Content Render Container -->
      <main class="content-area">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const isSidebarCollapsed = ref(false);

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

const currentDate = computed(() => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date().toLocaleDateString('en-US', options);
});

const PAGE_TITLES = {
    'Dashboard': 'Dashboard',
    'Settings': 'Settings',
    'Seats/Index': 'Seats Grid',
    'Members/Index': 'Members',
};

const pageTitle = computed(() => {
    return PAGE_TITLES[page.component] || 'Dashboard';
});

// Flash messages handler
const showToast = (message, type) => {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let iconClass = 'fa-info-circle';
    if (type === 'success') iconClass = 'fa-check-circle';
    if (type === 'danger') iconClass = 'fa-circle-xmark';
    if (type === 'warning') iconClass = 'fa-triangle-exclamation';
    
    toast.innerHTML = `
      <div class="toast-icon"><i class="fa-solid ${iconClass}"></i></div>
      <div class="toast-message">${message}</div>
      <button class="toast-close"><i class="fa-solid fa-xmark"></i></button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
      toast.classList.add('show');
    }, 10);
    
    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', () => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 300);
    });
    
    setTimeout(() => {
      if (toast.parentElement) {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
      }
    }, 5000);
};

onMounted(() => {
    // Check flash messages on mount
    if (page.props.flash?.success) showToast(page.props.flash.success, 'success');
    if (page.props.flash?.error) showToast(page.props.flash.error, 'danger');
});

watch(() => page.props.flash, (flash) => {
    if (flash?.success) showToast(flash.success, 'success');
    if (flash?.error) showToast(flash.error, 'danger');
}, { deep: true });
</script>
