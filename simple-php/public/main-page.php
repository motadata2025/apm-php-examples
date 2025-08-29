<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple PHP - APM Example</title>
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
            <h1>🐘 Simple PHP Application</h1>
            <p>APM Examples - Production Ready Architecture</p>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h3>🔧 Framework</h3>
                <p><?php echo htmlspecialchars($framework); ?></p>
            </div>
            <div class="info-card">
                <h3>🐘 PHP Version</h3>
                <p><?php echo htmlspecialchars($phpVersion); ?></p>
            </div>
            <div class="info-card">
                <h3>🌐 Web Server</h3>
                <p><?php echo htmlspecialchars($deploymentType); ?></p>
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
                    <button class="btn success" onclick="checkHealth()">Check Application Health</button>
                </div>
                <div id="health-loading" class="loading">Checking health status...</div>
                <div id="health-result" class="result" style="display: none;"></div>
            </div>

            <!-- Database Operations -->
            <div class="operation-section">
                <h2>🗄️ Database Operations</h2>
                <div class="button-group">
                    <button class="btn" onclick="testDatabases()">Test Database Connections</button>
                    <button class="btn secondary" onclick="demoCrud()">Demo CRUD Operations</button>
                </div>
                <div id="db-loading" class="loading">Processing database operations...</div>
                <div id="db-result" class="result" style="display: none;"></div>
            </div>

            <!-- API Operations -->
            <div class="operation-section">
                <h2>🌐 API Operations</h2>
                <div class="button-group">
                    <button class="btn" onclick="fetchApiData()">Test External APIs</button>
                </div>
                <div id="api-loading" class="loading">Fetching API data...</div>
                <div id="api-result" class="result" style="display: none;"></div>
            </div>

            <!-- Queue System Operations -->
            <div class="operation-section">
                <h2>📋 Queue System Operations</h2>
                <div class="queue-info">
                    <strong>Queue Configuration:</strong> Default storage time: 1 minute | Auto-expiry enabled
                </div>
                
                <div class="queue-input-group">
                    <input type="text" id="queue-data" class="queue-input" 
                           value='<?php echo htmlspecialchars(json_encode($randomData, JSON_PRETTY_PRINT)); ?>' 
                           placeholder="Queue data (JSON format)" readonly>
                    <button class="btn success" onclick="generateNewData()">🎲 Generate New Data</button>
                </div>
                
                <div class="button-group">
                    <button class="btn" onclick="demoQueue()">Demo Queue Operations</button>
                    <button class="btn secondary" onclick="addQueueData()">Add Data to Queue</button>
                    <button class="btn success" onclick="readQueueData()">Read Data from Queue</button>
                    <button class="btn danger" onclick="clearQueue()">Clear Queue</button>
                </div>
                <div id="queue-loading" class="loading">Processing queue operations...</div>
                <div id="queue-result" class="result" style="display: none;"></div>
            </div>
        </div>
    </div>

    <script>
        // Generate new random data for queue operations
        function generateNewData() {
            fetch('/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate_random_data'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('queue-data').value = JSON.stringify(data.data, null, 2);
                } else {
                    console.error('Failed to generate random data:', data.error);
                }
            })
            .catch(error => {
                console.error('Error generating random data:', error);
            });
        }

        // Health check function
        function checkHealth() {
            showLoading('health');
            fetch('/health')
                .then(response => response.json())
                .then(data => {
                    hideLoading('health');
                    displayResult('health', formatHealthResult(data));
                })
                .catch(error => {
                    hideLoading('health');
                    displayResult('health', 'Error: ' + error.message);
                });
        }

        // Format health check result
        function formatHealthResult(data) {
            let html = '<h3>Health Status: ' + data.status.toUpperCase() + '</h3>';
            html += '<p><strong>Timestamp:</strong> ' + data.timestamp + '</p>';
            html += '<p><strong>PHP Version:</strong> ' + data.php_version + '</p>';
            html += '<p><strong>Memory Usage:</strong> ' + (data.memory_usage / 1024 / 1024).toFixed(2) + ' MB</p>';
            if (data.uptime) {
                html += '<p><strong>System Uptime:</strong> ' + data.uptime + ' seconds</p>';
            }
            
            html += '<h4>Services:</h4>';
            for (const [service, status] of Object.entries(data.services)) {
                const statusClass = status === 'healthy' ? 'health-healthy' : 'health-unhealthy';
                html += '<span class="health-status ' + statusClass + '">' + service + ': ' + status + '</span> ';
            }
            
            return html;
        }

        // Database operations
        function testDatabases() {
            makeRequest('test_databases', 'db');
        }

        function demoCrud() {
            makeRequest('demo_crud', 'db');
        }

        // API operations
        function fetchApiData() {
            makeRequest('fetch_api_data', 'api');
        }

        // Queue operations
        function demoQueue() {
            makeRequest('test_queue', 'queue');
        }

        function addQueueData() {
            const queueData = document.getElementById('queue-data').value;
            try {
                const data = JSON.parse(queueData);
                makeRequest('add_queue_data', 'queue', { data: JSON.stringify(data) });
                // Generate new data after adding
                setTimeout(generateNewData, 1000);
            } catch (error) {
                displayResult('queue', 'Error: Invalid JSON data');
            }
        }

        function readQueueData() {
            makeRequest('read_queue_data', 'queue');
        }

        function clearQueue() {
            if (confirm('Are you sure you want to clear the entire queue?')) {
                makeRequest('clear_queue', 'queue');
            }
        }

        // Generic request function
        function makeRequest(action, section, extraData = {}) {
            showLoading(section);

            const formData = new FormData();
            formData.append('action', action);

            for (const [key, value] of Object.entries(extraData)) {
                formData.append(key, value);
            }

            fetch('/', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading(section);
                if (data.success) {
                    displayResult(section, formatResult(data));
                } else {
                    displayResult(section, 'Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                hideLoading(section);
                displayResult(section, 'Error: ' + error.message);
            });
        }

        // Format result for display
        function formatResult(data) {
            return '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }

        // Show loading indicator
        function showLoading(section) {
            document.getElementById(section + '-loading').style.display = 'block';
            document.getElementById(section + '-result').style.display = 'none';
        }

        // Hide loading indicator
        function hideLoading(section) {
            document.getElementById(section + '-loading').style.display = 'none';
        }

        // Display result
        function displayResult(section, content) {
            const resultDiv = document.getElementById(section + '-result');
            resultDiv.innerHTML = content;
            resultDiv.style.display = 'block';
        }

        // Auto-generate new data every 30 seconds
        setInterval(generateNewData, 30000);
    </script>
</body>
</html>
