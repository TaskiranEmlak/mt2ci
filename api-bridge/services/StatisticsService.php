<?php
/**
 * Statistics Service - Playtime, Levels, Gold
 */

require_once __DIR__ . '/../core/DatabaseManager.php';

class StatisticsService
{
    private DatabaseManager $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    /**
     * Get total playtime
     */
    public function getTotalPlaytime(int $accountId): array
    {
        $time = $this->db->selectOne(
            'account',
            "SELECT SUM(LEN) as total FROM GameTime WHERE account_id = ?",
            [$accountId]
        );

        $totalSeconds = (int) ($time['total'] ?? 0);
        $hours = floor($totalSeconds / 3600);
        $days = floor($hours / 24);

        return [
            'total_seconds' => $totalSeconds,
            'total_hours' => $hours,
            'total_days' => $days,
            'formatted' => "{$days} Gün, " . ($hours % 24) . " Saat"
        ];
    }

    /**
     * Get level progression
     */
    public function getLevelProgression(int $playerId, int $limit = 20): array
    {
        $levels = $this->db->select(
            'log',
            "SELECT level, time FROM levellog WHERE pid = ? ORDER BY time DESC LIMIT ?",
            [$playerId, $limit]
        );

        return array_map(function ($l) {
            return [
                'level' => (int) $l['level'],
                'date' => date('Y-m-d H:i', strtotime($l['time']))
            ];
        }, array_reverse($levels));
    }

    /**
     * Get gold statistics
     */
    public function getGoldStatistics(int $playerId): array
    {
        // Gold earned (positive values)
        $earned = $this->db->selectOne(
            'log',
            "SELECT SUM(gold) as total FROM goldlog WHERE pid = ? AND gold > 0",
            [$playerId]
        );

        // Gold spent (negative values)
        $spent = $this->db->selectOne(
            'log',
            "SELECT SUM(ABS(gold)) as total FROM goldlog WHERE pid = ? AND gold < 0",
            [$playerId]
        );

        $totalEarned = (float) ($earned['total'] ?? 0);
        $totalSpent = (float) ($spent['total'] ?? 0);

        return [
            'total_earned' => $totalEarned,
            'total_earned_formatted' => number_format($totalEarned, 0, ',', '.') . ' Yang',
            'total_spent' => $totalSpent,
            'total_spent_formatted' => number_format($totalSpent, 0, ',', '.') . ' Yang',
            'net' => $totalEarned - $totalSpent,
            'net_formatted' => number_format($totalEarned - $totalSpent, 0, ',', '.') . ' Yang'
        ];
    }

    /**
     * Get refine statistics
     */
    public function getRefineStatistics(int $playerId): array
    {
        $refines = $this->db->select(
            'log',
            "SELECT result FROM refinelog WHERE pid = ? LIMIT 1000",
            [$playerId]
        );

        $total = count($refines);
        $success = count(array_filter($refines, fn($r) => $r['result'] == 1));
        $failed = $total - $success;

        return [
            'total_attempts' => $total,
            'successful' => $success,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0
        ];
    }

    /**
     * Get fishing statistics
     */
    public function getFishingStatistics(int $playerId): array
    {
        $fish = $this->db->selectOne(
            'log',
            "SELECT COUNT(*) as total FROM fish_log WHERE pid = ?",
            [$playerId]
        );

        return [
            'total_fish_caught' => (int) ($fish['total'] ?? 0)
        ];
    }
}
