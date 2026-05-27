<?php
// Print groups and repos
if (empty($groups)) : ?>
    <div class="empty-state">
        <p class="empty-state-title">Nothing to see here!</p>
        <p class="note">There is no repository to display.</p>
    </div>
    <?php
else :
    foreach ($groups as $groupId => $group) {
        include(ROOT . '/views/includes/containers/repos/group.inc.php');
    }
endif; ?>

<script>
$(document).ready(function() {
    myrepo.getLatestTaskStatus();
});
</script>