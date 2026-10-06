<?php
// path: model/manager/MessageManager.php
// typage strict
declare(strict_types=1);

namespace model\manager;

use model\interface\ManagerInterface;
use model\MyPDO;
use model\mapping\MessageMapping;
use model\mapping\UserMapping;
use model\mapping\ArticleMapping;
use Exception;

class MessageManager implements ManagerInterface
{

    protected MyPDO $connect;

    public function __construct(MyPDO $connect)
    {
        $this->connect = $connect;
    }

    // récupération des messages publiés d'un article, du plus ancien au plus récent
    public function getMessagesByArticle(int $articleId): array
    {
        $sql = "SELECT
            m.message_id, m.message_text, m.message_create_at, m.message_status,
            m.user_user_id, m.article_article_id,
            u.user_id, u.user_login, u.user_full_name
            FROM message m
            INNER JOIN user u ON m.user_user_id = u.user_id
            WHERE m.article_article_id = :article_id AND m.message_status='publié'
            ORDER BY m.message_create_at ASC";
        $stmt = $this->connect->prepare($sql);
        $stmt->bindValue(':article_id', $articleId, MyPDO::PARAM_INT);
        try{
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des messages : " . $e->getMessage());
        }

        $messages = $stmt->fetchAll();
        $stmt->closeCursor();

        // création d'un tableau d'objets MessageMapping avec leur auteur
        $result = [];
        foreach ($messages as $message) {
            $mess = new MessageMapping($message);
            $mess->setUser(new UserMapping($message));
            $result[] = $mess;
        }
        return $result;
    }

    // ---------- Administration (CRUD) ----------

    // récupération de tous les messages, quel que soit leur statut
    public function getAllMessagesAdmin(): array
    {
        $sql = "SELECT
            m.message_id, m.message_text, m.message_create_at, m.message_validate_at,
            m.message_status, m.user_user_id, m.article_article_id,
            u.user_id, u.user_login, u.user_full_name,
            a.article_id, a.article_title, a.article_slug
            FROM message m
            INNER JOIN user u ON m.user_user_id = u.user_id
            INNER JOIN article a ON m.article_article_id = a.article_id
            ORDER BY m.message_create_at DESC";
        $stmt = $this->connect->prepare($sql);
        try{
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des messages : " . $e->getMessage());
        }

        $messages = $stmt->fetchAll();
        $stmt->closeCursor();

        $result = [];
        foreach ($messages as $message) {
            $mess = new MessageMapping($message);
            $mess->setUser(new UserMapping($message));
            $mess->setArticle(new ArticleMapping($message));
            $result[] = $mess;
        }
        return $result;
    }

    // récupération d'un message par son id, quel que soit son statut
    public function getMessageById(int $id): ?MessageMapping
    {
        $sql = "SELECT
            m.message_id, m.message_text, m.message_create_at, m.message_validate_at,
            m.message_status, m.user_user_id, m.article_article_id,
            u.user_id, u.user_login, u.user_full_name,
            a.article_id, a.article_title, a.article_slug
            FROM message m
            INNER JOIN user u ON m.user_user_id = u.user_id
            INNER JOIN article a ON m.article_article_id = a.article_id
            WHERE m.message_id = :id";
        $stmt = $this->connect->prepare($sql);
        $stmt->bindValue(':id', $id, MyPDO::PARAM_INT);
        try{
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération du message : " . $e->getMessage());
        }

        if($stmt->rowCount() === 0) {
            return null;
        }
        $message = $stmt->fetch();
        $stmt->closeCursor();

        $mess = new MessageMapping($message);
        $mess->setUser(new UserMapping($message));
        $mess->setArticle(new ArticleMapping($message));
        return $mess;
    }

    // création d'un message, retourne l'id du nouveau message
    public function insertMessage(MessageMapping $message): int
    {
        // statut par défaut si non précisé
        $status = $message->getMessageStatus() ?? MessageMapping::DEFAULT_STATUS;

        // si le message est publié directement, on le valide maintenant
        $sql = "INSERT INTO message
            (message_text, message_status, message_validate_at, user_user_id, article_article_id)
            VALUES (:text, :status, IF(:status_check = 'publié', NOW(), NULL), :user_id, :article_id)";
        $stmt = $this->connect->prepare($sql);
        $stmt->bindValue(':text', $message->getMessageText());
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':status_check', $status);
        $stmt->bindValue(':user_id', $message->getUserUserId(), MyPDO::PARAM_INT);
        $stmt->bindValue(':article_id', $message->getArticleArticleId(), MyPDO::PARAM_INT);
        try{
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la création du message : " . $e->getMessage());
        }

        return (int) $this->connect->lastInsertId();
    }

    // modification d'un message (texte et statut)
    public function updateMessage(MessageMapping $message): bool
    {
        // la date de validation est remplie la première fois que le message passe en "publié"
        $sql = "UPDATE message SET
            message_text = :text,
            message_validate_at = IF(message_validate_at IS NULL AND :status_check = 'publié', NOW(), message_validate_at),
            message_status = :status
            WHERE message_id = :id";
        $stmt = $this->connect->prepare($sql);
        $stmt->bindValue(':text', $message->getMessageText());
        $stmt->bindValue(':status_check', $message->getMessageStatus());
        $stmt->bindValue(':status', $message->getMessageStatus());
        $stmt->bindValue(':id', $message->getMessageId(), MyPDO::PARAM_INT);
        try{
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la modification du message : " . $e->getMessage());
        }

        return true;
    }

    // modification du statut d'un message (modération), retourne false si le message n'existait pas
    public function updateMessageStatus(int $id, string $status): bool
    {
        // validation du statut par le setter du mapping (exception si invalide)
        $message = new MessageMapping(['message_id' => $id, 'message_status' => $status]);

        // la date de validation est remplie la première fois que le message passe en "publié"
        $sql = "UPDATE message SET
            message_validate_at = IF(message_validate_at IS NULL AND :status_check = 'publié', NOW(), message_validate_at),
            message_status = :status
            WHERE message_id = :id";
        $stmt = $this->connect->prepare($sql);
        $stmt->bindValue(':status_check', $message->getMessageStatus());
        $stmt->bindValue(':status', $message->getMessageStatus());
        $stmt->bindValue(':id', $message->getMessageId(), MyPDO::PARAM_INT);
        try{
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la modération du message : " . $e->getMessage());
        }

        // rowCount vaut 0 si le statut était déjà le même : on vérifie donc l'existence
        return $stmt->rowCount() === 1 || $this->getMessageById($id) !== null;
    }

    // suppression d'un message, retourne false si le message n'existait pas
    public function deleteMessage(int $id): bool
    {
        $sql = "DELETE FROM message WHERE message_id = :id";
        $stmt = $this->connect->prepare($sql);
        $stmt->bindValue(':id', $id, MyPDO::PARAM_INT);
        try{
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la suppression du message : " . $e->getMessage());
        }

        return $stmt->rowCount() === 1;
    }
}
