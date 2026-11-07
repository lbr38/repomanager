## Repository statistics

To enable repository **statistics and metrics**, see the `ENABLE REPOSITORIES STATISTICS` parameter in the [Statistics](/configuration/settings/#statistics) section from the **SETTINGS** tab.

From the **REPOSITORIES** tab:

**Step 1:** Locate the repository for which you want to visualize statistics.

**Step 2:** Click on the ``Release version`` or ``Distribution / Component`` (or on the name of a mirror) to access the statistics page: snapshots timeline and size, accesses over time by environment, access requests and top IP addresses.

[![Repository statistics](https://assets.repomanager.net/repomanager/6.0.0/usage/stats-monitoring/view-stats.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/stats-monitoring/view-stats.png)

For mirrors, the accesses of apt, dnf/yum and pacman clients to the environments of the mirror are counted. As mirrors may be public and heavily used, keep in mind that each access request is stored in the statistics database (requests older than one year are deleted).

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
