# API Documentation

## Overview

The Clean Flow API provides endpoints for third-party integrations, IoT device management, and service interactions. All endpoints require proper authentication unless specified otherwise.

## Base URL

```
http://localhost:8000/api
```

For production, replace with your domain:
```
https://yourdomain.com/api
```

---

## Authentication

### Session-Based Authentication
Most authenticated endpoints require a logged-in user session. Use browser cookies or establish a session via login.

### Device Token Authentication
IoT device endpoints use device tokens for authentication instead of user sessions.

### Bearer Token
Some endpoints may accept Bearer tokens in the Authorization header:
```
Authorization: Bearer <token>
```

---

## IoT Device Endpoints

### 1. Staff Attendance Punch

**Endpoint:** `POST /iot/attendance/punch`

**Authentication:** Device Token (required)

**Description:** Records staff attendance via biometric/fingerprint verification.

**Request Headers:**
```
Content-Type: application/json
Device-Token: your_device_token
```

**Request Body:**
```json
{
  "device_id": "device_001",
  "fingerprint_template": "binary_template_data",
  "employee_id": "EMP001",
  "punch_time": "2026-04-15T08:30:00Z"
}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Attendance recorded",
  "punch_id": 12345,
  "employee_name": "John Doe",
  "punch_type": "in",
  "punched_at": "2026-04-15T08:30:00Z",
  "verification_status": "verified"
}
```

**Response (Error - 401):**
```json
{
  "status": "error",
  "message": "Invalid device token",
  "code": "INVALID_TOKEN"
}
```

---

### 2. Device Heartbeat

**Endpoint:** `POST /iot/device/heartbeat`

**Authentication:** Device Token (required)

**Description:** Monitors device health and connectivity. Send periodically (e.g., every 30 seconds).

**Request Headers:**
```
Content-Type: application/json
Device-Token: your_device_token
```

**Request Body:**
```json
{
  "device_id": "device_001",
  "signal_strength": -67,
  "battery_level": 85,
  "uptime_seconds": 3600,
  "connected_at": "2026-04-15T07:00:00Z",
  "enrolled_templates": 42
}
```

**Response (Success - 200):**
```json
{
  "status": "ok",
  "server_time": "2026-04-15T08:30:00Z",
  "next_check_interval": 30,
  "device_status": "healthy"
}
```

---

### 3. Get Next Enrollment Request

**Endpoint:** `GET /iot/device/enrollment/next`

**Authentication:** Device Token (required)

**Description:** Retrieves the next pending fingerprint enrollment request for the device.

**Request Headers:**
```
Device-Token: your_device_token
```

**Response (Success - 200):**
```json
{
  "enrollment_request_id": 567,
  "employee_id": "EMP002",
  "employee_name": "Jane Smith",
  "template_finger": "index_right",
  "quality_threshold": 90,
  "timeout_seconds": 300,
  "status": "pending"
}
```

**Response (No Pending - 204):**
```
(Empty body)
```

---

### 4. Update Enrollment Status

**Endpoint:** `POST /iot/device/enrollment/status`

**Authentication:** Device Token (required)

**Description:** Updates the status of a fingerprint enrollment request after template capture.

**Request Headers:**
```
Content-Type: application/json
Device-Token: your_device_token
```

**Request Body:**
```json
{
  "enrollment_request_id": 567,
  "status": "completed",
  "fingerprint_template": "binary_template_data",
  "quality_score": 95,
  "attempts": 3,
  "completed_at": "2026-04-15T08:25:00Z"
}
```

**Alternative for Failure:**
```json
{
  "enrollment_request_id": 567,
  "status": "failed",
  "error_reason": "poor_quality",
  "attempts": 5,
  "failed_at": "2026-04-15T08:28:00Z"
}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Enrollment recorded",
  "enrollment_id": 567,
  "employee_id": "EMP002"
}
```

---

## Admin Endpoints

### 1. Today's Attendance Status

**Endpoint:** `GET /attendance/today`

**Authentication:** User session (role: admin)

**Description:** Retrieves staff attendance status for the current day.

**Response (Success - 200):**
```json
{
  "date": "2026-04-15",
  "staff_present": [
    {
      "staff_id": 1,
      "employee_id": "EMP001",
      "name": "John Doe",
      "punch_in": "2026-04-15T08:30:00Z",
      "punch_out": null,
      "status": "checked_in",
      "hours_worked": 0.5
    }
  ],
  "staff_absent": [
    {
      "staff_id": 2,
      "employee_id": "EMP002",
      "name": "Jane Smith",
      "status": "absent"
    }
  ],
  "total_staff": 5,
  "present_count": 3,
  "absent_count": 2
}
```

