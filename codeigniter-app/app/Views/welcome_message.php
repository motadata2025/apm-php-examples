<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeIgniter APM Demo</title>
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
        }

        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .info-card p {
            color: #7f8c8d;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .main-content {
            padding: 30px;
        }

        .section {
            margin-bottom: 40px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 5px solid #e74c3c;
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .btn.success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }

        .btn.success:hover {
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
        }

        .btn.warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }

        .btn.warning:hover {
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.4);
        }

        .btn.danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }

        .btn.danger:hover {
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .btn-small {
            padding: 5px 12px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .btn-small:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }

        .loader {
            display: none;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            display: none;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            font-weight: 600;
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

        .alert.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .result-area {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
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

            .info-cards {
                grid-template-columns: 1fr;
            }

            .container {
                margin: 10px;
                border-radius: 10px;
            }

            .main-content, .info-cards {
                padding: 20px;
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
                <p><?= phpversion() ?></p>
            </div>
            <div class="info-card">
                <h3>CodeIgniter Version</h3>
                <p><?= \CodeIgniter\CodeIgniter::CI_VERSION ?></p>
            </div>
            <div class="info-card">
                <h3>Environment</h3>
                <p><?= ucfirst(ENVIRONMENT) ?></p>
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
                    <button class="btn success" onclick="testCrud()">Demo CRUD Operations</button>
                </div>
                <div class="loader" id="db-loader"></div>
                <div class="alert" id="db-alert"></div>
                <div class="result-area" id="db-results">Click a button above to test database operations...</div>
            </div>

            <!-- External API Section -->
            <div class="section">
                <h2>🌐 External API Calls</h2>
                <div class="button-group">
                    <button class="btn" onclick="testApiCalls()">Fetch External API Data</button>
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
                    <button class="btn warning" onclick="testHealth()">Health Check</button>
                </div>
                <div class="loader" id="queue-loader"></div>
                <div class="alert" id="queue-alert"></div>
                <div class="result-area" id="queue-results">Use the buttons above to interact with the queue system...</div>
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
            const resultsDiv = document.getElementById(section + '-results');
            resultsDiv.textContent = JSON.stringify(data, null, 2);
        }

        async function makeRequest(url, method = 'POST', data = null) {
            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    }
                };

                if (data) {
                    options.body = JSON.stringify(data);
                }

                const response = await fetch(url, options);
                const result = await response.json();
                return { success: response.ok, data: result, status: response.status };
            } catch (error) {
                return { success: false, error: error.message };
            }
        }

        async function testDatabases() {
            showLoader('db');
            const result = await makeRequest('/apm/test-databases', 'POST');
            hideLoader('db');

            if (result.success) {
                updateResults('db', result.data);
                showAlert('db', 'Database connections tested successfully!');
            } else {
                showAlert('db', 'Error: ' + (result.error || 'Unknown error'), 'error');
                updateResults('db', result);
            }
        }

        async function testCrud() {
            showLoader('db');
            const result = await makeRequest('/apm/demo-crud', 'POST');
            hideLoader('db');

            if (result.success) {
                updateResults('db', result.data);
                showAlert('db', 'CRUD operations completed successfully!');
            } else {
                showAlert('db', 'Error: ' + (result.error || 'Unknown error'), 'error');
                updateResults('db', result);
            }
        }

        async function testApiCalls() {
            showLoader('api');
            const result = await makeRequest('/apm/fetch-api-data', 'POST');
            hideLoader('api');

            if (result.success) {
                updateResults('api', result.data);
                showAlert('api', 'API data fetched successfully!');
            } else {
                showAlert('api', 'Error: ' + (result.error || 'Unknown error'), 'error');
                updateResults('api', result);
            }
        }

        async function testQueue() {
            showLoader('queue');
            const result = await makeRequest('/apm/test-queue', 'POST');
            hideLoader('queue');

            if (result.success) {
                updateResults('queue', result.data);
                showAlert('queue', 'Queue operations completed successfully!');
            } else {
                showAlert('queue', 'Error: ' + (result.error || 'Unknown error'), 'error');
                updateResults('queue', result);
            }
        }

        async function testHealth() {
            showLoader('queue');
            const result = await makeRequest('/apm/health', 'GET');
            hideLoader('queue');

            if (result.success) {
                updateResults('queue', result.data);
                showAlert('queue', 'Health check completed successfully!');
            } else {
                showAlert('queue', 'Error: ' + (result.error || 'Unknown error'), 'error');
                updateResults('queue', result);
            }
        }
    </script>
</body>
</html>
