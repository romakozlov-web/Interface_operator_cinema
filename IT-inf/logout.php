<?php
require 'config.php';
require 'functions.php';

session_unset();
session_destroy();
redirect('index.php');