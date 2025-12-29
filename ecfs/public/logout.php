<?php
require_once __DIR__ . '/../src/autoload.php';
use EazeWebIT\Auth;

Auth::logout();
header('Location: login.php');
exit;
