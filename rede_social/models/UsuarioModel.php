<?php
require_once __DIR__ . '/../core/Conexao.php';

class UsuarioModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getPdo();
    }

    public function buscarPorEmail($email) {
        $stmt = $this->pdo->prepare("SELECT id, nome, username, senha, email, foto FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function buscarPorUsername($username) {
        $stmt = $this->pdo->prepare("SELECT id, nome, username, email, data_nascimento, genero, foto FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT id, nome, username, email, data_nascimento, genero, foto, senha FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function verificarUnicidade($email, $username, $id = null) {
        $sql = "SELECT id, email, username FROM usuarios WHERE (email = :email OR username = :username)";
        if ($id) {
            $sql .= " AND id != :id";
        }
        $stmt = $this->pdo->prepare($sql);
        $params = [':email' => $email, ':username' => $username];
        if ($id) {
            $params[':id'] = $id;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function cadastrar($nome, $username, $email, $senha_hash, $data_nasc, $genero) {
        $sql = "INSERT INTO usuarios (nome, username, email, senha, data_nascimento, genero) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nome, $username, $email, $senha_hash, $data_nasc, strtolower($genero)]);
    }
    
    public function atualizarDados($id, $nome, $username, $email, $senha_hash, $data_nascimento, $genero) {
        $sql = "UPDATE usuarios SET nome = ?, username = ?, email = ?, senha = ?, data_nascimento = ?, genero = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nome, $username, $email, $senha_hash, $data_nascimento, strtolower($genero), $id]);
    }
    
    public function atualizarFoto($id, $dados_foto) {
        $sql = "UPDATE usuarios SET foto = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $dados_foto, PDO::PARAM_LOB); 
        $stmt->bindParam(2, $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function pesquisarUsuarios($termo) {
        $sql = "SELECT id, nome, username, foto FROM usuarios WHERE nome LIKE CONCAT('%', :termo1, '%') OR username LIKE CONCAT('%', :termo2, '%') ORDER BY nome";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':termo1' => $termo, ':termo2' => $termo]);
        return $stmt->fetchAll();
    }
}
?>