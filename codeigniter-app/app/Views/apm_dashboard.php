<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APM Dashboard - <?= esc($app_type) ?></title>
    <link rel="stylesheet" href="/assets/css/apm-style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>APM Dashboard - <?= esc($app_type) ?></h1>
            <p class="subtitle">Application Performance Monitoring</p>
        </header>

        <main class="dashboard-grid">
            <!-- Application Info Block -->
            <section class="dashboard-block app-info">
                <h2>Application Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Application Type:</label>
                        <span class="value"><?= esc($app_type) ?></span>
                    </div>
                    <div class="info-item">
                        <label>PHP Version:</label>
                        <span class="value"><?= esc($php_version) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Web Server:</label>
                        <span class="value"><?= esc($web_server) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Queue Name:</label>
                        <span class="value"><?= esc($queue_name) ?></span>
                    </div>
                </div>
                <div class="button-group">
                    <button onclick="callExternalApi()" class="btn btn-primary">Test External API</button>
                </div>
            </section>

            <!-- Database Operations Block -->
            <section class="dashboard-block database-ops">
                <h2>Database Operations</h2>
                <div class="button-group">
                    <button onclick="checkDbConnections()" class="btn btn-secondary">Connection Check</button>
                    <button onclick="performDbCrud()" class="btn btn-secondary">DB CRUD Operations</button>
                </div>
                <div id="db-results" class="results-area"></div>
            </section>

            <!-- Redis Operations Block -->
            <section class="dashboard-block redis-ops">
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

        <!-- Global Results Area -->
        <section class="global-results">
            <h3>Operation Results</h3>
            <div id="global-results" class="results-area"></div>
        </section>

        <!-- Toast Notifications -->
        <div id="toast-container" class="toast-container"></div>
    </div>

    <script src="/assets/js/apm.js"></script>
</body>
</html>
