<?php
// Pega o nome do arquivo atual (ex: 'index.php', 'lancamentos.php')
$currentPage = basename($_SERVER['PHP_SELF']);

// Busca o nome do usuário logado para exibir no cabeçalho
$username_logado = '';
if (isset($_SESSION['user_id'])) {
    // Inclui a conexão com o banco de dados SE ELA AINDA NÃO EXISTIR
    // @ suprime o warning se o arquivo já foi incluído na página principal
    @require_once __DIR__ . '/../config/database.php';
    
    try {
        $stmt_user = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_info = $stmt_user->fetch();
        if ($user_info) {
            $username_logado = $user_info['username'];
        }
    } catch (PDOException $e) {
        // Em caso de erro, não faz nada, apenas não exibe o nome
        $username_logado = '';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle Financeiro</title>

    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="img/favicon.png">

    <!-- ÍCONES E ESTILOS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="includes/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>
    <a href="index.php">
        <img src="img/logo.png" alt="Logo ERP" class="header-logo">
        Financeiro: <?= isset($_SESSION['nome_perfil_ativo']) ? htmlspecialchars($_SESSION['nome_perfil_ativo']) : '' ?>
    </a>
</h1>
            <!-- BOTÃO HAMBÚRGUER (só aparece no mobile) -->
            <button class="menu-toggle" aria-label="Abrir menu">
                <i class="fas fa-bars"></i>
            </button>

            <!-- **** ESTRUTURA DE NAVEGAÇÃO **** -->
            <nav class="main-nav">
                <!-- Links Principais -->
                <a href="index.php" class="<?= ($currentPage == 'index.php') ? 'active' : '' ?>">Dashboard</a>
                
                <?php if (has_permission('lancamento_ver')): ?>
                    <a href="lancamentos.php" class="<?= ($currentPage == 'lancamentos.php' || $currentPage == 'editar_lancamento.php') ? 'active' : '' ?>">Lançamentos</a>
                <?php endif; ?>
                
                <?php if (has_permission('categoria_gerenciar')): ?>
                    <a href="categorias.php" class="<?= ($currentPage == 'categorias.php') ? 'active' : '' ?>">Categorias</a>
                <?php endif; ?>
                
                <?php if (has_permission('historico_ver')): ?>
                    <a href="historico.php" class="<?= ($currentPage == 'historico.php') ? 'active' : '' ?>">Histórico</a>
                <?php endif; ?>
                
                <!-- Menu Suspenso de Administração -->
                <?php if (has_permission('usuario_gerenciar')): ?>
                    <div class="nav-dropdown">
                        <a href="#" class="dropdown-toggle">Administração <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-menu">
                            <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
                            <a href="gerenciar_funcoes.php">Gerenciar Funções</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- MENU DE USUÁRIO -->
                <div class="nav-dropdown user-dropdown">
                    <a href="#" class="dropdown-toggle user-toggle">
    Olá, <strong><?= htmlspecialchars($username_logado) ?></strong> <i class="fas fa-chevron-down"></i>
</a>
                    <div class="dropdown-menu">
                        <a href="selecionar_perfil.php">Trocar Perfil</a>
                        <a href="minha_conta.php">Minha Conta</a>
                        <a href="gerenciar_perfis.php">Gerenciar Perfis</a>
                        <div class="divider"></div>
                        <a href="logout.php">Sair</a>
                    </div>
                </div>
            </nav>
    </header>
    <main>

    <!-- JAVASCRIPT PARA O MENU -->
    <script>
        const menuToggle = document.querySelector('.menu-toggle');
        const mainNav = document.querySelector('.main-nav');
        if (menuToggle && mainNav) {
            menuToggle.addEventListener('click', () => {
                mainNav.classList.toggle('is-open');
            });
        }
    </script>