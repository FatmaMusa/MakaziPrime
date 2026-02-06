/* assets/js/admin.js */

const API_BASE = '../api';

// ============ SESSION CHECK ============
document.addEventListener('DOMContentLoaded', async () => {
    // Only check session if not on login page
    if (!window.location.href.includes('login.html')) {
        const session = await checkSession();
        if (!session.loggedIn) {
            window.location.href = 'login.html';
        } else {
            const adminNameEl = document.getElementById('adminName');
            if (adminNameEl) {
                adminNameEl.textContent = session.user.name;
            }
        }
    }

    // Initialize mobile menu
    initMobileMenu();
});

async function checkSession() {
    try {
        const res = await fetch(`${API_BASE}/auth/check_session.php`);
        return await res.json();
    } catch (e) {
        return { loggedIn: false };
    }
}

async function logout() {
    await fetch(`${API_BASE}/auth/logout.php`);
    window.location.href = 'login.html';
}

// ============ FETCH API HELPER ============
async function fetchAPI(endpoint, method = 'GET', body = null) {
    const options = {
        method,
        headers: {}
    };

    if (body) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    try {
        const res = await fetch(`${API_BASE}/${endpoint}`, options);
        return await res.json();
    } catch (e) {
        console.error('API Error:', e);
        return null;
    }
}

// ============ MOBILE MENU ============
function initMobileMenu() {
    const sidebar = document.querySelector('.sidebar');
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');

    // Create overlay if it doesn't exist
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    // Toggle sidebar
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.add('active');
            overlay.classList.add('active');
        });
    }

    // Close sidebar when clicking overlay
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });

    // Close sidebar when clicking a nav link (on mobile)
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        });
    });
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    sidebar.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active');
}

// ============ CONFIRMATION MODAL ============
let confirmCallback = null;

function showConfirmModal(message, callback) {
    confirmCallback = callback;

    // Create modal if it doesn't exist
    let modal = document.getElementById('confirmModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'confirmModal';
        modal.className = 'confirm-modal';
        modal.innerHTML = `
            <div class="confirm-modal-content">
                <h3><i class="fas fa-exclamation-triangle" style="color: #f39c12;"></i> Confirm Action</h3>
                <p id="confirmMessage"></p>
                <div class="confirm-modal-actions">
                    <button class="btn btn-danger" onclick="confirmAction(true)">Yes, Proceed</button>
                    <button class="btn btn-primary" onclick="confirmAction(false)">Cancel</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    document.getElementById('confirmMessage').textContent = message;
    modal.classList.add('active');
}

function confirmAction(confirmed) {
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('active');

    if (confirmed && confirmCallback) {
        confirmCallback();
    }
    confirmCallback = null;
}

// ============ STATUS BADGE HELPER ============
function getStatusBadge(status) {
    const statusLower = status.toLowerCase();
    let badgeClass = 'badge-info';

    switch (statusLower) {
        case 'available':
            badgeClass = 'badge-available';
            break;
        case 'sold':
            badgeClass = 'badge-sold';
            break;
        case 'reserved':
            badgeClass = 'badge-reserved';
            break;
        case 'pending':
            badgeClass = 'badge-pending';
            break;
        case 'approved':
            badgeClass = 'badge-approved';
            break;
        case 'rejected':
            badgeClass = 'badge-rejected';
            break;
    }

    return `<span class="badge ${badgeClass}">${status}</span>`;
}

// ============ TABLE LOADER ============
function renderTable(tableId, data, columns, actions = null) {
    const tbody = document.querySelector(`#${tableId} tbody`);
    tbody.innerHTML = '';

    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${columns.length + (actions ? 1 : 0)}" class="text-center">No data found.</td></tr>`;
        return;
    }

    data.forEach(row => {
        const tr = document.createElement('tr');

        columns.forEach(col => {
            const td = document.createElement('td');
            if (col.render) {
                td.innerHTML = col.render(row[col.key], row);
            } else {
                td.textContent = row[col.key] || '-';
            }
            tr.appendChild(td);
        });

        if (actions) {
            const actionTd = document.createElement('td');
            actionTd.innerHTML = actions(row);
            tr.appendChild(actionTd);
        }

        tbody.appendChild(tr);
    });
}

// ============ FORMAT HELPERS ============
function formatCurrency(amount) {
    return 'Tsh ' + new Intl.NumberFormat().format(amount || 0);
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString();
}

function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString();
}
