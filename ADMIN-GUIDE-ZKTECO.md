# FitHub ZKTeco (ZKBio) Setup

## 1. Prerequisites
- ZKBio software is installed and running.
- Turnstile/device is already online inside ZKBio.
- FitHub server can reach the ZKBio host URL/IP.

## 2. Connect ZKTeco
1. Open `ZKTeco -> Settings`.
2. Enter:
   - `ZKBio Base URL or Host`
   - optional `Port`
   - `Username/Password` or `API Key` (as required by your ZKBio instance)
   - SSL and timeout options
3. Click `Save Settings`.
4. Click `Test Connection`.

If the host is a private LAN IP and FitHub is cloud-hosted, expose ZKBio through VPN or reverse proxy first.

## 3. Fetch Devices and Map Branch
1. Click `Fetch Devices`.
2. In `Branch & Device Mapping`, select branch.
3. Select target door/lane/turnstile devices.
4. Click `Save Device Mapping`.

## 4. Sync Members and Logs
- Click `Sync Personnel` to push active members and access rights.
- Click `Sync Logs` to pull entry/exit events.
- Use `ZKTeco -> Logs` to review imported records.

Members without biometric enrollment on ZKBio/device are tracked as `Biometric Pending`.

## 5. Scheduler (required)
Ensure server cron runs Laravel scheduler every minute:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

Registered ZKTeco tasks:
- `zkteco:health-check` (every 10 minutes)
- `zkteco:sync-events` (every 5 minutes)
- `zkteco:sync-personnel` (hourly)

