<?php
namespace src\Repositories;

use PDO;

class UserRepository implements UserRepositoryInterface
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(array $columns = ['*'])
    {
        $columns = implode(', ', $columns);
        $stmt = $this->db->query("SELECT {$columns} FROM users");
        return $stmt->fetchAll();
    }

    public function find(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO users (mail, login, password, ip_address) VALUES (:mail, :login, :password, :ip_address)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'mail' => $data['mail'],
            'login' => $data['login'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'ip_address' => $data['ip_address'] ?? $_SERVER['REMOTE_ADDR']
        ]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data)
    {
        // В реальном проекте здесь должна быть более сложная логика обновления
        $sql = "UPDATE users SET login = :login WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'login' => $data['login']
        ]);
    }

    public function delete(int $id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
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
        $stmt = $this->db->prepare("SELECT * FROM users WHERE {$whereClause}");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByEmail(string $email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE mail = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findByLogin(string $login)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        return $stmt->fetch();
    }
}