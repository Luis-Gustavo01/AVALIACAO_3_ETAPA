<?php
session_start();
require_once __DIR__ . '/../models/PostModel.php';

class FeedController {
    private $postModel;

    public function __construct() {
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
            header('Location: AuthController.php?action=login');
            exit;
        }
        $this->postModel = new PostModel();
    }

    public function index() {
        $user_id = $_SESSION['user_id'];
        $mensagem_erro = '';
        
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $acao = $_POST['acao'] ?? '';
            $post_id = $_POST['post_id'] ?? 0;
            $conteudo = trim($_POST['conteudo'] ?? '');

            try {
                if ($acao === 'criar_post' && !empty($conteudo)) {
                    $this->postModel->criarPost($user_id, $conteudo);
                } elseif ($acao === 'curtir') {
                    $curtiu = $this->postModel->curtiu($post_id, $user_id);
                    $this->postModel->processarCurtida($post_id, $user_id, $curtiu ? 'descurtir' : 'curtir');
                } elseif ($acao === 'excluir') {
                    $this->postModel->excluirPost($post_id, $user_id);
                }
                
                header('Location: FeedController.php');
                exit;

            } catch (\Exception $e) {
                $mensagem_erro = "Erro na interação: " . $e->getMessage();
            }
        }
        
        $posts = $this->postModel->buscarFeed($user_id);
        
        $dados = [
            'user_id' => $user_id,
            'user_info' => $_SESSION['user_info'],
            'posts' => $posts,
            'mensagem_erro' => $mensagem_erro,
            'postModel' => $this->postModel
        ];
        
        include __DIR__ . '/../views/feed.phtml';
    }
}

$controller = new FeedController();
$controller->index();
?>