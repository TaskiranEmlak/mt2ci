/**
* Shop Service - IkarusShop Support
*/

require_once __DIR__ . '/../core/DatabaseManager.php';

class ShopService {
private DatabaseManager $db;

public function __construct() {
$this->db = DatabaseManager::getInstance();
}

/**
* Get player's offline shop
*/
public function getPlayerShop(int $playerId): array {
// Check if player has an offline shop
$shop = $this->db->selectOne('player',
"SELECT * FROM ikashop_offlineshop WHERE owner = ?",
[$playerId]
);

if (!$shop) {
return [
'has_shop' => false,
'shop_name' => null,
'total_items' => 0,
'total_value' => '0 Yang',
'items' => []
];
}

// Get player name for shop name
$player = $this->db->selectOne('player',
"SELECT name FROM player WHERE id = ?",
[$playerId]
);

// Get items from safebox (ikashop uses safebox for offline shop items)
$items = $this->db->select('player',
"SELECT * FROM ikashop_safebox WHERE player_id = ?",
[$playerId]
);

$formattedItems = [];
$totalValue = 0;

foreach ($items as $item) {
$vnum = $item['vnum'] ?? $item['item_vnum'] ?? 0;
$count = $item['count'] ?? 1;
$price = $item['price'] ?? 0;

$formattedItems[] = [
'name' => "Item #" . $vnum,
'vnum' => $vnum,
'count' => $count,
'price' => (float)$price,
'price_formatted' => number_format($price, 0, ',', '.') . ' Yang',
'attributes' => []
];

$totalValue += $price * $count;
}

return [
'has_shop' => true,
'shop_name' => ($player['name'] ?? 'Oyuncu') . "'nın Pazarı",
'total_items' => count($formattedItems),
'total_value' => number_format($totalValue, 0, ',', '.') . ' Yang',
'items' => $formattedItems,
'duration' => (int)($shop['duration'] ?? 0),
'map' => (int)($shop['map'] ?? 0)
];
}

/**
* Get shop summary for dashboard
*/
public function getShopSummary(int $playerId): array {
$shop = $this->getPlayerShop($playerId);

if (!$shop['has_shop']) {
return [
'has_shop' => false
];
}

return [
'has_shop' => true,
'shop_name' => $shop['shop_name'],
'total_items' => $shop['total_items'],
'total_value' => $shop['total_value']
];
}
}