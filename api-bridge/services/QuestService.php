<?php
/**
 * Metin2 Web Panel - Quest Service
 * Reads quest flags from player.quest table
 * Used for "Bugün Ne Yapmalıyım?" panel
 */

require_once __DIR__ . '/../core/DatabaseManager.php';

class QuestService
{
    private DatabaseManager $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    /**
     * Get all quest flags for a character
     */
    public function getQuestFlags(int $playerId): array
    {
        return $this->db->select(
            'player',
            "SELECT szName, lValue FROM quest WHERE dwPID = ?",
            [$playerId]
        );
    }

    /**
     * Get specific quest flag value
     */
    public function getFlag(int $playerId, string $flagName): ?int
    {
        $result = $this->db->selectOne(
            'player',
            "SELECT lValue FROM quest WHERE dwPID = ? AND szName = ?",
            [$playerId, $flagName]
        );

        return $result ? (int) $result['lValue'] : null;
    }

    /**
     * Get biologist status
     */
    public function getBiologistStatus(int $playerId): array
    {
        // Common biologist flag names
        $durationFlags = [
            'biolog.duration',
            'biolog.wait_until',
            'biyolog.sure',
            'bio_quest.duration',
            'biologist.cooldown'
        ];
        $countFlags = [
            'biolog.count',
            'biolog.collect_count',
            'biyolog.miktar',
            'bio_quest.count',
            'biologist.delivered'
        ];
        $levelFlags = [
            'biolog.level',
            'biolog.stage',
            'biyolog.seviye',
            'bio_quest.level',
            'biologist.stage'
        ];

        $duration = null;
        $count = 0;
        $level = 1;

        // Get all biolog-related flags
        $flags = $this->db->select(
            'player',
            "SELECT szName, lValue FROM quest WHERE dwPID = ? AND szName LIKE '%biolog%'",
            [$playerId]
        );

        foreach ($flags as $flag) {
            $name = strtolower($flag['szName']);
            $value = (int) $flag['lValue'];

            if (strpos($name, 'duration') !== false || strpos($name, 'wait') !== false || strpos($name, 'sure') !== false || strpos($name, 'cooldown') !== false) {
                $duration = $value;
            } elseif (strpos($name, 'count') !== false || strpos($name, 'miktar') !== false || strpos($name, 'deliver') !== false) {
                $count = $value;
            } elseif (strpos($name, 'level') !== false || strpos($name, 'stage') !== false || strpos($name, 'seviye') !== false) {
                $level = $value;
            }
        }

        // If no flags found at all, biologist is not active
        if (empty($flags)) {
            return [
                'enabled' => false
            ];
        }

        $now = time();
        $canDeliver = true;
        $remainingSeconds = 0;

        if ($duration !== null && $duration > $now) {
            $canDeliver = false;
            $remainingSeconds = $duration - $now;
        }

        return [
            'enabled' => true,
            'level' => $level,
            'stage_name' => $this->getBiologStageName($level),
            'delivered_today' => $count,
            'can_deliver' => $canDeliver,
            'remaining_seconds' => $remainingSeconds,
            'remaining_formatted' => $this->formatDuration($remainingSeconds),
            'next_delivery' => $duration ? date('Y-m-d H:i:s', $duration) : null
        ];
    }

    /**
     * Get biologist stage name
     */
    private function getBiologStageName(int $level): string
    {
        $stages = [
            1 => 'Ork Dişi (Lv.30)',
            2 => 'Çoban Köpek Dişi (Lv.40)',
            3 => 'Kurt Dişi (Lv.50)',
            4 => 'Ayı Pençesi (Lv.60)',
            5 => 'Yabani Domuz Dişi (Lv.70)',
            6 => 'Düşmüş Yılan Kabuğu (Lv.80)',
            7 => 'Kertenkele Kuyruğu (Lv.85)',
            8 => 'Şeytan Boynuzu (Lv.90)',
            // ... more stages
        ];

        return $stages[$level] ?? "Aşama $level";
    }

