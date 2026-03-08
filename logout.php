<?php
require_once __DIR__ . '/php/init.php';
mitos_logout();
header('Location: ' . mitos_url('index.php'));
exit;
