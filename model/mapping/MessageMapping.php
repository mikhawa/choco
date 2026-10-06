<?php
// path: model/mapping/MessageMapping.php
// typage strict
declare(strict_types=1);

namespace model\mapping;

use model\abstract\AbstractMapping;

// Exception si une donnée du message n'est pas valide
use Exception;

use model\mapping\UserMapping;
use model\mapping\ArticleMapping;

class MessageMapping extends AbstractMapping
{
    // propriétés correspondant aux champs de la table `message`
    protected ?int $message_id = null;
    protected ?string $message_text = null;
    protected ?string $message_create_at = null;
    protected ?string $message_validate_at = null;
    protected ?string $message_status = null;
    protected ?int $user_user_id = null;
    protected ?int $article_article_id = null;

    // ajout de l'utilisateur lié lors d'une requête
    protected ?UserMapping $user = null;

    // ajout de l'article lié lors d'une requête
    protected ?ArticleMapping $article = null;

    public function getUser(): ?UserMapping
    {
        return $this->user;
    }

    public function setUser(?UserMapping $user): void
    {
        $this->user = $user;
    }

    public function getArticle(): ?ArticleMapping
    {
        return $this->article;
    }

    public function setArticle(?ArticleMapping $article): void
    {
        $this->article = $article;
    }

    // valeurs autorisées pour `message_status`
    // constante privée pour les statuts de message
    private const STATUS = ['publié', 'en attente', 'désactivé'];

    // statut par défaut d'un nouveau message
    public const DEFAULT_STATUS = 'en attente';

    // liste des statuts autorisés (pour les formulaires)
    public static function getStatusList(): array
    {
        return self::STATUS;
    }

    // Le constructeur est hérité de AbstractMapping, il appelle la méthode hydrate() pour initialiser les propriétés avec les données passées en paramètre.

    // getters

    public function getMessageId(): ?int
    {
        return $this->message_id;
    }

    public function getMessageText(): ?string
    {
        return $this->message_text;
    }

    public function getMessageCreateAt(): ?string
    {
        return $this->message_create_at;
    }

    public function getMessageValidateAt(): ?string
    {
        return $this->message_validate_at;
    }

    public function getMessageStatus(): ?string
    {
        return $this->message_status;
    }

    public function getUserUserId(): ?int
    {
        return $this->user_user_id;
    }

    public function getArticleArticleId(): ?int
    {
        return $this->article_article_id;
    }

    // setters

    public function setMessageId(int|string|null $message_id): void
    {
        // PDO renvoie des chaînes, on convertit en entier
        if ($message_id === null) {
            $this->message_id = null;
            return;
        }
        $message_id = (int) $message_id;
        if ($message_id < 1) {
            throw new Exception("L'id du message doit être positif");
        }
        $this->message_id = $message_id;
    }

    public function setMessageText(?string $message_text): void
    {
        if ($message_text === null) {
            $this->message_text = null;
            return;
        }
        $message_text = trim(strip_tags($message_text));
        if ($message_text === '' || mb_strlen($message_text) > 600) {
            throw new Exception("Le message doit contenir entre 1 et 600 caractères");
        }
        $this->message_text = $message_text;
    }

    public function setMessageCreateAt(?string $message_create_at): void
    {
        $this->message_create_at = $message_create_at;
    }

    public function setMessageValidateAt(?string $message_validate_at): void
    {
        $this->message_validate_at = $message_validate_at;
    }

    public function setMessageStatus(?string $message_status): void
    {
        if ($message_status !== null && !in_array($message_status, self::STATUS, true)) {
            throw new Exception("Statut de message invalide");
        }
        $this->message_status = $message_status;
    }

    public function setUserUserId(int|string|null $user_user_id): void
    {
        if ($user_user_id === null) {
            $this->user_user_id = null;
            return;
        }
        $user_user_id = (int) $user_user_id;
        if ($user_user_id < 1) {
            throw new Exception("L'id de l'utilisateur doit être positif");
        }
        $this->user_user_id = $user_user_id;
    }

    public function setArticleArticleId(int|string|null $article_article_id): void
    {
        if ($article_article_id === null) {
            $this->article_article_id = null;
            return;
        }
        $article_article_id = (int) $article_article_id;
        if ($article_article_id < 1) {
            throw new Exception("L'id de l'article doit être positif");
        }
        $this->article_article_id = $article_article_id;
    }
}
