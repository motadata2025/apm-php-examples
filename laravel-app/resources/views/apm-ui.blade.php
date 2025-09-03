<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>APM Laravel UI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5rem;
        }
        
        .info-blocks {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .info-block {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        
        .info-block h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            color: #007bff;
            font-weight: 500;
        }
        
        .action-blocks {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .action-block {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .action-block h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.2rem;
            border-bottom: 2px solid #007bff;
            padding-bottom: 8px;
        }
        
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #1e7e34;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .result-area {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            min-height: 60px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            overflow-x: auto;
        }
        
        .loading {
            color: #007bff;
            font-style: italic;
        }
        
        .success {
            color: #28a745;
        }
        
        .error {
            color: #dc3545;
        }
        
        .redis-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .redis-buttons {
                grid-template-columns: 1fr;
            }
            
            .info-blocks {
                grid-template-columns: 1fr;
            }
            
            .action-blocks {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>APM Laravel UI</h1>
        
        <!-- Application Info Blocks -->
        <div class="info-blocks">
            <div class="info-block">
                <h3>Application Information</h3>
                <div class="info-item">
                    <span class="info-label">Application Type:</span>
                    <span class="info-value">{{ $app_type }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Running PHP Version:</span>
                    <span class="info-value">{{ $php_version }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Web Server:</span>
                    <span class="info-value">{{ $web_server }}</span>
                </div>
            </div>
        </div>
        
        <!-- Action Blocks -->
        <div class="action-blocks">
            <!-- External API Block -->
            <div class="action-block">
                <h3>External API</h3>
                <div class="button-group">
                    <button class="btn btn-primary" onclick="callExternalApi()">Call External API</button>
                </div>
                <div id="external-api-result" class="result-area">Ready to call external API...</div>
            </div>
            
            <!-- Database Block -->
            <div class="action-block">
                <h3>Database Operations</h3>
                <div class="button-group">
                    <button class="btn btn-success" onclick="checkDbConnections()">Connection Check</button>
                    <button class="btn btn-warning" onclick="performDbCrud()">DB Calls</button>
                </div>
                <div id="db-result" class="result-area">Ready to test database operations...</div>
            </div>
            
            <!-- Redis Queue Block -->
            <div class="action-block">
                <h3>Redis Queue Operations</h3>
                <div class="redis-buttons">
                    <button class="btn btn-primary" onclick="redisInsertBulk()">Insert 3 Values</button>
                    <button class="btn btn-primary" onclick="redisInsertSingle()">Insert 1 Value</button>
                    <button class="btn btn-success" onclick="redisReadOne()">Read Single Message</button>
                    <button class="btn btn-danger" onclick="redisClear()">Clear Queue</button>
                </div>
                <div id="redis-result" class="result-area">Ready to perform Redis operations...</div>
            </div>
        </div>
    </div>
    
    <script src="{{ asset('js/apm-ui.js') }}"></script>
</body>
</html>