---

## Web Routes (Booking & Client Endpoints)

### Booking Management

**GET** `/bookings` - List client's bookings (requires: auth, verified, client role)

**GET** `/bookings/create` - Show booking creation form (requires: auth, verified, client role)

**POST** `/bookings` - Create new booking (requires: auth, verified, client role)

**GET** `/bookings/{id}` - View booking details (requires: auth)

**POST** `/bookings/calculate-price` - Calculate booking price (requires: auth, verified, client role)

**POST** `/bookings/{id}/rate` - Submit rating for completed booking (requires: auth, verified, client role)

**PATCH** `/bookings/{id}/cancel` - Cancel pending booking (requires: auth, verified, client role)

### Live Booking Tracking

**GET** `/bookings/{id}/location/current` - Get current live location (requires: auth)

**GET** `/bookings/{id}/location/history` - Get location history (requires: auth)

**POST** `/bookings/{id}/location/update` - Update booking location (requires: auth, staff role)

---

## Admin Dashboard Routes

**GET** `/admin/dashboard` - Operations overview with analytics

**GET** `/admin/customers` - Customer management page

**GET** `/admin/bookings` - Booking management page

**GET** `/admin/services` - Service catalog management

**GET** `/admin/staff` - Staff management page

**GET** `/admin/reports` - Reporting and analytics

**GET** `/admin/attendance` - Attendance management

**GET** `/admin/attendance/history` - Attendance history logs

**GET** `/admin/service-areas` - Service coverage areas map

---

## Error Codes

| Code | HTTP | Description |
| --- | --- | --- |
| INVALID_TOKEN | 401 | Device token is invalid or expired |
| UNAUTHORIZED | 403 | User doesn't have required permissions |
| NOT_FOUND | 404 | Resource not found |
| VALIDATION_ERROR | 422 | Request validation failed |
| RATE_LIMITED | 429 | Too many requests, try again later |
| SERVER_ERROR | 500 | Internal server error |

---

## Rate Limiting

IoT device endpoints have rate limits to prevent abuse:

- **Attendance Punch:** 1 request per 10 seconds per device
- **Heartbeat:** 1 request per 5 seconds per device
- **Enrollment:** 1 request per 5 seconds per device

Exceeding limits returns HTTP 429 with retry-after header.

---

## Pagination

List endpoints support pagination via query parameters:

```
GET /api/bookings?page=1&per_page=20
```

Response includes:
```json
{
  "data": [...],
  "pagination": {
    "total": 150,
    "per_page": 20,
    "current_page": 1,
    "last_page": 8,
    "from": 1,
    "to": 20
  }
}
```

---

## Webhooks (Future)

The following webhook events can be enabled for integrations:

- `booking.created` - New booking submitted
- `booking.confirmed` - Booking confirmed and staff assigned
- `booking.completed` - Service completed
- `attendance.punch` - Staff punch recorded
- `rating.submitted` - Customer rating submitted

Configure webhook URLs in the admin panel Settings > Webhooks section.

---

## Code Examples

### JavaScript/Fetch

```javascript
// Record attendance
const response = await fetch('/api/iot/attendance/punch', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Device-Token': 'your_device_token'
  },
  body: JSON.stringify({
    device_id: 'device_001',
    employee_id: 'EMP001',
    punch_time: new Date().toISOString()
  })
});

const data = await response.json();
console.log(data);
```

### Python/Requests

```python
import requests
import json
from datetime import datetime

url = 'http://localhost:8000/api/iot/attendance/punch'
headers = {
    'Content-Type': 'application/json',
    'Device-Token': 'your_device_token'
}
payload = {
    'device_id': 'device_001',
    'employee_id': 'EMP001',
    'punch_time': datetime.now().isoformat() + 'Z'
}

response = requests.post(url, headers=headers, json=payload)
print(response.json())
```

### cURL

```bash
curl -X POST http://localhost:8000/api/iot/attendance/punch \
  -H "Content-Type: application/json" \
  -H "Device-Token: your_device_token" \
  -d '{
    "device_id": "device_001",
    "employee_id": "EMP001",
    "punch_time": "2026-04-15T08:30:00Z"
  }'
```

---

## Support

For API issues or questions:
- Email: support@cleanflow.local
- Documentation: See README.md
- Issue Tracker: GitHub Issues (if applicable)
