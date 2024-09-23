<?php
if (!IS_ADMIN) {
    throw new \Exception('You are not allowed to execute actions on hosts');
} ?>

<div id="confirm-box" class="alert">
    <div class="confirmAlert-buttons-container">
        <span class="pointer btn-doGeneric hostsActionBtn" action="request-general-infos" title="Request the selected host(s) to send their general informations (OS, profile, agent status)">Request general informations</span>
        <span class="pointer btn-doGeneric hostsActionBtn" action="request-packages-infos" title="Request the selected host(s) to send their packages informations (available, installed, updated...)">Request packages informations</span>
        <span class="pointer btn-doGeneric hostsActionBtn" action="update-all-packages" title="Request the selected host(s) to update all packages">Update all packages</span>
        <span class="pointer btn-doConfirm hostsActionBtn" action="reset" title="Reset selected host(s) informations">Reset</span>
        <span class="pointer btn-doConfirm hostsActionBtn" action="delete" title="Delete selected host(s)">Delete</span>
    </div>
</div>
