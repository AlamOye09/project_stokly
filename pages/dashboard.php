<?php
session_start();

if (!isset($_SESSION['id_user'])) {
	header('Location: ../auth/login.php');
	exit;
}

require_once __DIR__ . '/../modul/get_summary.php';
?>