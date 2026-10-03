<?php
require __DIR__ . '/_admin.php';

$_SESSION = [];
session_destroy();
redirect('login.php');
