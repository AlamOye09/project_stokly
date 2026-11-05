<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function wajibMasuk(): void
{
    if (empty($_SESSION['pengguna_id'])) {
        header('Location: login.php');
        exit;
    }
}
