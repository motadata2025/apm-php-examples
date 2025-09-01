<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel - APM Example</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
        }

        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
        }

        .info-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .info-card p {
            color: #666;
            font-size: 0.95em;
        }

        .operations {
            padding: 30px;
        }

        .operation-section {
            margin-bottom: 40px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
        }

        .operation-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .button-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn.secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
        }

        .btn.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
        }

        .btn.danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            box-shadow: 0 4px 15px rgba(250, 112, 154, 0.3);
        }

        .result {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            max-height: 300px;
            overflow-y: auto;
        }

        .result pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.4;
            color: #333;
        }

        .loading {
            display: none;
            color: #667eea;
            font-style: italic;
            margin-top: 10px;
        }

        .queue-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            align-items: center;
        }

        .queue-input {
            flex: 1;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .queue-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .queue-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #1565c0;
        }

        .health-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .health-healthy {
            background: #d4edda;
            color: #155724;
        }

        .health-unhealthy {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 2em;
            }

            .info-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .operations {
                padding: 20px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Laravel Application</h1>
            <p>APM Examples - Production Ready Architecture</p>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h3>🔧 Framework</h3>
                <p>{{ $framework }}</p>
            </div>
            <div class="info-card">
                <h3>🐘 PHP Version</h3>
                <p>{{ $phpVersion }}</p>
            </div>
            <div class="info-card">
                <h3>🌐 Web Server</h3>
                <p>{{ $deploymentType }}</p>
            </div>
            <div class="info-card">
                <h3>⚡ Status</h3>
                <p>Ready for Operations</p>
            </div>
        </div>

        <div class="operations">
            <!-- Health Check Section -->
            <div class="operation-section">
                <h2>🏥 Health Check</h2>
                <div class="button-group">
                    <button class="btn" onclick="checkHealth()">Check Application Health</button>
                </div>
                <div class="loading" id="health-loading">Checking application health...</div>
                <div class="result" id="health-result">
                    <pre>Click the button above to check application health...</pre>
                </div>
            </div>

            <!-- Database Operations Section -->
            <div class="operation-section">
                <h2>🗄️ Database Operations</h2>
                <div class="button-group">
                    <button class="btn" onclick="testDatabases()">Test Database Connections</button>
                    <button class="btn secondary" onclick="createTables()">Create Tables</button>
                    <button class="btn success" onclick="demoCrud()">Demo CRUD Operations</button>
                </div>
                <div class="loading" id="database-loading">Processing database operations...</div>
                <div class="result" id="database-result">
                    <pre>Click a button above to test database operations...</pre>
                </div>
            </div>

            <!-- API Operations Section -->
            <div class="operation-section">
                <h2>🌐 API Operations</h2>
                <div class="button-group">
                    <button class="btn" onclick="fetchApiData()">Test External APIs</button>
                </div>
                <div class="loading" id="api-loading">Testing external APIs...</div>
                <div class="result" id="api-result">
                    <pre>Click the button above to test external API integrations...</pre>
                </div>
            </div>

            <!-- Queue System Operations Section -->
            <div class="operation-section">
                <h2>📋 Queue System Operations</h2>
                <div class="queue-info">
                    <strong>Queue Name:</strong> <span id="queue-name">laravel_84_apache_fpm</span><br>
                    <strong>TTL:</strong> 1 minute (auto-expiry)<br>
                    <strong>Backend:</strong> Redis
                </div>
                <div class="button-group">
                    <button class="btn" onclick="testQueue()">Demo Queue Operations</button>
                    <button class="btn success" onclick="addQueueData()">Add Data to Queue</button>
                    <button class="btn secondary" onclick="readQueueData()">Read Data from Queue</button>
                    <button class="btn danger" onclick="clearQueue()">Clear Queue</button>
                </div>
                <div class="queue-input-group">
                    <textarea class="queue-input" id="queue-data" rows="3" placeholder="Auto-generated data will appear here..."></textarea>
                    <button class="btn" onclick="generateRandomData()">Generate New Data</button>
                </div>
                <div class="loading" id="queue-loading">Processing queue operations...</div>
                <div class="result" id="queue-result">
                    <pre>Use the buttons above to interact with the Laravel queue system...</pre>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set CSRF token for all AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Utility functions
        function showLoading(section) {
            document.getElementById(section + '-loading').style.display = 'block';
        }

        function hideLoading(section) {
            document.getElementById(section + '-loading').style.display = 'none';
        }

        function updateResult(section, data) {
            const resultElement = document.getElementById(section + '-result');
            if (typeof data === 'object') {
                resultElement.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } else {
                resultElement.innerHTML = '<pre>' + data + '</pre>';
            }
        }

        function makeRequest(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('_token', csrfToken);

            for (const key in data) {
                formData.append(key, data[key]);
            }

            return fetch('/', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(response => response.json());
        }

        // Health Check
        function checkHealth() {
            showLoading('health');
            fetch('/health')
                .then(response => response.json())
                .then(data => {
                    hideLoading('health');
                    updateResult('health', data);
                })
                .catch(error => {
                    hideLoading('health');
                    updateResult('health', 'Error: ' + error.message);
                });
        }

        // Database Operations
        function testDatabases() {
            showLoading('database');
            makeRequest('test_databases')
                .then(response => {
                    hideLoading('database');
                    if (response.success) {
                        updateResult('database', response.data);
                    } else {
                        updateResult('database', 'Error: ' + response.error);
                    }
                })
                .catch(error => {
                    hideLoading('database');
                    updateResult('database', 'Network error: ' + error.message);
                });
        }

        function createTables() {
            showLoading('database');
            makeRequest('create_tables')
                .then(response => {
                    hideLoading('database');
                    if (response.success) {
                        updateResult('database', response.data);
                    } else {
                        updateResult('database', 'Error: ' + response.error);
                    }
                })
                .catch(error => {
                    hideLoading('database');
                    updateResult('database', 'Network error: ' + error.message);
                });
        }

        function demoCrud() {
            showLoading('database');
            makeRequest('demo_crud')
                .then(response => {
                    hideLoading('database');
                    if (response.success) {
                        updateResult('database', response.data);
                    } else {
                        updateResult('database', 'Error: ' + response.error);
                    }
                })
                .catch(error => {
                    hideLoading('database');
                    updateResult('database', 'Network error: ' + error.message);
                });
        }

        // API Operations
        function fetchApiData() {
            showLoading('api');
            makeRequest('fetch_api_data')
                .then(response => {
                    hideLoading('api');
                    if (response.success) {
                        updateResult('api', response.data);
                    } else {
                        updateResult('api', 'Error: ' + response.error);
                    }
                })
                .catch(error => {
                    hideLoading('api');
                    updateResult('api', 'Network error: ' + error.message);
                });
        }

        // Queue Operations
        function testQueue() {
            showLoading('queue');
            makeRequest('test_queue')
                .then(response => {
                    hideLoading('queue');
                    if (response.success) {
                        updateResult('queue', response.data);
                    } else {
                        updateResult('queue', 'Error: ' + response.error);
                    }
                })
                .catch(error => {
                    hideLoading('queue');
                    updateResult('queue', 'Network error: ' + error.message);
                });
        }

        function addQueueData() {
            const queueData = document.getElementById('queue-data').value.trim();
            if (!queueData) {
                updateResult('queue', 'Please enter some data to add to the queue');
                return;
            }

            try {
                JSON.parse(queueData); // Validate JSON
            } catch (e) {
                updateResult('queue', 'Invalid JSON format');
                return;
            }

            showLoading('queue');
            makeRequest('add_queue_data', { data: queueData })
                .then(response => {
                    hideLoading('queue');
                    if (response.success) {
                        updateResult('queue', response.data);
                    } else {
                        updateResult('queue', 'Error: ' + response.error);
                    }
                })
                .catch(error => {
                    hideLoading('queue');
                    updateResult('queue', 'Network error: ' + error.message);
                });
        }

        function readQueueData() {
            showLoading('queue');
            makeRequest('read_queue_data')
                .then(response => {
                    hideLoading('queue');
                    if (response.success) {
                        updateResult('queue', response);
                    } else {
                        updateResult('queue', 'Error: ' + response.error);
                    }
                })
                .catch(error => {
                    hideLoading('queue');
                    updateResult('queue', 'Network error: ' + error.message);
                });
        }

        function clearQueue() {
            if (!confirm('Are you sure you want to clear the entire queue?')) {
                return;
            }

            showLoading('queue');
            makeRequest('clear_queue')
                .then(response => {
                    hideLoading('queue');
                    if (response.success) {
                        updateResult('queue', response);
                    } else {
                        updateResult('queue', 'Error: ' + response.error);
                    }
                })
                .catch(error => {
                    hideLoading('queue');
                    updateResult('queue', 'Network error: ' + error.message);
                });
        }

        function generateRandomData() {
            makeRequest('generate_random_data')
                .then(response => {
                    if (response.success) {
                        document.getElementById('queue-data').value = JSON.stringify(response.data, null, 2);
                    } else {
                        updateResult('queue', 'Error generating data: ' + response.error);
                    }
                })
                .catch(error => {
                    updateResult('queue', 'Network error: ' + error.message);
                });
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Generate initial random data
            generateRandomData();
        });
    </script>
</body>
</html>