<?php
/**
 * Metin2 Web Panel - Shop Service
 * Handles offline shop data retrieval
 * Supports both Great Offline Shop and Premium Private Shop systems
 */

require_once __DIR__ . '/../core/DatabaseManager.php';

class ShopService
{
    private DatabaseManager $db;
    private string $shopSystem = 'unknown';

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
        $this->detectShopSystem();
    }

    /**
     * Detect which offline shop system is installed
     */
    private function detectShopSystem(): void
    {
        // Check for Premium Private Shop (player_shop + player_shop_items)
        if ($this->db->tableExists('player', 'player_shop')) {
            $this->shopSystem = 'premium';
            return;
        }

        // Check for Great Offline Shop (offline_shop_npc)
        if ($this->db->tableExists('player', 'offline_shop_npc')) {
            $this->shopSystem = 'great';
            return;
        }

        // Check for other variants
        if ($this->db->tableExists('player', 'offline_shop')) {
            $this->shopSystem = 'basic';
            return;
        }

        $this->shopSystem = 'none';
    }

    /**
     * Get shop data for an account
     */
    public function getShopByAccountId(int $accountId): array
    {
        // First get all character IDs for this account
        $characters = $this->db->select(
            'player',
            "SELECT id, name FROM player WHERE account_id = ? AND name != ''",
            [$accountId]
        );

        if (empty($characters)) {
            return $this->emptyShopResponse();
        }

        $playerIds = array_column($characters, 'id');
        $playerNames = [];
        foreach ($characters as $c) {
            $playerNames[$c['id']] = $c['name'];
        }

        switch ($this->shopSystem) {
            case 'premium':
                return $this->getPremiumShop($playerIds, $playerNames);
            case 'great':
                return $this->getGreatShop($playerIds, $playerNames);
            case 'basic':
                return $this->getBasicShop($playerIds, $playerNames);
            default:
                return $this->emptyShopResponse();
        }
    }

    /**
     * Get Premium Private Shop data
     */
    private function getPremiumShop(array $playerIds, array $playerNames): array
    {
        $placeholders = implode(',', array_fill(0, count($playerIds), '?'));

        $shop = $this->db->selectOne(
            'player',
            "SELECT * FROM player_shop WHERE player_id IN ($placeholders)",
            $playerIds
        );

        if (!$shop) {
            return $this->emptyShopResponse();
        }

        // Get items
        $items = $this->db->select(
            'player',
            "SELECT * FROM player_shop_items WHERE shop_id = ?",
            [$shop['id']]
        );

        $activeItems = [];
        $soldItems = [];
        $totalValue = 0;

        foreach ($items as $item) {
            $formatted = $this->formatShopItem($item);
            if ($item['sold'] ?? false) {
                $soldItems[] = $formatted;
            } else {
                $activeItems[] = $formatted;
                $totalValue += $formatted['total_price'];
            }
        }

        return [
            'has_shop' => true,
            'system' => 'Premium Private Shop',
            'owner' => $playerNames[$shop['player_id']] ?? 'Bilinmiyor',
            'name' => $shop['name'] ?? 'Pazar',
            'opened_at' => $shop['created_at'] ?? $shop['time'] ?? null,
            'hours_open' => $this->calculateHoursOpen($shop['created_at'] ?? $shop['time'] ?? null),
            'items' => $activeItems,
            'sold_items' => $soldItems,
            'total_items' => count($activeItems),
            'total_sold' => count($soldItems),
            'total_value' => $totalValue,
            'total_value_formatted' => number_format($totalValue, 0, ',', '.') . ' Yang'
        ];
    }

    /**
     * Get Great Offline Shop data
     */
    private function getGreatShop(array $playerIds, array $playerNames): array
    {
        $placeholders = implode(',', array_fill(0, count($playerIds), '?'));

        $shop = $this->db->selectOne(
            'player',
            "SELECT * FROM offline_shop_npc WHERE owner_id IN ($placeholders)",
            $playerIds
        );

        if (!$shop) {
            return $this->emptyShopResponse();
        }

        // Get items from offline_shop_item
        $items = $this->db->select(
            'player',
            "SELECT * FROM offline_shop_item WHERE shop_id = ? OR owner_id = ?",
            [$shop['id'] ?? 0, $shop['owner_id'] ?? 0]
        );

        $activeItems = [];
        $totalValue = 0;

        foreach ($items as $item) {
            $formatted = $this->formatShopItem($item);
            $activeItems[] = $formatted;
            $totalValue += $formatted['total_price'];
        }

        // Gold earned (stored on the NPC)
        $goldEarned = (float) ($shop['gold'] ?? 0);

        return [
            'has_shop' => true,
            'system' => 'Great Offline Shop',
            'owner' => $playerNames[$shop['owner_id']] ?? 'Bilinmiyor',
            'name' => $shop['sign'] ?? 'Pazar',
            'opened_at' => isset($shop['time']) ? date('Y-m-d H:i:s', $shop['time']) : null,
            'hours_open' => isset($shop['time']) ? round((time() - $shop['time']) / 3600, 1) : null,
            'items' => $activeItems,
            'sold_items' => [],
            'total_items' => count($activeItems),
            'total_sold' => 0,
            'total_value' => $totalValue,
            'total_value_formatted' => number_format($totalValue, 0, ',', '.') . ' Yang',
            'gold_earned' => $goldEarned,
            'gold_earned_formatted' => number_format($goldEarned, 0, ',', '.') . ' Yang'
        ];
    }

    /**
     * Get basic offline shop data
     */
    private function getBasicShop(array $playerIds, array $playerNames): array
    {
        $placeholders = implode(',', array_fill(0, count($playerIds), '?'));

        $shop = $this->db->selectOne(
            'player',
            "SELECT * FROM offline_shop WHERE owner_id IN ($placeholders) OR player_id IN ($placeholders)",
            array_merge($playerIds, $playerIds)
        );

        if (!$shop) {
            return $this->emptyShopResponse();
        }

        return [
            'has_shop' => true,
            'system' => 'Basic Offline Shop',
            'owner' => $playerNames[$shop['owner_id'] ?? $shop['player_id'] ?? 0] ?? 'Bilinmiyor',
            'name' => $shop['name'] ?? $shop['sign'] ?? 'Pazar',
            'items' => [],
            'sold_items' => [],
            'total_items' => 0,
            'total_sold' => 0,
            'total_value' => 0,
            'total_value_formatted' => '0 Yang'
        ];
    }

    /**
     * Format shop item for display
     */
    private function formatShopItem(array $item): array
    {
        $vnum = (int) ($item['vnum'] ?? $item['item_vnum'] ?? 0);
        $count = (int) ($item['count'] ?? $item['amount'] ?? 1);
        $price = (float) ($item['price'] ?? $item['cost'] ?? 0);

        return [
            'id' => $item['id'] ?? null,
            'vnum' => $vnum,
            'name' => "Item #$vnum", // TODO: item_proto tablosundan çekilmeli
            'count' => $count,
            'price' => $price,
            'price_formatted' => number_format($price, 0, ',', '.') . ' Yang',
            'total_price' => $price * $count,
            'socket0' => $item['socket0'] ?? null,
            'socket1' => $item['socket1'] ?? null,
            'socket2' => $item['socket2'] ?? null,
            'attributes' => $this->extractAttributes($item)
        ];
    }

    /**
     * Extract item attributes
     */
    private function extractAttributes(array $item): array
    {
        $attrs = [];
        for ($i = 0; $i <= 6; $i++) {
            $type = $item["attrtype$i"] ?? 0;
            $value = $item["attrvalue$i"] ?? 0;
            if ($type > 0 && $value != 0) {
                $attrs[] = [
                    'type' => $type,
                    'value' => $value,
                    'name' => $this->getAttributeName($type)
                ];
            }
        }
        return $attrs;
    }

    /**
     * Get attribute name
     */
    private function getAttributeName(int $type): string
    {
        $names = [
            1 => 'Max HP',
            2 => 'Max SP',
            7 => 'Saldırı Hızı',
            8 => 'Hareket Hızı',
            15 => 'Kritik Vuruş',
            16 => 'Delici Vuruş',
            17 => 'Yarı İnsan',
            29 => 'Kılıç Savunması',
            53 => 'Saldırı Değeri'
        ];
        return $names[$type] ?? "Bonus $type";
    }

    /**
     * Calculate hours since shop opened
     */
    private function calculateHoursOpen($timestamp): ?float
    {
        if (!$timestamp)
            return null;

        if (is_numeric($timestamp)) {
            $opened = $timestamp;
        } else {
            $opened = strtotime($timestamp);
        }

        if (!$opened)
            return null;
        return round((time() - $opened) / 3600, 1);
    }

    /**
     * Get shop sales history
     */
    public function getSalesHistory(int $accountId, int $limit = 50): array
    {
        $characters = $this->db->select(
            'player',
            "SELECT id FROM player WHERE account_id = ?",
            [$accountId]
        );

        if (empty($characters))
            return [];

        $playerIds = array_column($characters, 'id');
        $placeholders = implode(',', array_fill(0, count($playerIds), '?'));

        // Try common log table names
        $logTables = ['shop_log', 'offline_shop_log', 'player_shop_log'];

        foreach ($logTables as $table) {
            if ($this->db->tableExists('player', $table)) {
                return $this->db->select(
                    'player',
                    "SELECT * FROM $table WHERE seller_id IN ($placeholders) ORDER BY time DESC LIMIT ?",
                    array_merge($playerIds, [$limit])
                );
            }
        }

        return [];
    }

    /**
     * Empty shop response
     */
    private function emptyShopResponse(): array
    {
        return [
            'has_shop' => false,
            'system' => $this->shopSystem,
            'owner' => null,
            'name' => null,
            'items' => [],
            'sold_items' => [],
            'total_items' => 0,
            'total_sold' => 0,
            'total_value' => 0,
            'total_value_formatted' => '0 Yang'
        ];
    }

    /**
     * Get shop system type
     */
    public function getShopSystem(): string
    {
        return $this->shopSystem;
    }
}