    /**
     * Get dungeon cooldowns
     */
    public function getDungeonCooldowns(int $playerId): array
    {
        // Common dungeon flag patterns
        $dungeonPatterns = [
            'demon_tower' => ['name' => 'Şeytan Kulesi', 'cooldown' => 3600], // 1 hour
            'spider_dungeon' => ['name' => 'Örümcek Zindanı', 'cooldown' => 7200],
            'azrael' => ['name' => 'Azrael Mabedi', 'cooldown' => 86400], // 24 hours
            'meley' => ['name' => 'Meley Zindanı', 'cooldown' => 86400],
            'razador' => ['name' => 'Razador', 'cooldown' => 604800], // Weekly
            'jotun' => ['name' => 'Jotun Thrym', 'cooldown' => 604800],
        ];

        // Get all dungeon-related flags
        $flags = $this->db->select(
            'player',
            "SELECT szName, lValue FROM quest WHERE dwPID = ? AND (szName LIKE '%dungeon%' OR szName LIKE '%zindan%' OR szName LIKE '%last_entry%')",
            [$playerId]
        );

        $dungeons = [];
        $now = time();
        $today = strtotime('today');

        foreach ($dungeonPatterns as $key => $info) {
            $lastEntry = null;

            // Find matching flag
            foreach ($flags as $flag) {
                if (stripos($flag['szName'], $key) !== false) {
                    $lastEntry = (int) $flag['lValue'];
                    break;
                }
            }

            $available = true;
            $remainingSeconds = 0;

            if ($lastEntry !== null) {
                // If cooldown is 24h (daily), check if done today
                if ($info['cooldown'] == 86400) {
                    $available = $lastEntry < $today;
                } else {
                    $nextAvailable = $lastEntry + $info['cooldown'];
                    $available = $now >= $nextAvailable;
                    if (!$available) {
                        $remainingSeconds = $nextAvailable - $now;
                    }
                }
            }

            $dungeons[] = [
                'key' => $key,
                'name' => $info['name'],
                'available' => $available,
                'status' => $available ? '✅ Müsait' : '❌ Tamamlandı',
                'remaining_seconds' => $remainingSeconds,
                'remaining_formatted' => $this->formatDuration($remainingSeconds),
                'last_entry' => $lastEntry ? date('Y-m-d H:i:s', $lastEntry) : null
            ];
        }

        return $dungeons;
    }

    /**
     * Get daily quest status
     */
    public function getDailyQuestStatus(int $playerId): array
    {
        // Common daily quest flag patterns
        $flags = $this->db->select(
            'player',
            "SELECT szName, lValue FROM quest WHERE dwPID = ? AND (szName LIKE '%daily%' OR szName LIKE '%gunluk%')",
            [$playerId]
        );

        $quests = [];

        foreach ($flags as $flag) {
            $name = $flag['szName'];
            $value = (int) $flag['lValue'];

            // Parse quest name
            if (strpos($name, 'target') !== false || strpos($name, 'hedef') !== false) {
                // This is a target (how many to kill)
                continue;
            }

            if (strpos($name, 'count') !== false || strpos($name, 'miktar') !== false) {
                // This is progress
                continue;
            }

            if (strpos($name, 'completed') !== false || strpos($name, 'done') !== false) {
                $quests[] = [
                    'name' => $this->beautifyQuestName($name),
                    'completed' => $value > 0,
                    'status' => $value > 0 ? '✅ Tamamlandı' : '⏳ Devam Ediyor'
                ];
            }
        }

        return $quests;
    }

    /**
     * Format duration to human readable
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0)
            return 'Hazır';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%d Saat %d Dakika', $hours, $minutes);
        }
        return sprintf('%d Dakika', $minutes);
    }

    /**
     * Beautify quest flag name
     */
    private function beautifyQuestName(string $name): string
    {
        $name = str_replace(['_', '.', 'daily', 'gunluk', 'quest'], [' ', ' ', '', '', ''], $name);
        return ucwords(trim($name));
    }
}
