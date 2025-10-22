<?php
// Inicia a sessão para verificar as variáveis de sessão
ob_start();
session_start();

// Verifica se o usuário NÃO está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Se o usuário está logado, mas não selecionou um perfil, força a seleção
// (Permite acesso a páginas que não dependem de perfil, como as de gerenciamento de conta/perfil)
$pagina_atual = basename($_SERVER['PHP_SELF']);
$paginas_sem_perfil = ['selecionar_perfil.php', 'definir_perfil.php', 'gerenciar_perfis.php', 'minha_conta.php', 'atualizar_conta.php', 'logout.php'];

if (!isset($_SESSION['id_perfil_ativo']) && !in_array($pagina_atual, $paginas_sem_perfil)) {
    header('Location: selecionar_perfil.php');
    exit;
}

// Disponibiliza o ID do perfil ativo para todas as páginas
$id_perfil_ativo = $_SESSION['id_perfil_ativo'] ?? null;

// ---- LÓGICA DE CARREGAMENTO DE PERMISSÕES ----
// Se as permissões ainda não foram carregadas nesta sessão, busca no banco
if (!isset($_SESSION['permissions'])) {
    require_once __DIR__ . '/config/database.php';

    $stmt = $pdo->prepare("
        SELECT p.nome_permissao
        FROM users u
        JOIN roles r ON u.role_id = r.id
        JOIN role_permissions rp ON r.id = rp.role_id
        JOIN permissions p ON rp.permission_id = p.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $permissions_from_db = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Armazena as permissões em um formato fácil de checar
    $_SESSION['permissions'] = array_flip($permissions_from_db);
}

/**
 * Função global para verificar se o usuário logado tem uma permissão específica.
 *
 * @param string $permission O nome da permissão a ser verificada (ex: 'lancamento_criar').
 * @return bool True se o usuário tem a permissão, false caso contrário.
 */
function has_permission(string $permission): bool {
    // Verifica se a chave da permissão existe no array da sessão
    return isset($_SESSION['permissions'][$permission]);
}
// --------------------------------------------------
?>