<?php
$count = count($tasks);
?>
<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td align="left" valign="middle" style="padding:0;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding:0 0 6px 0;border-bottom:2px solid #15bf7f;font-family:<?= $font ?>;font-size:18px;line-height:24px;font-weight:bold;letter-spacing:0.5px;text-transform:uppercase;color:#ffffff;">Upcoming tasks</td>
                </tr>
            </table>
        </td>
        <td align="right" valign="middle" style="padding:0 0 0 12px;">
            <span style="display:inline-block;padding:4px 12px;border:1.5px solid #ffb536;border-radius:20px;font-family:<?= $font ?>;font-size:12px;line-height:16px;font-weight:bold;color:#ffb536;white-space:nowrap;">Scheduled</span>
        </td>
    </tr>
</table>

<p style="margin:20px 0 8px 0;font-family:<?= $font ?>;font-size:14px;line-height:22px;color:#c0d0e2;">Reminder: <?= $count ?> scheduled task<?= $count > 1 ? 's are' : ' is' ?> about to run.</p>

<?php foreach ($tasks as $task) : ?>
    <p style="margin:24px 0 10px 0;font-family:<?= $font ?>;font-size:14px;line-height:20px;font-weight:bold;letter-spacing:0.5px;text-transform:uppercase;">
        <a href="<?= __SERVER_PROTOCOL__ . '://' . htmlspecialchars(WWW_HOSTNAME, ENT_QUOTES, 'UTF-8') . '/task/' . $task['taskId'] ?>" target="_blank" style="color:#15bf7f;text-decoration:none;">Task #<?= $task['taskId'] ?></a>
    </p>

    <?= \Controllers\Mail::render('details', ['rows' => $task['rows']]) ?>
<?php endforeach ?>
