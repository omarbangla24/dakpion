<?php
defined('ADMIN') || exit;

if (current_user()) log_activity('Logged out');
$_SESSION = [];
session_destroy();
redirect(aurl('login'));
