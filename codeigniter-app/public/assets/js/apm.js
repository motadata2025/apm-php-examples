/**
 * APM Dashboard JavaScript
 * Handles AJAX calls to backend endpoints with timeout support
 */

// Global configuration
const CONFIG = {
    timeout: 15000, // 15 seconds
    baseUrl: window.location.origin
};

/**
 * Create AbortController with timeout
 */
function createTimeoutController(timeoutMs = CONFIG.timeout) {
    const controller = new AbortController();
    setTimeout(() => controller.abort(), timeoutMs);
    return controller;
}

/**
 * Make API call with timeout
 */
async function apiCall(endpoint, options = {}) {
    const controller = createTimeoutController();
    
    const defaultOptions = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        signal: controller.signal
    };

    const finalOptions = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(`${CONFIG.baseUrl}${endpoint}`, finalOptions);
        const data = await response.json();
        
        return {
            ok: response.ok,
            status: response.status,
            data: data
        };
    } catch (error) {
        if (error.name === 'AbortError') {
            throw new Error('Request timed out after ' + (CONFIG.timeout / 1000) + ' seconds');
        }
        throw error;
    }
}

/**
 * Display results in a target element
 */
function displayResults(targetId, data, title = '') {
    const element = document.getElementById(targetId);
    if (!element) return;

    const timestamp = new Date().toLocaleTimeString();
    const header = title ? `=== ${title} (${timestamp}) ===\n` : `=== Results (${timestamp}) ===\n`;
    
    let content = header;
    if (typeof data === 'object') {
        content += JSON.stringify(data, null, 2);
    } else {
        content += data;
    }
    
    element.textContent = content;
    element.scrollTop = element.scrollHeight;
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

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

/**
 * Set loading state for button
 */
function setButtonLoading(button, loading = true) {
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.textContent;
        button.textContent = 'Loading...';
        button.classList.add('loading');
    } else {
        button.disabled = false;
        button.textContent = button.dataset.originalText || button.textContent;
        button.classList.remove('loading');
    }
}

/**
 * External API call
 */
async function callExternalApi() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/external');
        
        displayResults('global-results', result.data, 'External API Call');
        
        if (result.data.ok) {
            showToast('External API call successful', 'success');
        } else {
            showToast('External API call failed: ' + result.data.error, 'error');
        }
    } catch (error) {
        const errorData = { error: error.message, timestamp: new Date().toISOString() };
        displayResults('global-results', errorData, 'External API Error');
        showToast('External API error: ' + error.message, 'error');
    } finally {
        setButtonLoading(button, false);
    }
}

/**
 * Database connection check
 */
async function checkDbConnections() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/db/connection');
        
        displayResults('db-results', result.data, 'Database Connection Check');
        displayResults('global-results', result.data, 'Database Connection Check');
        
        const mysqlOk = result.data.mysql && result.data.mysql.ok;
        const pgOk = result.data.pg && result.data.pg.ok;
        
        if (mysqlOk && pgOk) {
            showToast('All database connections successful', 'success');
        } else {
            showToast('Some database connections failed', 'warning');
        }
    } catch (error) {
        const errorData = { error: error.message, timestamp: new Date().toISOString() };
        displayResults('db-results', errorData, 'Database Connection Error');
        displayResults('global-results', errorData, 'Database Connection Error');
        showToast('Database connection error: ' + error.message, 'error');
    } finally {
        setButtonLoading(button, false);
    }
}

/**
 * Database CRUD operations
 */
async function performDbCrud() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/db/crud');
        
        displayResults('db-results', result.data, 'Database CRUD Operations');
        displayResults('global-results', result.data, 'Database CRUD Operations');
        
        const mysqlOk = result.data.mysql && result.data.mysql.ok;
        const pgOk = result.data.pg && result.data.pg.ok;
        
        if (mysqlOk && pgOk) {
            showToast('Database CRUD operations successful', 'success');
        } else {
            showToast('Some database CRUD operations failed', 'warning');
        }
    } catch (error) {
        const errorData = { error: error.message, timestamp: new Date().toISOString() };
        displayResults('db-results', errorData, 'Database CRUD Error');
        displayResults('global-results', errorData, 'Database CRUD Error');
        showToast('Database CRUD error: ' + error.message, 'error');
    } finally {
        setButtonLoading(button, false);
    }
}

/**
 * Redis insert batch
 */
async function redisInsertBatch() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/redis/insert-batch');
        
        displayResults('redis-results', result.data, 'Redis Insert Batch');
        displayResults('global-results', result.data, 'Redis Insert Batch');
        
        if (result.data.ok) {
            showToast(`Inserted ${result.data.inserted} messages to queue`, 'success');
        } else {
            showToast('Redis insert batch failed: ' + result.data.error, 'error');
        }
    } catch (error) {
        const errorData = { error: error.message, timestamp: new Date().toISOString() };
        displayResults('redis-results', errorData, 'Redis Insert Batch Error');
        displayResults('global-results', errorData, 'Redis Insert Batch Error');
        showToast('Redis insert batch error: ' + error.message, 'error');
    } finally {
        setButtonLoading(button, false);
    }
}

/**
 * Redis insert one
 */
async function redisInsertOne() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/redis/insert-one');
        
        displayResults('redis-results', result.data, 'Redis Insert One');
        displayResults('global-results', result.data, 'Redis Insert One');
        
        if (result.data.ok) {
            showToast(`Message inserted. Queue length: ${result.data.queue_length}`, 'success');
        } else {
            showToast('Redis insert one failed: ' + result.data.error, 'error');
        }
    } catch (error) {
        const errorData = { error: error.message, timestamp: new Date().toISOString() };
        displayResults('redis-results', errorData, 'Redis Insert One Error');
        displayResults('global-results', errorData, 'Redis Insert One Error');
        showToast('Redis insert one error: ' + error.message, 'error');
    } finally {
        setButtonLoading(button, false);
    }
}

/**
 * Redis pop message
 */
async function redisPop() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/redis/pop');
        
        displayResults('redis-results', result.data, 'Redis Pop Message');
        displayResults('global-results', result.data, 'Redis Pop Message');
        
        if (result.data.ok) {
            const message = result.data.message || 'No message in queue';
            showToast(`Popped: ${message}. Queue length: ${result.data.queue_length}`, 'success');
        } else {
            showToast('Redis pop failed: ' + result.data.error, 'error');
        }
    } catch (error) {
        const errorData = { error: error.message, timestamp: new Date().toISOString() };
        displayResults('redis-results', errorData, 'Redis Pop Error');
        displayResults('global-results', errorData, 'Redis Pop Error');
        showToast('Redis pop error: ' + error.message, 'error');
    } finally {
        setButtonLoading(button, false);
    }
}

/**
 * Redis clear queue
 */
async function redisClear() {
    const button = event.target;
    setButtonLoading(button, true);
    
    try {
        const result = await apiCall('/api/redis/clear');
        
        displayResults('redis-results', result.data, 'Redis Clear Queue');
        displayResults('global-results', result.data, 'Redis Clear Queue');
        
        if (result.data.ok) {
            showToast('Queue cleared successfully', 'success');
        } else {
            showToast('Redis clear failed: ' + result.data.error, 'error');
        }
    } catch (error) {
        const errorData = { error: error.message, timestamp: new Date().toISOString() };
        displayResults('redis-results', errorData, 'Redis Clear Error');
        displayResults('global-results', errorData, 'Redis Clear Error');
        showToast('Redis clear error: ' + error.message, 'error');
    } finally {
        setButtonLoading(button, false);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('APM Dashboard initialized');
    showToast('APM Dashboard loaded successfully', 'success');
});
