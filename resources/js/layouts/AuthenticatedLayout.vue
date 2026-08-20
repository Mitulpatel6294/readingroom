<template>
  <div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="sidebar" :class="{ 'collapsed': isSidebarCollapsed }">
      <div class="sidebar-brand">
        <i class="fa-solid fa-book-open-reader"></i>
        <span id="site-title-brand">Clever's Room</span>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="nav-item active">
            <Link href="/dashboard"><i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span></Link>
          </li>
          <!-- Other items to be added as features are developed -->
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
          <h1>Dashboard</h1>
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
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const isSidebarCollapsed = ref(false);

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

const currentDate = computed(() => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date().toLocaleDateString('en-US', options);
});
</script>
