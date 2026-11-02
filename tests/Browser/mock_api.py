"""A fake ShaloTrack C# API that answers with HOSTILE strings, to prove the portal's pages stay inert in a real browser."""
import json, sys
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import urlparse

CUST = "22222222-2222-4222-8222-222222222222"
V1   = "11111111-1111-4111-8111-111111111111"

# Every payload tries a different way out of an inline handler or an HTML context. Each one, if it ran, counts.
P = {
  "quote":  "x');window.__pwn=(window.__pwn||0)+1;//",
  "dq":     'x");window.__pwn=(window.__pwn||0)+1;//',
  "img":    '"><img src=x onerror="window.__pwn=(window.__pwn||0)+1">',
  "script": "</script><script>window.__pwn=(window.__pwn||0)+1</script>",
  "tpl":    "${window.__pwn=(window.__pwn||0)+1}`",
  "amp":    "&#39;);window.__pwn=(window.__pwn||0)+1;//",
}
PLATE = P["quote"]

def ok(data): return {"success": True, "data": data}

PLACES = [{"placeId": P["quote"], "name": P["img"], "latitude": 6.9, "longitude": 79.8, "address": P["script"], "category": "Home"},
          {"placeId": P["dq"],    "name": P["amp"], "latitude": 7.1, "longitude": 80.1, "address": P["tpl"],    "category": "Work"}]
GEOFENCES = [{"geofenceId": P["quote"], "name": P["img"], "latitude": 6.9, "longitude": 79.8, "radiusMeters": 200, "vehicleId": None,
              "alertOnEnter": True, "alertOnExit": True, "isActive": True, "vehicleNumber": P["dq"]}]
VEHICLES = [{"vehicleId": V1, "vehicleNumber": PLATE, "make": P["img"], "model": P["amp"], "year": 2018, "color": P["tpl"], "vehicleType": "Car",
             "hasGpsDevice": True, "isDemoVehicle": False, "imei": "123456789012345", "currentAssignmentId": V1, "fuelType": "Petrol",
             "subscriptionStatus": "Active", "subscriptionEndDate": "2027-01-01T00:00:00Z"}]
RENEWALS = [{"renewalRequestId": P["quote"], "vehicleId": V1, "vehicleNumber": P["dq"], "status": "Pending", "packageName": P["img"],
             "amount": 1000, "createdAt": "2026-10-20T10:00:00Z", "months": 12}]
DASH = {"vehicles": [{"vehicleId": P["quote"], "vehicleNumber": P["img"], "make": P["amp"], "model": "m", "isMoving": False, "status": "Online",
                      "latitude": 6.9, "longitude": 79.8, "speedKmh": 0, "lastUpdate": "2026-10-28T10:00:00Z", "hasGpsDevice": True}],
        "totalVehicles": 1, "activeVehicles": 1, "totalDistanceToday": 0}
LIVE = {"success": True, "data": {"plateNumber": P["img"], "vehicleType": P["quote"], "expiresAt": "2099-01-01T00:00:00Z",
        "serverTime": "2026-10-28T10:00:00Z", "position": {"latitude": 6.9, "longitude": 79.8, "speedKmh": 10, "heading": 0, "isMoving": True,
        "lastUpdate": "2026-10-28T09:59:50Z"}, "trail": []}}

SHARE = lambda sid, extra={}: {"shareId": sid, "vehicleId": V1, "vehicleNumber": P["quote"], "make": P["img"], "model": P["amp"], "year": 2018,
    "vehicleType": "Car", "otherPartyName": P["dq"], "otherPartyPhoneNumber": P["script"], "status": "Accepted", "createdAt": "2026-10-20T10:00:00Z", **extra}
ROUTES = {
  "/api/Customers/me": ok({"customerId": CUST, "fullName": P["img"], "email": "a@b.lk"}),
  "/api/SavedPlaces": ok(PLACES),
  "/api/Geofences": ok(GEOFENCES),
  f"/api/Vehicles/customer/{CUST}": ok(VEHICLES),
  "/api/VehicleShares/shared-with-me": ok([SHARE(P["quote"])]),
  "/api/VehicleShares/pending-invites": ok([SHARE(P["dq"], {"status": "Pending"})]),
  "/api/VehicleShares/my-shares": ok([SHARE(P["amp"], {"status": "Accepted"}), SHARE(P["img"], {"status": "Pending"})]),
  "/api/Complaints/mine": ok([{"complaintId": P["quote"], "subject": P["img"], "message": P["script"], "status": "Open", "createdAt": "2026-10-20T10:00:00Z"}]),
  "/api/Renewals": ok(RENEWALS),
  "/api/Renewals/packages": ok([{"packageId": V1, "name": P["quote"], "months": 12, "price": 1000}]),
  f"/api/Customers/{CUST}/dashboard": ok(DASH),
  "/api/EmergencyContacts": ok([{"contactId": P["quote"], "name": P["img"], "phoneNumber": "0771234567", "relationship": P["dq"]}]),
}

class H(BaseHTTPRequestHandler):
    def log_message(self, *a): pass
    def _send(self, code, obj):
        b = json.dumps(obj).encode(); self.send_response(code)
        self.send_header("Content-Type", "application/json"); self.send_header("Content-Length", str(len(b))); self.end_headers(); self.wfile.write(b)
    def do_GET(self):
        path = urlparse(self.path).path
        if path.startswith("/api/public/live/"): return self._send(200, LIVE)
        if path in ROUTES: return self._send(200, ROUTES[path])
        sys.stderr.write(f"MOCK-404 {path}\n"); self._send(200, ok([]))   # unknown reads: empty list, keeps pages rendering
    do_POST = do_PUT = do_DELETE = do_PATCH = lambda self: self._send(200, ok({}))

ThreadingHTTPServer(("127.0.0.1", int(sys.argv[1]) if len(sys.argv) > 1 else 9001), H).serve_forever()
