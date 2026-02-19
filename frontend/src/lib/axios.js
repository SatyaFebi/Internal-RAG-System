import axios from 'axios';

const instance = axios.create({
    baseURL: 'http://localhost:8000/api', // Adjust if your backend port is different
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

// Interceptor to add the token to every request
instance.interceptors.request.use((config) => {
    const token = localStorage.getItem('tokenRAG');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});


export default instance;
