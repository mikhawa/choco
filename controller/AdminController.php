<?php
// path: controller/AdminController.php
// typage strict
declare(strict_types=1);

use model\manager\ArticleManager;
use model\manager\UserManager;
use model\manager\MessageManager;
use model\mapping\ArticleMapping;

// contrôleur du CRUD des articles et de la modération des messages, réservé aux admins
// routes : /admin, /admin/create, /admin/update/{id}, /admin/delete/{id}
//          /admin/messages, /admin/message-publish/{id}, /admin/message-disable/{id}, /admin/message-delete/{id}

// sécurité : on vérifie à nouveau le rôle on peut aussi vérifier le rôle dans la session.
if(!UserManager::isAdmin()) {
    header('Location: '.RACINE_URL.'/');
    exit;
}

$articleManager = new ArticleManager($db);

$action = $_GET['slug'] ?? '';
$id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : 0;
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

if($action == 'create') {
    // ---------- création ----------
    // un nouvel article vide, dont l'auteur est l'admin connecté
    $article = new ArticleMapping([
        'article_status' => ArticleMapping::DEFAULT_STATUS,
        'user_user_id' => $_SESSION['user_id'],
    ]);

    $error = null;
    if($isPost) {
        if(!UserManager::checkToken($_POST['token'] ?? null)) {
            $error = "Session expirée, veuillez réessayer.";
        } else {
            try {
                // les setters de ArticleMapping valident les données (exception si invalide)
                $article = new ArticleMapping([
                    'article_title' => $_POST['article_title'] ?? '',
                    'article_text' => $_POST['article_text'] ?? '',
                    'article_status' => $_POST['article_status'] ?? '',
                    'user_user_id' => $_SESSION['user_id'],
                ]);
                $articleManager->insertArticle($article);
                UserManager::flashAndRedirect('/admin', "L'article a bien été créé.");
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }

    $statusList = ArticleMapping::getStatusList();
    require RACINE_PATH.'/view/private/article-form.view.php';

} elseif($action == 'update' && $id > 0) {
    // ---------- modification ----------
    // l'article à modifier vient de la base
    $article = $articleManager->getArticleById($id);

    if($article === null) {
        UserManager::flashAndRedirect('/admin', "Cet article n'existe pas.", 'error');
    }

    $error = null;
    if($isPost) {
        if(!UserManager::checkToken($_POST['token'] ?? null)) {
            $error = "Session expirée, veuillez réessayer.";
        } else {
            try {
                // les setters de ArticleMapping valident les données (exception si invalide)
                $article = new ArticleMapping([
                    'article_id' => $id,
                    'article_title' => $_POST['article_title'] ?? '',
                    'article_text' => $_POST['article_text'] ?? '',
                    'article_status' => $_POST['article_status'] ?? '',
                ]);
                $articleManager->updateArticle($article);
                UserManager::flashAndRedirect('/admin', "L'article a bien été modifié.");
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }

    $statusList = ArticleMapping::getStatusList();
    require RACINE_PATH.'/view/private/article-form.view.php';

} elseif($action == 'delete' && $id > 0) {
    // ---------- suppression (en GET, avec le jeton dans l'URL) ----------
    if(!UserManager::checkToken($_GET['token'] ?? null)) {
        UserManager::flashAndRedirect('/admin', "Suppression refusée, veuillez réessayer.", 'error');
    }
    if(!$articleManager->deleteArticle($id)) {
        UserManager::flashAndRedirect('/admin', "Cet article n'existe pas.", 'error');
    }
    UserManager::flashAndRedirect('/admin', "L'article a bien été supprimé.");

} elseif($action == 'messages') {
    // ---------- modération des messages ----------
    $messageManager = new MessageManager($db);
    $messages = $messageManager->getAllMessagesAdmin();
    $flash = UserManager::getFlash();

    require RACINE_PATH.'/view/private/messages.view.php';

} elseif(in_array($action, ['message-publish', 'message-disable', 'message-delete'], true) && $id > 0) {
    // ---------- actions de modération (en GET, avec le jeton dans l'URL) ----------
    if(!UserManager::checkToken($_GET['token'] ?? null)) {
        UserManager::flashAndRedirect('/admin/messages', "Action refusée, veuillez réessayer.", 'error');
    }
    $messageManager = new MessageManager($db);

    if($action == 'message-delete') {
        $done = $messageManager->deleteMessage($id);
        $success = "Le message a bien été supprimé.";
    } else {
        $status = $action == 'message-publish' ? 'publié' : 'désactivé';
        $done = $messageManager->updateMessageStatus($id, $status);
        $success = $action == 'message-publish' ? "Le message a bien été publié." : "Le message a bien été désactivé.";
    }

    if(!$done) {
        UserManager::flashAndRedirect('/admin/messages', "Ce message n'existe pas.", 'error');
    }
    UserManager::flashAndRedirect('/admin/messages', $success);

} else {
    // ---------- liste des articles ----------
    $articles = $articleManager->getAllArticlesAdmin();
    $flash = UserManager::getFlash();

    require RACINE_PATH.'/view/private/admin.view.php';
}
