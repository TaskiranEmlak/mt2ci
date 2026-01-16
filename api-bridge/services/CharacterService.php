<?php
/**
 * Metin2 Web Panel - Character Service
 * Handles character data retrieval and formatting
 */

require_once __DIR__ . '/../core/DatabaseManager.php';

class CharacterService
{
    private DatabaseManager $db;

    // Job (class) name mapping
    private const JOB_NAMES = [
        0 => 'Savaşçı (E)',   // Warrior Male
        1 => 'Savaşçı (K)',   // Warrior Female
        2 => 'Ninja (E)',     // Ninja Male
        3 => 'Ninja (K)',     // Ninja Female
        4 => 'Sura (E)',      // Sura Male
        5 => 'Sura (K)',      // Sura Female
        6 => 'Şaman (E)',     // Shaman Male
        7 => 'Şaman (K)',     // Shaman Female
        8 => 'Lycan'          // Lycan (Wolfman)
    ];

    // Alignment rank mapping
    private const ALIGNMENT_RANKS = [
        [-20000, -10000, 'Zalim'],
        [-10000, -1000, 'Kötü'],
        [-1000, 0, 'Düşman'],
        [0, 1000, 'Yansız'],
        [1000, 5000, 'İyi'],
        [5000, 10000, 'Kahramanca'],
        [10000, 20000, 'Kahraman']
    ];

    // EXP table for calculating percentage
    private const EXP_TABLE = [
        1 => 300,
        2 => 800,
        3 => 1500,
        4 => 2500,
        5 => 4300,
        6 => 7200,
        7 => 11000,
        8 => 17000,
        9 => 24000,
        10 => 33000,
        11 => 43000,
        12 => 58000,
        13 => 76000,
        14 => 100000,
        15 => 130000,
        16 => 169000,
        17 => 219000,
        18 => 283000,
        19 => 365000,
        20 => 472000,
        21 => 610000,
        22 => 705000,
        23 => 813000,
        24 => 937000,
        25 => 1077000,
        26 => 1237000,
        27 => 1418000,
        28 => 1624000,
        29 => 1857000,
        30 => 2122000,
        31 => 2421000,
        32 => 2761000,
        33 => 3145000,
        34 => 3580000,
        35 => 4073000,
        36 => 4632000,
        37 => 5194000,
        38 => 5717000,
        39 => 6264000,
        40 => 6837000,
        41 => 7600000,
        42 => 8274000,
        43 => 8990000,
        44 => 9753000,
        45 => 10560000,
        46 => 11410000,
        47 => 12320000,
        48 => 13270000,
        49 => 14280000,
        50 => 15340000,
        51 => 16870000,
        52 => 18960000,
        53 => 19980000,
        54 => 21420000,
        55 => 22930000,
        56 => 24580000,
        57 => 26200000,
        58 => 27960000,
        59 => 29800000,
        60 => 32780000,
        61 => 36060000,
        62 => 39670000,
        63 => 43640000,
        64 => 48000000,
        65 => 52800000,
        66 => 58080000,
        67 => 63890000,
        68 => 70280000,
        69 => 77310000,
        70 => 85040000,
        71 => 93540000,
        72 => 102900000,
        73 => 113200000,
        74 => 124500000,
        75 => 137000000,
        76 => 150700000,
        77 => 165700000,
        78 => 236990000,
        79 => 260650000,
        80 => 286780000,
        81 => 315000000,
        82 => 346970000,
        83 => 381680000,
        84 => 419770000,
        85 => 461760000,
        86 => 508040000,
        87 => 558740000,
        88 => 614640000,
        89 => 676130000,
        90 => 743730000,
        91 => 1041222000,
        92 => 1145344200,
        93 => 1259878620,
        94 => 1385866482,
        95 => 1524453130,
        96 => 1676898443,
        97 => 1844588288,
        98 => 2029047116,
        99 => 2050000000,
        100 => 2150000000,
        // Champion levels use different exp
    ];

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    /**
     * Get all characters for an account
     */
    public function getCharactersByAccountId(int $accountId): array
    {
        $characters = $this->db->select(
            'player',
            "SELECT * FROM player WHERE account_id = ? AND name != ''",
            [$accountId]
        );

        return array_map([$this, 'formatCharacter'], $characters);
    }

    /**
     * Get single character by ID (with account ownership check)
     */
    public function getCharacterById(int $characterId, int $accountId): ?array
    {
        $character = $this->db->selectOne(
            'player',
            "SELECT * FROM player WHERE id = ? AND account_id = ?",
            [$characterId, $accountId]
        );

        return $character ? $this->formatCharacter($character) : null;
    }

