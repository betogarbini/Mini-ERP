<?php
require_once 'check_auth.php';
require_once 'config/database.php';

// Busca os perfis que pertencem ao usuário logado
$stmt = $pdo->prepare("
    SELECT p.id, p.nome 
    FROM perfis p
    JOIN perfil_users pu ON p.id = pu.perfil_id
    WHERE pu.user_id = ?
    ORDER BY p.nome
");
$stmt->execute([$_SESSION['user_id']]);
$perfis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Se o usuário só tiver um perfil, seleciona-o automaticamente e redireciona
if (count($perfis) === 1) {
    $_SESSION['id_perfil_ativo'] = $perfis[0]['id'];
    $_SESSION['nome_perfil_ativo'] = $perfis[0]['nome'];
    header('Location: index.php');
    exit();
}

// Se o usuário não tiver perfis, redireciona para a página de criação de perfis
if (count($perfis) === 0) {
    header('Location: gerenciar_perfis.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Selecionar Perfil</title>
    <link rel="stylesheet" href="includes/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* --- Estilo especifico para essa página --- */
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f9; }
        .profile-selector { text-align: center; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); width: 90%; max-width: 400px; }
        .selector-logo { height: 50px; margin-bottom: 20px; } /* NOVO */
        .profile-selector h1 { margin-bottom: 30px; font-size: 1.8em; color: #34495e; }
        .profile-selector a.profile-link { display: block; background: #3498db; color: white; padding: 15px 30px; margin-bottom: 15px; text-decoration: none; border-radius: 5px; font-size: 1.2em; font-weight: 500; transition: background-color 0.3s; }
        .profile-selector a.profile-link:hover { background-color: #2980b9; }
        .profile-selector .separator { height: 1px; background-color: #eee; margin: 30px 0; } /* NOVO */
        .profile-selector a.manage-profiles-link { color: #555; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: color 0.3s; } /* MODIFICADO */
        .profile-selector a.manage-profiles-link:hover { color: #3498db; text-decoration: underline; }
        </style>

</head>
<body>
    <div class="profile-selector">
        <!-- Logo do sistema -->
        <img src="img/logo.png" alt="Logo" class="selector-logo">
        
        <h1>Selecione um Perfil</h1>

        <?php foreach ($perfis as $perfil): ?>
            <a class="profile-link" href="definir_perfil.php?id=<?= $perfil['id'] ?>">
                <?= htmlspecialchars($perfil['nome']) ?>
            </a>
        <?php endforeach; ?>

        <!-- Separador e link com ícone -->
        <div class="separator"></div>
        <a class="manage-profiles-link" href="gerenciar_perfis.php">
            <i class="fas fa-cog"></i> Gerenciar Perfis
        </a>
    </div>
</body>
</html>