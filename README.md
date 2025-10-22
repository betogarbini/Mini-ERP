💰 Esquema do Banco de Dados — financeiro_schema

Este repositório contém o esquema completo (estrutura) do banco de dados usado no sistema financeiro desenvolvido em PHP + MySQL.
Os dados foram removidos para manter a privacidade, restando apenas a estrutura das tabelas, índices e chaves estrangeiras.


⚙️ Tecnologias Utilizadas

Banco de Dados: MariaDB / MySQL

Versão recomendada: 10.5 ou superior

Compatível com: Hostinger, XAMPP, Laragon, WAMP, LAMP e outros ambientes PHP/MySQL.

🗄️ Instalação
1. Criar o banco de dados

No seu painel phpMyAdmin ou via terminal MySQL:

CREATE DATABASE financeiro_schema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

2. Importar o esquema

Via phpMyAdmin:

Acesse o phpMyAdmin;

Selecione o banco financeiro_schema;

Vá em Importar → Envie o arquivo database_schema.sql.

Via terminal (Linux/Mac/Windows):

mysql -u seu_usuario -p financeiro_schema < database_schema.sql

📋 Estrutura das Tabelas
Tabela	Descrição
categorias	Lista de categorias financeiras (ex: aluguel, salários, despesas, receitas).
lancamentos	Armazena os lançamentos financeiros (entradas e saídas), com valores, datas e relação à categoria.
meios_pagamento	(Opcional) Caso exista, define métodos de pagamento (ex: dinheiro, cartão, transferência).
perfis	(Opcional) Identifica diferentes perfis de usuários ou empresas dentro do sistema.

⚠️ Dependendo da versão do banco, podem existir mais tabelas relacionadas à autenticação, perfis ou meios de pagamento.

🧩 Relacionamentos Principais

lancamentos.id_categoria → categorias.id

categorias.id_perfil → perfis.id (quando disponível)

lancamentos.id_meio_pagamento → meios_pagamento.id (quando disponível)

🧠 Exemplo de Conexão PHP
<?php
$host = 'localhost';
$db   = 'financeiro_schema';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão bem-sucedida!";
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage();
}
?>

🧾 Licença

Este projeto pode ser usado livremente para fins de estudo e desenvolvimento.
Não contém dados reais de empresas ou usuários.
