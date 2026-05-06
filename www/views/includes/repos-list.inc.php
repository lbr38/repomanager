<?php
// Print groups and repos
if (!empty($groups)) {
    foreach ($groups as $groupId => $group) {
        if (!$group['show']) {
            continue;
        }

        include(ROOT . '/views/includes/containers/repos/includes-temp/group.inc.php');
    }
} ?>

<script>
$(document).ready(function() {
    myrepo.getSize();
    myrepo.getLatestTaskStatus();
});
</script>