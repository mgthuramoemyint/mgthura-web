<?php
// Set the current directory
$dir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$dir = rtrim($dir, '/'); // Remove trailing slash if present

// Function to get the size of a directory
function getDirectorySize($path) {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
        $size += $file->getSize();
    }
    return $size;
}

// Function to format file sizes
function formatSize($bytes) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = floor(log($bytes, 1024));
    return sprintf('%.2f', $bytes / pow(1024, $i)) . ' ' . $units[$i];
}

// Handle actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
if ($action === 'zip') {
    $files = isset($_GET['files']) ? $_GET['files'] : [];
    $zip = new ZipArchive();
    $zipName = 'files.zip';
    if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
        foreach ($files as $file) {
            if (is_dir($file)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file));
                foreach ($iterator as $subFile) {
                    // Add files to the zip, preserving directory structure
                    if (!$iterator->isDot()) {
                        $zip->addFile($subFile, $subFile->getPathname());
                    }
                }
            } else {
                $zip->addFile($file);
            }
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=' . $zipName);
        header('Content-Length: ' . filesize($zipName));
        readfile($zipName);
        unlink($zipName); // Delete zip file after download
        exit;
    } else {
        echo "Failed to create zip file";
    }
} elseif ($action === 'copy' || $action === 'move') {
    $source = isset($_GET['source']) ? $_GET['source'] : '';
    $destination = isset($_GET['destination']) ? $_GET['destination'] : '';
    if ($action === 'copy') {
        if (is_dir($source)) {
            exec("cp -r '$source' '$destination'");
        } else {
            copy($source, $destination);
        }
    } elseif ($action === 'move') {
        if (is_dir($source)) {
            exec("mv '$source' '$destination'");
        } else {
            rename($source, $destination);
        }
    }
    header("Location: $_SERVER[PHP_SELF]?dir=$dir");
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP File Manager</title>
    <style>
        body { font-family: Arial, sans-serif; }
        ul { list-style-type: none; padding-left: 0; }
        li { margin-bottom: 5px; }
        .directory { color: blue; }
        .file { color: green; }
    </style>
</head>
<body>

<h2>PHP File Manager</h2>
<h3>Current Directory: <?php echo $dir; ?></h3>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET">
    <input type="hidden" name="dir" value="<?php echo $dir; ?>">
    <input type="hidden" name="action" value="zip">
    <input type="submit" value="Download Selected Files/Folders as ZIP">
</form>

<?php
// List directories and files
echo '<ul>';
echo '<li><a href="?dir='.dirname($dir).'">[Parent Directory]</a></li>';
foreach (glob("$dir/*") as $item) {
    $name = basename($item);
    if (is_dir($item)) {
        echo '<li class="directory"><a href="?dir='.$item.'">'.$name.'</a> [<a href="?action=zip&files[]='.urlencode($item).'">Zip</a>]</li>';
    } else {
        echo '<li class="file">'.$name.' ('.formatSize(filesize($item)).')';
        echo ' [<a href="?action=copy&source='.urlencode($item).'&destination='.urlencode($dir.'/'.$name).'">Copy</a>]';
        echo ' [<a href="?action=move&source='.urlencode($item).'&destination='.urlencode($dir.'/'.$name).'">Move</a>]</li>';
    }
}
echo '</ul>';
?>

</body>
</html>
