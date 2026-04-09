#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <Adafruit_Fingerprint.h>

// Required Arduino libraries:
// - Adafruit Fingerprint Sensor Library
// - Adafruit SSD1306
// - Adafruit GFX Library

// WiFi and Server
constexpr char WIFI_SSID[] = "sander";
constexpr char WIFI_PASSWORD[] = "san12345";
constexpr char SERVER_URL[] = "http://192.168.137.1:8001/api/iot";
constexpr char DEVICE_TOKEN[] = "yWGFNwAWgN7mQOtZF6evE7GwUC2lV7v1ok7QrwwyEjEATOdVl62hnwd2ueXty1Wm";

// Pin Config
constexpr uint8_t FINGER_RX_PIN = 16;   // AS608 TX -> ESP32 RX2
constexpr uint8_t FINGER_TX_PIN = 17;   // AS608 RX -> ESP32 TX2
constexpr uint8_t OLED_SDA = 21;
constexpr uint8_t OLED_SCL = 22;

// Baud Rates
constexpr uint32_t SERIAL_BAUD = 115200;
constexpr uint32_t FINGER_BAUD_PRIMARY = 57600;
constexpr uint32_t FINGER_BAUD_FALLBACK = 9600;

// OLED
constexpr uint8_t SCREEN_WIDTH = 128;
constexpr uint8_t SCREEN_HEIGHT = 64;
constexpr int8_t OLED_RESET = -1;
constexpr uint8_t OLED_PRIMARY_ADDRESS = 0x3C;
constexpr uint8_t OLED_FALLBACK_ADDRESS = 0x3D;
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// Fingerprint
HardwareSerial fingerSerial(2);
Adafruit_Fingerprint finger(&fingerSerial);

// Timers
unsigned long lastHeartbeat = 0;
unsigned long lastEnrollCheck = 0;
unsigned long lastAcceptedAt = 0;

constexpr unsigned long HEARTBEAT_INTERVAL = 30000UL;
constexpr unsigned long ENROLL_CHECK_INTERVAL = 5000UL;
constexpr unsigned long SCAN_COOLDOWN_MS = 8000UL;
constexpr unsigned long ENROLLMENT_WAIT_MS = 30000UL;
constexpr unsigned long REMOVE_FINGER_WAIT_MS = 15000UL;

bool displayReady = false;
uint32_t activeFingerBaud = 0;
uint16_t lastAcceptedFingerId = 0;

struct EnrollmentRequest {
  bool valid;
  uint32_t requestId;
  uint16_t templateId;
  String staffName;
};

String jsonField(const String& json, const char* key) {
  String pattern = String("\"") + key + "\":";
  int start = json.indexOf(pattern);

  if (start < 0) {
    return "";
  }

  start += pattern.length();

  while (start < json.length() && json[start] == ' ') {
    start++;
  }

  if (start >= json.length()) {
    return "";
  }

  if (json[start] == '"') {
    start++;
    int end = json.indexOf('"', start);
    return end >= 0 ? json.substring(start, end) : "";
  }

  int end = json.indexOf(',', start);
  if (end < 0) {
    end = json.indexOf('}', start);
  }

  if (end < 0) {
    return "";
  }

  return json.substring(start, end);
}

String jsonEscape(String value) {
  value.replace("\\", "\\\\");
  value.replace("\"", "\\\"");
  value.replace("\n", " ");
  value.replace("\r", " ");
  return value;
}

String httpErrorMessage(int statusCode) {
  if (statusCode >= 0) {
    return "";
  }

  String message = HTTPClient::errorToString(statusCode);
  if (message.length() == 0) {
    message = "HTTP error " + String(statusCode);
  }

  return message;
}

void showMessage(const String& line1, const String& line2 = "", const String& line3 = "") {
  Serial.println(line1);
  if (line2.length() > 0) {
    Serial.println(line2);
  }
  if (line3.length() > 0) {
    Serial.println(line3);
  }

  if (!displayReady) {
    return;
  }

  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(line1);
  display.setCursor(0, 22);
  display.println(line2);
  display.setCursor(0, 44);
  display.println(line3);
  display.display();
}

