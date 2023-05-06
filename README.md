<h1>REPOMANAGER</h1>

**Repomanager** is a web mirroring tool for RPM or DEB packages repositories.

Designed for an enterprise usage and to help deployment of packages updates on large Linux servers farms, it can create mirrors of public repositories (eg. Debian or CentOS official repos or third-party editors) and manage several snapshots versions and environments.

<h2>Main features</h2>

- Create deb or rpm mirror repositories
- Sign repo with GPG
- Upload packages into repositories
- Create environments (eg. preprod, prod...) and make mirrors available only for specific envs.
- Manage hosts packages updates
- Plan tasks
- ...

![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/demo-1.gif?raw=true)
![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager-2.png?raw=true)
![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager-4.png?raw=true)
![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager-5.png?raw=true)
![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager-3.png?raw=true)

<h2>Requirements</h2>

<h3>Hardware</h3>

- CPU and RAM are mostly sollicited during mirror creation if GPG signature is enabled.
- Disk space depends on the size of the repos you need to clone.

<h3>Software and configuration</h3>

- **docker** (service must be up and running)
- **docker-compose**
- **A fully qualified domain name** (FQDN) and a valid SSL certificate for this FQDN if you want to access the web interface through a secure connection (https)
- A least a **SPF record** configured for your FQDN, to be able to send emails from Repomanager

<h2>Installation and usage</h2>

Official documentation is available <a href="https://github.com/lbr38/repomanager/wiki">here</a>.

It should help you **installing** and starting using Repomanager.
