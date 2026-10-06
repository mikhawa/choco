<?php
// path: view/public/article.view.php
// page de détail d'un article
// variables attendues : $article (objet ArticleMapping ou null si introuvable)
// et $messages (tableau d'objets MessageMapping publiés)
// $isConnected (bool), $messageError (erreur du formulaire ou null),
// $messageText (texte saisi), $flash (message après envoi ou null)

if ($article === null) {
    // article inexistant ou non publié : erreur 404 (avant tout affichage)
    http_response_code(404);
    $title = 'Article introuvable';
} else {
    $title = $article->getArticleTitle();
    $user = $article->getUser();
    // nom complet de l'auteur s'il existe, sinon son login
    $author = $user?->getUserFullName() ?? $user?->getUserLogin() ?? 'Anonyme';
    $date = new DateTime($article->getArticleCreateAt());
}

require RACINE_PATH . '/view/inc/header.view.php';
?>

<a class="back-link" href="<?= RACINE_URL ?>/">&larr; Retour aux articles</a>

<?php if ($article === null): ?>
    <section class="empty">
        <h1>Article introuvable</h1>
        <p>Cet article n'existe pas ou n'est plus disponible.</p>
    </section>
<?php else: ?>
    <article class="article-detail">
        <header class="article-header">
            <h1><?= htmlspecialchars($article->getArticleTitle()) ?></h1>
            <p class="card-meta">
                Par <strong><?= htmlspecialchars($author) ?></strong>
                le <time datetime="<?= $date->format('Y-m-d') ?>"><?= $date->format('d/m/Y') ?></time>
            </p>
        </header>

        <div class="article-content">
            <?= nl2br(htmlspecialchars($article->getArticleText())) ?>
        </div>
    </article>

    <section class="messages" id="messages">
        <h2>Messages (<?= count($messages) ?>)</h2>

        <?php if ($flash): ?>
            <p class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>" role="status">
                <?= htmlspecialchars($flash['message']) ?>
            </p>
        <?php endif; ?>

        <?php if (empty($messages)): ?>
            <p class="messages-empty">Aucun message pour cet article.</p>
        <?php else: ?>
            <?php foreach ($messages as $message):
                $messageUser = $message->getUser();
                // nom complet de l'auteur du message s'il existe, sinon son login
                $messageAuthor = $messageUser?->getUserFullName() ?? $messageUser?->getUserLogin() ?? 'Anonyme';
                $messageDate = new DateTime($message->getMessageCreateAt());
            ?>
                <article class="message">
                    <p class="card-meta">
                        <strong><?= htmlspecialchars($messageAuthor) ?></strong>
                        le <time datetime="<?= $messageDate->format('Y-m-d\TH:i') ?>"><?= $messageDate->format('d/m/Y à H:i') ?></time>
                    </p>
                    <p class="message-text"><?= nl2br(htmlspecialchars($message->getMessageText())) ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($isConnected): ?>
            <form action="<?= RACINE_URL ?>/article/<?= htmlspecialchars($article->getArticleSlug()) ?>#messages"
                  method="post" class="form message-form">
                <!-- jeton contre les attaques CSRF -->
                <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token']) ?>">

                <?php if ($messageError !== null): ?>
                    <p class="alert alert-error" role="alert"><?= htmlspecialchars($messageError) ?></p>
                <?php endif; ?>

                <div class="form-group">
                    <label for="message_text">Votre message</label>
                    <textarea id="message_text" name="message_text" maxlength="600" required><?= htmlspecialchars($messageText) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Envoyer</button>
                </div>
            </form>
        <?php else: ?>
            <p class="messages-empty">
                <a href="<?= RACINE_URL ?>/connexion">Connectez-vous</a> pour poster un message.
            </p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php
require RACINE_PATH . '/view/inc/footer.view.php';
