<?php
// run_hash_passwords.php
require_once 'config/database.php';

$stmt = $pdo->query("SELECT id, password FROM technicians WHERE password_hash IS NULL");
$technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($technicians as $tech) {
    $hashed = password_hash($tech['password'], PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE technicians SET password_hash = ? WHERE id = ?");
    $update->execute([$hashed, $tech['id']]);
    echo "Updated technician ID {$tech['id']}<br>";
}
echo "Done!";
?>