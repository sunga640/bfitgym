# FitHub Cloud - Access Control Administration Guide

This guide is for administrators managing access control devices and agents in the FitHub cloud system.

## Table of Contents

1. [System Overview](#system-overview)
2. [Managing Devices](#managing-devices)
3. [Managing Agents](#managing-agents)
4. [Generating Enrollment Codes](#generating-enrollment-codes)
5. [Monitoring & Troubleshooting](#monitoring--troubleshooting)
6. [Common Tasks](#common-tasks)
7. [Emergency Procedures](#emergency-procedures)
8. [When Things Go Wrong - Recovery Guide](#when-things-go-wrong---recovery-guide)
9. [PHP Application Commands (Cloud Server)](#php-application-commands-cloud-server)
10. [Quick Reference](#quick-reference)
11. [Support](#support)

---

## System Overview

The FitHub Access Control system consists of:

1. **Cloud Application** (this system) - Central management
2. **Local Agents** - Software running on branch computers
3. **Hikvision Devices** - Physical access control hardware

### How It Works

```
Cloud (FitHub)                    Branch Location
┌────────────────┐               ┌────────────────┐
│ Member Data    │───Commands───►│ Local Agent    │
│ Access Rules   │               │ (Windows PC)   │
│ Event Logs     │◄───Events─────│                │
└────────────────┘               └───────┬────────┘
                                         │ LAN
                                         ▼
                                 ┌────────────────┐
                                 │ Hikvision      │
                                 │ Access Device  │
                                 └────────────────┘
```

### Data Flow

| Direction | Data | Frequency |
|-----------|------|-----------|
| Cloud → Agent | Member sync commands | On member changes |
| Cloud → Agent | Access validity updates | On subscription changes |
| Agent → Cloud | Access logs (entry/exit) | Every 10 seconds |
| Agent → Cloud | Heartbeat | Every 30 seconds |

---

## Managing Devices

### Adding a New Device

1. Navigate to **Access Control** → **Devices**
2. Click **Add Device**
3. Fill in:
   - **Name:** Descriptive name (e.g., "Main Entrance", "Staff Exit")
   - **Device Model:** Select the Hikvision model (e.g., DS-K1T804MF)
   - **Serial Number:** Found on device label (required, must be unique)
   - **Device Type:** Entry, Exit, or Both
4. Click **Save**

> **Note:** The device IP, username, and password are configured on the local agent, not in the cloud. This is for security - credentials never leave the local network.

### Device Status

| Status | Meaning |
|--------|---------|
| 🟢 Online | Agent connected and device responding |
| 🟡 Unknown | No recent heartbeat from agent |
| 🔴 Offline | Agent reported device connection failure |

### Editing Device Settings

1. Go to **Access Control** → **Devices**
2. Click on the device name
3. Update settings
4. Click **Save**

> **Important:** If you change the device serial number, you'll need to re-configure it on the local agent.

---

## Managing Agents

### Viewing Agent Status

1. Navigate to **Access Control** → **Agents**
2. View the list of registered agents

### Agent Information

| Field | Description |
|-------|-------------|
| Name | Computer/agent name |
| UUID | Unique identifier |
| Status | Active or Revoked |
| Last Seen | Last heartbeat timestamp |
| Last IP | IP address of last connection |
| Devices | Assigned devices |

### Revoking an Agent

If an agent is compromised or no longer needed:

1. Go to **Access Control** → **Agents**
2. Click on the agent
3. Click **Revoke Agent**
4. Confirm

> **Warning:** This immediately stops the agent from communicating with the cloud. The local agent will show authentication errors.

---

## Generating Enrollment Codes

Enrollment codes allow local agents to register with the cloud.

### Creating an Enrollment Code

1. Navigate to **Access Control** → **Agent Enrollments**
2. Click **Generate Code**
3. Fill in:
   - **Label:** (Optional) Identify this enrollment (e.g., "Downtown Branch Setup")
   - **Expires In:** How long the code is valid (default: 30 minutes)
   - **Pre-assign Devices:** Select devices this agent will manage
4. Click **Generate**
5. **Copy the code immediately** - it's only shown once!

### Code Lifecycle

| Status | Meaning |
|--------|---------|
| Active | Code can be used |
| Used | Code was used to register an agent |
| Expired | Code expired before use |
| Revoked | Code was manually revoked |

### Best Practices

- Generate codes only when ready to install
- Use short expiration times (30 min) for security
- Pre-assign devices to reduce manual configuration
- Label codes clearly for tracking

---

## Monitoring & Troubleshooting

### Checking Agent Health

1. Go to **Access Control** → **Agents**
2. Look for:
   - **Last Seen:** Should be within last minute
   - **Status:** Should be "Active"
   - **Queue:** Should show low numbers

### Common Issues

#### Agent Shows "Offline"

**Possible Causes:**
- Computer turned off or restarted
- Agent process stopped
- Network connectivity issues
- Agent credentials revoked

**Resolution:**
1. Check if the branch computer is on
2. Verify the agent is running (check Task Manager for php.exe)
3. Check internet connectivity
4. View agent logs for errors

#### Device Shows "Offline" but Agent is Online

**Possible Causes:**
- Device IP changed
- Device credentials changed
- Network issue between computer and device
- Device powered off

**Resolution:**
1. Contact branch to verify device is on
2. Update device IP/password on local agent if changed
3. Check local network connectivity

#### Commands Stuck in Queue

**Possible Causes:**
- Agent not running
- Network issues
- Device errors

**Resolution:**
1. Check agent status
2. View command details for error messages
3. Retry failed commands

### Viewing Logs

1. Go to **Access Control** → **Agents** → Click agent
2. View recent activity in the logs section
3. Check error messages for details

---

## Common Tasks

### Re-enrolling a Branch

If an agent needs to be completely re-setup:

1. Revoke the old agent (if still registered)
2. Generate a new enrollment code
3. On the branch computer:
   - Delete `storage\agent.sqlite`
   - Run `install-agent.bat`
   - Enter new enrollment code

### Changing Device Assignment

To assign a device to a different agent:

1. Go to **Access Control** → **Devices**
2. Edit the device
3. Change the assigned agent
4. On the new agent's computer, add the device credentials

### Rotating Agent Credentials

If credentials are compromised:

1. Revoke the current agent
2. Generate new enrollment code
3. Re-enroll the local agent

### Checking Access Logs

1. Navigate to **Access Control** → **Access Logs**
2. Filter by date, device, or member
3. Export if needed

### Syncing a Specific Member

If a member's access isn't synced:

1. Go to **Members** → Find member
2. Check their access status
3. Click **Sync to Devices** if available
4. Check for errors in command queue

---

## Emergency Procedures

### Agent Computer Replaced

1. Generate new enrollment code
2. Install agent on new computer
3. Configure device credentials
4. Start agent

### Device Replaced

1. Update device serial number in cloud
2. Configure new device credentials on local agent
3. Sync all members to new device

### Internet Outage at Branch

- Local agent queues commands and events
- Syncs automatically when connection restored
- Access events are timestamped correctly
- No manual intervention needed

---

## When Things Go Wrong - Recovery Guide

This section is written for **branch staff and non-technical users**. Follow the steps below when the access control system is not working properly. You do not need any technical knowledge — just follow the instructions in order.

---

### How to Tell Something Is Wrong

You may notice one or more of the following signs:

- Members scan their fingerprints or cards but the door does not open (even though their membership is active)
- The access control device screen is blank or shows an error
- In the FitHub cloud dashboard, the agent or device shows as **Offline** or **Unknown**
- You were told the local computer restarted, crashed, or lost power

If you see any of these signs, use the sections below to fix the problem.

---

### Situation 1: The Branch Computer Was Turned Off, Restarted, or Crashed

**What happened:** The computer that runs the local agent was shut down unexpectedly (power outage, Windows update restart, someone turned it off, blue screen crash, etc.). When this happens, the local agent software does not start on its own — you need to start it manually.

**What you will see:**
- The FitHub cloud dashboard shows the agent as **Offline** or **Unknown**
- The access control device may still be powered on but is no longer syncing with the cloud
- New members or subscription changes are not reaching the device

**How to fix it — step by step:**

1. **Make sure the computer is turned on**
   - Go to the branch computer (the one connected to the access control device)
   - If the screen is black, move the mouse or press a key on the keyboard
   - If nothing happens, press the power button on the computer to turn it on
   - Wait for Windows to fully load (you should see the desktop)

2. **Log in to Windows** (if required)
   - If you see a login screen, enter the username and password for that computer
   - Wait for the desktop to appear

3. **Start the local agent**
   - Look on the desktop for a file called **`start-agent.bat`**
   - **Double-click** on `start-agent.bat`
   - A black window (command prompt) will appear — **do not close this window**
   - You should see messages appearing in the window. Look for a message that says something like **"Agent started"** or **"Connected to cloud"**

4. **Verify the connection**
   - Wait about 1–2 minutes
   - Go to the FitHub cloud dashboard on any computer or phone
   - Navigate to **Access Control** → **Agents**
   - The agent status should now show as **Active** and **Last Seen** should show a recent time (within the last minute)

5. **If the agent still does not start**, see [Situation 4: The Local Agent Will Not Start](#situation-4-the-local-agent-will-not-start) below.

> **Tip:** The black command prompt window must stay open for the agent to keep running. If someone closes it, the agent will stop. Do not close it — you can minimize it instead.

---

### Situation 2: Connection to the Access Control Device Is Lost

**What happened:** The local agent is running on the computer, but it cannot communicate with the physical access control device (the Hikvision unit at the door). This can happen if:
- The network cable between the computer and the device was unplugged or damaged
- The device lost power (unplugged, tripped breaker, etc.)
- The device's network settings were accidentally changed
- A network switch or router between the computer and the device failed

**What you will see:**
- The FitHub cloud dashboard shows the **agent as Online** but the **device as Offline** (red dot)
- Members cannot scan in or out even though the computer is on
- The device screen may be blank (if it lost power) or may show a normal screen but is not syncing

**How to fix it — step by step:**

1. **Check the access control device**
   - Go to the physical device at the door
   - Is the screen on? If the screen is blank or dark:
     - Check that the power cable is firmly plugged in
     - Check that the power outlet is working (try plugging in something else)
     - If using a power-over-ethernet (PoE) switch, make sure the switch is on
   - If the screen is on and showing normally, the device has power — move to step 2

2. **Check the network cable**
   - Follow the network cable from the access control device to the computer (or network switch)
   - Make sure the cable is firmly plugged in at **both ends**
   - If the cable looks damaged (bent, cut, crushed), replace it
   - If you are using a network switch between the device and computer, make sure the switch is powered on and its lights are blinking

3. **Test the connection from the computer**
   - On the branch computer, look on the desktop for a file called **`test-connection.bat`**
   - **Double-click** on `test-connection.bat`
   - A black window will open and show you whether the computer can reach the device
   - If it says **"Connection successful"** — the problem may have been temporary, and things should start working again shortly
   - If it says **"Connection failed"** — the cable, switch, or device still has a problem. Go back to steps 1 and 2

4. **Restart the device (if nothing else works)**
   - Unplug the power to the access control device
   - Wait **30 seconds**
   - Plug the power back in
   - Wait **2 minutes** for the device to fully start up
   - Run `test-connection.bat` again on the computer

5. **Verify in the cloud**
   - Go to the FitHub cloud dashboard
   - Navigate to **Access Control** → **Devices**
   - The device should now show a **green dot** (Online)
   - If it is still red, contact technical support (see [Support](#support))

---

### Situation 3: The Computer Has Internet Problems

**What happened:** The branch computer is on and the local agent is running, but the computer cannot reach the internet. This means the agent cannot communicate with the FitHub cloud. This can happen if:
- The internet service is down at the branch
- The Wi-Fi or network cable to the router is disconnected
- The router or modem needs to be restarted

**What you will see:**
- The FitHub cloud dashboard shows the agent as **Offline** or **Unknown**
- The access control device may still allow previously-synced members to scan in/out
- New member changes, subscription updates, or new fingerprints are NOT syncing

**How to fix it — step by step:**

1. **Check if the internet is working**
   - On the branch computer, open a web browser (Chrome, Edge, etc.)
   - Try to open any website (e.g., type `google.com` in the address bar)
   - If the website loads, the internet is working — skip to step 3
   - If the website does not load, the internet is down — continue to step 2

2. **Restart the router/modem**
   - Find the internet router (and modem, if separate) at the branch
   - Unplug the power cable from the router
   - If you have a separate modem, unplug that too
   - Wait **60 seconds**
   - Plug the modem back in first, wait 2 minutes
   - Then plug the router back in, wait 2 minutes
   - Go back to the computer and try opening a website again
   - If the internet is still not working, contact your internet service provider

3. **Restart the local agent** (once internet is back)
   - On the branch computer, look for the black command prompt window where the agent is running
   - Click on that window to select it
   - Press **Ctrl + C** on the keyboard (hold Ctrl and press C) — this stops the agent
   - The window may close, or it may show a message that the agent stopped
   - Now **double-click** `start-agent.bat` on the desktop to start it again
   - Wait 1–2 minutes for it to reconnect

4. **Verify the connection**
   - Go to the FitHub cloud dashboard
   - Navigate to **Access Control** → **Agents**
   - The agent should show as **Active** with a recent **Last Seen** time

> **Good to know:** When the internet comes back, the local agent will automatically send any access events (entry/exit logs) that happened while offline. No data is lost — it just gets delayed until the internet returns.

---

### Situation 4: The Local Agent Will Not Start

**What happened:** You tried to start the local agent by double-clicking `start-agent.bat`, but it does not work. The black window might flash and close immediately, or show an error message.

**Common reasons this can happen:**
- The computer was freshly reinstalled or reset
- An antivirus program is blocking the agent
- Required files were accidentally deleted
- A Windows update changed system settings
- The agent was never fully installed on this computer

**How to fix it — step by step:**

1. **Try running the start script again**
   - Find `start-agent.bat` on the desktop
   - **Right-click** on it and choose **"Run as administrator"**
   - If Windows asks "Do you want to allow this app to make changes?", click **Yes**
   - Watch the black window carefully for any error messages
   - If the agent starts successfully, you are done

2. **Check if the agent files are still there**
   - Open **File Explorer** (the folder icon on the taskbar)
   - Navigate to the folder where the agent was installed (usually `C:\fithub-agent` or a similar location — ask your IT person if you are unsure)
   - You should see files including `start-agent.bat`, `stop-agent.bat`, and other files
   - If the folder is empty or missing, the agent needs to be reinstalled — go to step 5

3. **Check if antivirus is blocking the agent**
   - Some antivirus programs may block the agent because it runs scripts
   - Look for your antivirus icon in the bottom-right corner of the taskbar (near the clock)
   - Open the antivirus and check the **quarantine** or **blocked items** section
   - If you see any FitHub or agent-related files listed, **restore** them and add them to the antivirus **exceptions/whitelist**
   - Try starting the agent again

4. **Check the agent status**
   - Find `check-status.bat` on the desktop
   - **Double-click** on it
   - Read the information in the black window — it will tell you if there are problems
   - If it mentions a missing database or configuration, you may need to re-enroll (step 5)

5. **Re-install and re-enroll the agent (last resort)**

   If nothing above works, you need to set up the agent from scratch. **You will need someone with access to the FitHub cloud dashboard to help with this step.**

   **Person at the cloud dashboard:**
   - Go to **Access Control** → **Agent Enrollments**
   - Click **Generate Code**
   - Set the label (e.g., "Branch Name - Re-enrollment")
   - Pre-assign the devices for this branch
   - Click **Generate** and **copy the code**
   - Send the code to the person at the branch computer (by phone, text, or message)

   **Person at the branch computer:**
   - Find `install-agent.bat` on the desktop (or in the agent folder)
   - **Double-click** on `install-agent.bat`
   - When it asks for the enrollment code, **paste or type the code** you received
   - Wait for the installation to complete — you should see a success message
   - Now **double-click** `start-agent.bat` to start the agent

   **Verify together:**
   - Wait 1–2 minutes
   - On the cloud dashboard, go to **Access Control** → **Agents**
   - The new agent should appear as **Active**

> **Important:** The enrollment code expires quickly (usually within 30 minutes). Make sure the person at the branch computer is ready before generating the code.

---

### Situation 5: Everything Looks Normal but Members Cannot Scan In

**What happened:** The agent shows as Online, the device shows as Online, but members are being rejected when they try to scan their fingerprint or card.

**Possible reasons:**
- The member's subscription has expired or been suspended
- The member's fingerprint or card was never synced to the device
- There is a sync backlog (commands waiting to be processed)

**How to fix it — step by step:**

1. **Check the member's status in the cloud**
   - Go to **Members** in the FitHub cloud dashboard
   - Search for the member by name
   - Check that their **subscription is active** and has not expired
   - If the subscription expired, renew it first

2. **Re-sync the member to the device**
   - On the member's page, look for a **"Sync to Devices"** button
   - Click it to send the member's access credentials to the device again
   - Wait 1–2 minutes for the sync to complete

3. **Check for stuck commands**
   - Go to **Access Control** → **Agents** → Click on the branch agent
   - Look at the **command queue**
   - If there are many commands waiting (stuck), the device may be overwhelmed
   - Try restarting the local agent (stop and start — see Quick Restart below)

---

### Quick Restart of the Local Agent (Summary)

Use this whenever you need to restart the agent. These are the **shortest steps**:

| Step | What to Do |
|------|-----------|
| **1** | Go to the branch computer |
| **2** | Find the black command prompt window where the agent is running |
| **3** | Click on the window, then press **Ctrl + C** to stop it |
| **4** | Wait 5 seconds |
| **5** | Double-click **`start-agent.bat`** on the desktop |
| **6** | Wait for the message **"Agent started"** or **"Connected to cloud"** |
| **7** | **Do not close** the black window — minimize it instead |

If there is **no black window open** (the agent was not running):
- Simply **double-click** `start-agent.bat` on the desktop
- That's it — the agent will start and connect to the cloud automatically

---

### Making the Agent Start Automatically (Recommended)

To avoid needing to manually start the agent every time the computer restarts, you can set it up to start automatically. Choose **one** of the methods below — Method B (Task Scheduler) is the most reliable option.

---

#### Method A: Windows Startup Folder (Simplest)

This is the easiest method. The agent will start after the user logs in to Windows.

1. **On the branch computer**, press the **Windows key + R** on your keyboard
2. In the box that appears, type `shell:startup` and press **Enter**
3. A folder will open — this is the **Startup folder**
4. Find `start-agent.bat` on the desktop
5. **Right-click** on `start-agent.bat` and choose **Copy**
6. **Right-click** inside the Startup folder and choose **Paste shortcut**
7. Now the agent will automatically start every time the computer turns on or restarts

> **Pros:** Very easy to set up, no technical knowledge needed.
> **Cons:** Only starts after a user logs in. If the computer restarts and no one logs in, the agent won't start. Cannot auto-restart if the agent crashes.

---

#### Method B: Task Scheduler (Most Reliable — Recommended)

Task Scheduler gives you the most control. The agent can start even before anyone logs in, and it can automatically restart if it crashes.

1. **On the branch computer**, press the **Windows key** and type **Task Scheduler**, then open it
2. In the right panel, click **Create Task** (not "Create Basic Task")
3. **General tab:**
   - **Name:** `FitHub Access Control Agent`
   - Check **"Run whether user is logged on or not"**
   - Check **"Run with highest privileges"**
4. **Triggers tab:**
   - Click **New…**
   - Set **"Begin the task"** to **At startup**
   - Under **Advanced settings**, check **"Delay task for"** and set it to **1 minute** (gives Windows time to connect to the network)
   - Click **OK**
5. **Actions tab:**
   - Click **New…**
   - **Action:** Start a program
   - **Program/script:** Click Browse and select `start-agent.bat` on the desktop
   - **Start in:** Enter the folder where `start-agent.bat` is located (e.g., `C:\Users\BranchUser\Desktop`)
   - Click **OK**
6. **Settings tab:**
   - Check **"If the task fails, restart every"** and set to **5 minutes**
   - Set **"Attempt to restart up to"** to **3 times**
   - Uncheck **"Stop the task if it runs longer than"** (the agent should run forever)
   - Check **"If the running task does not end when requested, force it to stop"**
   - At the bottom, set **"If the task is already running"** to **"Do not start a new instance"**
7. Click **OK** — you may be asked to enter the Windows password for the user account
8. To test, right-click the task and choose **Run** — verify the agent starts in the cloud dashboard

> **Pros:** Starts before user login, auto-restarts on failure, configurable delay. Most reliable method.
> **Cons:** Slightly more steps to set up.

---

#### Method C: Registry Run Key (Alternative)

This method adds a registry entry so Windows automatically runs the agent at login. It works similarly to the Startup folder but is harder to accidentally remove.

1. **On the branch computer**, press **Windows key + R**
2. Type `regedit` and press **Enter** (click **Yes** if prompted)
3. Navigate to: `HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Run`
4. **Right-click** in the right panel → **New** → **String Value**
5. Name it: `FitHubAgent`
6. **Double-click** the new entry and set the **Value data** to the full path of the batch file, e.g.:
   ```
   "C:\Users\BranchUser\Desktop\start-agent.bat"
   ```
7. Click **OK** and close the Registry Editor
8. The agent will now start automatically every time the user logs in

> **To remove it later:** Go back to the same registry path and delete the `FitHubAgent` entry.

> **Pros:** Harder to accidentally delete than a Startup folder shortcut. Simple once set up.
> **Cons:** Requires editing the registry (be careful). Only starts after user login. Cannot auto-restart on crash.

---

#### Method D: Windows Service using NSSM (Advanced)

For advanced setups, you can install the agent as a **Windows Service** using [NSSM (Non-Sucking Service Manager)](https://nssm.cc/). This makes the agent run as a true background service — no user login needed, automatic restart on failure, and full service management via Windows.

**Step 1 — Download NSSM:**
1. Download NSSM from https://nssm.cc/download
2. Extract the zip file
3. Copy `nssm.exe` from the `win64` folder to a permanent location, e.g., `C:\Tools\nssm.exe`

**Step 2 — Install the service:**
1. Open **Command Prompt as Administrator** (right-click → Run as administrator)
2. Run:
   ```
   C:\Tools\nssm.exe install FitHubAgent
   ```
3. A GUI window will appear:
   - **Path:** Browse and select `start-agent.bat`
   - **Startup directory:** Enter the folder where the batch file is located
   - Click the **Details** tab:
     - **Display name:** `FitHub Access Control Agent`
     - **Description:** `Local agent for FitHub access control device communication`
   - Click the **Exit actions** tab:
     - **Restart action:** `Restart application`
     - **Restart delay:** `60000` ms (waits 1 minute before restarting)
4. Click **Install service**

**Step 3 — Start the service:**
```
net start FitHubAgent
```

**Useful service commands:**
| Command | What it does |
|---------|-------------|
| `net start FitHubAgent` | Start the agent service |
| `net stop FitHubAgent` | Stop the agent service |
| `nssm restart FitHubAgent` | Restart the agent service |
| `nssm status FitHubAgent` | Check if the service is running |
| `nssm remove FitHubAgent confirm` | Uninstall the service completely |

> **Pros:** True background service, no user login needed, automatic restart, managed via Windows Services.
> **Cons:** Requires downloading NSSM, more technical setup.

---

#### Which Method Should I Use?

| Method | Difficulty | Starts Before Login | Auto-Restart on Crash | Best For |
|--------|-----------|--------------------|-----------------------|----------|
| **A. Startup Folder** | Easy | No | No | Quick setup, single-user PCs |
| **B. Task Scheduler** | Medium | Yes | Yes | **Most branches (recommended)** |
| **C. Registry Run Key** | Medium | No | No | Alternative to Startup Folder |
| **D. NSSM Service** | Advanced | Yes | Yes | High-reliability / server setups |

> **Our recommendation:** Use **Method B (Task Scheduler)** for most branch computers. It provides the best balance of reliability and ease of setup.

> **Note:** Whichever method you choose, you should still verify that the agent starts properly after the next computer restart by checking the cloud dashboard.

---

### Troubleshooting Checklist (Print This Out)

If something goes wrong at the branch, work through this checklist from top to bottom:

- [ ] **Is the computer turned on?** → If no, turn it on and wait for Windows to load
- [ ] **Is the computer logged in?** → If no, log in with the correct username and password
- [ ] **Is the agent running?** → Look for a black command prompt window. If not there, double-click `start-agent.bat`
- [ ] **Is the internet working?** → Open a web browser and try visiting google.com. If no, restart the router
- [ ] **Is the access control device powered on?** → Check the screen on the device. If blank, check power cables
- [ ] **Is the network cable connected?** → Check cables between the computer and the device. Make sure both ends are firmly plugged in
- [ ] **Does the cloud dashboard show the agent as Active?** → Go to Access Control → Agents. If not Active, restart the agent
- [ ] **Does the cloud dashboard show the device as Online (green)?** → Go to Access Control → Devices. If not green, run `test-connection.bat`
- [ ] **Still not working?** → Contact technical support with the details from the [Support](#support) section

---

## PHP Application Commands (Cloud Server)

These are artisan commands you run **on the cloud server** (not the branch computer). They are used to manage devices, sync members, and maintain the access control system from the server side. All commands are run via the terminal on the server where the FitHub cloud application is hosted.

> **Note:** All commands below must be prefixed with `php artisan`. For example, to run the `access:disable-expired` command, you would type: `php artisan access:disable-expired`

---

### Command Summary

| Command | Purpose |
|---------|---------|
| `access:disable-expired` | Disable access for members with expired subscriptions |
| `subscriptions:update-expired` | Mark expired subscriptions as "expired" |
| `test:user-sync` | Queue a member sync to a device (for testing) |
| `device:set-password` | Set or update a device password |
| `device:link-user` | Link a Hikvision device user to a system member |

---

### `access:disable-expired`

**What it does:** Finds all members whose subscription or insurance has expired and queues commands to disable their fingerprint/card access on the access control devices. The local agent at the branch will pick up these disable commands and execute them on the device.

**When to use it:** Run this if you need to immediately revoke access for expired members rather than waiting for the automatic schedule.

**Usage:**
```bash
php artisan access:disable-expired
```

**Arguments:** None

**Options:** None

**Example output:**
```
Checking for expired member access...
Disabled 3 member(s) with expired subscriptions/insurance.
Disable commands have been enqueued for the local agent.
```

**Automatic schedule:** This command runs automatically every hour and also daily at 00:10. Output is logged to `storage/logs/access-disable-expired.log`.

---

### `subscriptions:update-expired`

**What it does:** Finds all active subscriptions where the end date has already passed and updates their status to "expired". This ensures the system accurately reflects which members still have valid subscriptions.

**When to use it:** Run this if you need to immediately update subscription statuses rather than waiting for the automatic schedule. This command is typically run **before** `access:disable-expired` since expired subscriptions need to be marked first before access can be disabled.

**Usage:**
```bash
php artisan subscriptions:update-expired
```

**Arguments:** None

**Options:** None

**Example output:**
```
Checking for expired subscriptions...
Found 5 expired subscription(s). Updating...
Updated 5 subscription(s) to expired status.
```

**Automatic schedule:** This command runs automatically every hour and also daily at 00:05. Output is logged to `storage/logs/subscriptions-update.log`.

---

### `test:user-sync`

**What it does:** Queues a `person_upsert` command for a specific member on a specific device. This creates or updates an AccessIdentity for the member and puts a sync command in the queue for the local agent to pick up. The agent will then push the member's profile to the Hikvision device. After syncing, the member's fingerprint must still be enrolled manually via the device's web dashboard.

**When to use it:** Use this for testing member sync to a device, or to manually force a re-sync of a specific member when the normal automatic sync is not working.

**Usage:**
```bash
php artisan test:user-sync {member_id} [--device=DEVICE_ID] [--valid-days=365]
```

**Arguments:**

| Argument | Required | Description |
|----------|----------|-------------|
| `member_id` | Yes | The ID of the member to sync to the device |

**Options:**

| Option | Default | Description |
|--------|---------|-------------|
| `--device=` | Auto-detected | Specific device ID. If not provided, uses the first active device for the member's branch |
| `--valid-days=` | `365` | Number of days the member's access will be valid from today |

**Examples:**
```bash
# Sync member ID 42 to the default device for their branch
php artisan test:user-sync 42

# Sync member ID 42 to a specific device (ID 3)
php artisan test:user-sync 42 --device=3

# Sync member ID 42 with access valid for 30 days
php artisan test:user-sync 42 --valid-days=30
```

**Example output:**
```
============================================
   USER SYNC TO DEVICE TEST
============================================

Member: John Doe (ID: 42)
Member No: MEM-00042
Branch ID: 1

Device: Main Entrance (ID: 1)

Device User ID: MEM-00042
Creating new AccessIdentity...
AccessIdentity ID: 15

============================================
   COMMAND QUEUED SUCCESSFULLY
============================================

Command ID: 9c3f1a2b-...
Type: person_upsert
Status: pending
Device ID: 1

The local agent will pick up this command on its next poll.
Watch the agent logs for execution details.

After the user is synced to the device, add fingerprint manually via:
  Device web dashboard: http://<device-ip>
```

**After running this command:**
1. The local agent at the branch will pick up the command on its next poll cycle
2. The agent will push the member profile to the Hikvision device
3. You must then enroll the member's fingerprint manually via the device's web dashboard (`http://<device-ip>`)

---

### `device:set-password`

**What it does:** Sets or updates the stored password for an access control device in the cloud database. This is the password the local agent uses when connecting to the Hikvision device over the local network.

**When to use it:** Use this when setting up a new device, or when the device password has been changed and needs to be updated in the system.

**Usage:**
```bash
php artisan device:set-password {device_id} [--password=PASSWORD]
```

**Arguments:**

| Argument | Required | Description |
|----------|----------|-------------|
| `device_id` | Yes | The ID of the access control device |

**Options:**

| Option | Default | Description |
|--------|---------|-------------|
| `--password=` | Prompts for input | The device password. If not provided, you will be prompted to enter it securely (hidden input) |

**Examples:**
```bash
# Set password for device ID 1 (will prompt for secure input)
php artisan device:set-password 1

# Set password directly via option
php artisan device:set-password 1 --password=MyDevicePass123
```

**Example output:**
```
Device: Main Entrance
IP: 192.168.1.100:80
Current username: admin
Password currently set: No

✓ Password updated successfully!

Cloud does not test or poll devices directly.
To verify connectivity and sync logs, use the Local Agent.
```

> **Security tip:** Prefer running without `--password=` so the password is entered via hidden prompt and does not appear in your terminal history.

---

### `device:link-user`

**What it does:** Links a user that already exists on the Hikvision device (identified by their Employee No) to a member in the FitHub system. This is useful when members were enrolled directly on the device before the cloud system was set up, and you need to link their device identity to their system profile so access events are properly recorded.

**When to use it:** Use this when a member already has a fingerprint/card enrolled on the device but is not linked to their FitHub member record. This typically happens during initial setup or migration from a standalone device to the cloud-managed system.

**Usage:**
```bash
php artisan device:link-user {device_user_id} {member_id} [--branch=BRANCH_ID]
```

**Arguments:**

| Argument | Required | Description |
|----------|----------|-------------|
| `device_user_id` | Yes | The Employee No from the Hikvision device (visible in the device's user list) |
| `member_id` | Yes | The FitHub Member ID to link to |

**Options:**

| Option | Default | Description |
|--------|---------|-------------|
| `--branch=` | Member's branch | Branch ID. Defaults to the member's assigned branch |

**Examples:**
```bash
# Link Hikvision Employee No "EMP001" to FitHub member ID 42
php artisan device:link-user EMP001 42

# Link with a specific branch override
php artisan device:link-user EMP001 42 --branch=2
```

**Example output:**
```
✓ Created AccessIdentity!

Linked device user 'EMP001' to member:
  Name: John Doe
  Member ID: 42
  Branch: 1

✓ Member has active subscription/insurance - access logs will be recorded!
```

**If the device user is already linked**, you will be asked to confirm before updating:
```
An AccessIdentity already exists for device_user_id 'EMP001' in branch 1
  Current link: member ID 15
Do you want to update it? (yes/no)
```

> **Note:** This command also checks whether the member has an active subscription or insurance. If they don't, you will see a warning that access events will be skipped until the member has an active subscription.

---

### Scheduled Commands

The following commands run automatically on the server. You do not need to run them manually unless you want to trigger them immediately.

| Command | Schedule | Log File |
|---------|----------|----------|
| `subscriptions:update-expired` | Every hour + daily at 00:05 | `storage/logs/subscriptions-update.log` |
| `access:disable-expired` | Every hour + daily at 00:10 | `storage/logs/access-disable-expired.log` |

**How the schedule works:**
1. `subscriptions:update-expired` runs first and marks any past-due subscriptions as "expired"
2. `access:disable-expired` runs shortly after and disables device access for any members whose subscriptions/insurance are now expired
3. The disable commands are queued and the local agent at each branch picks them up and executes them on the physical device

**To check scheduled command logs**, read the log files on the server:
```bash
# View recent subscription update output
tail -50 storage/logs/subscriptions-update.log

# View recent access disable output
tail -50 storage/logs/access-disable-expired.log
```

---

### Common Workflows Using Commands

#### Manually expire and disable a batch of members

If you need to immediately cut off access for all expired members (e.g., end of month):

```bash
# Step 1: Mark expired subscriptions
php artisan subscriptions:update-expired

# Step 2: Disable their device access
php artisan access:disable-expired
```

#### Set up a new device

```bash
# Step 1: Add the device via the cloud dashboard (Access Control → Devices → Add Device)
# Step 2: Set the device password
php artisan device:set-password <device_id>
```

#### Migrate existing device users to the cloud system

For each member already enrolled on the device:
```bash
# Find their Employee No on the device, then link it
php artisan device:link-user <employee_no> <member_id>
```

#### Test that a member syncs correctly

```bash
# Queue a sync command for the member
php artisan test:user-sync <member_id>

# Then monitor via the cloud dashboard (Access Control → Agents → check command queue)
```

---

## Quick Reference

### Agent Commands (on branch computer)

| Command | Purpose |
|---------|---------|
| `install-agent.bat` | Initial setup |
| `start-agent.bat` | Start agent |
| `stop-agent.bat` | Stop agent |
| `check-status.bat` | View status |
| `test-connection.bat` | Test connectivity |
| `view-logs.bat` | View agent logs |

### Cloud Server Commands (PHP Artisan)

| Command | Purpose |
|---------|---------|
| `php artisan access:disable-expired` | Disable access for expired members |
| `php artisan subscriptions:update-expired` | Mark expired subscriptions |
| `php artisan test:user-sync {member_id}` | Test sync a member to device |
| `php artisan device:set-password {device_id}` | Set device password |
| `php artisan device:link-user {employee_no} {member_id}` | Link device user to member |

### Admin URLs

| Page | Path |
|------|------|
| Devices | `/access-control/devices` |
| Agents | `/access-control/agents` |
| Enrollments | `/access-control/enrollments` |
| Access Logs | `/access-control/logs` |

---

## Support

For technical support, provide:
1. Agent UUID
2. Device serial numbers
3. Error messages from logs
4. Screenshots of issues
