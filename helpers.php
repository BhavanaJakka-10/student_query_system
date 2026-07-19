<?php
/**
 * Global Helper Functions
 * College Study Material & Practice Portal
 */

/**
 * Escapes HTML output for XSS protection
 */
function esc($value) {
    if ($value === null) return '';
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Clean & sanitize user input fields
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return trim(htmlspecialchars((string)$data, ENT_NOQUOTES, 'UTF-8'));
}

/**
 * Generates HTML input field for CSRF verification
 */
function csrf_token_field() {
    $token = $_SESSION['csrf_token'] ?? '';
    return '<input type="hidden" name="csrf_token" value="' . esc($token) . '">';
}

/**
 * Verifies that the submitted CSRF token matches the session token
 */
function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die("Error: Invalid or missing CSRF token validation.");
        }
    }
}

/**
 * Safely redirects the browser to a given URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Sets a flash message to be shown on the next page request
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'info', 'warning'
        'message' => $message
    ];
}

/**
 * Renders any active flash message and clears it from the session
 */
function display_flash_messages() {
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);
        
        $class = 'alert-' . $type;
        if ($type === 'danger') $class = 'alert-danger';
        if ($type === 'success') $class = 'alert-success';
        if ($type === 'warning') $class = 'alert-warning';
        if ($type === 'info') $class = 'alert-info';

        return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">
                    ' . esc($message) . '
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                </div>';
    }
    return '';
}

/**
 * Formats file sizes in a readable format (B, KB, MB, GB)
 */
function format_size($bytes) {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

/**
 * Returns user profile picture URL or a fallback default SVG
 */
function get_avatar_url($profile_pic) {
    if (!empty($profile_pic) && file_exists(dirname(__DIR__) . '/uploads/profile_pics/' . $profile_pic)) {
        return BASE_URL . 'uploads/profile_pics/' . esc($profile_pic);
    }
    // Return standard fallback SVG placeholder
    return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%230d6efd"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>';
}

/**
 * Returns a human-friendly file extension icon or badge
 */
function get_file_icon($type) {
    switch ($type) {
        case 'pdf':
            return '📄 PDF';
        case 'ppt':
            return '📊 PPT';
        case 'doc':
            return '📝 WORD';
        case 'zip':
            return '📦 ZIP';
        case 'image':
            return '🖼️ IMAGE';
        case 'video':
            return '🎥 VIDEO';
        default:
            return '📁 FILE';
    }
}
