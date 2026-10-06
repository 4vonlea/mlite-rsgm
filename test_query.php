<?php
require 'systems/Main.php';

// Bootstrap minimal core
class FakeTpl { public function assign(){} public function set(){} }
class FakeRouter { public function __construct(){} }
class FakeSettings { public function __construct(){} }

require 'systems/BaseModule.php';
require 'systems/AdminModule.php';
require 'systems/lib/QueryWrapper.php';
require 'systems/lib/Medoo.php';

$medoo = new \Medoo\Medoo([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'mlitersgm',
    'username' => 'root',
    'password' => '' // Try empty password or 'root'
]);

// Since I don't know the exact db setup, let me just run a direct PDO connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=mlitersgm', 'root', '');
    $stmt = $pdo->prepare("SELECT * FROM reg_periksa WHERE no_rkm_medis = '055026' ORDER BY tgl_registrasi DESC LIMIT 1");
    $stmt->execute();
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
