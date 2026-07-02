<?php
http_response_code(404);
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found | Flexi Feet</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="error-page">
        <img src="assets/images/flexi-feet-logo.png" alt="Flexi Feet">
        <p class="error-code-label">404</p>
        <h1>Page not found</h1>
        <p>The page you are looking for may have moved or no longer exists.</p>
        <a href="./" class="cta-button">Return Home</a>
    </main>
</body>
</html>
