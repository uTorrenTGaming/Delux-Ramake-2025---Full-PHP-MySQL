<?php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "delux-php"; // O banco de dados que você deve criar

$conn = new mysqli($servidor, $usuario, $senha, $banco);

if($conn->connect_error){
    die("❌ Falha na conexão: " . $conn->connect_error);
}
?>