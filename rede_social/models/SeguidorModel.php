<?php
require_once __DIR__ . '/../core/Conexao.php';

class SeguidorModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getPdo();
    }

    public function isFollowing($seguidor_id, $seguido_id) {
        if ($seguidor_id == $seguido_id) return 'self';
        $stmt = $this->pdo->prepare("SELECT id FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
        $stmt->execute([$seguidor_id, $seguido_id]);
        return $stmt->rowCount() > 0;
    }

    public function seguir($seguidor_id, $seguido_id) {
        if ($seguidor_id == $seguido_id) return false;
        $stmt = $this->pdo->prepare("INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)");
        try {
            return $stmt->execute([$seguidor_id, $seguido_id]);
        } catch (\PDOException $e) {
            return ($e->getCode() === '23000');
        }
    }

    public function deixarDeSeguir($seguidor_id, $seguido_id) {
        $stmt = $this->pdo->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
        return $stmt->execute([$seguidor_id, $seguido_id]);
    }
}
?>