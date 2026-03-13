## :simple-ansible: With Ansible

You can find an Ansible role to install and update Repomanager [here](https://github.com/lbr38/repomanager/tree/devel/ansible)

This role pulls the latest image and creates a reverse proxy vhost for Nginx.

Replace the variables in ``roles/repomanager/vars/repomanager.yml``, add the role to your Ansible playbook and run it!

!!! warning "Requirements"
    The role does not install the basic requirements (Docker and Nginx). You will need to install them before running the role.

## :simple-kubernetes: With Kubernetes

Some users managed to install Repomanager inside a Kubernetes cluster but this is not officially documented yet.
