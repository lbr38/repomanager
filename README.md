<div align="center">
    <img src="https://raw.githubusercontent.com/lbr38/repomanager/refs/heads/devel/images/readme/github-readme-black.png#gh-light-mode-only" align="top">
</div>

<div align="center">
    <img src="https://raw.githubusercontent.com/lbr38/repomanager/refs/heads/devel/images/readme/github-readme-white.png#gh-dark-mode-only" align="top" width=70%>
</div>

<br><br>

**Repomanager** is a web mirroring tool for ``rpm`` and ``deb`` packages repositories.

<h2>Main features</h2>

- Create ``deb`` and ``rpm`` mirror repositories
- Sign packages/repositories with GPG
- Upload packages into repositories
- Create environments (eg. ``preprod``, ``prod``) and make mirrors only available for specific environments
- Manage hosts packages updates
- Schedule tasks

![alt text](https://raw.githubusercontent.com/lbr38/repomanager/refs/heads/devel/images/readme/demo.gif)
![alt text](https://raw.githubusercontent.com/lbr38/repomanager/refs/heads/devel/images/readme/screenshot01.png)
![alt text](https://raw.githubusercontent.com/lbr38/repomanager/refs/heads/devel/images/readme/screenshot02.png)
![alt text](https://raw.githubusercontent.com/lbr38/repomanager/refs/heads/devel/images/readme/screenshot03.png)
![alt text](https://raw.githubusercontent.com/lbr38/repomanager/refs/heads/devel/images/readme/screenshot04.png)

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
