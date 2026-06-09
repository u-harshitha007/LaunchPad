<?php
/**
 * Authentication helpers — sessions, login checks, logout
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Redirect to login if user is not authenticated
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Check if a user session exists
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user's ID
 */
function getUserId(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

/**
 * Fetch current user row from database
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    $db = getDB();
    $stmt = $db->prepare('SELECT id, full_name, email, about, college, degree, graduation_year, profile_photo, created_at FROM users WHERE id = ?');
    $userId = getUserId();
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

/**
 * Attempt login with email and password
 */
function attemptLogin(string $email, string $password): bool
{
    $db = getDB();
    $stmt = $db->prepare('SELECT id, password FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = '';
        return true;
    }

    return false;
}

/**
 * Register a new user with hashed password
 */
function registerUser(string $fullName, string $email, string $password): array
{
    $db = getDB();

    // Check if email already exists
    $check = $db->prepare('SELECT id FROM users WHERE email = ?');
    $check->bind_param('s', $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        return ['success' => false, 'message' => 'An account with this email already exists.'];
    }
    $check->close();

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $fullName, $email, $hashed);

    if ($stmt->execute()) {
        $_SESSION['user_id'] = (int) $stmt->insert_id;
        $stmt->close();
        return ['success' => true, 'message' => 'Account created successfully.'];
    }

    $error = $stmt->error;
    $stmt->close();
    return ['success' => false, 'message' => 'Registration failed: ' . $error];
}

/**
 * Destroy session and log user out
 */
function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}
