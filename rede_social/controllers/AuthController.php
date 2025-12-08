<?php
session_start();
require_once __DIR__ . '/../models/UsuarioModel.php';

class AuthController {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    public function login() {
        $erro_login = '';
        $email_preenchido = '';
        $sucesso_cadastro = $_SESSION['cadastro_sucesso'] ?? null;
        unset($_SESSION['cadastro_sucesso']);

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $senha_digitada = $_POST['senha'] ?? '';
            $email_preenchido = $email;

            try {
                $usuario = $this->usuarioModel->buscarPorEmail($email);

                if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $usuario['id'];
                    $_SESSION['user_info'] = [
                        'nome_completo' => $usuario['nome'],
                        'username' => $usuario['username'],
                        'email' => $usuario['email'],
                        'foto' => $usuario['foto'] 
                    ];
                    header('Location: FeedController.php');
                    exit;
                } else {
                    $erro_login = "E-mail ou senha inválidos.";
                }
            } catch (\Exception $e) {
                $erro_login = "Erro no servidor: " . $e->getMessage();
            }
        }
        
        include __DIR__ . '/../views/login.phtml';
    }

    public function cadastro() {
        $erros = [];
        $dados_preenchidos = ['nome' => '', 'username' => '', 'email' => '', 'data_nasc' => '', 'genero' => ''];

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            
            $senha = isset($_POST['senha']) ? (string)$_POST['senha'] : '';
            $confirmacao_senha = isset($_POST['confirmaSenha']) ? (string)$_POST['confirmaSenha'] : '';
            
            $nome = $_POST['nome'] ?? '';
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $data_nasc = $_POST['data_nasc'] ?? '';
            $genero = $_POST['genero'] ?? '';

            $dados_preenchidos = ['nome' => $nome, 'username' => $username, 'email' => $email, 'data_nasc' => $data_nasc, 'genero' => $genero];

            if (empty($nome) || empty($username) || empty($email) || empty($senha) || empty($confirmacao_senha) || empty($data_nasc) || empty($genero)) {
                $erros[] = "Todos os campos obrigatórios devem ser preenchidos.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erros[] = "O e-mail fornecido não é válido.";
            }
            
            if ($senha !== $confirmacao_senha) {
                $erros[] = "A senha e a confirmação de senha não coincidem.";
            }
            if (strlen($senha) < 6 || !preg_match('/[A-Z]/', $senha) || !preg_match('/[0-9]/', $senha)) {
                $erros[] = "A senha deve ter no mínimo 6 caracteres, 1 maiúscula e 1 número.";
            }
            
            $generos_validos = ['feminino', 'masculino', 'outro'];
            if (!in_array(strtolower($genero), $generos_validos)) {
                $erros[] = "O gênero selecionado não é válido.";
            }
            
            if (empty($erros)) {
                try {
                    $existentes = $this->usuarioModel->verificarUnicidade($email, $username);
                    foreach ($existentes as $usuario) {
                        if ($usuario['email'] === $email) { $erros[] = "Este e-mail já está cadastrado."; }
                        if ($usuario['username'] === $username) { $erros[] = "Nome de usuário já em uso."; }
                    }
                } catch (\Exception $e) {
                    $erros[] = "Erro ao verificar unicidade: " . $e->getMessage();
                }
            }

            if (empty($erros)) {
                 $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                 $this->usuarioModel->cadastrar($nome, $username, $email, $senha_hash, $data_nasc, $genero);
                 $_SESSION['cadastro_sucesso'] = "Cadastro realizado com sucesso! Faça login.";
                 header('Location: AuthController.php?action=login');
                 exit;
            }
        }
        
        include __DIR__ . '/../views/cadastro.phtml';
    }
    
    public function logout() {
        session_destroy();
        header('Location: AuthController.php?action=login');
        exit;
    }
}

$controller = new AuthController();
$action = $_GET['action'] ?? 'login';

if ($action === 'cadastro') {
    $controller->cadastro();
} elseif ($action === 'logout') {
    $controller->logout();
} else {
    $controller->login();
}
?>