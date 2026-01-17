<?php
/**
 * Social Service - Friends & Marriage
 */

require_once __DIR__ . '/../core/DatabaseManager.php';

class SocialService
{
    private DatabaseManager $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    /**
     * Get marriage status
     */
    public function getMarriageStatus(int $playerId): array
    {
        $marriage = $this->db->selectOne(
            'player',
            "SELECT * FROM marriage WHERE pid1 = ? OR pid2 = ?",
            [$playerId, $playerId]
        );

        if (!$marriage || !$marriage['is_married']) {
            return ['is_married' => false];
        }

        // Get partner ID
        $partnerId = ($marriage['pid1'] == $playerId) ? $marriage['pid2'] : $marriage['pid1'];

        // Get partner info
        $partner = $this->db->selectOne(
            'player',
            "SELECT name, level, job FROM player WHERE id = ?",
            [$partnerId]
        );

        if (!$partner) {
            return ['is_married' => false];
        }

        return [
            'is_married' => true,
            'partner_name' => $partner['name'],
            'partner_level' => (int) $partner['level'],
            'partner_job' => $this->getJobName((int) $partner['job']),
            'love_point' => (int) ($marriage['love_point'] ?? 0),
            'married_since' => date('Y-m-d', $marriage['time']),
            'duration_days' => floor((time() - $marriage['time']) / 86400)
        ];
    }

    /**
     * Get friend list
     */
    public function getFriendList(string $accountName): array
    {
        $friends = $this->db->select(
            'player',
            "SELECT companion FROM messenger_list WHERE account = ?",
            [$accountName]
        );

        return array_map(function ($f) {
            return [
                'account' => $f['companion'],
                'status' => 'offline' // Can't determine online status without game server
            ];
        }, $friends);
    }

    /**
     * Get job name
     */
    private function getJobName(int $job): string
    {
        $names = [
            0 => 'Savaşçı (E)',
            1 => 'Savaşçı (K)',
            2 => 'Ninja (E)',
            3 => 'Ninja (K)',
            4 => 'Sura (E)',
            5 => 'Sura (K)',
            6 => 'Şaman (E)',
            7 => 'Şaman (K)',
            8 => 'Lycan'
        ];
        return $names[$job] ?? 'Bilinmiyor';
    }
}
