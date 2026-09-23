<?php
$statuses = [
    'success' => ['Success', '#15bf7f', 'The scheduled task has completed successfully.'],
    'error'   => ['Error', '#F32F63', 'The scheduled task has completed with errors. Check the task log for more details.']
];

[$statusLabel, $statusColor, $statusIntro] = $statuses[$status];

if (!empty($summary['total'])) {
    if ($status == 'error') {
        $statusIntro = $summary['failed'] . ' of ' . $summary['total'] . ' sub-task' . ($summary['total'] > 1 ? 's' : '') . ' failed. Check the task log for more details.';
    } else {
        $statusIntro = ($summary['total'] > 1 ? 'All ' . $summary['total'] . ' sub-tasks have' : 'The sub-task has') . ' completed successfully.';
    }

    $rows['Sub-tasks'] = '<span style="color:' . ($summary['success'] > 0 ? '#15bf7f' : '#8A99AA') . ';">' . $summary['success'] . ' succeeded</span>'
                       . ' &middot; <span style="color:' . ($summary['failed'] > 0 ? '#F32F63' : '#8A99AA') . ';">' . $summary['failed'] . ' failed</span>'
                       . ' &middot; ' . $summary['total'] . ' total';
}

if (!empty($duration)) {
    $rows['Duration'] = htmlspecialchars($duration, ENT_QUOTES, 'UTF-8');
}
?>
<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td align="left" valign="middle" style="padding:0;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding:0 0 6px 0;border-bottom:2px solid #15bf7f;font-family:<?= $font ?>;font-size:18px;line-height:24px;font-weight:bold;letter-spacing:0.5px;text-transform:uppercase;color:#ffffff;">Task #<?= $taskId ?></td>
                </tr>
            </table>
        </td>
        <td align="right" valign="middle" style="padding:0 0 0 12px;">
            <span style="display:inline-block;padding:4px 12px;border:1.5px solid <?= $statusColor ?>;border-radius:20px;font-family:<?= $font ?>;font-size:12px;line-height:16px;font-weight:bold;color:<?= $statusColor ?>;white-space:nowrap;"><?= $statusLabel ?></span>
        </td>
    </tr>
</table>

<p style="margin:20px 0 24px 0;font-family:<?= $font ?>;font-size:14px;line-height:22px;color:#c0d0e2;"><?= $statusIntro ?></p>

<?= \Controllers\Mail::render('details', ['rows' => $rows]) ?>
