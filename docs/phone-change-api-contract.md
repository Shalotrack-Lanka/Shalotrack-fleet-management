# Phone-number change: API contract (Fleet side is built; this is what the C# API must provide)

The phone number is the sign-in identity (Text.lk OTP). Fleet no longer lets `PUT /profile` change it. It changes
only through the two calls below. **Fleet's checks are friendly UX; the API must enforce everything itself.**

## 1. Request a code

`POST /api/Customers/me/phone-change/request`  (Bearer = the customer's Firebase ID token)

```json
{ "PhoneNumber": "+94719876543" }
```

Success `200`:
```json
{ "success": true, "data": { "resendAfterSeconds": 60 } }
```
Fleet clamps `resendAfterSeconds` to 30..300 and defaults to 60 when it is missing.

| Situation | Status | Notes |
|---|---|---|
| Sign-in older than 10 minutes | `403` body containing `REAUTH_REQUIRED` | Same code the account-deletion call already uses. Fleet tells the customer to sign in again. |
| Number not a valid SL mobile | `400` | Fleet normalises to `+947XXXXXXXX` first, but never trust that. |
| Number already used by another account | `200` with **no SMS sent** (recommended) or `409` | Do not reveal which numbers are registered. The verify step then fails generically. Fleet handles both. |
| Too many requests | `429` | |
| Anything else failing (SMS provider down) | `5xx` | Fleet shows a generic "try again". Never return provider text. |

## 2. Verify the code and change the number

`POST /api/Customers/me/phone-change/verify`

```json
{ "PhoneNumber": "+94719876543", "Code": "123456" }
```

Success `200`:
```json
{ "success": true, "data": { "phoneNumber": "+94719876543" } }
```
Fleet shows `data.phoneNumber` as the new number and drops its cached profile.

| Situation | Status |
|---|---|
| Wrong, expired, reused code, or number not the one the code was sent to | `400` with a plain, generic message (Fleet shows its own text) |
| Locked out after too many wrong codes | `429` |
| Stale sign-in | `403` `REAUTH_REQUIRED` |

## What the API must enforce (non-negotiable)

1. **One-time code, 6 digits**, stored hashed, expires in 5 minutes, single use, bound to *customer + new number*.
2. **Attempt limit**: lock after 5 wrong codes per customer; the Fleet limiter (5/min, 10/h) is only a second wall.
3. **Resend and volume limits per customer AND per target number** (an attacker must not be able to spam a stranger's phone through us), plus a daily cap of changes per account (suggest 1 successful change / 24 h).
4. **Fresh sign-in required** (`auth_time` within 10 minutes) on both calls, otherwise `403 REAUTH_REQUIRED`.
5. **Number uniqueness** checked at verify time under a unique index (race-safe), not just at request time.
6. **Update atomically**: `Customers.PhoneNumber` and the login identity the OTP sign-in resolves by must change in the same transaction. Otherwise the old number can still sign in, or the new one cannot.
7. **Revoke other sessions** after a change (Firebase refresh tokens / app sessions) so a thief's session dies with the old number.
8. **Notify the OLD number by SMS** ("Your ShaloTrack number was changed. Not you? Contact support"). One cheap message that lets the real owner catch a takeover.
9. **Audit log** the request, verify, failures and the old to new change (never the code).
10. **Close the bypass**: `PUT /api/Customers/{id}` (used by Fleet and the Android app) must ignore or reject a `PhoneNumber` that differs from the stored one. Fleet now always sends the stored number, but the Android app and anyone with a token can still call the API directly.

## Fleet side (done)

- Routes `POST /profile/phone/request` and `POST /profile/phone/verify`; throttles 2/min + 5/h and 5/min + 10/h per customer.
- Input normalised to `+947XXXXXXXX` (`App\Support\SriLankanPhone`); invalid numbers, same-as-current number and codes that are not exactly 6 digits never reach the API.
- `PUT /profile` ignores any `phoneNumber` the browser sends and forwards the stored one.
- Tests: `tests/Feature/PhoneChangeTest.php`, `tests/Unit/SriLankanPhoneTest.php`, updated `ProfileTest.php` and `InputFormatValidationTest.php`.
