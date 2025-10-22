<?php
function log_activity(PDO $pdo, int $id_user, string $username, string $acao, string $entidade, ?int $id_entidade, string $detalhes) {
    try {
        $sql = "INSERT INTO logs (id_user, username, acao, entidade, id_entidade, detalhes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_user, $username, $acao, $entidade, $id_entidade, $detalhes]);
    } catch (PDOException $e) {
    }
}