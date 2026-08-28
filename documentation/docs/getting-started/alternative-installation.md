## :simple-ansible: With Ansible

You can find an Ansible role to install and update Repomanager [here](https://github.com/lbr38/repomanager/tree/devel/ansible)

This role pulls the latest image and creates a reverse proxy vhost for Nginx.

Replace the variables in ``roles/repomanager/vars/repomanager.yml``, add the role to your Ansible playbook and run it!

!!! warning "Requirements"
    The role does not install the basic requirements (Docker and Nginx). You will need to install them before running the role.

## :simple-kubernetes: With Kubernetes

Some users managed to install Repomanager inside a Kubernetes cluster but this is not officially documented yet.

## :simple-docker: Local development with Docker Compose

For local development, you can build and run Repomanager with the provided Docker Compose file:

```bash
PUID=$(id -u) PGID=$(id -g) docker compose -f docker-compose.local.yml up --build
```

The local Compose file:

- binds the web interface to `http://localhost:8888`
- bind-mounts `./www` into the container so source changes are visible immediately
- stores application data and repositories in named Docker volumes
- passes your local user and group IDs to avoid root-owned files in the working tree

Default login:

```text
admin / repomanager
```

Stop the local instance with:

```bash
docker compose -f docker-compose.local.yml down
```

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
