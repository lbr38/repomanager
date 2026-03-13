## Hardware

These are the minimum hardware requirements to run Repomanager smoothly:

- :material-cpu-64-bit: 4 vCPU
- :fontawesome-solid-memory: 4 GB of RAM
- :material-harddisk: Please use an SSD disk to avoid disk I/O and latency issues

## Software and configuration

- :simple-docker: Docker (service must be up and running)
- :material-certificate: If you want to access the web interface through a secure connection (``https://``), you will need a reverse proxy (nginx for example), a fully qualified domain name (FQDN) and a valid SSL certificate for this FQDN
- :material-email-fast-outline: At least an SPF record configured for your FQDN, to be able to send emails from Repomanager