void showReady() {
  showMessage("Ready", "Place finger", WiFi.status() == WL_CONNECTED ? "WiFi OK" : "WiFi reconnecting");
}

void setupDisplay() {
  Wire.begin(OLED_SDA, OLED_SCL);

  displayReady = display.begin(SSD1306_SWITCHCAPVCC, OLED_PRIMARY_ADDRESS);
  if (!displayReady) {
    displayReady = display.begin(SSD1306_SWITCHCAPVCC, OLED_FALLBACK_ADDRESS);
  }

  if (!displayReady) {
    Serial.println("OLED not found. Serial-only mode.");
    return;
  }

  display.clearDisplay();
  display.display();
}

void connectWiFi() {
  showMessage("Connecting WiFi...", WIFI_SSID, "");
  WiFi.mode(WIFI_STA);
  WiFi.setHostname("cleanflow-attendance");
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("WiFi connected: " + WiFi.localIP().toString());
    showMessage("WiFi Connected!", WiFi.localIP().toString(), "");
    delay(1500);
  } else {
    Serial.println("WiFi failed");
    showMessage("WiFi Failed", "Running offline", "");
    delay(1500);
  }
}

bool httpPost(const String& endpoint, const String& payload, String& responseBody, int& statusCode) {
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
  }

  if (WiFi.status() != WL_CONNECTED) {
    responseBody = "WiFi offline";
    statusCode = -1;
    return false;
  }

  HTTPClient http;
  String url = String(SERVER_URL) + endpoint;
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  http.addHeader("X-Device-Token", DEVICE_TOKEN);
  http.setTimeout(10000);

  statusCode = http.POST(payload);
  if (statusCode > 0) {
    responseBody = http.getString();
  } else {
    responseBody = httpErrorMessage(statusCode);
  }

  Serial.println("POST " + url + " -> " + String(statusCode));
  if (responseBody.length() > 0) {
    Serial.println(responseBody);
  }

  http.end();
  return statusCode > 0 && statusCode < 300;
}

bool httpGet(const String& endpoint, String& responseBody, int& statusCode) {
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
  }

  if (WiFi.status() != WL_CONNECTED) {
    responseBody = "WiFi offline";
    statusCode = -1;
    return false;
  }

  HTTPClient http;
  String url = String(SERVER_URL) + endpoint;
  http.begin(url);
  http.addHeader("Accept", "application/json");
  http.addHeader("X-Device-Token", DEVICE_TOKEN);
  http.setTimeout(10000);

  statusCode = http.GET();
  if (statusCode > 0) {
    responseBody = http.getString();
  } else {
    responseBody = httpErrorMessage(statusCode);
  }

  Serial.println("GET " + url + " -> " + String(statusCode));
  if (responseBody.length() > 0) {
    Serial.println(responseBody);
  }

  http.end();
  return statusCode > 0 && statusCode < 300;
}

bool connectFingerprintSensorAtBaud(uint32_t baud) {
  Serial.println("Trying fingerprint baud: " + String(baud));
  fingerSerial.begin(baud, SERIAL_8N1, FINGER_RX_PIN, FINGER_TX_PIN);
  delay(100);
  finger.begin(baud);

  if (finger.verifyPassword()) {
    activeFingerBaud = baud;
    return true;
  }

  return false;
}

void setupFingerprint() {
  if (!connectFingerprintSensorAtBaud(FINGER_BAUD_PRIMARY) &&
      !connectFingerprintSensorAtBaud(FINGER_BAUD_FALLBACK)) {
    Serial.println("AS608 not found - check wiring");
    showMessage("Sensor ERROR", "Check wiring", "AS608 not found");
    while (true) {
      delay(1000);
    }
  }

  finger.getTemplateCount();
  Serial.println("AS608 found!");
  showMessage("Sensor OK", "Templates: " + String(finger.templateCount), "Baud: " + String(activeFingerBaud));
  delay(1500);
}

