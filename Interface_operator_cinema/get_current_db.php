<?php
require_once 'config.php';
header('Content-Type: application/json');

echo json_encode(['db' => DEFAULT_DB]);