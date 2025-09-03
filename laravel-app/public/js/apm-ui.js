// APM UI JavaScript Functions
// Get CSRF token from meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Utility function to make AJAX requests
async function makeRequest(url, method = 'POST', data = {}) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    };
    
    if (method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        return {
            ok: false,
            details: { error: 'Network error: ' + error.message }
        };
    }
}

// Utility function to update result area
function updateResult(elementId, result, isLoading = false) {
    const element = document.getElementById(elementId);
    
    if (isLoading) {
        element.innerHTML = 'Loading...';
        element.className = 'result-area loading';
        return;
    }
    
    element.className = result.ok ? 'result-area success' : 'result-area error';
    element.innerHTML = JSON.stringify(result, null, 2);
}

// Utility function to disable/enable button
function toggleButton(buttonElement, disabled) {
    buttonElement.disabled = disabled;
}

// External API Functions
async function callExternalApi() {
    const button = event.target;
    toggleButton(button, true);
    updateResult('external-api-result', null, true);
    
    try {
        const result = await makeRequest('/api/external-call');
        updateResult('external-api-result', result);
    } finally {
        toggleButton(button, false);
    }
}

// Database Functions
async function checkDbConnections() {
    const button = event.target;
    toggleButton(button, true);
    updateResult('db-result', null, true);
    
    try {
        const result = await makeRequest('/api/db-check');
        updateResult('db-result', result);
    } finally {
        toggleButton(button, false);
    }
}

async function performDbCrud() {
    const button = event.target;
    toggleButton(button, true);
    updateResult('db-result', null, true);
    
    try {
        const result = await makeRequest('/api/db-crud');
        updateResult('db-result', result);
    } finally {
        toggleButton(button, false);
    }
}

// Redis Functions
async function redisInsertBulk() {
    const button = event.target;
    toggleButton(button, true);
    updateResult('redis-result', null, true);
    
    try {
        const result = await makeRequest('/api/redis/insert-bulk');
        updateResult('redis-result', result);
    } finally {
        toggleButton(button, false);
    }
}

async function redisInsertSingle() {
    const button = event.target;
    toggleButton(button, true);
    updateResult('redis-result', null, true);
    
    try {
        const result = await makeRequest('/api/redis/insert-single');
        updateResult('redis-result', result);
    } finally {
        toggleButton(button, false);
    }
}

async function redisReadOne() {
    const button = event.target;
    toggleButton(button, true);
    updateResult('redis-result', null, true);
    
    try {
        const result = await makeRequest('/api/redis/pop');
        updateResult('redis-result', result);
    } finally {
        toggleButton(button, false);
    }
}

async function redisClear() {
    const button = event.target;
    toggleButton(button, true);
    updateResult('redis-result', null, true);
    
    try {
        const result = await makeRequest('/api/redis/clear');
        updateResult('redis-result', result);
    } finally {
        toggleButton(button, false);
    }
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('APM UI loaded successfully');
    
    // Add some visual feedback for button interactions
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mousedown', function() {
            this.style.transform = 'scale(0.98)';
        });
        
        button.addEventListener('mouseup', function() {
            this.style.transform = 'scale(1)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});
