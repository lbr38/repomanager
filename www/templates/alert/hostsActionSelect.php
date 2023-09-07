<?php
if (!IS_ADMIN) {
    throw new \Exception('You are not allowed to do this action');
} ?>

<div id="newConfirmAlert" class="alert">
    <div class="confirmAlert-buttons-container">
        <span class="pointer btn-doGeneric hostsActionBtn" action="general-status-update" title="Retrieve general informations (OS and state informations)">Retrieve general informations</span>
        <span class="pointer btn-doGeneric hostsActionBtn" action="packages-status-update" title="Retrieve packages informations (available, installed, updated...)">Retrieve packages informations</span>
        <span class="pointer btn-doGeneric hostsActionBtn" action="update" title="Update all available packages">Update packages</span>
        <span class="pointer btn-doConfirm hostsActionBtn" action="reset" title="Reset known data">Reset</span>
        <span class="pointer btn-doConfirm hostsActionBtn" action="delete" title="Delete">Delete</span>
    </div>
</div>
