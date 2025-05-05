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
  "datetime": "2025-05-04T12:00:00+00:00",
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
| `datetime` | string | ISO 8601 format (Y-m-d\TH:i:sP) | The timestamp when the log was generated |
| `channel` | string | Length: 3-255 chars | The source or category of the log |
| `level` | string | One of: DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY | The severity level of the log |
| `message` | string | Length: 3-255 chars | The log message |
| `context` | object | Must be a valid JSON object | Additional data related to the log entry |

#### Response Codes

- `201`: Log received successfully
- `400`: Invalid request (validation errors)
- `415`: Unsupported Media Type (when Content-Type is not application/json)

#### Example Requests

1. Successful log entry:
```bash
curl -X POST http://localhost:8080/api/logs \
  -H "Content-Type: application/json" \
  -d '{
    "datetime": "2025-05-04T12:00:00+00:00",
    "channel": "application",
    "level": "info",
    "message": "User logged in successfully",
    "context": {
      "user_id": 123,
      "ip": "192.168.1.1"
    }
  }'
```

2. Debug log with array context:
```bash
curl -X POST http://localhost:8080/api/logs \
  -H "Content-Type: application/json" \
  -d '{
    "datetime": "2025-05-04T12:01:00+00:00",
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

3. Error log example:
```bash
curl -X POST http://localhost:8080/api/logs \
  -H "Content-Type: application/json" \
  -d '{
    "datetime": "2025-05-04T12:02:00+00:00",
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