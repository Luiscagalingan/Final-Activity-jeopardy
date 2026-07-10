<?php
require_once __DIR__ . '/../includes/functions.php';

player_logout();
header('Location: player_login.php');
exit;
