<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeIgniter APM Example</title>

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
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .info-cards {
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
            text-align: center;
        }

        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .info-card p {
            color: #7f8c8d;
            font-size: 1.1rem;
            font-weight: bold;
        }

        .main-content {
            padding: 30px;
        }

        .section {
            margin-bottom: 40px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #e67e22;
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .button-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .btn {
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(230, 126, 34, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn.success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }

        .btn.warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }

        .btn.danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }

        .result-area {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            min-height: 100px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            overflow-x: auto;
        }

        .loader {
            display: none;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #e67e22;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            display: none;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .queue-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            font-family: 'Courier New', monospace;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
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
            <h1>🚀 CodeIgniter Application</h1>
            <p>APM Integration Example - CodeIgniter Framework Implementation</p>
        </div>

        <div class="info-cards">
            <div class="info-card">
                <h3>PHP Version</h3>
                <p><?= esc($phpVersion) ?></p>
            </div>
            <div class="info-card">
                <h3>CodeIgniter Version</h3>
                <p><?= esc($ciVersion) ?></p>
            </div>
            <div class="info-card">
                <h3>Environment</h3>
                <p><?= esc(ucfirst($environment)) ?></p>
            </div>
            <div class="info-card">
                <h3>Framework</h3>
                <p>CodeIgniter</p>
            </div>
        </div>

        <div class="main-content">
            <!-- Database Operations Section -->
            <div class="section">
                <h2>🗄️ Database Operations</h2>
                <div class="button-group">
                    <button class="btn" onclick="testDatabases()">Test Database Connections</button>
                    <button class="btn success" onclick="demoCrud()">Demo CRUD Operations</button>
                </div>
                <div class="loader" id="db-loader"></div>
                <div class="alert" id="db-alert"></div>
                <div class="result-area" id="db-results">Click a button above to test database operations...</div>
            </div>

            <!-- External API Section -->
            <div class="section">
                <h2>🌐 External API Calls</h2>
                <div class="button-group">
                    <button class="btn" onclick="fetchApiData()">Fetch External API Data</button>
                </div>
                <div class="loader" id="api-loader"></div>
                <div class="alert" id="api-alert"></div>
                <div class="result-area" id="api-results">Click the button above to fetch data from external APIs...</div>
            </div>

            <!-- Queue Operations Section -->
            <div class="section">
                <h2>📋 Queue System Operations</h2>
                <div class="button-group">
                    <button class="btn" onclick="testQueue()">Demo Queue Operations</button>
                    <button class="btn success" onclick="addQueueData()">Add Data to Queue</button>
                    <button class="btn warning" onclick="readQueueData()">Read Data from Queue</button>
                    <button class="btn danger" onclick="clearQueue()">Clear Queue</button>
                </div>

                <textarea class="queue-input" id="queue-data" placeholder='Enter JSON data to add to queue, e.g., {"message": "Hello World", "priority": 1}'></textarea>

                <div class="loader" id="queue-loader"></div>
                <div class="alert" id="queue-alert"></div>
                <div class="result-area" id="queue-results">Use the buttons above to interact with the CodeIgniter queue system...</div>
            </div>
        </div>
    </div>

    <script>
        // Utility functions
        function showLoader(section) {
            document.getElementById(section + '-loader').style.display = 'block';
            hideAlert(section);
        }

        function hideLoader(section) {
            document.getElementById(section + '-loader').style.display = 'none';
        }

        function showAlert(section, message, type = 'success') {
            const alert = document.getElementById(section + '-alert');
            alert.textContent = message;
            alert.className = 'alert ' + type;
            alert.style.display = 'block';

            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        function hideAlert(section) {
            document.getElementById(section + '-alert').style.display = 'none';
        }

        function updateResults(section, data) {
            document.getElementById(section + '-results').textContent = JSON.stringify(data, null, 2);
        }

        function makeRequest(url, data = {}) {
            const formData = new FormData();

            for (const key in data) {
                formData.append(key, data[key]);
            }

            return fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(response => response.json());
        }

        // Database operations
        function testDatabases() {
            showLoader('db');
            makeRequest('<?= base_url('apm/testDatabases') ?>')
                .then(response => {
                    hideLoader('db');
                    if (response.success) {
                        updateResults('db', response.data);
                        showAlert('db', 'Database connections tested successfully!');
                    } else {
                        showAlert('db', 'Error: ' + response.error, 'error');
                    }
                })
                .catch(error => {
                    hideLoader('db');
                    showAlert('db', 'Network error: ' + error.message, 'error');
                });
        }

        function demoCrud() {
            showLoader('db');
            makeRequest('<?= base_url('apm/demoCrud') ?>')
                .then(response => {
                    hideLoader('db');
                    if (response.success) {
                        updateResults('db', response.data);
                        showAlert('db', 'CRUD operations completed successfully!');
                    } else {
                        showAlert('db', 'Error: ' + response.error, 'error');
                    }
                })
                .catch(error => {
                    hideLoader('db');
                    showAlert('db', 'Network error: ' + error.message, 'error');
                });
        }

        // API operations
        function fetchApiData() {
            showLoader('api');
            makeRequest('<?= base_url('apm/fetchApiData') ?>')
                .then(response => {
                    hideLoader('api');
                    if (response.success) {
                        updateResults('api', response.data);
                        showAlert('api', 'External API data fetched successfully!');
                    } else {
                        showAlert('api', 'Error: ' + response.error, 'error');
                    }
                })
                .catch(error => {
                    hideLoader('api');
                    showAlert('api', 'Network error: ' + error.message, 'error');
                });
        }

        // Queue operations
        function testQueue() {
            showLoader('queue');
            makeRequest('<?= base_url('apm/testQueue') ?>')
                .then(response => {
                    hideLoader('queue');
                    if (response.success) {
                        updateResults('queue', response.data);
                        showAlert('queue', 'Queue operations completed successfully!');
                    } else {
                        showAlert('queue', 'Error: ' + response.error, 'error');
                    }
                })
                .catch(error => {
                    hideLoader('queue');
                    showAlert('queue', 'Network error: ' + error.message, 'error');
                });
        }

        function addQueueData() {
            const data = document.getElementById('queue-data').value.trim();
            if (!data) {
                showAlert('queue', 'Please enter some data to add to the queue', 'error');
                return;
            }

            try {
                JSON.parse(data); // Validate JSON
            } catch (e) {
                showAlert('queue', 'Invalid JSON format', 'error');
                return;
            }

            showLoader('queue');
            makeRequest('<?= base_url('apm/addQueueData') ?>', { data: data })
                .then(response => {
                    hideLoader('queue');
                    if (response.success) {
                        updateResults('queue', { message: response.message, data: JSON.parse(data) });
                        showAlert('queue', 'Data added to queue successfully!');
                        document.getElementById('queue-data').value = '';
                    } else {
                        showAlert('queue', 'Error: ' + response.error, 'error');
                    }
                })
                .catch(error => {
                    hideLoader('queue');
                    showAlert('queue', 'Network error: ' + error.message, 'error');
                });
        }

        function readQueueData() {
            showLoader('queue');
            makeRequest('<?= base_url('apm/readQueueData') ?>')
                .then(response => {
                    hideLoader('queue');
                    if (response.success) {
                        if (response.data) {
                            updateResults('queue', response.data);
                            showAlert('queue', 'Data read from queue successfully!');
                        } else {
                            updateResults('queue', { message: 'Queue is empty' });
                            showAlert('queue', 'Queue is empty', 'warning');
                        }
                    } else {
                        showAlert('queue', 'Error: ' + response.error, 'error');
                    }
                })
                .catch(error => {
                    hideLoader('queue');
                    showAlert('queue', 'Network error: ' + error.message, 'error');
                });
        }

        function clearQueue() {
            if (!confirm('Are you sure you want to clear the entire queue?')) {
                return;
            }

            showLoader('queue');
            makeRequest('<?= base_url('apm/clearQueue') ?>')
                .then(response => {
                    hideLoader('queue');
                    if (response.success) {
                        updateResults('queue', { message: response.message });
                        showAlert('queue', 'Queue cleared successfully!');
                    } else {
                        showAlert('queue', 'Error: ' + response.error, 'error');
                    }
                })
                .catch(error => {
                    hideLoader('queue');
                    showAlert('queue', 'Network error: ' + error.message, 'error');
                });
        }

        // Initialize with sample data
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('queue-data').value = '{"message": "Hello from CodeIgniter", "timestamp": "' + new Date().toISOString() + '", "priority": 1}';
        });
    </script>
</body>
</html>