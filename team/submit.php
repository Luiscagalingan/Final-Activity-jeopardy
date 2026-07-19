<?php
require_once __DIR__ . '/../includes/functions.php';
player_require_login('../board/player_login.php');

header('Location: ../board/team_submission.php');
exit;
