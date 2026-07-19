<?php
/**
 * Safe File Upload Handling Utilities
 * College Study Material & Practice Portal
 */

// Max file size configuration (50 MB max for videos)
define('MAX_UPLOAD_SIZE', 52428800); 

// Allowed mime types mapping
const ALLOWED_MIME_TYPES = [
    'pdf'   => ['application/pdf'],
    'doc'   => [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ],
    'ppt'   => [
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation'
    ],
    'zip'   => [
        'application/zip',
        'application/x-zip-compressed',
        'application/x-compressed',
        'multipart/x-zip'
    ],
    'image' => [
        'image/jpeg',
        'image/png',
        'image/gif'
    ],
    'video' => [
        'video/mp4',
        'video/webm',
        'video/ogg'
    ]
];

// Allowed extensions
const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm'];

/**
 * Validates and uploads a file to a specific category directory
 * 
 * @param array $file The $_FILES['name'] entry
 * @param string $category Folder category (notes, videos, labs, profile_pics)
 * @return array Array with status (success: true/false), file_name/error, and file_type
 */
function upload_file($file, $category) {
    // 1. Check for system upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload failed with error code ' . $file['error']];
    }
    
    // 2. Validate size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'File exceeds maximum upload size (50MB).'];
    }
    
    // 3. Detect safe extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'Extension .' . $ext . ' is not permitted.'];
    }
    
    // 4. Validate MIME Type using PHP Fileinfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // Map extension to general file category
    $generalType = '';
    foreach (ALLOWED_MIME_TYPES as $typeKey => $mimeTypes) {
        if (in_array($mime, $mimeTypes)) {
            $generalType = $typeKey;
            break;
        }
    }
    
    // Fallback/extra check for extensions if mime type detection is slightly off (local server issues)
    if (empty($generalType)) {
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $generalType = 'image';
        elseif (in_array($ext, ['pdf'])) $generalType = 'pdf';
        elseif (in_array($ext, ['doc', 'docx'])) $generalType = 'doc';
        elseif (in_array($ext, ['ppt', 'pptx'])) $generalType = 'ppt';
        elseif (in_array($ext, ['zip'])) $generalType = 'zip';
        elseif (in_array($ext, ['mp4', 'webm'])) $generalType = 'video';
    }
    
    if (empty($generalType)) {
        return ['success' => false, 'error' => 'Security check: Invalid file contents detected (MIME mismatch).'];
    }
    
    // 5. Ensure target directory exists and is protected
    $targetDir = UPLOAD_DIR . $category . '/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Create htaccess in upload folders to prevent executing PHP scripts
    $htaccessFile = UPLOAD_DIR . '.htaccess';
    if (!file_exists($htaccessFile)) {
        file_put_contents($htaccessFile, "Options -ExecCGI -Indexes\nRemoveHandler .php .phtml .php3 .php4 .php5 .php6 .php7 .php8\nRemoveType .php .phtml .php3 .php4 .php5 .php6 .php7 .php8\n<Files *>\n  SetHandler default-handler\n</Files>");
    }
    
    // 6. Generate a random unique file name to prevent collision and override
    $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetFilePath = $targetDir . $randomName;
    
    // 7. Move file to target path
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        return [
            'success' => true,
            'file_name' => $randomName,
            'file_type' => $generalType,
            'file_size' => $file['size'],
            'original_name' => basename($file['name'])
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to write file to storage.'];
    }
}
