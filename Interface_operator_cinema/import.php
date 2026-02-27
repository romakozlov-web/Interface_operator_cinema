<?php
/**
 * Import SQL file into database.
 */
require_once 'config.php';
require_once 'functions.php';

use Cinema\Database;
use Cinema\Importer;
use Cinema\Formatter;

$pdo = Database::getConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Cannot connect to database.</div>';
    return;
}

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sqlfile'])) {
    $file = $_FILES['sqlfile'];
    $maxSize = 10 * 1024 * 1024; // 10 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error: ' . $file['error'];
    } elseif ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds limit of 10 MB.';
    } else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'sql') {
            $errors[] = 'Only .sql files are allowed.';
        } else {
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                $errors[] = 'Cannot read uploaded file.';
            } else {
                $importer = new Importer($pdo);
                if ($importer->import($content)) {
                    $message = 'SQL file imported successfully.';
                } else {
                    $errors = $importer->getErrors();
                }
            }
        }
    }
}
?>

<div class="fade-in">
    <div class="card">
        <h3><i class="fas fa-database"></i> Import SQL File</h3>
        <p>Upload a <code>.sql</code> file to execute on the current database. Be careful: this can modify or delete data.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo Formatter::escape($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo Formatter::escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="form">
            <div class="form-group">
                <label for="sqlfile"><i class="fas fa-file-upload"></i> Choose SQL file:</label>
                <input type="file" name="sqlfile" id="sqlfile" accept=".sql" required class="form-control">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to execute this SQL file? It may alter the database.');">
                    <i class="fas fa-play"></i> Execute SQL
                </button>
                <a href="?action=dashboard" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h4>Instructions</h4>
        <ul>
            <li>Upload a plain text file with .sql extension.</li>
            <li>File should contain valid SQL statements separated by semicolons.</li>
            <li>All queries are executed within a transaction if possible; if any query fails, the whole transaction is rolled back.</li>
            <li>Comments (lines starting with -- or #) are ignored.</li>
            <li>Maximum file size: 10 MB.</li>
        </ul>
    </div>
</div>