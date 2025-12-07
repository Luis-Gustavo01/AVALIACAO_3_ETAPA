<?php
require_once __DIR__ . '/../core/Conexao.php';

class PostModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getPdo();
    }

    public function criarPost($usuario_id, $conteudo) {
        $stmt = $this->pdo->prepare("INSERT INTO posts (usuario_id, conteudo) VALUES (?, ?)");
        return $stmt->execute([$usuario_id, $conteudo]);
    }
    
    public function excluirPost($post_id, $usuario_id) {
        $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$post_id, $usuario_id]);
        return $stmt->rowCount();
    }
    
    public function buscarFeed($user_id) {
        $sql = "
            SELECT 
                p.id AS post_id, p.conteudo, p.curtidas, p.data_criacao, 
                u.id AS autor_id, u.nome, u.username, u.foto
            FROM posts p
            JOIN usuarios u ON p.usuario_id = u.id
            LEFT JOIN seguidores s ON p.usuario_id = s.seguido_id AND s.seguidor_id = :user_id_seguidor
            WHERE p.usuario_id = :user_id_proprio OR s.seguidor_id IS NOT NULL
            ORDER BY p.data_criacao DESC;
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id_seguidor' => $user_id,
            ':user_id_proprio' => $user_id
        ]);
        return $stmt->fetchAll();
    }

    public function curtiu($post_id, $usuario_id) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM curtidas WHERE post_id = ? AND usuario_id = ?");
        $stmt->execute([$post_id, $usuario_id]);
        return $stmt->rowCount() > 0;
    }

    public function processarCurtida($post_id, $usuario_id, $acao) {
        $this->pdo->beginTransaction();
        try {
            if ($acao === 'curtir') {
                $this->pdo->prepare("INSERT INTO curtidas (post_id, usuario_id) VALUES (?, ?)")->execute([$post_id, $usuario_id]);
                $this->pdo->prepare("UPDATE posts SET curtidas = curtidas + 1 WHERE id = ?")->execute([$post_id]);
            } else {
                $this->pdo->prepare("DELETE FROM curtidas WHERE post_id = ? AND usuario_id = ?")->execute([$post_id, $usuario_id]);
                $this->pdo->prepare("UPDATE posts SET curtidas = GREATEST(curtidas - 1, 0) WHERE id = ?")->execute([$post_id]);
            }
            $this->pdo->commit();
            return true;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            if ($e->getCode() === '23000') return true; 
            throw $e;
        }
    }
}
?>