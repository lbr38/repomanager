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
                    echo '<pre class="codeblock">';
                    echo $errorMessage;
                    echo '</pre>';
                } ?>
            </div>
        </div>
    </body>
</html>