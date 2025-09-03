/**
 * Slim Framework APM Dashboard JavaScript
 * Handles AJAX calls to API endpoints and UI updates
 */

// Utility functions
function showSpinner(spinnerId) {
    const spinner = document.getElementById(spinnerId);
    if (spinner) {
        spinner.classList.remove('d-none');
    }
}

function hideSpinner(spinnerId) {
    const spinner = document.getElementById(spinnerId);
    if (spinner) {
        spinner.classList.add('d-none');
    }
}

function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container');
    const toastId = 'toast-' + Date.now();
    
    const bgClass = type === 'success' ? 'bg-success' : 
                   type === 'error' ? 'bg-danger' : 
                   type === 'warning' ? 'bg-warning' : 'bg-info';
    
    const toastHtml = `
        <div class="toast ${bgClass} text-white" id="${toastId}" role="alert">
            <div class="toast-header ${bgClass} text-white border-0">
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

function updateResultDiv(divId, content, isError = false) {
    const div = document.getElementById(divId);
    if (div) {
        div.innerHTML = `<div class="alert ${isError ? 'alert-danger' : 'alert-success'} alert-sm">${content}</div>`;
    }
}

// API call wrapper
async function apiCall(endpoint, spinnerId) {
    showSpinner(spinnerId);
    
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error?.msg || `HTTP ${response.status}`);
        }
        
        return data;
    } catch (error) {
        throw error;
    } finally {
        hideSpinner(spinnerId);
    }
}

// External API test
async function testExternalApi() {
    try {
        const result = await apiCall('/api/external', 'external-spinner');
        
        if (result.ok) {
            const payload = result.payload;
            const message = `HTTP ${payload.http_code} - ${payload.duration_ms}ms`;
            updateResultDiv('external-result', message);
            showToast(`External API test successful (${payload.duration_ms}ms)`, 'success');
        } else {
            throw new Error(result.error?.msg || 'External API test failed');
        }
    } catch (error) {
        updateResultDiv('external-result', `Error: ${error.message}`, true);
        showToast(`External API test failed: ${error.message}`, 'error');
    }
}

// Database connection test
async function testDbConnection() {
    try {
        const result = await apiCall('/api/db/check', 'db-check-spinner');
        
        if (result.ok) {
            const payload = result.payload;
            const message = `MySQL: ${payload.mysql}, PostgreSQL: ${payload.pg}`;
            updateResultDiv('db-result', message);
            showToast('Database connection test successful', 'success');
        } else {
            throw new Error('Database connection test failed');
        }
    } catch (error) {
        updateResultDiv('db-result', `Error: ${error.message}`, true);
        showToast(`Database connection test failed: ${error.message}`, 'error');
    }
}

// Database CRUD operations
async function testDbCrud() {
    try {
        const result = await apiCall('/api/db/crud', 'db-crud-spinner');
        
        if (result.ok) {
            const payload = result.payload;
            const mysqlInfo = payload.mysql.ok ? `MySQL ID: ${payload.mysql.inserted_id}` : 'MySQL: Error';
            const pgInfo = payload.pg.ok ? `PG ID: ${payload.pg.inserted_id}` : 'PG: Error';
            const message = `${mysqlInfo}, ${pgInfo}`;
            updateResultDiv('db-result', message);
            showToast('Database CRUD operations successful', 'success');
        } else {
            throw new Error('Database CRUD operations failed');
        }
    } catch (error) {
        updateResultDiv('db-result', `Error: ${error.message}`, true);
        showToast(`Database CRUD operations failed: ${error.message}`, 'error');
    }
}

// Redis insert bulk
async function redisInsertBulk() {
    try {
        const result = await apiCall('/api/redis/insert_bulk', 'redis-bulk-spinner');
        
        if (result.ok) {
            const payload = result.payload;
            const message = `Inserted 3 items. Queue length: ${payload.new_length}`;
            updateResultDiv('redis-result', message);
            showToast(`Inserted 3 items. Queue length: ${payload.new_length}`, 'success');
        } else {
            throw new Error(result.error?.msg || 'Redis bulk insert failed');
        }
    } catch (error) {
        updateResultDiv('redis-result', `Error: ${error.message}`, true);
        showToast(`Redis bulk insert failed: ${error.message}`, 'error');
    }
}

// Redis insert single
async function redisInsertSingle() {
    try {
        const result = await apiCall('/api/redis/insert_single', 'redis-single-spinner');
        
        if (result.ok) {
            const payload = result.payload;
            const message = `Inserted 1 item. Queue length: ${payload.queue_length}`;
            updateResultDiv('redis-result', message);
            showToast(`Inserted 1 item. Queue length: ${payload.queue_length}`, 'success');
        } else {
            throw new Error(result.error?.msg || 'Redis single insert failed');
        }
    } catch (error) {
        updateResultDiv('redis-result', `Error: ${error.message}`, true);
        showToast(`Redis single insert failed: ${error.message}`, 'error');
    }
}

// Redis read single
async function redisReadSingle() {
    try {
        const result = await apiCall('/api/redis/read_single', 'redis-read-spinner');
        
        if (result.ok) {
            const payload = result.payload;
            if (payload.empty) {
                const message = 'Queue is empty';
                updateResultDiv('redis-result', message);
                showToast('Queue is empty', 'warning');
            } else {
                const message = `Read: ${payload.popped_value.substring(0, 30)}... Remaining: ${payload.remaining_length}`;
                updateResultDiv('redis-result', message);
                showToast(`Read 1 item. Remaining: ${payload.remaining_length}`, 'success');
            }
        } else {
            throw new Error(result.error?.msg || 'Redis read failed');
        }
    } catch (error) {
        updateResultDiv('redis-result', `Error: ${error.message}`, true);
        showToast(`Redis read failed: ${error.message}`, 'error');
    }
}

// Redis clear queue
async function redisClear() {
    try {
        const result = await apiCall('/api/redis/clear', 'redis-clear-spinner');
        
        if (result.ok) {
            const payload = result.payload;
            const message = `Cleared queue. Previous length: ${payload.previous_length}`;
            updateResultDiv('redis-result', message);
            showToast(`Queue cleared. Previous length: ${payload.previous_length}`, 'success');
        } else {
            throw new Error(result.error?.msg || 'Redis clear failed');
        }
    } catch (error) {
        updateResultDiv('redis-result', `Error: ${error.message}`, true);
        showToast(`Redis clear failed: ${error.message}`, 'error');
    }
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    showToast('Slim Framework APM Dashboard loaded', 'success');
});
