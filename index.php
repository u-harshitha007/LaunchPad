<?php
/**
 * LaunchPad — Entry point
 * Redirects to dashboard or login based on session
 */

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
