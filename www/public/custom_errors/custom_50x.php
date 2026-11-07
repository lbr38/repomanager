<?php
use Controllers\App\DebugMode; ?>
<!DOCTYPE html>
<html>
    <head>
        <title>Error</title>
        <link rel="stylesheet" type="text/css" href="/resources/styles/common.css">
        <link rel="stylesheet" type="text/css" href="/resources/styles/custom_errors.css">
    </head>
    <body>
        <div id="error-container">
            <div id="error" class="flex flex-direction-column justify-center row-gap-15">
                <p>500: An error occurred.</p>

                <?php
                if (!empty($errorMessage)) {
                    // Always log the error details, they are only displayed when debug mode is enabled
                    error_log($errorMessage);

                    try {
                        $debugMode = defined('DATA_DIR') && DebugMode::enabled();
                    } catch (Throwable $e) {
                        $debugMode = false;
                    }

                    if ($debugMode) {
                        echo '<pre class="codeblock">';
                        echo htmlspecialchars($errorMessage);
                        echo '</pre>';
                    }
                } ?>
            </div>
        </div>
    </body>
</html>