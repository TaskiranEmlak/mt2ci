<?php
/**
 * Event Service - Real Event Data
 */

require_once __DIR__ . '/../core/DatabaseManager.php';

class EventService
{
    private DatabaseManager $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    /**
     * Get active and upcoming events
     */
    public function getEvents(): array
    {
        // Check if common.event table exists
        $hasEventTable = $this->db->tableExists('common', 'event');

        if (!$hasEventTable) {
            // No event table, return empty
            return [
                'active' => [],
                'upcoming' => []
            ];
        }

        $now = time();

        // Get active events
        $activeEvents = $this->db->select(
            'common',
            "SELECT * FROM event WHERE start_time <= ? AND end_time>= ? ORDER BY start_time ASC",
            [$now, $now]
        );

        // Get upcoming events
        $upcomingEvents = $this->db->select(
            'common',
            "SELECT * FROM event WHERE start_time > ? ORDER BY start_time ASC LIMIT 10",
            [$now]
        );

        $active = [];
        foreach ($activeEvents as $event) {
            $remaining = (int) $event['end_time'] - $now;
            $active[] = [
                'name' => $event['name'] ?? 'Etkinlik',
                'description' => $event['description'] ?? '',
                'icon' => '🔥',
                'start_time' => (int) $event['start_time'],
                'end_time' => (int) $event['end_time'],
                'remaining' => $this->formatDuration($remaining),
                'rate' => $event['rate'] ?? 1
            ];
        }

        $upcoming = [];
        foreach ($upcomingEvents as $event) {
            $timeUntil = (int) $event['start_time'] - $now;
            $upcoming[] = [
                'name' => $event['name'] ?? 'Etkinlik',
                'description' => $event['description'] ?? '',
                'icon' => '📅',
                'start_time' => (int) $event['start_time'],
                'starts_in' => $this->formatDuration($timeUntil)
            ];
        }

        return [
            'active' => $active,
            'upcoming' => $upcoming
        ];
    }

    /**
     * Format duration to human-readable format
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 0)
            return '0 dakika';
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $parts = [];
        if ($days > 0)
            $parts[] = "{$days} gün";
        if ($hours > 0)
            $parts[] = "{$hours} saat";
        if ($minutes > 0)
            $parts[] = "{$minutes} dakika";

        return !empty($parts) ? implode(' ', $parts) : '1 dakika';
    }
}