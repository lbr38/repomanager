<h1>REPOMANAGER</h1>

**Repomanager** is a web mirroring tool for ``rpm`` and ``deb`` packages repositories.

<h2>Main features</h2>

- Create ``deb`` and ``rpm`` mirror repositories
- Sign packages/repositories with GPG
- Upload packages into repositories
- Create environments (eg. ``preprod``, ``prod``) and make mirrors only available for specific environments
- Manage hosts packages updates
- Schedule tasks

![alt text](https://github.com/user-attachments/assets/5a7c2bc5-72c7-45ad-8b67-0be9aaccd8f6)
![alt text](https://github.com/user-attachments/assets/0a48aa80-1821-45a0-a236-1376365361ce)
![alt text](https://github.com/user-attachments/assets/e285657e-5b3e-49db-a3ea-ed3a3ae2ffb7)
![alt text](https://github.com/user-attachments/assets/3d2e1339-5cdc-4c23-ac4a-ea1f63e42f9f)
![alt text](https://github.com/user-attachments/assets/2080d964-c3f9-4865-b6fe-a3fceaf76c84)

<h2>Requirements</h2>

<h3>Hardware</h3>

- CPU and RAM are mostly sollicited during mirror creation if GPG signature is enabled
- Disk space depends on the size of the repos you need to clone

Minimum requirements:
- 4 vCPU
- 4 GB of RAM
- Please use a SSD disk to avoid disk I/O and latency issues

<h3>Software and configuration</h3>

- **docker** (service must be up and running)
- **A fully qualified domain name** (FQDN) and a valid SSL certificate for this FQDN if you want to access the web interface through a secure connection (https)
- A least a **SPF record** configured for your FQDN, to be able to send emails from Repomanager

<h2>Installation and usage</h2>

Official documentation is available <a href="https://github.com/lbr38/repomanager/wiki">here</a>.

It should help you **installing** and starting using Repomanager.

<h2>Contact</h2>

- For bug reports, issues or features requests, please open a new issue in the Github ``Issues`` section
- A Discord channel is available <a href="https://discord.gg/34yeNsMmkQ">here</a> for any questions or quick help/debugging (English or French spoken)
- You can also contact me at <a href="mailto:repomanager@protonmail.com">repomanager@protonmail.com</a> (English or French spoken)
