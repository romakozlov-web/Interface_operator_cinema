<?php

use Cinema\App;

/**
 * Unified add-record handler: film, hall, session.
 * Accessed via ?action=add_film|add_hall|add_session
 */

$pdo = App::getConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Ошибка подключения к БД</div>';
    return;
}

$type = $_GET['action'] === 'add_hall' ? 'hall' : ($_GET['action'] === 'add_session' ? 'session' : 'film');

/* ---------- Films ---------- */
if ($type === 'film') {
    $success = false; $error = '';
    $form = ['title'=>'','description'=>'','duration'=>'','poster'=>'','release_date'=>''];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $form['title'] = trim($_POST['title'] ?? '');
        $form['description'] = trim($_POST['description'] ?? '');
        $form['duration'] = (int)($_POST['duration'] ?? 0);
        $form['poster'] = trim($_POST['poster'] ?? '');
        $form['release_date'] = $_POST['release_date'] ?? null;

        if (empty($form['title'])) $error = 'Название фильма обязательно';
        elseif ($form['duration'] <= 0) $error = 'Длительность должна быть > 0';
        else {
            try {
                App::addFilm($pdo, $form);
                $success = true;
                $form = array_fill_keys(array_keys($form), '');
            } catch (Exception $e) {
                $error = 'Ошибка: ' . $e->getMessage();
            }
        }
    }
    ?>
    <div class="card fade-in">
        <h3><i class="fas fa-plus-circle"></i> Добавить фильм</h3>
        <?php if ($success): ?><div class="alert alert-success">Фильм добавлен! <a href="?table=films">К списку</a></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo App::escape($error); ?></div><?php endif; ?>
        <form method="post" class="form">
            <div class="form-group">
                <label for="title"><i class="fas fa-heading"></i> Название *</label>
                <input type="text" name="title" id="title" class="form-control" required value="<?php echo App::escape($form['title']); ?>" placeholder="Название фильма">
            </div>
            <div class="form-group">
                <label for="description"><i class="fas fa-align-left"></i> Описание</label>
                <textarea name="description" id="description" class="form-control" rows="5" placeholder="Описание фильма"><?php echo App::escape($form['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="duration"><i class="fas fa-clock"></i> Длительность (мин) *</label>
                <input type="number" name="duration" id="duration" class="form-control" required min="1" value="<?php echo App::escape($form['duration']); ?>" placeholder="120">
            </div>
            <div class="form-group">
                <label for="poster"><i class="fas fa-image"></i> Постер URL</label>
                <input type="url" name="poster" id="poster" class="form-control" value="<?php echo App::escape($form['poster']); ?>" placeholder="https://example.com/poster.jpg">
            </div>
            <div class="form-group">
                <label for="release_date"><i class="fas fa-calendar"></i> Дата выхода</label>
                <input type="date" name="release_date" id="release_date" class="form-control" value="<?php echo App::escape($form['release_date']); ?>">
            </div>
            <div class="d-flex">
                <button type="submit" class="btn"><i class="fas fa-save"></i> Сохранить</button>
                <a href="?table=films" class="btn btn-secondary"><i class="fas fa-times"></i> Отмена</a>
            </div>
        </form>
    </div>
    <?php
}

/* ---------- Halls ---------- */
elseif ($type === 'hall') {
    $success = false; $error = '';
    $form = ['name'=>'','seats'=>'','description'=>''];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $form['name'] = trim($_POST['name'] ?? '');
        $form['seats'] = intval($_POST['seats'] ?? 0);
        $form['description'] = trim($_POST['description'] ?? '');

        if (empty($form['name']) || $form['seats'] <= 0) {
            $error = 'Название и количество мест обязательны';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO halls (name, seats, description) VALUES (?, ?, ?)");
                $stmt->execute([$form['name'], $form['seats'], $form['description']]);
                $success = true;
                $form = ['name'=>'','seats'=>'','description'=>''];
            } catch (Exception $e) {
                $error = 'Ошибка: ' . $e->getMessage();
            }
        }
    }
    ?>
    <div class="card">
        <h3><i class="fas fa-plus-circle"></i> Добавить зал</h3>
        <?php if ($success): ?><div class="alert alert-success">Зал добавлен! <a href="?table=halls">К списку</a></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo App::escape($error); ?></div><?php endif; ?>
        <form method="post" class="form">
            <div class="form-group">
                <label for="name">Название зала *</label>
                <input type="text" name="name" id="name" class="form-control" required value="<?php echo App::escape($form['name']); ?>">
            </div>
            <div class="form-group">
                <label for="seats">Количество мест *</label>
                <input type="number" name="seats" id="seats" class="form-control" required min="1" value="<?php echo App::escape($form['seats']); ?>">
            </div>
            <div class="form-group">
                <label for="description">Описание</label>
                <textarea name="description" id="description" class="form-control" rows="3"><?php echo App::escape($form['description']); ?></textarea>
            </div>
            <button type="submit" class="btn"><i class="fas fa-save"></i> Сохранить</button>
            <a href="?table=halls" class="btn btn-secondary">Отмена</a>
        </form>
    </div>
    <?php
}

