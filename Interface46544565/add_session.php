<?php

use Exception;

/**
 * Add session form handler
 * Follows PSR-1 and PSR-12 standards
 */

$pdo = connectToDB(DEFAULT_DB);

if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error</div>';
    return;
}

// Get films and halls for dropdowns
$films = $pdo->query("SELECT id, title FROM films ORDER BY title")->fetchAll();
$halls = $pdo->query("SELECT id, name FROM halls ORDER BY name")->fetchAll();

// Detect date column name
$dateColumn = 'start_time';
try {
    $stmt = $pdo->query("DESCRIBE sessions");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        if (in_array($column['Field'], ['date', 'start_time', 'session_date'])) {
            $dateColumn = $column['Field'];
            break;
        }
    }
} catch (Exception $e) {
    error_log("Error detecting date column: " . $e->getMessage());
}

$success = false;
$error = '';
$formData = [
    'film_id' => '',
    'hall_id' => '',
    'date' => '',
    'price' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['film_id'] = (int)($_POST['film_id'] ?? 0);
    $formData['hall_id'] = (int)($_POST['hall_id'] ?? 0);
    $formData['date'] = $_POST['date'] ?? '';
    $formData['price'] = (float)($_POST['price'] ?? 0);

    if ($formData['film_id'] <= 0) {
        $error = 'Please select a film';
    } elseif ($formData['hall_id'] <= 0) {
        $error = 'Please select a hall';
    } elseif (empty($formData['date'])) {
        $error = 'Please select date and time';
    } elseif ($formData['price'] <= 0) {
        $error = 'Price must be greater than 0';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO sessions (film_id, hall_id, $dateColumn, price) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $formData['film_id'],
                $formData['hall_id'],
                $formData['date'],
                $formData['price']
            ]);
            $success = true;
            
            // Clear form data on success
            $formData = array_fill_keys(array_keys($formData), '');
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            error_log("Add session error: " . $e->getMessage());
        }
    }
}
?>
<div class="card fade-in">
    <h3><i class="fas fa-plus-circle"></i> Add New Session</h3>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Session successfully added! 
            <a href="?table=sessions">Return to list</a>
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
            <label for="film_id">
                <i class="fas fa-film"></i> Film *
            </label>
            <select name="film_id" id="film_id" class="form-control" required>
                <option value="">-- Select Film --</option>
                <?php foreach ($films as $film): ?>
                    <option value="<?php echo $film['id']; ?>" 
                        <?php echo $formData['film_id'] == $film['id'] ? 'selected' : ''; ?>>
                        <?php echo escapeOutput($film['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="hall_id">
                <i class="fas fa-door-open"></i> Hall *
            </label>
            <select name="hall_id" id="hall_id" class="form-control" required>
                <option value="">-- Select Hall --</option>
                <?php foreach ($halls as $hall): ?>
                    <option value="<?php echo $hall['id']; ?>"
                        <?php echo $formData['hall_id'] == $hall['id'] ? 'selected' : ''; ?>>
                        <?php echo escapeOutput($hall['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="date">
                <i class="fas fa-calendar-alt"></i> Date and Time *
            </label>
            <input 
                type="datetime-local" 
                name="date" 
                id="date" 
                class="form-control" 
                required
                value="<?php echo escapeOutput($formData['date']); ?>"
            >
        </div>

        <div class="form-group">
            <label for="price">
                <i class="fas fa-ruble-sign"></i> Price (RUB) *
            </label>
            <input 
                type="number" 
                step="0.01" 
                name="price" 
                id="price" 
                class="form-control" 
                required 
                min="0"
                value="<?php echo escapeOutput($formData['price']); ?>"
                placeholder="e.g., 350.00"
            >
        </div>

        <div class="d-flex">
            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Save Session
            </button>
            <a href="?table=sessions" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>