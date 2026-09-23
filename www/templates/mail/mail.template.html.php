<?php
$escapedSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$escapedLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
$escapedLinkName = htmlspecialchars($linkName, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en" dir="ltr" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
  <meta name="color-scheme" content="light dark">
  <meta name="supported-color-schemes" content="light dark">
  <!--[if !mso]><!-->
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <!--<![endif]-->
  <title><?= $escapedSubject ?></title>
  <!--[if mso]>
  <noscript>
    <xml>
      <o:OfficeDocumentSettings>
        <o:AllowPNG/>
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
  </noscript>
  <style type="text/css">
    body, table, td, p, a, span { font-family: Arial, Helvetica, sans-serif !important; }
  </style>
  <![endif]-->
  <!--[if !mso]><!-->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" type="text/css">
  <!--<![endif]-->
  <style type="text/css">
    :root { color-scheme: light dark; supported-color-schemes: light dark; }
    html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    body { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
    #outlook a { padding: 0; }
    a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; font-size: inherit !important; font-family: inherit !important; font-weight: inherit !important; line-height: inherit !important; }
    u + #body a { color: inherit; text-decoration: none; font-size: inherit; font-family: inherit; font-weight: inherit; line-height: inherit; }
    @media only screen and (max-width: 620px) {
      .outer-padding { padding-left: 10px !important; padding-right: 10px !important; }
      .card-padding { padding: 24px 20px !important; }
    }
  </style>
</head>

<body id="body" style="margin:0;padding:0;word-spacing:normal;background-color:#0e1e30;">
  <div role="article" aria-roledescription="email" aria-label="<?= $escapedSubject ?>" lang="en" style="background-color:#0e1e30;">
    <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#0e1e30" style="background-color:#0e1e30;">
      <tr>
        <td align="center" class="outer-padding" style="padding:32px 16px;">
          <!--[if mso]><table role="presentation" align="center" width="600" border="0" cellpadding="0" cellspacing="0"><tr><td><![endif]-->
          <div style="max-width:600px;margin:0 auto;">
            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width:600px;border-collapse:separate;">
              <!-- Logo -->
              <tr>
                <td align="center" style="padding:0 0 24px 0;">
                  <img src="<?= PROJECT_LOGO ?>" width="48" height="48" alt="<?= PROJECT_NAME ?>" style="display:block;margin:0 auto;width:48px;height:48px;border:0;font-family:<?= $font ?>;font-size:14px;font-weight:bold;color:#8A99AA;">
                </td>
              </tr>

              <!-- Card -->
              <tr>
                <td class="card-padding" bgcolor="#182b3e" style="padding:32px;background-color:#182b3e;border:1px solid #1a4a6a;border-radius:20px;font-family:<?= $font ?>;font-size:14px;line-height:22px;color:#ffffff;text-align:left;">
                  <?= $content ?>

                  <?php if (!empty($link)) : ?>
                  <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                      <td align="center" style="padding:28px 0 0 0;">
                        <!--[if mso]>
                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="<?= $escapedLink ?>" style="height:42px;v-text-anchor:middle;width:200px;" arcsize="50%" strokecolor="#15bf7f" strokeweight="1.5px" fillcolor="#184148">
                          <w:anchorlock/>
                          <center style="color:#15bf7f;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;"><?= $escapedLinkName ?></center>
                        </v:roundrect>
                        <![endif]-->
                        <!--[if !mso]><!-->
                        <a href="<?= $escapedLink ?>" target="_blank" style="display:inline-block;padding:11px 28px;border:1.5px solid #15bf7f;border-radius:60px;background-color:#184148;font-family:<?= $font ?>;font-size:14px;font-weight:bold;line-height:18px;color:#15bf7f;text-decoration:none;"><?= $escapedLinkName ?></a>
                        <!--<![endif]-->
                      </td>
                    </tr>
                  </table>
                  <?php endif; ?>
                </td>
              </tr>

              <!-- Footer -->
              <tr>
                <td align="center" style="padding:24px 16px 0 16px;font-family:<?= $font ?>;font-size:12px;line-height:18px;color:#8A99AA;">
                  Automated message sent by <?= PROJECT_NAME ?> from <?= htmlspecialchars(WWW_HOSTNAME, ENT_QUOTES, 'UTF-8') ?>, please do not reply.<br>
                  <a href="<?= PROJECT_GIT_REPO ?>" target="_blank" style="font-family:<?= $font ?>;font-size:12px;color:#8A99AA;text-decoration:underline;">GitHub</a>
                </td>
              </tr>
            </table>
          </div>
          <!--[if mso]></td></tr></table><![endif]-->
        </td>
      </tr>
    </table>
  </div>
</body>

</html>