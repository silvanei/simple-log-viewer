## API Documentation

### POST /api/logs

This endpoint allows you to send log entries to the log viewer.

#### Request Headers

```
Content-Type: application/json
```

#### Request Body

```json
{
  "datetime": "2025-05-04T12:00:00.000+00:00",
  "channel": "application",
  "level": "info",
  "message": "User logged in successfully",
  "context": {
    "user_id": 123,
    "ip": "192.168.1.1"
  }
}
```

#### Field Validations

| Field | Type | Validation Rules | Description |
|-------|------|-----------------|-------------|
| `datetime` | string | ISO 8601 format (Y-m-d\TH:i:sP) or RFC 3339 Extended format (Y-m-d\TH:i:s.vP) | The timestamp when the log was generated |
| `channel` | string | Length: 3-255 chars | The source or category of the log |
| `level` | string | One of: DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY | The severity level of the log |
| `message` | string | Length: 3-255 chars | The log message |
| `context` | object | Must be a valid JSON object | Additional data related to the log entry |

#### Response Codes

- `201 Created`: Log received successfully.
- `400 Bad Request`: Invalid request body or validation errors. The response body may contain details about the validation errors.
- `415 Unsupported Media Type`: The `Content-Type` header was not `application/json`.
- `500 Internal Server Error`: An unexpected error occurred on the server.

#### Example Requests

1. Successful log entry (`curl`):
```bash
curl -X POST http://localhost:8080/api/logs \
  -H "Content-Type: application/json" \
  -d '{
    "datetime": "2025-05-04T12:00:00.000+00:00",
    "channel": "application",
    "level": "info",
    "message": "User logged in successfully",
    "context": {
      "user_id": 123,
      "ip": "192.168.1.1"
    }
  }'
```

2. Debug log with array context (`curl`):
```bash
curl -X POST http://localhost:8080/api/logs \
  -H "Content-Type: application/json" \
  -d '{
    "datetime": "2025-05-04T12:01:00.000+00:00",
    "channel": "system",
    "level": "debug",
    "message": "Cache hit ratio statistics",
    "context": {
      "hits": 1500,
      "misses": 45,
      "ratio": 0.97,
      "popular_keys": ["user_1", "settings", "menu"]
    }
  }'
```

3. Error log example (`curl`):
```bash
curl -X POST http://localhost:8080/api/logs \
  -H "Content-Type: application/json" \
  -d '{
    "datetime": "2025-05-04T12:02:00.000+00:00",
    "channel": "database",
    "level": "error",
    "message": "Database connection failed",
    "context": {
      "error_code": "SQLSTATE[HY000] [2002]",
      "host": "db.example.com",
      "port": 3306,
      "retry_attempt": 3
    }
  }'
```

4. Example using Python (`requests` library):
```python
import requests
import json

url = "http://localhost:8080/api/logs"
headers = {"Content-Type": "application/json"}
data = {
    "datetime": "2025-05-04T12:03:00.000+00:00",
    "channel": "payment_gateway",
    "level": "warning",
    "message": "Transaction timed out",
    "context": {
        "transaction_id": "txn_12345",
        "amount": 99.99,
        "currency": "USD"
    }
}

response = requests.post(url, headers=headers, data=json.dumps(data))

print(f"Status Code: {response.status_code}")
try:
    print(f"Response Body: {response.json()}")
except requests.exceptions.JSONDecodeError:
    print(f"Response Body: {response.text}")

```

### DELETE /api/logs

This endpoint allows you to clear all logs from the viewer.

#### Request Headers

```
Content-Type: application/json
```

#### Response Codes

- `200 OK`: Logs cleared successfully.
- `500 Internal Server Error`: An unexpected error occurred on the server.

#### Example Request

```bash
curl -X POST http://localhost:8080/api/logs/clear \
  -H "Content-Type: application/json"
```

### GET /search

