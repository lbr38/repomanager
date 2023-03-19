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

<h3>OS</h3>

Repomanager runs on following systems:
- Debian 9,10
- RHEL 7/8, CentOS 7/8

**Recommended system:** Debian 10 or RHEL/CentOS 8.

<h3>Hardware</h3>

- CPU and RAM are mostly sollicited during mirror creation if GPG signature is enabled.
- Disk space depends on the size of the repos you need to clone.

<h3>Software</h3>

- Common packages (curl, gnupg2...). Repomanager will automatically install them during the installation process.
- A web service + PHP and SQLite.

**Recommended:** nginx + PHP-FPM (PHP 8.1)

<h2>Installation and documentation</h2>

Official documentation is available <a href="https://github.com/lbr38/repomanager/wiki/Documentation">here</a>.

It should help you **installing** and starting using Repomanager.
