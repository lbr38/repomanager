<?php
$labelStyle = 'padding:10px 12px 10px 0;font-family:' . $font . ';font-size:13px;line-height:20px;color:#8A99AA;';
$valueStyle = 'padding:10px 0;font-family:' . $font . ';font-size:14px;line-height:20px;font-weight:bold;color:#ffffff;word-break:break-word;';
$separator  = 'border-top:1px solid #24405c;';
$first      = true;
?>
<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#13263a" style="background-color:#13263a;border:1px solid #24405c;border-radius:14px;border-collapse:separate;">
    <tr>
        <td style="padding:6px 20px;">
            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                <?php foreach ($rows as $label => $value) : ?>
                    <tr>
                        <td width="38%" valign="middle" style="<?= $labelStyle . ($first ? '' : $separator) ?>"><?= $label ?></td>
                        <td valign="middle" style="<?= $valueStyle . ($first ? '' : $separator) ?>"><?= $value ?></td>
                    </tr>
                    <?php $first = false;
                endforeach ?>
            </table>
        </td>
    </tr>
</table>
