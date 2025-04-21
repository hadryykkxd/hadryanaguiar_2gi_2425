<?php
// Configuração da conexão com o banco de dados
$host = "localhost";
$user = "root"; // usuário padrão do XAMPP
$password = ""; // senha padrão do XAMPP (vazia)
$database = "classicmodels"; // nome do banco de dados importado

// Criar conexão
$conn = new mysqli($host, $user, $password, $database);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Definir charset para UTF-8
$conn->set_charset("utf8");
?>