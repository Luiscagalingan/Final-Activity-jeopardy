@ -0,0 +1,6 @@
<?php
require_once __DIR__ . '/../includes/functions.php';

player_logout();
header('Location: ../index.php');
exit;