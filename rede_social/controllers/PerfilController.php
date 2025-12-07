<?php
session_start();
require_once __DIR__ . '/../models/UsuarioModel.php';

class PerfilController {
    private $usuarioModel;

    public function __construct() {
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
            header('Location: AuthController.php?action=login');
            exit;
        }
        $this->usuarioModel = new UsuarioModel();
    }

    public function index() {
        $user_id = $_SESSION['user_id'];
        $mensagem_perfil = '';
        $sucesso_perfil = false;
        $erros = [];
        $dados_form = [];
        $usuario = $this->usuarioModel->buscarPorId($user_id);

        if (!$usuario) {
            session_destroy();
            header('Location: AuthController.php?action=login');
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $acao = $_POST['acao'] ?? '';
            
            if ($acao === 'atualizar_dados') {
                $nome = $_POST['nome'] ?? '';
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $data_nascimento = $_POST['data_nascimento'] ?? '';
                $genero = $_POST['genero'] ?? '';
                $senha_atual = $_POST['senha_atual'] ?? '';
                $nova_senha = $_POST['nova_senha'] ?? '';
                $confirma_senha = $_POST['confirma_senha'] ?? '';

                $dados_form = ['nome' => $nome, 'username' => $username, 'email' => $email, 'data_nascimento' => $data_nascimento, 'genero' => $genero];

                try {
                    $user_db = $this->usuarioModel->buscarPorId($user_id);

                    if (!password_verify($senha_atual, $user_db['senha'])) {
                        $erros[] = "Senha atual incorreta.";
                    }
                    
                    $existentes = $this->usuarioModel->verificarUnicidade($email, $username, $user_id);
                    foreach ($existentes as $u) {
                        if ($u['email'] === $email) { $erros[] = "E-mail já em uso por outro usuário."; }
                        if ($u['username'] === $username) { $erros[] = "Nome de usuário já em uso."; }
                    }

                    $senha_hash_final = $user_db['senha'];
                    if (!empty($nova_senha)) {
                        if (strlen($nova_senha) < 6 || !preg_match('/[A-Z]/', $nova_senha) || !preg_match('/[0-9]/', $nova_senha)) {
                            $erros[] = "A nova senha deve ter no mínimo 6 caracteres, 1 maiúscula e 1 número.";
                        } elseif ($nova_senha !== $confirma_senha) {
                            $erros[] = "A nova senha e a confirmação não coincidem.";
                        } else {
                            $senha_hash_final = password_hash($nova_senha, PASSWORD_DEFAULT);
                        }
                    }

                    if (empty($erros)) {
                        $this->usuarioModel->atualizarDados($user_id, $nome, $username, $email, $senha_hash_final, $data_nascimento, $genero);
                        $_SESSION['user_info']['nome_completo'] = $nome;
                        $_SESSION['user_info']['username'] = $username;
                        $_SESSION['user_info']['email'] = $email;
                        $mensagem_perfil = "Dados atualizados com sucesso!";
                        $sucesso_perfil = true;
                    }

                } catch (\Exception $e) {
                    $erros[] = "Erro ao atualizar dados: " . $e->getMessage();
                }

            } elseif (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $arquivo = $_FILES['foto_perfil'];
                $dados_foto = file_get_contents($arquivo['tmp_name']);
                $tamanho_maximo = 5 * 1024 * 1024;
        
                if ($arquivo['size'] > $tamanho_maximo) {
                    $mensagem_perfil = "A foto é muito grande. O limite é 5MB.";
                } else {
                    try {
                        $this->usuarioModel->atualizarFoto($user_id, $dados_foto);
                        
                        $usuario_recarregado = $this->usuarioModel->buscarPorId($user_id);
                        $_SESSION['user_info']['foto'] = $usuario_recarregado['foto'];
                        
                        $mensagem_perfil = "Foto de perfil atualizada com sucesso!";
                        $sucesso_perfil = true;

                    } catch (\Exception $e) {
                        $mensagem_perfil = "Erro ao salvar a foto: " . $e->getMessage();
                    }
                }
            } elseif (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
                $mensagem_perfil = "Erro no upload do arquivo. Código: " . $_FILES['foto_perfil']['error'];
            }
            
            $usuario = $this->usuarioModel->buscarPorId($user_id);
        }

        if (empty($dados_form)) {
            $dados_form = [
                'nome' => $usuario['nome'],
                'username' => $usuario['username'],
                'email' => $usuario['email'],
                'data_nascimento' => $usuario['data_nascimento'],
                'genero' => $usuario['genero']
            ];
        }

        $dados = [
            'usuario' => $usuario,
            'erros' => $erros,
            'mensagem_perfil' => $mensagem_perfil,
            'sucesso_perfil' => $sucesso_perfil,
            'dados_form' => $dados_form
        ];
        
        include __DIR__ . '/../views/perfil.phtml';
    }
}

$controller = new PerfilController();
$controller->index();
?>