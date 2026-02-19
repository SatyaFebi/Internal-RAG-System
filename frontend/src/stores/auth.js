import { defineStore } from 'pinia';
import axios from '../lib/axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('userRAG')) || null,
        token: localStorage.getItem('tokenRAG') || null,
        loading: false,
        error: null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
    },

    actions: {
        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.post('/login', credentials);
                this.token = response.data.token;
                this.user = response.data.user;
                
                localStorage.setItem('tokenRAG', this.token);
                localStorage.setItem('userRAG', JSON.stringify(this.user));
                
                return response.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'Login failed';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await axios.post('/logout');
            } catch (err) {
                console.error('Logout error', err);
            } finally {
                this.user = null;
                this.token = null;
                localStorage.removeItem('tokenRAG');
                localStorage.removeItem('userRAG');
                window.location.href = '/login';
            }
        },


        async fetchUser() {
            if (!this.token) return;
            
            try {
                const response = await axios.get('/user');
                this.user = response.data;
            } catch (err) {
                this.logout();
            }
        },
    },
});
