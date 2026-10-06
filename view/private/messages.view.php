<?php
// path: view/private/messages.view.php
// modération des messages pour l'administration
// variables attendues : $messages (tableau de MessageMapping), $flash (message ou null)
$title = 'Modération des messages';
require RACINE_PATH . '/view/inc/header.view.php';

// classe CSS du badge selon le statut
$badges = ['publié' => 'badge-success', 'en attente' => 'badge-warning', 'désactivé' => 'badge-muted'];

// nombre de messages en attente de validation
$pending = count(array_filter($messages, fn($m) => $m->getMessageStatus() === 'en attente'));
$token = urlencode($_SESSION['token']);
?>

<section class="page-head">
    <div>
        <h1>Modération des messages</h1>
        <p>
            <?= count($messages) ?> message<?= count($messages) > 1 ? 's' : '' ?> au total,
            <?= $pending ?> en attente
        </p>
    </div>
    <a class="btn btn-outline" href="<?= RACINE_URL ?>/admin">&larr; Articles</a>
</section>

<?php if ($flash): ?>
    <p class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>" role="status">
        <?= htmlspecialchars($flash['message']) ?>
    </p>
<?php endif; ?>

<?php if (empty($messages)): ?>
    <p class="empty">Aucun message pour le moment.</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
            <tr>
                <th>Message</th>
                <th>Article</th>
                <th>Auteur</th>
                <th>Statut</th>
                <th>Création</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($messages as $message):
                $user = $message->getUser();
                $author = $user?->getUserFullName() ?? $user?->getUserLogin() ?? 'Anonyme';
                $article = $message->getArticle();
                $status = $message->getMessageStatus();
                $date = new DateTime($message->getMessageCreateAt());
                $id = $message->getMessageId();
            ?>
                <tr>
                    <td data-label="Message" class="cell-message"><?= nl2br(htmlspecialchars($message->getMessageText())) ?></td>
                    <td data-label="Article">
                        <a href="<?= RACINE_URL ?>/article/<?= htmlspecialchars($article->getArticleSlug()) ?>#messages">
                            <?= htmlspecialchars($article->getArticleTitle()) ?>
                        </a>
                    </td>
                    <td data-label="Auteur"><?= htmlspecialchars($author) ?></td>
                    <td data-label="Statut">
                        <span class="badge <?= $badges[$status] ?? 'badge-muted' ?>"><?= htmlspecialchars($status) ?></span>
                    </td>
                    <td data-label="Création">
                        <time datetime="<?= $date->format('Y-m-d\TH:i') ?>"><?= $date->format('d/m/Y H:i') ?></time>
                    </td>
                    <td class="cell-actions">
                        <?php if ($status !== 'publié'): ?>
                            <a href="<?= RACINE_URL ?>/admin/message-publish/<?= $id ?>?token=<?= $token ?>">Publier</a>
                        <?php endif; ?>
                        <?php if ($status !== 'désactivé'): ?>
                            <a href="<?= RACINE_URL ?>/admin/message-disable/<?= $id ?>?token=<?= $token ?>">Désactiver</a>
                        <?php endif; ?>
                        <a class="link-danger"
                           href="<?= RACINE_URL ?>/admin/message-delete/<?= $id ?>?token=<?= $token ?>"
                           onclick="return confirm('Supprimer définitivement ce message ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
require RACINE_PATH . '/view/inc/footer.view.php';
