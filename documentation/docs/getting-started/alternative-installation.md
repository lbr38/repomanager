## :simple-ansible: With Ansible

You can find an Ansible role to install and update Repomanager [here](https://github.com/lbr38/repomanager/tree/devel/ansible)

This role pulls the latest image and creates a reverse proxy vhost for nginx.

Replace the variables in ``roles/repomanager/vars/repomanager.yml``, add the role inside your ansible playbook and run it!

/!\ The role does not install the basic requirements (docker and nginx). You will have to install them before running the role.

## :simple-kubernetes: With Kubernetes

Some users managed to install Repomanager inside a Kubernetes cluster but this is not officially documented yet.