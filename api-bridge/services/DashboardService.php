<?php
/**
 * Metin2 Web Panel - Dashboard Service
 * Aggregates data for "Bugün Ne Yapmalıyım?" panel
 */

require_once __DIR__ . '/CharacterService.php';
require_once __DIR__ . '/QuestService.php';
require_once __DIR__ . '/ShopService.php';
require_once __DIR__ . '/EventService.php';

class DashboardService
{
    private CharacterService $characterService;
    private QuestService $questService;
    private ShopService $shopService;
    private EventService $eventService;

    public function __construct()
    {
        $this->characterService = new CharacterService();
        $this->questService = new QuestService();
        $this->shopService = new ShopService();
        $this->eventService = new EventService();
    }

    /**
     * Get complete dashboard data
     */
    public function getDashboardData(int $accountId): array
    {
        // Get all characters
        $characters = $this->characterService->getCharactersByAccountId($accountId);
        $mainChar = !empty($characters) ? $characters[0] : null;

        // Get character summary  
        $characterSummary = [
            'total_characters' => count($characters),
            'main_character' => $mainChar
        ];

        // Get shop summary (if character exists)
        $shopSummary = ['has_shop' => false];
        if ($mainChar) {
            $shopSummary = $this->shopService->getShopSummary($mainChar['id']);
        }

        // Get quest data if main character exists
        $biologist = ['enabled' => false];
        $dungeons = [];

        if ($mainChar) {
            $biologist = $this->questService->getBiologistStatus($mainChar['id']);
            $dungeons = $this->questService->getDungeonCooldowns($mainChar['id']);
        }

        // Get events
        $events = $this->eventService->getEvents();

        // Generate "Bugün Ne Yapmalıyım?" recommendations
        $todoList = $this->generateTodoList($mainChar, $biologist, $dungeons, $events);

        return [
            'character_summary' => $characterSummary,
            'shop_summary' => $shopSummary,
            'biologist' => $biologist,
            'dungeons' => $dungeons,
            'quick_stats' => [
                'total_characters' => count($characters),
                'items_in_shop' => $shopSummary['total_items'] ?? 0,
                'available_dungeons' => count(array_filter($dungeons, fn($d) => $d['available'])),
                'active_events_count' => count($events['active'] ?? [])
            ],
            'todo_list' => $todoList,
            'active_events' => $events['active'] ?? []
        ];
    }

    /**
     * Generate \"Bugün Ne Yapmalıyım?\" recommendations
     */
    private function generateTodoList(?array $mainChar, array $biologist, array $dungeons, array $events): array
    {
        $todos = [];

        // Biologist check
        if ($biologist['enabled'] && ($biologist['can_deliver'] ?? false)) {
            $todos[] = [
                'priority' => 'high',
                'icon' => '🧪',
                'title' => 'Biyolog Teslimata Hazır',
                'description' => "Aşama: {$biologist['stage_name']}"
            ];
        }

        // Dungeon checks
        foreach ($dungeons as $dungeon) {
            if ($dungeon['available']) {
                $todos[] = [
                    'priority' => 'medium',
                    'icon' => '⚔️',
                    'title' => "{$dungeon['name']} Müsait",
                    'description' => 'Günlük zindan hakkın var'
                ];
            }
        }

        // Active events
        foreach ($events['active'] ?? [] as $event) {
            $todos[] = [
                'priority' => 'high',
                'icon' => '🔥',
                'title' => "Etkinlik: {$event['name']}",
                'description' => $event['description'] ?? ''
            ];
        }

        return $todos;
    }
}
