## Alternative installation methods

### With Ansible <img src="https://github.com/user-attachments/assets/81ab3096-d13c-47a6-92c5-aa4a389b65b4" width="18" />

You can find an Ansible role to install and update Repomanager [here](https://github.com/lbr38/repomanager/tree/devel/ansible)

This role pulls the latest image and creates a reverse proxy vhost for nginx.

Replace the variables in ``roles/repomanager/vars/repomanager.yml``, add the role inside your ansible playbook and run it!

/!\ The role does not install the basic requirements (docker and nginx). You will have to install them before running the role.

### With Kubernetes <img src="https://github.com/user-attachments/assets/4e4c55cd-0018-4408-ac07-6b0840974e54" width="18" />

Some users managed to install Repomanager inside a Kubernetes cluster but this is not officially documented yet.