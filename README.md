import os

# Define the precise, scoped README content focused exactly on the project scope
readme_v2_content = """# Automated Virtual Host Engine for XAMPP

A localized automation tool engineered to streamline web development workflows on Windows. This project provides a web-based dashboard that automates the creation of clean, production-like URLs (pretty URLs) for local projects, eliminating manual configuration file updates and XAMPP control panel restarts.

---

## 📋 Project Scope & Objectives

The sole purpose of this application is to automate a tedious three-step local development workflow into a single click:

1. **Local DNS Mapping:** Automatically opens and appends the custom domain name mapped to the local IP address (`127.0.0.1`) inside the protected Windows `hosts` file.
2. **Virtual Host Configuration:** Appends the required `<VirtualHost>` blocks, matching the pretty URL to the project's absolute path, inside Apache's `httpd-vhosts.conf` file.
3. **Asynchronous Server Hot-Swap:** Safely restarts the Apache web server behind the scenes to apply changes instantly without crashing the web page or freezing the browser.

---

## ⚡ The Core Problem Solved

When a web page tries to restart the very server it is running on, a process deadlock occurs. Under Windows (`mpm_winnt`), a standard restart command causes Apache child threads to lose track of their parent process. This results in a server crash and leaves background "zombie" processes trapping network ports 80 and 443.

This project solves that bottleneck by changing the server lifecycle workflow:
* **Forced Eviction:** It completely wipes out all active and hung Apache instances at the operating system level, ensuring network ports are instantly freed.
* **Asynchronous Detachment:** Instead of letting the script wait for a response, it triggers Apache to launch independently in the background, cutting the connection to the web page immediately so the automation completes smoothly.

---

## ⚙️ Requirements & System Privileges

Because this application modifies protected operating system paths and restarts system binaries, it operates under strict environmental rules:

* **Administrative Privileges:** The XAMPP Control Panel or Apache service **must** be executed using **"Run as Administrator"** to allow file access to the Windows `hosts` file.
* **Local Loopback Security:** The application is locked strictly to `127.0.0.1` (localhost), ensuring configuration endpoints are never exposed to external networks.
"""

# Save to file
file_name = "README.md"
with open(file_name, "w", encoding="utf-8") as f:
    f.write(readme_v2_content.strip())

print(f"File successfully created: {file_name}")
