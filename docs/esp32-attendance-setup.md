# ESP32 Staff Attendance Setup

This project already includes a Laravel attendance API and admin attendance pages. The simplest way to use your `ESP32 + AS608 + OLED` is:

1. Enroll fingerprints into the AS608.
2. Map each fingerprint template ID to a staff `username`.
3. Let the ESP32 call this app's `/api/iot/attendance/punch` endpoint with `punch_type=auto`.

With `auto`, the backend decides whether the scan is a time-in or time-out, so staff only need to place a finger on the sensor.

## 1. Hardware Wiring

This guide assumes:

- ESP32 dev board
- AS608 connected over UART
- 128x64 I2C SSD1306 OLED display at address `0x3C`

Recommended wiring:

| Part | Pin | ESP32 |
| --- | --- | --- |
| AS608 | `VCC` or `+5V` | `5V` / `VIN` |
| AS608 | GND | `GND` |
| AS608 | TX | `GPIO16` |
| AS608 | RX | `GPIO17` |
| OLED | VCC | `3V3` |
| OLED | GND | `GND` |
| OLED | SDA | `GPIO21` |
| OLED | SCL | `GPIO22` |

Notes:

- The AS608 TX line must go to the ESP32 RX pin, and the AS608 RX line must go to the ESP32 TX pin.
- Many AS608 boards do not print `VCC`; they print `+5V` instead. Treat `+5V` as the power input.
- If your AS608 has both `+5V` and `3.3V` pins, power it from only one of them. For this guide, use `+5V` and leave `3.3V` disconnected.
- Some AS608 boards use these labels instead: `V+`, `TX`, `RX`, `GND`, `TCH`, `VA`, `D+`, `D-`.
- On that version, wire only `V+`, `GND`, `TX`, and `RX` for the basic attendance device.
- `TCH` is an optional touch-detect output pin.
- `VA` is the 3.3V supply used only for the touch feature. Leave it disconnected unless you want to use `TCH`.
- `D+` and `D-` are the sensor's USB data pins and are not needed when you are using UART with the ESP32.
- Many AS608 modules accept 5V power, but verify your exact module before powering it.
- If your OLED is SPI or uses another controller, adjust the sketch before uploading.

## 2. Prepare the Laravel App

Run the app and migrations:

```bash
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8001
```

Create or confirm your staff accounts. Each staff member still needs a unique `username`, and the website can now assign a `fingerprint_template_id` to that staff member after enrollment completes.

Open the admin attendance pages after login:

- `/admin/attendance`
- `/admin/attendance/history`

## 3. Register the ESP32 Device

Create a device record and generate its API token:

```bash
php artisan attendance:register-device ESP32-FRONT-01 "Front Desk Device" --location="Main Office"
```

Or use the admin UI:

- log in as admin
- open `/admin/attendance`
- fill in the device name, serial number, and location
- click `Generate Device Token`

The command prints:

- the device serial number
- the generated `api_token`
- the location shown in the admin UI

Put that token into the ESP32 sketch as `DEVICE_TOKEN`.

If you ever need a new token:

```bash
php artisan attendance:register-device ESP32-FRONT-01 "Front Desk Device" --rotate-token
```

## 4. Install Arduino IDE Support

In Arduino IDE install:

- `esp32` by Espressif Systems
- `Adafruit Fingerprint Sensor Library`
- `Adafruit SSD1306`
- `Adafruit GFX Library`

## 5. Enroll Fingerprints From The Website

The browser does not talk to the AS608 directly. The website creates an enrollment request, and the ESP32 performs the actual fingerprint capture on the sensor.

Recommended process:

1. Log in as admin.
2. Open `/admin/attendance`.
3. In `Fingerprint Enrollment From Website`, choose:
   - the ESP32 device
   - the staff member
   - the fingerprint slot number, for example `1`
4. Click `Start Fingerprint Enrollment`.
5. Go to the device and ask the staff member to place the same finger twice.
6. Wait for the queue item to change to `completed`.

What happens after completion:

- the AS608 stores the fingerprint in the chosen slot
- Laravel saves that slot number to the staff member's `fingerprint_template_id`
- the attendance sketch can now identify that staff member by the scanned slot

Fallback option:

- You can still use the Adafruit `enroll` example for manual testing
- but normal day-to-day registration should now start from `/admin/attendance`

## 6. Edit the ESP32 Sketch

Open [iot/esp32_staff_attendance/esp32_staff_attendance.ino](/c:/Users/xander/Downloads/cleanflow-app/iot/esp32_staff_attendance/esp32_staff_attendance.ino) and update:

- `WIFI_SSID`
- `WIFI_PASSWORD`
- `API_BASE_URL`
- `DEVICE_TOKEN`

Important:

- `API_BASE_URL` must be reachable by the ESP32.
- If you use `php artisan serve`, start it with `--host=0.0.0.0` so devices on your Wi-Fi can reach it.
- For local testing, use your computer's LAN IP and the same port Laravel is listening on, for example `http://192.168.1.2:8001`.
- Do not use `http://127.0.0.1:8001` or `http://localhost:8001`, because those point to the ESP32 itself, not your computer.
- The new sketch no longer needs a hardcoded `STAFF[]` table for normal use.

## 7. Upload and Test

After uploading the sketch:

1. Power the ESP32.
2. Wait for Wi-Fi to connect.
3. Check the OLED for `Ready`.
4. If you need a new staff registration, create it first from `/admin/attendance` and complete the two-finger enrollment on the device.
5. Scan a registered finger.
6. Confirm a new record appears in `/admin/attendance` or `/admin/attendance/history`.

The device also sends heartbeat pings to `/api/iot/device/heartbeat`, so the admin page can show the unit as recently online even when nobody is punching.

## 8. How Auto Punch Works

The firmware sends:

```json
{
  "template_id": 1,
  "punch_type": "auto"
}
```

The backend converts `auto` into:

- `in` if the staff member has no attendance log yet for that date
- `out` if the latest log for that date is already `in`

That makes a fingerprint-only kiosk possible without extra buttons.

## 9. Troubleshooting

If the OLED stays blank:

- confirm the OLED really is I2C
- confirm the I2C address is `0x3C` and not `0x3D`
- confirm `GPIO21` and `GPIO22` are free on your ESP32 board

If the fingerprint sensor is not detected:

- swap RX and TX
- confirm the sensor has stable power
- lower the baud rate only if your module requires it

If attendance logs are not saved:

- confirm the Laravel app is reachable from the ESP32 over Wi-Fi
- confirm the `X-Device-Token` matches the token created by the artisan command
- confirm the scanned `template_id` was enrolled and assigned to a real staff member from `/admin/attendance`

## 10. Recommended Next Upgrade

For a larger team, the next clean improvement would be adding a dedicated attendance device dashboard with enrollment cancellation, slot deletion, and a staff-facing enrollment history.