void sendHeartbeat() {
  String responseBody;
  int statusCode = 0;
  httpPost("/device/heartbeat", "{}", responseBody, statusCode);
}

bool reportEnrollmentStatus(uint32_t requestId, const char* status, const String& errorMessage = "") {
  String payload = String("{\"request_id\":") + String(requestId) + ",\"status\":\"" + status + "\"";

  if (errorMessage.length() > 0) {
    payload += ",\"error_message\":\"" + jsonEscape(errorMessage) + "\"";
  }

  payload += "}";

  String responseBody;
  int statusCode = 0;
  return httpPost("/device/enrollment/status", payload, responseBody, statusCode);
}

EnrollmentRequest fetchEnrollmentRequest() {
  EnrollmentRequest request = {false, 0, 0, ""};
  String responseBody;
  int statusCode = 0;

  if (!httpGet("/device/enrollment/next", responseBody, statusCode)) {
    return request;
  }

  if (jsonField(responseBody, "has_request") != "true") {
    return request;
  }

  request.valid = true;
  request.requestId = static_cast<uint32_t>(jsonField(responseBody, "request_id").toInt());
  request.templateId = static_cast<uint16_t>(jsonField(responseBody, "template_id").toInt());
  request.staffName = jsonField(responseBody, "staff_name");
  return request;
}

bool waitForNoFinger(String& error) {
  unsigned long startedAt = millis();

  while (millis() - startedAt < REMOVE_FINGER_WAIT_MS) {
    if (finger.getImage() == FINGERPRINT_NOFINGER) {
      return true;
    }
    delay(50);
  }

  error = "Remove finger timeout";
  return false;
}

void waitForFingerRelease() {
  while (finger.getImage() != FINGERPRINT_NOFINGER) {
    delay(50);
  }
}

bool captureFingerTemplate(uint8_t bufferId, const String& title, const String& subtitle, String& error) {
  showMessage(title, subtitle, "Place finger");

  unsigned long startedAt = millis();
  while (millis() - startedAt < ENROLLMENT_WAIT_MS) {
    uint8_t p = finger.getImage();

    if (p == FINGERPRINT_NOFINGER) {
      delay(50);
      continue;
    }

    if (p == FINGERPRINT_PACKETRECIEVEERR) {
      error = "Sensor comm error";
      return false;
    }

    if (p == FINGERPRINT_IMAGEFAIL) {
      error = "Image failed";
      return false;
    }

    if (p != FINGERPRINT_OK) {
      error = "Sensor error";
      return false;
    }

    p = finger.image2Tz(bufferId);
    if (p == FINGERPRINT_OK) {
      return true;
    }

    if (p == FINGERPRINT_IMAGEMESS) {
      error = "Image too messy";
    } else if (p == FINGERPRINT_FEATUREFAIL || p == FINGERPRINT_INVALIDIMAGE) {
      error = "Could not read print";
    } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
      error = "Sensor comm error";
    } else {
      error = "Convert failed";
    }

    return false;
  }

  error = "Finger wait timeout";
  return false;
}

bool enrollFinger(uint16_t id, const String& staffName, String& error) {
  Serial.println("Enrolling ID: " + String(id));

  if (!captureFingerTemplate(1, "Enrolling...", "ID: " + String(id), error)) {
    return false;
  }

  showMessage("Remove finger", staffName, "");
  if (!waitForNoFinger(error)) {
    return false;
  }

  delay(500);

  if (!captureFingerTemplate(2, "Place again", "ID: " + String(id), error)) {
    return false;
  }

  uint8_t p = finger.createModel();
  if (p != FINGERPRINT_OK) {
    if (p == FINGERPRINT_ENROLLMISMATCH) {
      error = "Prints mismatch";
    } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
      error = "Sensor comm error";
    } else {
      error = "Model failed";
    }
    return false;
  }

  p = finger.storeModel(id);
  if (p != FINGERPRINT_OK) {
    if (p == FINGERPRINT_BADLOCATION) {
      error = "Invalid slot";
    } else if (p == FINGERPRINT_FLASHERR) {
      error = "Store failed";
    } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
      error = "Sensor comm error";
    } else {
      error = "Store failed";
    }
    return false;
  }

  return true;
}

