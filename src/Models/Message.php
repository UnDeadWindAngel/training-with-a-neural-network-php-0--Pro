<?php

namespace src\Models;

use PDO;

class Message
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll($search = '', $limit = 10, $offset = 0)
    {

        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $sql = "SELECT * FROM messages WHERE message LIKE ? OR name LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $searchParam, PDO::PARAM_STR);
            $stmt->bindValue(2, $searchParam, PDO::PARAM_STR);
            $stmt->bindValue(3, $limit, PDO::PARAM_INT);
            $stmt->bindValue(4, $offset, PDO::PARAM_INT);
        } else {
            $sql = "SELECT * FROM messages ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }
    public function getById($id = 0)
    {
        if(!empty($id)){
            $searchParam = $id;
            $sql = "SELECT * FROM messages WHERE messages.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $searchParam, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll();
        }
        return false;
    }

    public function getCount($search = '')
    {
        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $sql = "SELECT COUNT(*) FROM messages WHERE message LIKE ? OR name LIKE ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $searchParam, PDO::PARAM_STR);
            $stmt->bindValue(2, $searchParam, PDO::PARAM_STR);
        } else {
            $sql = "SELECT COUNT(*) FROM messages";
            $stmt = $this->db->prepare($sql);
        }
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function create($name, $message, $ip)
    {
        $sql = "INSERT INTO messages (name, message, ip_address) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, trim($name), PDO::PARAM_STR);
        $stmt->bindValue(2, trim($message), PDO::PARAM_STR);
        $stmt->bindValue(3, $ip, PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function delete($id)
    {

        $sql = "DELETE FROM messages WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function update($id, $newMessage)
    {
        $sql = "UPDATE messages SET message = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, trim($newMessage), PDO::PARAM_STR);
        $stmt->bindValue(2, $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}