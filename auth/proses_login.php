<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
if (!is_string($username) || !is_string($password) || trim($username) === '' || $password === '') {
    header('Location: login.php?error=1');
    exit;
}
$username = trim($username);

$statement = $koneksi->prepare(
    'SELECT id_user, nama, username, password, role FROM users WHERE username = ? LIMIT 1'
);
$statement->bind_param('s', $username);
$statement->execute();
$user = $statement->get_result()->fetch_assoc();
$statement->close();

if ($user === null || !password_verify($password, $user['password'])) {
    header('Location: login.php?error=1');
    exit;
}

session_regenerate_id(true);
$_SESSION['id_user'] = (int) $user['id_user'];
$_SESSION['nama'] = $user['nama'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

header('Location: ../pages/dashboard.php');
exit;