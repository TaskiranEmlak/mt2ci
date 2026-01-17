<?php
/**
 * Item Proto Service - Real Item Names
 * Provides vnum to name mapping with caching
 */

require_once __DIR__ . '/../core/DatabaseManager.php';

class ItemProtoService
{
    private DatabaseManager $db;
    private static ?array $protoCache = null;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    /**
     * Load all item proto data (cached)
     */
    private function loadItemProto(): void
    {
        if (self::$protoCache !== null) {
            return; // Already loaded
        }

        $items = $this->db->select(
            'player',
            "SELECT vnum, locale_name FROM item_proto",
            []
        );

        self::$protoCache = [];
        foreach ($items as $item) {
            self::$protoCache[(int) $item['vnum']] = $item['locale_name'];
        }
    }

    /**
     * Get item name by vnum
     */
    public function getItemName(int $vnum): string
    {
        $this->loadItemProto();
        return self::$protoCache[$vnum] ?? "Item #{$vnum}";
    }

    /**
     * Get multiple item names
     */
    public function getItemNames(array $vnums): array
    {
        $this->loadItemProto();
        $result = [];

        foreach ($vnums as $vnum) {
            $result[$vnum] = self::$protoCache[$vnum] ?? "Item #{$vnum}";
        }

        return $result;
    }

    /**
     * Search items by name
     */
    public function searchItems(string $query, int $limit = 20): array
    {
        $this->loadItemProto();
        $query = strtolower($query);
        $results = [];

        foreach (self::$protoCache as $vnum => $name) {
            if (stripos($name, $query) !== false) {
                $results[] = [
                    'vnum' => $vnum,
                    'name' => $name
                ];

                if (count($results) >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }
}
