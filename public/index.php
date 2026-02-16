<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Utils\Auth;

if (Auth::check()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
