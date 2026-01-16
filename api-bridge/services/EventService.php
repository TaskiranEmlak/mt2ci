<?php
/**
 * Metin2 Web Panel - Event Service
 * Handles active and upcoming events
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
     * Get all active events
     */
    public function getActiveEvents(): array
    {
        // Try common event table names
        $tables = ['event', 'events', 'event_table', 'server_event'];

        foreach ($tables as $table) {
            if ($this->db->tableExists('common', $table)) {
                $events = $this->db->select(
                    'common',
                    "SELECT * FROM $table WHERE start_time <= NOW() AND end_time >= NOW()",
                    []
                );

                return array_map([$this, 'formatEvent'], $events);
            }
        }

        // No event table found - return sample/static events
        return $this->getStaticEvents();
    }

    /**
     * Get upcoming events
     */
    public function getUpcomingEvents(): array
    {
        $tables = ['event', 'events', 'event_table', 'server_event'];

        foreach ($tables as $table) {
            if ($this->db->tableExists('common', $table)) {
                $events = $this->db->select(
                    'common',
                    "SELECT * FROM $table WHERE start_time > NOW() ORDER BY start_time ASC LIMIT 10",
                    []
                );

                return array_map([$this, 'formatEvent'], $events);
            }
        }

        return [];
    }

    /**
     * Get all events (active + upcoming)
     */
    public function getAllEvents(): array
    {
        return [
            'active' => $this->getActiveEvents(),
            'upcoming' => $this->getUpcomingEvents()
        ];
    }

    /**
     * Format event for display
     */
    private function formatEvent(array $event): array
    {
        $startTime = $event['start_time'] ?? $event['start'] ?? null;
        $endTime = $event['end_time'] ?? $event['end'] ?? null;

        return [
            'id' => $event['id'] ?? null,
            'name' => $event['name'] ?? $event['event_name'] ?? 'Bilinmeyen Etkinlik',
            'description' => $event['description'] ?? $event['desc'] ?? '',
            'type' => $event['type'] ?? $event['event_type'] ?? 'general',
            'rate' => $event['rate'] ?? $event['value'] ?? null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => $this->isActive($startTime, $endTime),
            'remaining' => $this->getRemainingTime($endTime),
            'countdown' => $this->getCountdown($startTime)
        ];
    }

    /**
     * Check if event is currently active
     */
    private function isActive($startTime, $endTime): bool
    {
        $now = time();
        $start = is_numeric($startTime) ? $startTime : strtotime($startTime);
        $end = is_numeric($endTime) ? $endTime : strtotime($endTime);

        return $start <= $now && $end >= $now;
    }

    /**
     * Get remaining time until event ends
     */
    private function getRemainingTime($endTime): ?string
    {
        if (!$endTime)
            return null;

        $end = is_numeric($endTime) ? $endTime : strtotime($endTime);
        $remaining = $end - time();

        if ($remaining <= 0)
            return null;

        $hours = floor($remaining / 3600);
        $minutes = floor(($remaining % 3600) / 60);

        return sprintf('%d Saat %d Dakika', $hours, $minutes);
    }

    /**
     * Get countdown until event starts
     */
    private function getCountdown($startTime): ?string
    {
        if (!$startTime)
            return null;

        $start = is_numeric($startTime) ? $startTime : strtotime($startTime);
        $remaining = $start - time();

        if ($remaining <= 0)
            return null;

        $days = floor($remaining / 86400);
        $hours = floor(($remaining % 86400) / 3600);
        $minutes = floor(($remaining % 3600) / 60);

        if ($days > 0) {
            return sprintf('%d Gün %d Saat', $days, $hours);
        }
        return sprintf('%d Saat %d Dakika', $hours, $minutes);
    }

    /**
     * Get static events (when no DB table exists)
     * These can be configured manually
     */
    private function getStaticEvents(): array
    {
        // Example static events - should be customized per server
        return [
            [
                'id' => 1,
                'name' => 'Exp Etkinliği',
                'description' => '2x EXP',
                'type' => 'exp',
                'rate' => 200,
                'is_active' => false,
                'remaining' => null
            ]
        ];
    }
}
