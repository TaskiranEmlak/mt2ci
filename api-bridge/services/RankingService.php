/**
* Ranking Service - Top players by level, gold, alignment
*/

require_once __DIR__ . '/../core/DatabaseManager.php';

class RankingService {
private DatabaseManager $db;

public function __construct() {
$this->db = DatabaseManager::getInstance();
}

/**
* Get top players by level
*/
public function getTopLevel(int $limit = 100): array {
$players = $this->db->select('player',
"SELECT name, level, exp, job, empire FROM player
WHERE name != '' AND level > 0
ORDER BY level DESC, exp DESC
LIMIT ?",
[$limit]
);

return array_map(fn($p, $idx) => [
'rank' => $idx + 1,
'name' => $p['name'],
'level' => (int)$p['level'],
'job' => $this->getJobName((int)$p['job']),
'empire' => $this->getEmpireName((int)($p['empire'] ?? 0))
], $players, array_keys($players));
}

/**
* Get top players by gold
*/
public function getTopGold(int $limit = 100): array {
$players = $this->db->select('player',
"SELECT name, level, gold, job, empire FROM player
WHERE name != ''
ORDER BY gold DESC
LIMIT ?",
[$limit]
);

return array_map(fn($p, $idx) => [
'rank' => $idx + 1,
'name' => $p['name'],
'level' => (int)$p['level'],
'gold' => (float)$p['gold'],
'gold_formatted' => number_format($p['gold'], 0, ',', '.') . ' Yang',
'job' => $this->getJobName((int)$p['job']),
'empire' => $this->getEmpireName((int)($p['empire'] ?? 0))
], $players, array_keys($players));
}

/**
* Get top heroes by alignment
*/
public function getTopAlignment(int $limit = 100): array {
$players = $this->db->select('player',
"SELECT name, level, alignment, job, empire FROM player
WHERE name != ''
ORDER BY alignment DESC
LIMIT ?",
[$limit]
);

return array_map(fn($p, $idx) => [
'rank' => $idx + 1,
'name' => $p['name'],
'level' => (int)$p['level'],
'alignment' => (int)$p['alignment'],
'alignment_rank' => $this->getAlignmentRank((int)$p['alignment']),
'job' => $this->getJobName((int)$p['job']),
'empire' => $this->getEmpireName((int)($p['empire'] ?? 0))
], $players, array_keys($players));
}

/**
* Get job name
*/
private function getJobName(int $job): string {
$names = [
0 => 'Savaşçı (E)', 1 => 'Savaşçı (K)',
2 => 'Ninja (E)', 3 => 'Ninja (K)',
4 => 'Sura (E)', 5 => 'Sura (K)',
6 => 'Şaman (E)', 7 => 'Şaman (K)',
8 => 'Lycan'
];
return $names[$job] ?? 'Bilinmiyor';
}

/**
* Get empire name
*/
private function getEmpireName(int $empire): string {
$names = [0 => '-', 1 => 'Shinsoo', 2 => 'Chunjo', 3 => 'Jinno'];
return $names[$empire] ?? '-';
}

/**
* Get alignment rank
*/
private function getAlignmentRank(int $alignment): string {
if ($alignment >= 10000) return 'Kahraman';
if ($alignment >= 5000) return 'Kahramanca';
if ($alignment >= 1000) return 'İyi';
if ($alignment >= 0) return 'Yansız';
if ($alignment >= -1000) return 'Düşman';
if ($alignment >= -10000) return 'Kötü';
return 'Zalim';
}
}