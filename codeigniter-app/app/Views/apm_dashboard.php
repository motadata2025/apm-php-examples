<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeIgniter APM Dashboard</title>
    <link rel="stylesheet" href="/assets/css/apm-style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>CodeIgniter APM Dashboard</h1>
            <p>Application Performance Monitoring & Testing Interface</p>
        </header>

        <main class="dashboard-grid">
            <!-- Block 1: Application Information -->
            <section class="dashboard-block app-info">
                <h2>Application Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Application Type:</label>
                        <span class="value">CodeIgniter</span>
                    </div>
                    <div class="info-item">
                        <label>PHP Version:</label>
                        <span class="value"><?= PHP_VERSION ?></span>
                    </div>
                    <div class="info-item">
                        <label>Web Server:</label>
                        <span class="value">php_cli</span>
                    </div>
                </div>
                <div class="button-group">
                    <button onclick="testExternalApi()" class="btn btn-primary">Test External API</button>
                </div>
            </section>

            <!-- Block 2: Database Operations -->
            <section class="dashboard-block database">
                <h2>Database Operations</h2>
                <div class="button-group">
                    <button onclick="testDbConnection()" class="btn btn-secondary">Connection Check</button>
                    <button onclick="testDbCrud()" class="btn btn-secondary">DB CRUD Operations</button>
                </div>
                <div id="db-results" class="results-area"></div>
            </section>

            <!-- Block 3: Redis Queue Operations -->
            <section class="dashboard-block redis">
                <h2>Redis Queue Operations</h2>
                <div class="button-group">
                    <button onclick="redisInsertBatch()" class="btn btn-accent">Insert Batch (3)</button>
                    <button onclick="redisInsertOne()" class="btn btn-accent">Insert Single</button>
                    <button onclick="redisPop()" class="btn btn-accent">Pop Message</button>
                    <button onclick="redisClear()" class="btn btn-danger">Clear Queue</button>
                </div>
                <div id="redis-results" class="results-area"></div>
            </section>
        </main>

        <!-- Toast notifications -->
        <div id="toast-container"></div>
    </div>

    <script src="/assets/js/apm.js"></script>
</body>
</html>
