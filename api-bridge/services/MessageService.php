<?php
/**
 * Metin2 Web Panel - Message Service
 * Handles PM (private message) history
 */

require_once __DIR__ . '/../core/DatabaseManager.php';

class MessageService
{
    private DatabaseManager $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    /**
     * Get message history for a character
     */
    public function getMessageHistory(string $characterName, int $limit = 100): array
    {
        // chat_log table exists in srv1_log
        if ($this->db->tableExists('log', 'chat_log')) {
            $messages = $this->db->select(
                'log',
                "SELECT who_name, whom_name, msg, `when` FROM chat_log 
                 WHERE (who_name = ? OR whom_name = ?) 
                 AND type = 'WHISPER'
                 ORDER BY `when` DESC LIMIT ?",
                [$characterName, $characterName, $limit]
            );

            return $this->groupConversations($messages, $characterName);
        }

        return [];
    }

    /**
     * Get conversations grouped by contact
     */
    private function groupConversations(array $messages, string $myName): array
    {
        $conversations = [];

        foreach ($messages as $msg) {
            $otherPerson = $msg['who_name'] === $myName ? $msg['whom_name'] : $msg['who_name'];

            if (!isset($conversations[$otherPerson])) {
                $conversations[$otherPerson] = [
                    'contact' => $otherPerson,
                    'last_message' => null,
                    'last_time' => null,
                    'unread' => 0,
                    'messages' => []
                ];
            }

            $formatted = [
                'from' => $msg['who_name'],
                'to' => $msg['whom_name'],
                'content' => $msg['msg'],
                'time' => $msg['when'],
                'is_mine' => $msg['who_name'] === $myName
            ];

            $conversations[$otherPerson]['messages'][] = $formatted;

            // Update last message
            if (
                !$conversations[$otherPerson]['last_time'] ||
                strtotime($msg['when']) > strtotime($conversations[$otherPerson]['last_time'])
            ) {
                $conversations[$otherPerson]['last_message'] = $formatted['content'];
                $conversations[$otherPerson]['last_time'] = $msg['when'];
            }
        }

        // Sort by last message time
        usort($conversations, function ($a, $b) {
            return strtotime($b['last_time'] ?? 0) - strtotime($a['last_time'] ?? 0);
        });

        return array_values($conversations);
    }

    /**
     * Get messages with specific person
     */
    public function getMessagesWithPerson(string $myName, string $otherName, int $limit = 50): array
    {
        if ($this->db->tableExists('log', 'chat_log')) {
            $messages = $this->db->select(
                'log',
                "SELECT who_name, whom_name, msg, `when` FROM chat_log 
                 WHERE ((who_name = ? AND whom_name = ?) OR (who_name = ? AND whom_name = ?))
                 AND type = 'WHISPER'
                 ORDER BY `when` DESC LIMIT ?",
                [$myName, $otherName, $otherName, $myName, $limit]
            );

            // Reverse to show oldest first
            $messages = array_reverse($messages);

            return array_map(function ($msg) use ($myName) {
                return [
                    'from' => $msg['who_name'],
                    'to' => $msg['whom_name'],
                    'content' => $msg['msg'],
                    'time' => $msg['when'],
                    'is_mine' => $msg['who_name'] === $myName
                ];
            }, $messages);
        }

        return [];
    }

    /**
     * Queue a message to be sent from web to game
     * Requires web_messages table in player database
     */
    public function queueMessage(string $fromName, string $toName, string $content): bool
    {
        // Check if web_messages table exists
        if (!$this->db->tableExists('player', 'web_messages')) {
            // Try to create it
            try {
                $this->db->getConnection('player')->exec("
                    CREATE TABLE IF NOT EXISTS web_messages (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        sender VARCHAR(50) NOT NULL,
                        target VARCHAR(50) NOT NULL,
                        message TEXT NOT NULL,
                        status TINYINT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
            } catch (Exception $e) {
                return false;
            }
        }

        $this->db->execute(
            'player',
            "INSERT INTO web_messages (sender, target, message, status) VALUES (?, ?, ?, 0)",
            [$fromName, $toName, $content]
        );

        return true;
    }
}
