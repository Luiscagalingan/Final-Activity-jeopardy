<?php
require_once __DIR__ . '/../includes/functions.php';
player_require_login();

header('Location: ../board/team_submission.php');
exit;