/* ---------- Sessions ---------- */
elseif ($type === 'session') {
    $films = $pdo->query("SELECT id, title FROM films ORDER BY title")->fetchAll();
    $halls = $pdo->query("SELECT id, name FROM halls ORDER BY name")->fetchAll();

    // Detect date column
    $dateColumn = 'date';
    try {
        $stmt = $pdo->query("DESCRIBE sessions");
        foreach ($stmt->fetchAll() as $col) {
            if (in_array($col['Field'], ['date','start_time','session_date'])) { $dateColumn = $col['Field']; break; }
        }
    } catch (Exception $e) {}

    $success = false; $error = '';
    $form = ['film_id'=>'','hall_id'=>'','date'=>'','price'=>''];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $form['film_id'] = (int)($_POST['film_id'] ?? 0);
        $form['hall_id'] = (int)($_POST['hall_id'] ?? 0);
        $form['date'] = $_POST['date'] ?? '';
        $form['price'] = (float)($_POST['price'] ?? 0);

        if ($form['film_id'] <= 0) $error = 'Выберите фильм';
        elseif ($form['hall_id'] <= 0) $error = 'Выберите зал';
        elseif (empty($form['date'])) $error = 'Выберите дату и время';
        elseif ($form['price'] <= 0) $error = 'Цена должна быть > 0';
        else {
            try {
                $stmt = $pdo->prepare("INSERT INTO sessions (film_id, hall_id, $dateColumn, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$form['film_id'], $form['hall_id'], $form['date'], $form['price']]);
                $success = true;
                $form = ['film_id'=>'','hall_id'=>'','date'=>'','price'=>''];
            } catch (Exception $e) {
                $error = 'Ошибка: ' . $e->getMessage();
            }
        }
    }
    ?>
    <div class="card fade-in">
        <h3><i class="fas fa-plus-circle"></i> Добавить сеанс</h3>
        <?php if ($success): ?><div class="alert alert-success">Сеанс добавлен! <a href="?table=sessions">К списку</a></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo App::escape($error); ?></div><?php endif; ?>
        <form method="post" class="form">
            <div class="form-group">
                <label for="film_id"><i class="fas fa-film"></i> Фильм *</label>
                <select name="film_id" id="film_id" class="form-control" required>
                    <option value="">-- Выберите фильм --</option>
                    <?php foreach ($films as $f): ?>
                        <option value="<?php echo $f['id']; ?>" <?php echo $form['film_id']==$f['id']?'selected':''; ?>><?php echo App::escape($f['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="hall_id"><i class="fas fa-door-open"></i> Зал *</label>
                <select name="hall_id" id="hall_id" class="form-control" required>
                    <option value="">-- Выберите зал --</option>
                    <?php foreach ($halls as $h): ?>
                        <option value="<?php echo $h['id']; ?>" <?php echo $form['hall_id']==$h['id']?'selected':''; ?>><?php echo App::escape($h['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="date"><i class="fas fa-calendar-alt"></i> Дата и время *</label>
                <input type="datetime-local" name="date" id="date" class="form-control" required value="<?php echo App::escape($form['date']); ?>">
            </div>
            <div class="form-group">
                <label for="price"><i class="fas fa-ruble-sign"></i> Цена (₽) *</label>
                <input type="number" step="0.01" name="price" id="price" class="form-control" required min="0" value="<?php echo App::escape($form['price']); ?>" placeholder="350.00">
            </div>
            <div class="d-flex">
                <button type="submit" class="btn"><i class="fas fa-save"></i> Сохранить</button>
                <a href="?table=sessions" class="btn btn-secondary"><i class="fas fa-times"></i> Отмена</a>
            </div>
        </form>
    </div>
    <?php
}
?>
