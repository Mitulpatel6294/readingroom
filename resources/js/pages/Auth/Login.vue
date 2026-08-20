<template>
  <Head title="Clever's Reading Room Management System" />
  <GuestLayout>
    <div class="login-container" style="display: flex;">
      <div class="login-card">
        <div class="login-header">
          <div class="login-logo"><i class="fa-solid fa-book-open-reader"></i></div>
          <h2>Welcome Back</h2>
          <p>Login to manage your Reading Room</p>
        </div>
        <form @submit.prevent="submit" class="login-form">
          <div v-if="form.errors.username || form.errors.password" style="color: var(--danger); text-align: center; margin-bottom: 15px; font-size: 0.9rem;">
            {{ form.errors.username || form.errors.password }}
          </div>
          <div class="form-group">
            <label for="username"><i class="fa-solid fa-user"></i> Username</label>
            <input type="text" id="username" v-model="form.username" placeholder="Enter username" required>
          </div>
          <div class="form-group">
            <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
            <input type="password" id="password" v-model="form.password" placeholder="Enter password" required autocomplete="current-password">
          </div>
          <button type="submit" class="btn btn-primary btn-block" :disabled="form.processing">
            <span class="btn-text" v-if="!form.processing">Sign In</span>
            <span class="loading-spinner" v-else style="display: inline-block;"><i class="fa-solid fa-spinner fa-spin"></i></span>
          </button>
        </form>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import GuestLayout from '../../layouts/GuestLayout.vue';

const form = useForm({
    username: '',
    password: '',
});

const submit = () => {
    form.post('/login');
};
</script>
