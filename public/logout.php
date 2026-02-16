<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Utils\Auth;

Auth::logout();
redirect('login.php');
