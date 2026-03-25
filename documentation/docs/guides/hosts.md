**HOSTS** tab provides an easy way to monitor hosts packages and update status. Hosts that are registered to **Repomanager** regulary send their system and packages informations to the server.

   Importing client hosts into **Repomanager** is possible by using a client side tool like `linupdate <https://github.com/lbr38/linupdate>`_

[![Hosts](https://github.com/user-attachments/assets/b99d6a21-06ab-4079-8fe0-7be3842651d7)](https://github.com/user-attachments/assets/b99d6a21-06ab-4079-8fe0-7be3842651d7)

[![Hosts](https://github.com/user-attachments/assets/27b7b7fa-a5b3-4fc5-9954-9b28abcb09a8)](https://github.com/user-attachments/assets/27b7b7fa-a5b3-4fc5-9954-9b28abcb09a8)


### Register a new host

The host must execute linupdate command line to register to Repomanager. Check linupdate documentation:

- Install and start using linupdate `here <https://github.com/lbr38/linupdate/wiki/Documentation>`_
- Enable reposerver module and register a host using linupdate, see **Quick setup example** `here <https://github.com/lbr38/linupdate/wiki/Module:-reposerver#quick-setup-example>`_


### Reset a host

Reseting a host deletes all known data about it.

From the **HOSTS** tab:

1. Select the host(s) you want to reset.
2. Use the **Reset** button.

[![Reset a host](https://github.com/user-attachments/assets/7d219d8c-f17c-4147-881e-ee3b90fed3fb)](https://github.com/user-attachments/assets/7d219d8c-f17c-4147-881e-ee3b90fed3fb)


### Delete (unregister) a host

From the **HOSTS** tab:

1. Select the host(s) you want to delete.
2. Use the **Delete** button.

[![Delete a host](https://github.com/user-attachments/assets/28197c81-558b-426d-b0d4-a7d64c1e193e)](https://github.com/user-attachments/assets/28197c81-558b-426d-b0d4-a7d64c1e193e)


### Request a host to send general informations

The general informations include the host name, OS version, kernel version, architecture, the profile and environment. Each host send this information to Repomanager every 1 hour but you can force it to be sent immediately.

From the **HOSTS** tab:

1. Select the host(s) you want to request.
2. Use the **Request general informations** button.

[![Request general informations](https://github.com/user-attachments/assets/24815e66-2d7c-4772-8f44-8ab88029d9f4)](https://github.com/user-attachments/assets/24815e66-2d7c-4772-8f44-8ab88029d9f4)


### Request a host to send packages informations

The packages informations include the list of installed packages, their version and their history (installed, updated, removed). Each host send this information to Repomanager every 1 hour but you can force it to be sent immediately.

From the **HOSTS** tab:

1. Select the host(s) you want to update.
2. Use the **Request packages informations** button. It can take some minutes to be sent depending on the number of packages installed on the host.

[![Request packages informations](https://github.com/user-attachments/assets/741829cf-e923-4817-a93c-bb938671c707)](https://github.com/user-attachments/assets/741829cf-e923-4817-a93c-bb938671c707)


### Request a host to execute packages update

From the **HOSTS** tab:

1. Select the host(s) you want to update.
2. Use the **Update packages** button.
3. Select either **All packages** or **Specific packages** to specify which packages you want to update.

[![Update packages](https://github.com/user-attachments/assets/8b3e4841-c2ce-420f-a7bc-7eaa5521f53a)](https://github.com/user-attachments/assets/8b3e4841-c2ce-420f-a7bc-7eaa5521f53a)

Note: you can also select specific packages to be updated by going to the host details page and selecting the packages you want to update:

[![Specific packages](https://github.com/user-attachments/assets/45da12e7-51bd-4f89-b0d4-fe71d502603b)](https://github.com/user-attachments/assets/45da12e7-51bd-4f89-b0d4-fe71d502603b)

Note: You can have a look at the running requests and their status in the host details page:

[![Running requests](https://github.com/user-attachments/assets/00a87a9b-1745-4ad7-8b5c-6a6f394fb540)](https://github.com/user-attachments/assets/00a87a9b-1745-4ad7-8b5c-6a6f394fb540)


## Profiles

The **Manage profiles** panel provides a way to create and manage configuration profiles for client hosts, including what repositories they should have access to and what packages they should exclude from their packages updates (for example critical packages).

Deploying profile configuration on client hosts is possible by using a client side tool like `linupdate <https://github.com/lbr38/linupdate>`_


### Create a new profile

From the **HOSTS** tab:

1. Click the **Manage profiles** button to open the **MANAGE PROFILES** panel.
2. Specify the name of the profile you want to create and create it.
3. Once the profile has been created, you can edit its configuration and define what repositories this profile will have access to and what packages must be excluded from updates.
4. Save.
5. Any client host can now use this profile and retrieve its configuration from it (see `Set profile with linupdate <https://github.com/lbr38/linupdate/wiki/Documentation#set-profile>`_)

[![Create a new profile](https://github.com/user-attachments/assets/c4c97f42-22e2-4db0-90ff-1147ae49967c)](https://github.com/user-attachments/assets/c4c97f42-22e2-4db0-90ff-1147ae49967c)


### Delete a profile

From the **HOSTS** tab:

1. Click the **Manage profiles** button to open the **MANAGE PROFILES** panel.
2. Use the Delete button to delete a profile. Client hosts that were using this profile will no longer be able to retrieve any configuration from it.

[![Delete a profile](https://github.com/user-attachments/assets/dc0d27b4-11be-45e2-8556-89128bc355d8)](https://github.com/user-attachments/assets/dc0d27b4-11be-45e2-8556-89128bc355d8)