This endpoint provides search functionality for logs.

#### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search query (optional) - searches in message, channel, and context fields |
| `fields` | string[] | Optional array of fields to include in results (e.g., `fields[]=level&fields[]=channel`) |

#### Response Codes

- `200 OK`: Search completed successfully.

#### Example Request

```bash
# Basic search
curl -X GET "http://localhost:8080/search?search=error"

# Search with specific fields
curl -X GET "http://localhost:8080/search?search=error&fields[]=level&fields[]=channel"
```

#### Response Example

```html
<div id="log-entries">
    <div class="log-entry" data-level="error">
        <span class="log-level">ERROR</span>
        <span class="log-message">Database connection failed</span>
        <span class="log-channel">database</span>
    </div>
</div>
```

### Server-Sent Events (SSE)

The application supports real-time log updates via Server-Sent Events. When a new log is received via the API, all connected clients will automatically refresh to show the new log entry.

#### SSE Endpoint

```
/.well-known/mercure?topic=logs
```

#### How It Works

1. The browser connects to the Mercure SSE endpoint
2. When a new log is POSTed to `/api/logs`, the server publishes an event
3. Mercure broadcasts the event to all connected clients
4. HTMX automatically triggers a refresh of the log list

#### Example (JavaScript)

```javascript
const eventSource = new EventSource('/.well-known/mercure?topic=logs');

eventSource.onmessage = (event) => {
    console.log('New log received:', event.data);
    // HTMX will automatically refresh the log list
};
```

## Datetime Format Support

The API accepts datetime strings in two formats for maximum compatibility:

### Supported Formats

| Format | Example | Description |
|--------|---------|-------------|
| ISO 8601 | `2025-05-04T12:00:00+00:00` | Standard format without microseconds |
| RFC 3339 Extended | `2025-05-04T12:00:00.000+00:00` | Format with microseconds |

### Format Requirements

- **Date**: Must be in `YYYY-MM-DD` format
- **Time**: Must be in `HH:MM:SS` or `HH:MM:SS.uuu` format (24-hour)
- **Timezone**: Must be in `+HH:MM` or `-HH:MM` format (e.g., `+00:00`, `-03:00`, `+05:30`)
- **Z notation**: Not supported (use `+00:00` for UTC)
- **Microseconds**: Optional for RFC 3339 Extended, must be exactly 3 digits (000-999)

### Code Examples

#### PHP

```php
// ISO 8601
$datetime1 = date('c'); // 2025-05-04T12:00:00+00:00

// RFC 3339 Extended
$datetime2 = (new DateTime())->format('Y-m-d\TH:i:s.vP'); // 2025-05-04T12:00:00.000+00:00
```

#### JavaScript

```javascript
// ISO 8601
const datetime1 = new Date().toISOString(); // 2025-05-04T12:00:00.000Z

// RFC 3339 Extended
const datetime2 = new Date().toISOString(); // Already in RFC 3339 Extended format
```

#### Python

```python
from datetime import datetime, timezone

# ISO 8601
datetime1 = datetime.now(timezone.utc).isoformat()  # 2025-05-04T12:00:00+00:00

# RFC 3339 Extended
datetime2 = datetime.now(timezone.utc).isoformat()  # 2025-05-04T12:00:00.000000+00:00
```

#### Bash

```bash
# ISO 8601
datetime1=$(date -u +%Y-%m-%dT%H:%M:%S%z)  # 2025-05-04T12:00:00+0000

# RFC 3339 Extended (GNU date)
datetime2=$(date -u +%Y-%m-%dT%H:%M:%S.%3N%z)  # 2025-05-04T12:00:00.000+0000
```

### Validation Rules

The datetime field is validated using the following rules:
- Must be a valid datetime string
- Must be in either ISO 8601 or RFC 3339 Extended format
- Year must be between 1970 and 2100
- Timezone offset must be valid (-23:59 to +23:59)
