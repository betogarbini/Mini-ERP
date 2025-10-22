<?php
session_start();
require_once 'check_auth.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['comprovanteFile']) && isset($_POST['id_lancamento'])) {
    
    $id_lancamento = (int)$_POST['id_lancamento'];
    $file = $_FILES['comprovanteFile'];

    // --- VALIDAÇÕES DE SEGURANÇA ---
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['notification'] = ['message' => 'Erro no upload do arquivo.', 'type' => 'error'];
        header('Location: lancamentos.php'); exit();
    }

    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['notification'] = ['message' => 'Tipo de arquivo inválido. Apenas JPG, PNG e PDF são permitidos.', 'type' => 'error'];
        header('Location: lancamentos.php'); exit();
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5 MB
        $_SESSION['notification'] = ['message' => 'Arquivo muito grande. O limite é 5 MB.', 'type' => 'error'];
        header('Location: lancamentos.php'); exit();
    }

    // --- PROCESSAMENTO DO ARQUIVO ---
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    // Cria um nome de arquivo único para evitar conflitos e problemas de segurança
    $new_filename = "comprovante_" . $id_lancamento . "_" . uniqid() . "." . $file_extension;
    $destination = __DIR__ . '/uploads/comprovantes/' . $new_filename;

    // Antes de mover o novo, verifica se existe um antigo para apagar
    $stmt_old = $pdo->prepare("SELECT comprovante_path FROM lancamentos WHERE id = ? AND id_perfil = ?");
    $stmt_old->execute([$id_lancamento, $id_perfil_ativo]);
    $old_file = $stmt_old->fetchColumn();
    if ($old_file && file_exists(__DIR__ . '/uploads/comprovantes/' . $old_file)) {
        unlink(__DIR__ . '/uploads/comprovantes/' . $old_file);
    }

    // Move o novo arquivo
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Atualiza o banco de dados com o nome do novo arquivo
        $stmt_update = $pdo->prepare("UPDATE lancamentos SET comprovante_path = ? WHERE id = ? AND id_perfil = ?");
        $stmt_update->execute([$new_filename, $id_lancamento, $id_perfil_ativo]);

        $_SESSION['notification'] = ['message' => 'Comprovante enviado com sucesso!', 'type' => 'success'];
    } else {
        $_SESSION['notification'] = ['message' => 'Falha ao mover o arquivo para o destino.', 'type' => 'error'];
    }

    header('Location: lancamentos.php');
    exit();
}
?>