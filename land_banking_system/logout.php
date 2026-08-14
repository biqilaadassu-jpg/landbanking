<?php
require __DIR__ . '/config/config.php';
if (current_user()) audit('LOGOUT', 'USER', (int)current_user()['id']);
session_destroy();
redirect('login.php');
