<h1>REPOMANAGER</h1>

**Repomanager** is a web mirroring tool for ``rpm`` or ``deb`` packages repositories.

Designed for an enterprise usage and to help deployment of packages updates on large Linux servers farms, it can create mirrors of public repositories (eg. Debian or CentOS official repos or third-party editors) and manage several snapshots versions and environments.

<h2>Main features</h2>

- Create ``deb`` or ``rpm`` mirror repositories
- Sign packages/repositories with GPG
- Upload packages into repositories
- Create environments (eg. ``preprod``, ``prod``...) and make mirrors available only for specific envs.
- Manage hosts packages updates
- Plan tasks
- ...

![alt text](https://github.com/lbr38/repomanager/assets/54670129/1cc4e053-cd74-4744-8f6b-f78fa1eef1cb)
![alt text](https://github.com/lbr38/repomanager/assets/54670129/250f2fa3-349d-4fbb-bd1c-dd5ae7556fa4)
![alt text](https://github.com/lbr38/repomanager/assets/54670129/8b2ad760-95a7-4735-bd88-f8630b1e4060)
![alt text](https://github.com/lbr38/repomanager/assets/54670129/774b7f52-6972-4aeb-8723-77c64b7afeaf)
![alt text](https://github.com/lbr38/repomanager/assets/54670129/38a325dd-22a9-4785-a8fd-7273bc97d047)

<h2>Requirements</h2>

<h3>Hardware</h3>

- CPU and RAM are mostly sollicited during mirror creation if GPG signature is enabled
- Disk space depends on the size of the repos you need to clone

<h3>Software and configuration</h3>

- **docker** (service must be up and running)
- **A fully qualified domain name** (FQDN) and a valid SSL certificate for this FQDN if you want to access the web interface through a secure connection (https)
- A least a **SPF record** configured for your FQDN, to be able to send emails from Repomanager

<h2>Installation and usage</h2>

Official documentation is available <a href="https://github.com/lbr38/repomanager/wiki">here</a>.

It should help you **installing** and starting using Repomanager.

<h2>Roadmap</h2>

No roadmap, just a todolist with some bug reports and ideas you can find <a href="https://github.com/lbr38/repomanager/blob/devel/Todolist">here</a>.

<h2>Contact</h2>

- For bug reports, issues or features requests, please open a new issue in the Github ``Issues`` section
- For any other question you can contact me at <a href="mailto:repomanager@protonmail.com">repomanager@protonmail.com</a> (English or French spoken)
