// Simple PHP UI JavaScript
(function() {
    'use strict';

    // DOM elements
    const elements = {
        // Application info
        phpVersion: document.getElementById('php-version'),
        
        // API & DB buttons
        externalApiBtn: document.getElementById('external-api-btn'),
        dbCheckBtn: document.getElementById('db-check-btn'),
        dbCrudBtn: document.getElementById('db-crud-btn'),
        apiDbResults: document.getElementById('api-db-results'),
        
        // Redis buttons
        redisInsert3Btn: document.getElementById('redis-insert-3-btn'),
        redisInsert1Btn: document.getElementById('redis-insert-1-btn'),
        redisRead1Btn: document.getElementById('redis-read-1-btn'),
        redisClearBtn: document.getElementById('redis-clear-btn'),
        redisResults: document.getElementById('redis-results')
    };

    // Utility functions
    function showLoading(button) {
        button.disabled = true;
        button.classList.add('loading');
    }

    function hideLoading(button) {
        button.disabled = false;
        button.classList.remove('loading');
    }

    function displayResults(container, data, isError = false) {
        container.className = `results ${isError ? 'error' : 'success'}`;
        container.textContent = JSON.stringify(data, null, 2);
    }

    function displayError(container, message) {
        displayResults(container, { error: message }, true);
    }

    // API call wrapper with timeout
    async function apiCall(url, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 20000); // 20s timeout

        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal,
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                }
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                throw new Error('Request timed out after 20 seconds');
            }
            throw error;
        }
    }

    // Event handlers
    async function handleExternalApi() {
        showLoading(elements.externalApiBtn);
        
        try {
            const result = await apiCall('/api/external', { method: 'POST' });
            displayResults(elements.apiDbResults, result);
        } catch (error) {
            displayError(elements.apiDbResults, error.message);
        } finally {
            hideLoading(elements.externalApiBtn);
        }
    }

    async function handleDbCheck() {
        showLoading(elements.dbCheckBtn);
        
        try {
            const result = await apiCall('/api/db/check', { method: 'POST' });
            displayResults(elements.apiDbResults, result);
        } catch (error) {
            displayError(elements.apiDbResults, error.message);
        } finally {
            hideLoading(elements.dbCheckBtn);
        }
    }

    async function handleDbCrud() {
        showLoading(elements.dbCrudBtn);
        
        try {
            const result = await apiCall('/api/db/crud', { method: 'POST' });
            displayResults(elements.apiDbResults, result);
        } catch (error) {
            displayError(elements.apiDbResults, error.message);
        } finally {
            hideLoading(elements.dbCrudBtn);
        }
    }

    async function handleRedisInsert3() {
        showLoading(elements.redisInsert3Btn);
        
        try {
            const result = await apiCall('/api/redis/insert-multiple?count=3', { method: 'POST' });
            displayResults(elements.redisResults, result);
        } catch (error) {
            displayError(elements.redisResults, error.message);
        } finally {
            hideLoading(elements.redisInsert3Btn);
        }
    }

    async function handleRedisInsert1() {
        showLoading(elements.redisInsert1Btn);
        
        try {
            const result = await apiCall('/api/redis/insert-single', { method: 'POST' });
            displayResults(elements.redisResults, result);
        } catch (error) {
            displayError(elements.redisResults, error.message);
        } finally {
            hideLoading(elements.redisInsert1Btn);
        }
    }

    async function handleRedisRead1() {
        showLoading(elements.redisRead1Btn);
        
        try {
            const result = await apiCall('/api/redis/read-single', { method: 'POST' });
            displayResults(elements.redisResults, result);
        } catch (error) {
            displayError(elements.redisResults, error.message);
        } finally {
            hideLoading(elements.redisRead1Btn);
        }
    }

    async function handleRedisClear() {
        showLoading(elements.redisClearBtn);
        
        try {
            const result = await apiCall('/api/redis/clear', { method: 'POST' });
            displayResults(elements.redisResults, result);
        } catch (error) {
            displayError(elements.redisResults, error.message);
        } finally {
            hideLoading(elements.redisClearBtn);
        }
    }

    // Initialize the application
    function init() {
        // Fetch and display PHP version
        apiCall('/api/php-version')
            .then(result => {
                if (result.ok && elements.phpVersion) {
                    elements.phpVersion.textContent = result.php_version;
                }
            })
            .catch(error => {
                console.error('Failed to fetch PHP version:', error);
            });

        // Attach event listeners
        elements.externalApiBtn?.addEventListener('click', handleExternalApi);
        elements.dbCheckBtn?.addEventListener('click', handleDbCheck);
        elements.dbCrudBtn?.addEventListener('click', handleDbCrud);
        elements.redisInsert3Btn?.addEventListener('click', handleRedisInsert3);
        elements.redisInsert1Btn?.addEventListener('click', handleRedisInsert1);
        elements.redisRead1Btn?.addEventListener('click', handleRedisRead1);
        elements.redisClearBtn?.addEventListener('click', handleRedisClear);
    }

    // Start the application when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