void handleEnrollmentRequest(const EnrollmentRequest& request) {
  showMessage("Enrollment job", request.staffName, "Slot #" + String(request.templateId));
  delay(1200);

  reportEnrollmentStatus(request.requestId, "in_progress");

  String error;
  bool ok = enrollFinger(request.templateId, request.staffName, error);

  if (ok) {
    reportEnrollmentStatus(request.requestId, "completed");
    showMessage("Enrolled!", "ID: " + String(request.templateId), request.staffName);
  } else {
    reportEnrollmentStatus(request.requestId, "failed", error);
    showMessage("Enroll failed", request.staffName, error);
  }

  delay(2000);
  showReady();
}

void checkEnrollmentQueue() {
  EnrollmentRequest request = fetchEnrollmentRequest();
  if (!request.valid) {
    return;
  }

  handleEnrollmentRequest(request);
}

void handleAttendanceSuccess(uint16_t templateId, const String& responseBody) {
  String staffName = jsonField(responseBody, "staff_name");
  String punchType = jsonField(responseBody, "punch_type");
  String status = jsonField(responseBody, "status");

  if (staffName.length() == 0) {
    staffName = "ID: " + String(templateId);
  }
  if (punchType.length() == 0) {
    punchType = "saved";
  }
  if (status.length() == 0) {
    status = "present";
  }

  showMessage(staffName, "Time-" + punchType, status);
}

void sendAttendancePunch(uint16_t templateId) {
  String payload = String("{\"template_id\":") + String(templateId) + ",\"punch_type\":\"auto\"}";
  String responseBody;
  int statusCode = 0;

  bool ok = httpPost("/attendance/punch", payload, responseBody, statusCode);

  if (ok) {
    handleAttendanceSuccess(templateId, responseBody);
    lastAcceptedFingerId = templateId;
    lastAcceptedAt = millis();
  } else {
    String error = jsonField(responseBody, "error");
    if (error.length() == 0) {
      error = responseBody;
    }
    showMessage("Punch Failed", "ID: " + String(templateId), error.length() > 0 ? error : "Check server");
  }

  delay(2000);
}

int getFingerprintID() {
  uint8_t p = finger.getImage();
  if (p != FINGERPRINT_OK) {
    return -1;
  }

  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) {
    return -1;
  }

  p = finger.fingerFastSearch();
  if (p != FINGERPRINT_OK) {
    showMessage("No match", "Try again", "");
    delay(1500);
    waitForFingerRelease();
    showReady();
    return -1;
  }

  if (lastAcceptedFingerId == finger.fingerID && millis() - lastAcceptedAt < SCAN_COOLDOWN_MS) {
    showMessage("Please wait", "Recent scan saved", "ID: " + String(finger.fingerID));
    delay(1200);
    waitForFingerRelease();
    showReady();
    return -1;
  }

  Serial.println("Match! ID: " + String(finger.fingerID) + "  Conf: " + String(finger.confidence));
  return finger.fingerID;
}

void setup() {
  Serial.begin(SERIAL_BAUD);
  delay(500);

  setupDisplay();
  showMessage("Booting...", "CleanFlow", "Attendance");

  setupFingerprint();
  connectWiFi();
  sendHeartbeat();
  showReady();
}

void loop() {
  unsigned long now = millis();

  if (now - lastHeartbeat >= HEARTBEAT_INTERVAL) {
    lastHeartbeat = now;
    sendHeartbeat();

    if (WiFi.status() != WL_CONNECTED) {
      connectWiFi();
    }
  }

  if (now - lastEnrollCheck >= ENROLL_CHECK_INTERVAL) {
    lastEnrollCheck = now;
    checkEnrollmentQueue();
  }

  int id = getFingerprintID();
  if (id > 0) {
    showMessage("Recognized!", "ID: " + String(id), "Logging...");
    sendAttendancePunch(id);
    waitForFingerRelease();
    showReady();
  }

  delay(100);
}