    /**
     * Format raw character data for display
     */
    private function formatCharacter(array $char): array
    {
        $level = (int) ($char['level'] ?? 1);
        $exp = (float) ($char['exp'] ?? 0);
        $gold = (float) ($char['gold'] ?? 0);
        $playtime = (int) ($char['playtime'] ?? 0);
        $alignment = (int) ($char['alignment'] ?? 0);
        $job = (int) ($char['job'] ?? 0);

        return [
            'id' => (int) $char['id'],
            'name' => $char['name'],
            'level' => $level,
            'exp' => $exp,
            'exp_percent' => $this->calculateExpPercent($level, $exp),
            'exp_needed' => $this->getExpNeeded($level),
            'gold' => $gold,
            'gold_formatted' => number_format($gold, 0, ',', '.') . ' Yang',
            'won' => (int) ($char['cheque'] ?? $char['won'] ?? 0),
            'job' => $job,
            'job_name' => self::JOB_NAMES[$job] ?? 'Bilinmiyor',
            'alignment' => $alignment,
            'alignment_rank' => $this->getAlignmentRank($alignment),
            'hp' => (int) ($char['hp'] ?? 0),
            'mp' => (int) ($char['sp'] ?? $char['mp'] ?? 0),
            'st' => (int) ($char['st'] ?? 0),       // Strength
            'ht' => (int) ($char['ht'] ?? 0),       // Vitality
            'dx' => (int) ($char['dx'] ?? 0),       // Dexterity
            'iq' => (int) ($char['iq'] ?? 0),       // Intelligence
            'playtime' => $playtime,
            'playtime_formatted' => $this->formatPlaytime($playtime),
            'last_play' => $char['last_play'] ?? null,
            'map_index' => (int) ($char['map_index'] ?? 0),
            'x' => (int) ($char['x'] ?? 0),
            'y' => (int) ($char['y'] ?? 0),
            // Champion level (if exists)
            'champion_level' => (int) ($char['myht'] ?? 0),
            'champion_exp' => (float) ($char['myht_exp'] ?? 0),
        ];
    }

    /**
     * Calculate EXP percentage
     */
    private function calculateExpPercent(int $level, float $currentExp): float
    {
        $needed = self::EXP_TABLE[$level] ?? 2000000000;
        if ($needed <= 0)
            return 100;
        $percent = ($currentExp / $needed) * 100;
        return round(max(0, min(100, $percent)), 2);
    }

    /**
     * Get EXP needed for current level
     */
    private function getExpNeeded(int $level): float
    {
        return (float) (self::EXP_TABLE[$level] ?? 2000000000);
    }

    /**
     * Format playtime (stored as minutes)
     */
    private function formatPlaytime(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        if ($days > 0) {
            return sprintf('%d Gün %d Saat', $days, $remainingHours);
        }
        return sprintf('%d Saat', $hours);
    }

    /**
     * Get alignment rank name
     */
    private function getAlignmentRank(int $alignment): string
    {
        foreach (self::ALIGNMENT_RANKS as [$min, $max, $name]) {
            if ($alignment >= $min && $alignment < $max) {
                return $name;
            }
        }
        return $alignment >= 10000 ? 'Kahraman' : 'Zalim';
    }

    /**
     * Get main character (highest level)
     */
    public function getMainCharacter(int $accountId): ?array
    {
        $character = $this->db->selectOne(
            'player',
            "SELECT * FROM player WHERE account_id = ? AND name != '' ORDER BY level DESC LIMIT 1",
            [$accountId]
        );

        return $character ? $this->formatCharacter($character) : null;
    }

    /**
     * Get total gold across all characters
     */
    public function getTotalGold(int $accountId): array
    {
        $result = $this->db->selectOne(
            'player',
            "SELECT SUM(gold) as total_gold, SUM(COALESCE(cheque, 0)) as total_won FROM player WHERE account_id = ?",
            [$accountId]
        );

        $gold = (float) ($result['total_gold'] ?? 0);
        $won = (int) ($result['total_won'] ?? 0);

        return [
            'gold' => $gold,
            'gold_formatted' => number_format($gold, 0, ',', '.') . ' Yang',
            'won' => $won,
            'combined' => $won > 0 ? "$won Won | " . number_format($gold, 0, ',', '.') . ' Yang' : number_format($gold, 0, ',', '.') . ' Yang'
        ];
    }
}
