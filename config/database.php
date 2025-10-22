<?php
// Configurações do Banco de Dados
$host = 'localhost';
$dbname = 'NAME'; // <-- NOME DO BANCO DE DADOS
$user = 'USER'; // <-- USUÁRIO DO BANCO DE DADOS
$pass = 'PASS'; // <-- SENHA DO BANCO DE DADOS

// Configurações de local e fuso horário para o Brasil
date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>