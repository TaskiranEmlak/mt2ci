<?php
/**
 * Guild Service - Guild System
 */

require_once __DIR__ . '/../core/DatabaseManager.php';

class GuildService
{
    private DatabaseManager $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    /**
     * Get player's guild info
     */
    public function getPlayerGuild(int $playerId): array
    {
        // Check if player is in a guild
        $membership = $this->db->selectOne(
            'player',
            "SELECT guild_id, grade, is_general FROM guild_member WHERE pid = ?",
            [$playerId]
        );

        if (!$membership) {
            return ['has_guild' => false];
        }

        // Get guild details
        $guild = $this->db->selectOne(
            'player',
            "SELECT * FROM guild WHERE id = ?",
            [$membership['guild_id']]
        );

        if (!$guild) {
            return ['has_guild' => false];
        }

        // Get guild master name
        $master = $this->db->selectOne(
            'player',
            "SELECT name FROM player WHERE id = ?",
            [$guild['master']]
        );

        // Get member count
        $memberCount = $this->db->selectOne(
            'player',
            "SELECT COUNT(*) as count FROM guild_member WHERE guild_id = ?",
            [$guild['id']]
        );

        return [
            'has_guild' => true,
            'guild_id' => (int) $guild['id'],
            'name' => $guild['name'],
            'level' => (int) $guild['level'],
            'exp' => (int) $guild['exp'],
            'gold' => (float) $guild['gold'],
            'gold_formatted' => number_format($guild['gold'], 0, ',', '.') . ' Yang',
            'master_name' => $master['name'] ?? 'Unknown',
            'member_count' => (int) ($memberCount['count'] ?? 0),
            'win' => (int) $guild['win'],
            'draw' => (int) $guild['draw'],
            'loss' => (int) $guild['loss'],
            'ladder_point' => (int) $guild['ladder_point'],
            'player_grade' => (int) $membership['grade'],
            'is_general' => (bool) $membership['is_general']
        ];
    }

    /**
     * Get guild members
     */
    public function getGuildMembers(int $guildId): array
    {
        $members = $this->db->select(
            'player',
            "SELECT gm.*, p.name, p.level, p.job 
             FROM guild_member gm 
             JOIN player p ON gm.pid = p.id 
             WHERE gm.guild_id = ? 
             ORDER BY gm.grade ASC, p.level DESC",
            [$guildId]
        );

        return array_map(function ($m) {
            return [
                'name' => $m['name'],
                'level' => (int) $m['level'],
                'job' => $this->getJobName((int) $m['job']),
                'grade' => (int) $m['grade'],
                'grade_name' => $this->getGradeName((int) $m['grade']),
                'is_general' => (bool) $m['is_general']
            ];
        }, $members);
    }

    /**
     * Get grade name
     */
    private function getGradeName(int $grade): string
    {
        $names = [
            1 => 'Lider',
            2 => 'Yardımcı',
            3 => 'Subay',
            4 => 'Komutan',
            5 => 'Üye'
        ];
        return $names[$grade] ?? 'Üye';
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
