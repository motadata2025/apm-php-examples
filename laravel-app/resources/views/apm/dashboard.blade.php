@extends('layouts.app')

@section('title', 'Laravel APM Example')

@section('content')
<div class="container">
    <div class="header">
        <h1>🚀 Laravel Application</h1>
        <p>APM Integration Example - Laravel Framework Implementation</p>
    </div>

    <div class="info-cards">
        <div class="info-card">
            <h3>PHP Version</h3>
            <p>{{ $phpVersion }}</p>
        </div>
        <div class="info-card">
            <h3>Laravel Version</h3>
            <p>{{ $laravelVersion }}</p>
        </div>
        <div class="info-card">
            <h3>Environment</h3>
            <p>{{ ucfirst($environment) }}</p>
        </div>
        <div class="info-card">
            <h3>Framework</h3>
            <p>Laravel</p>
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
            <div class="result-area" id="queue-results">Use the buttons above to interact with the Laravel queue system...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
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
                'X-CSRF-TOKEN': window.Laravel.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(response => response.json());
    }

    // Database operations
    function testDatabases() {
        showLoader('db');
        makeRequest('{{ route("apm.test-databases") }}')
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
        makeRequest('{{ route("apm.demo-crud") }}')
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
        makeRequest('{{ route("apm.fetch-api-data") }}')
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
        makeRequest('{{ route("apm.test-queue") }}')
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
        makeRequest('{{ route("apm.add-queue-data") }}', { data: data })
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
        makeRequest('{{ route("apm.read-queue-data") }}')
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
        makeRequest('{{ route("apm.clear-queue") }}')
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
        document.getElementById('queue-data').value = '{"message": "Hello from Laravel", "timestamp": "' + new Date().toISOString() + '", "priority": 1}';
    });
</script>
@endpush