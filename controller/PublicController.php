<?php
// path: controller/PublicController.php
// typage strict
declare(strict_types=1);

use model\manager\ArticleManager;
use model\manager\MessageManager;
use model\manager\UserManager;
use model\mapping\UserMapping;
use model\mapping\MessageMapping;


$articleManager = new ArticleManager($db);

// affichage d'un article
if(isset($_GET['pg'],$_GET['slug']) && $_GET['pg'] == 'article') {
    $slug = $_GET['slug'];
    $article = $articleManager->getArticleBySlug($slug);
    // l'utilisateur est-il connecté ? (seuls les connectés peuvent poster un message)
    $isConnected = isset($_SESSION['user_id']);
    $messageError = null;
    $messageText = '';
    $messages = [];
    $flash = null;

    if($article !== null) {
        $messageManager = new MessageManager($db);

        // envoi d'un nouveau message par un utilisateur connecté
        if($isConnected && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $messageText = is_string($_POST['message_text'] ?? null) ? $_POST['message_text'] : '';
            if(!UserManager::checkToken($_POST['token'] ?? null)) {
                $messageError = "Session expirée, veuillez réessayer.";
            } else {
                try {
                    // message publié directement pour un admin, en attente pour les autres
                    $message = new MessageMapping([
                        'message_text' => $messageText,
                        'message_status' => UserManager::isAdmin() ? 'publié' : MessageMapping::DEFAULT_STATUS,
                        'user_user_id' => $_SESSION['user_id'],
                        'article_article_id' => $article->getArticleId(),
                    ]);
                    $messageManager->insertMessage($message);
                    UserManager::flashAndRedirect('/article/'.$article->getArticleSlug().'#messages',
                        UserManager::isAdmin()
                            ? "Votre message a bien été publié."
                            : "Votre message a bien été envoyé, il sera visible après validation.");
                } catch (Exception $e) {
                    $messageError = $e->getMessage();
                }
            }
        }

        // récupération des messages publiés de l'article
        $messages = $messageManager->getMessagesByArticle($article->getArticleId());
        $flash = UserManager::getFlash();
    }
    require RACINE_PATH.'/view/public/article.view.php';
// affichage de la page de connexion
} elseif(isset($_GET['pg']) && $_GET['pg'] == 'connexion') {
    $error = null;
    $login = '';

    // création du jeton CSRF s'il n'existe pas encore
    if(!isset($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
    }

    // si le formulaire a été envoyé
    if(isset($_POST['user_login'], $_POST['user_pwd'], $_POST['token'])
        && is_string($_POST['user_login']) && is_string($_POST['user_pwd']) && is_string($_POST['token'])) {
        $login = trim($_POST['user_login']);
        $pwd = trim($_POST['user_pwd']);
        $userMapping = new UserMapping(['user_login' => $login, 'user_pwd' => $pwd]);

        if(!hash_equals($_SESSION['token'], $_POST['token'])) {
            $error = "Session expirée, veuillez réessayer.";
        } elseif($login === '' || $pwd === '') {
            $error = "Veuillez remplir tous les champs.";
        } else {
            $userManager = new UserManager($db);
            $user = $userManager->connectUser($userMapping);

            if($user === null) {
                // message volontairement vague pour ne pas indiquer si le login existe
                $error = "Identifiant ou mot de passe incorrect.";
            } else {
                // méthode statique pour créer la session de l'utilisateur
                UserManager::sessionUser($user);
            }
        }
    }

    require RACINE_PATH.'/view/public/connexion.view.php';

} else {

    $allArticles = $articleManager->getAllArticles();

// affichage de la page d'accueil
    require RACINE_PATH.'/view/public/homepage.view.php';
}
