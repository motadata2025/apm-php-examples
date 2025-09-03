/**
 * CodeIgniter APM Dashboard JavaScript
 * Handles AJAX calls to API endpoints with timeouts and user feedback
 */

// Utility functions
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    container.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}

function setButtonLoading(button, loading) {
    if (loading) {
        button.classList.add('loading');
        button.disabled = true;
    } else {
        button.classList.remove('loading');
        button.disabled = false;
    }
}

function updateResultsArea(elementId, data) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = JSON.stringify(data, null, 2);
    }
}

// API call wrapper with timeout
async function apiCall(url, options = {}) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            signal: controller.signal,
            ...options
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    } catch (error) {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            throw new Error('Request timed out after 15 seconds');
        }
        throw error;
    }
}

// External API test
async function testExternalApi() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/external');
        
        if (result.ok) {
            showToast('External API call successful', 'success');
            console.log('External API Response:', result);
        } else {
            showToast(`External API failed: ${result.error}`, 'error');
        }
    } catch (error) {
        showToast(`External API error: ${error.message}`, 'error');
    } finally {
        setButtonLoading(button, false);
    }
}

// Database connection test
async function testDbConnection() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/db/connection');
        
        let successCount = 0;
        let messages = [];
        
        if (result.mysql && result.mysql.ok) {
            successCount++;
            messages.push('MySQL: ✓ Connected');
        } else {
            messages.push(`MySQL: ✗ ${result.mysql?.message || 'Failed'}`);
        }
        
        if (result.pg && result.pg.ok) {
            successCount++;
            messages.push('PostgreSQL: ✓ Connected');
        } else {
            messages.push(`PostgreSQL: ✗ ${result.pg?.message || 'Failed'}`);
        }
        
        updateResultsArea('db-results', result);
        
        if (successCount === 2) {
            showToast('All database connections successful', 'success');
        } else if (successCount === 1) {
            showToast('Partial database connectivity', 'warning');
        } else {
            showToast('Database connections failed', 'error');
        }
        
    } catch (error) {
        showToast(`Database test error: ${error.message}`, 'error');
        updateResultsArea('db-results', { error: error.message });
    } finally {
        setButtonLoading(button, false);
    }
}

// Database CRUD operations
async function testDbCrud() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/db/crud');
        
        let successCount = 0;
        let messages = [];
        
        if (result.mysql && result.mysql.ok) {
            successCount++;
            messages.push(`MySQL CRUD: ✓ Inserted ID ${result.mysql.inserted_id}, Total: ${result.mysql.total_count}`);
        } else {
            messages.push(`MySQL CRUD: ✗ ${result.mysql?.error || 'Failed'}`);
        }
        
        if (result.pg && result.pg.ok) {
            successCount++;
            messages.push(`PostgreSQL CRUD: ✓ Inserted ID ${result.pg.inserted_id}, Total: ${result.pg.total_count}`);
        } else {
            messages.push(`PostgreSQL CRUD: ✗ ${result.pg?.error || 'Failed'}`);
        }
        
        updateResultsArea('db-results', result);
        
        if (successCount === 2) {
            showToast('Database CRUD operations successful', 'success');
        } else if (successCount === 1) {
            showToast('Partial CRUD success', 'warning');
        } else {
            showToast('Database CRUD operations failed', 'error');
        }
        
    } catch (error) {
        showToast(`Database CRUD error: ${error.message}`, 'error');
        updateResultsArea('db-results', { error: error.message });
    } finally {
        setButtonLoading(button, false);
    }
}

// Redis operations
async function redisInsertBatch() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/redis/insert-batch');
        
        updateResultsArea('redis-results', result);
        
        if (result.ok) {
            showToast(`Inserted ${result.inserted} messages. Queue length: ${result.queue_length}`, 'success');
        } else {
            showToast(`Redis batch insert failed: ${result.error}`, 'error');
        }
        
    } catch (error) {
        showToast(`Redis batch insert error: ${error.message}`, 'error');
        updateResultsArea('redis-results', { error: error.message });
    } finally {
        setButtonLoading(button, false);
    }
}

async function redisInsertOne() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/redis/insert-one');
        
        updateResultsArea('redis-results', result);
        
        if (result.ok) {
            showToast(`Message inserted. Queue length: ${result.queue_length}`, 'success');
        } else {
            showToast(`Redis insert failed: ${result.error}`, 'error');
        }
        
    } catch (error) {
        showToast(`Redis insert error: ${error.message}`, 'error');
        updateResultsArea('redis-results', { error: error.message });
    } finally {
        setButtonLoading(button, false);
    }
}

async function redisPop() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/redis/pop');
        
        updateResultsArea('redis-results', result);
        
        if (result.ok) {
            if (result.message) {
                showToast(`Popped message. Remaining: ${result.remaining_count}`, 'success');
            } else {
                showToast('Queue is empty', 'warning');
            }
        } else {
            showToast(`Redis pop failed: ${result.error}`, 'error');
        }
        
    } catch (error) {
        showToast(`Redis pop error: ${error.message}`, 'error');
        updateResultsArea('redis-results', { error: error.message });
    } finally {
        setButtonLoading(button, false);
    }
}

async function redisClear() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/redis/clear');
        
        updateResultsArea('redis-results', result);
        
        if (result.ok) {
            showToast('Queue cleared successfully', 'success');
        } else {
            showToast(`Redis clear failed: ${result.error}`, 'error');
        }
        
    } catch (error) {
        showToast(`Redis clear error: ${error.message}`, 'error');
        updateResultsArea('redis-results', { error: error.message });
    } finally {
        setButtonLoading(button, false);
    }
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    showToast('CodeIgniter APM Dashboard loaded', 'success');
    
    // Add click handlers for any buttons that might not have onclick attributes
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        if (!button.onclick) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                // Button-specific logic can be added here if needed
            });
        }
    });
});
