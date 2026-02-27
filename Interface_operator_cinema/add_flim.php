<?php

use Exception;

/**
 * Add film form handler
 * Follows PSR-1 and PSR-12 standards
 */

$pdo = connectToDB(DEFAULT_DB);

if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error</div>';
    return;
}

$success = false;
$error = '';
$formData = [
    'title' => '',
    'description' => '',
    'duration' => '',
    'poster' => '',
    'release_date' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['title'] = trim($_POST['title'] ?? '');
    $formData['description'] = trim($_POST['description'] ?? '');
    $formData['duration'] = (int)($_POST['duration'] ?? 0);
    $formData['poster'] = trim($_POST['poster'] ?? '');
    $formData['release_date'] = $_POST['release_date'] ?? null;

    if (empty($formData['title'])) {
        $error = 'Film title is required';
    } elseif ($formData['duration'] <= 0) {
        $error = 'Duration must be greater than 0';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO films (title, description, duration, poster, release_date) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $formData['title'],
                $formData['description'],
                $formData['duration'],
                $formData['poster'],
                $formData['release_date']
            ]);
            $success = true;
            
            // Clear form data on success
            $formData = array_fill_keys(array_keys($formData), '');
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            error_log("Add film error: " . $e->getMessage());
        }
    }
}
?>
<div class="card fade-in">
    <h3><i class="fas fa-plus-circle"></i> Add New Film</h3>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Film successfully added! 
            <a href="?table=films">Return to list</a>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo escapeOutput($error); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="form">
        <div class="form-group">
            <label for="title">
                <i class="fas fa-heading"></i> Film Title *
            </label>
            <input 
                type="text" 
                name="title" 
                id="title" 
                class="form-control" 
                required 
                value="<?php echo escapeOutput($formData['title']); ?>"
                placeholder="Enter film title"
            >
        </div>

        <div class="form-group">
            <label for="description">
                <i class="fas fa-align-left"></i> Description
            </label>
            <textarea 
                name="description" 
                id="description" 
                class="form-control" 
                rows="5"
                placeholder="Enter film description"
            ><?php echo escapeOutput($formData['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="duration">
                <i class="fas fa-clock"></i> Duration (minutes) *
            </label>
            <input 
                type="number" 
                name="duration" 
                id="duration" 
                class="form-control" 
                required 
                min="1"
                value="<?php echo escapeOutput($formData['duration']); ?>"
                placeholder="e.g., 120"
            >
        </div>

        <div class="form-group">
            <label for="poster">
                <i class="fas fa-image"></i> Poster URL
            </label>
            <input 
                type="url" 
                name="poster" 
                id="poster" 
                class="form-control" 
                value="<?php echo escapeOutput($formData['poster']); ?>"
                placeholder="https://example.com/poster.jpg"
            >
        </div>

        <div class="form-group">
            <label for="release_date">
                <i class="fas fa-calendar"></i> Release Date
            </label>
            <input 
                type="date" 
                name="release_date" 
                id="release_date" 
                class="form-control"
                value="<?php echo escapeOutput($formData['release_date']); ?>"
            >
        </div>

        <div class="d-flex">
            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Save Film
            </button>
            <a href="?table=films" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>