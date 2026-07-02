<?php
http_response_code(500);
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error | Flexi Feet</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="error-page">
        <img src="assets/images/flexi-feet-logo.png" alt="Flexi Feet">
        <p class="error-code-label">500</p>
        <h1>Something went wrong</h1>
        <p>Please try again shortly or contact Flexi Feet if the issue continues.</p>
        <a href="./" class="cta-button">Return Home</a>
    </main>
</body>
</html>
