// Admin Panel - Shared JS Utilities

const ADMIN_API = '../../backend/api';

// Get stored admin token
function getAdminToken() {
    return localStorage.getItem('token');
}

// Admin-authenticated API fetch
async function adminFetch(endpoint, options = {}) {
    const token = getAdminToken();
    if (!token) {
        window.location.href = '../../frontend/login.html';
        return;
    }
    options.headers = options.headers || {};
    options.headers['Authorization'] = `Bearer ${token}`;
    if (!(options.body instanceof FormData)) {
        options.headers['Content-Type'] = 'application/json';
    }
    try {
        const res = await fetch(`${ADMIN_API}/${endpoint}`, options);
        if (res.status === 401 || res.status === 403) {
            adminToast('Session expired. Redirecting to login.', 'error');
            setTimeout(() => {
                localStorage.removeItem('token');
                window.location.href = '../../frontend/login.html';
            }, 1500);
            return { status: 'error' };
        }
        return await res.json();
    } catch (e) {
        console.error(e);
        return { status: 'error', message: 'Network error' };
    }
}

// Admin Toast Notifications
function adminToast(message, type = 'success') {
    let container = document.querySelector('.admin-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'admin-toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'admin-toast';
    const icon = type === 'success'
        ? '<i class="bi bi-check-circle-fill" style="color:#16a34a; font-size:1.2rem;"></i>'
        : '<i class="bi bi-exclamation-triangle-fill" style="color:#dc2626; font-size:1.2rem;"></i>';
    toast.innerHTML = `${icon}<span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(40px)';
        toast.style.transition = 'all 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Format Indian Rupees
function adminFormatPrice(amount) {
    return '₹' + parseFloat(amount).toLocaleString('en-IN', { maximumFractionDigits: 2 });
}

// Verify admin is logged in on page load
document.addEventListener('DOMContentLoaded', () => {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    if (!getAdminToken() || user.role !== 'admin') {
        window.location.href = '../../frontend/login.html';
    }
    // Highlight active sidebar link
    const links = document.querySelectorAll('.sidebar-link');
    links.forEach(link => {
        if (link.href === window.location.href) {
            link.classList.add('active');
        }
    });
});
