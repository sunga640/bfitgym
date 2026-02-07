# FitHub Cloud - Access Control Administration Guide

This guide is for administrators managing access control devices and agents in the FitHub cloud system.

## Table of Contents

1. [System Overview](#system-overview)
2. [Managing Devices](#managing-devices)
3. [Managing Agents](#managing-agents)
4. [Generating Enrollment Codes](#generating-enrollment-codes)
5. [Monitoring & Troubleshooting](#monitoring--troubleshooting)
6. [Common Tasks](#common-tasks)

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
