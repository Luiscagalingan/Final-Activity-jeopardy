<?php
require_once __DIR__ . '/../includes/functions.php';

host_logout();
header('Location: login.php');
exit;
