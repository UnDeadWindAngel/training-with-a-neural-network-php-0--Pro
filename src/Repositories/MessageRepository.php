<?php
namespace src\Repositories;

use PDO;
use src\Models\Message;

class MessageRepository implements MessageRepositoryInterface
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(array $columns = ['*'])
    {
        $columns = implode(', ', $columns);
        $stmt = $this->db->query("SELECT {$columns} FROM messages ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?Message
    {
        $stmt = $this->db->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        $message = new Message();

        $message->setId($data['id']);
        $message->setName($data['name']);
        $message->setMessage($data['message']);
        $message->setCreatedAt($data['created_at']);
        $message->setIpAddress($data['ip_address']);

        return $message;
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO messages (name, message, ip_address) VALUES (:name, :message, :ip_address)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'message' => $data['message'],
            'ip_address' => $data['ip_address'] ?? $_SERVER['REMOTE_ADDR']
        ]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data)
    {
        $sql = "UPDATE messages SET message = :message WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'message' => $data['message']
        ]);
    }

    public function delete(int $id)
    {
        $stmt = $this->db->prepare("DELETE FROM messages WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findBy(array $criteria)
    {
        $where = [];
        $params = [];

        foreach ($criteria as $key => $value) {
            $where[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $whereClause = implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT * FROM messages WHERE {$whereClause}");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function search(string $query)
    {
        $searchTerm = "%{$query}%";
        $stmt = $this->db->prepare("SELECT * FROM messages WHERE message LIKE :query OR name LIKE :query ORDER BY created_at DESC");
        $stmt->execute(['query' => $searchTerm]);
        return $stmt->fetchAll();
    }

    public function paginate(int $perPage = 10, int $page = 1)
    {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("SELECT * FROM messages ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $messages = $stmt->fetchAll();

        // Получаем общее количество
        $totalStmt = $this->db->query("SELECT COUNT(*) FROM messages");
        $total = $totalStmt->fetchColumn();
        $totalPages = ceil($total / $perPage);

        return [
            'data' => $messages,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages
        ];
    }
}