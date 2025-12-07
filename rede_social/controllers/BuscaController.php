<?php
session_start();
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/SeguidorModel.php';

class BuscaController {
    private $usuarioModel;
    private $seguidorModel;

    public function __construct() {
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
            header('Location: AuthController.php?action=login');
            exit;
        }
        $this->usuarioModel = new UsuarioModel();
        $this->seguidorModel = new SeguidorModel();
    }

    public function index() {
        $user_id = $_SESSION['user_id'];
        $query_pesquisa = $_GET['q'] ?? '';
        $resultados = [];
        $mensagem_erro = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && isset($_POST['usuario_id'])) {
            $acao = $_POST['acao'];
            $usuario_alvo_id = $_POST['usuario_id'];
            
            if ($usuario_alvo_id == $user_id) {
                $mensagem_erro = "Você não pode seguir ou deixar de seguir a si mesmo.";
            } else {
                try {
                    if ($acao === 'seguir') {
                        $this->seguidorModel->seguir($user_id, $usuario_alvo_id);
                    } elseif ($acao === 'deixar_de_seguir') {
                        $this->seguidorModel->deixarDeSeguir($user_id, $usuario_alvo_id);
                    }
                } catch (\Exception $e) {
                    $mensagem_erro = "Erro ao processar ação: " . $e->getMessage();
                }
                header("Location: BuscaController.php?q=" . urlencode($query_pesquisa));
                exit;
            }
        }

        if (!empty($query_pesquisa)) {
            try {
                $resultados = $this->usuarioModel->pesquisarUsuarios($query_pesquisa);
            } catch (\Exception $e) {
                $mensagem_erro = "Erro ao realizar pesquisa: " . $e->getMessage();
            }
        }

        $dados = [
            'user_id' => $user_id,
            'query_pesquisa' => $query_pesquisa,
            'resultados' => $resultados,
            'mensagem_erro' => $mensagem_erro,
            'seguidorModel' => $this->seguidorModel
        ];
        
        include __DIR__ . '/../views/pesquisa.phtml';
    }
}

$controller = new BuscaController();
$controller->index();
?>