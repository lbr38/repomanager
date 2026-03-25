## The concept of environment

Environments are an important part of Repomanager as they define how repositories snapshots are being accessed by client hosts.

An example of a good practice is to create at least 2 environments:

- ``preprod``
- ``prod``

Tipically:

- The ``preprod`` environment will point to the most recent repositories snapshots and ensure that pre-production client hosts can update their packages from these snapshots. As pre-production hosts, they are the first in line to test the packages updates and ensure they are not breaking anything before updating on production hosts.

- The ``prod`` environment will point to the last stable repositories snapshots and ensure that production client hosts can not update their packages without them having been tested first by pre-production hosts. Once the packages updates have been tested and validated, the ``prod`` environment can point to the same repositories snapshots than the ``preprod`` environment.

This is what the environments have been designed for. To test packages updates on testing hosts before updating them on production hosts.

// TO UPDATE
[![Environments](https://github.com/lbr38/repomanager/assets/54670129/64a648ac-e6b9-4075-867e-43595d8ffd16)](https://github.com/lbr38/repomanager/assets/54670129/64a648ac-e6b9-4075-867e-43595d8ffd16)


!!! info

    There is no limit in the number of environment you can define but you must define at least one.


## Create an environment

From the **SETTINGS** tab and the **REPOSITORIES** > **ENVIRONMENTS** section, create a new environment.

// TO UPDATE
[![Create an environment](https://github.com/lbr38/repomanager/assets/54670129/1f70ca87-ef0d-4c9d-a0d7-484cd3161417)](https://github.com/lbr38/repomanager/assets/54670129/1f70ca87-ef0d-4c9d-a0d7-484cd3161417)


## Delete an environment

From the **SETTINGS** tab and the **REPOSITORIES** > **ENVIRONMENTS** section, use the Delete icon to delete an environment.

// TO UPDATE
[![Delete an environment](https://github.com/lbr38/repomanager/assets/54670129/fdf69aa1-53c3-426a-813c-eff5b2340f49)](https://github.com/lbr38/repomanager/assets/54670129/fdf69aa1-53c3-426a-813c-eff5b2340f49)


!!! info

    Deleting an environment will not delete it from repositories snapshots it was pointing to (to avoid breaking client hosts access).
