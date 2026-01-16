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
        // Get main character
        $mainChar = $this->characterService->getMainCharacter($accountId);
        $totalGold = $this->characterService->getTotalGold($accountId);

        // Get shop data
        $shop = $this->shopService->getShopByAccountId($accountId);

        // Get events
        $events = $this->eventService->getAllEvents();

        // Get quest data if main character exists
        $biologist = ['enabled' => false];
        $dungeons = [];
        $dailyQuests = [];

        if ($mainChar) {
            $biologist = $this->questService->getBiologistStatus($mainChar['id']);
            $dungeons = $this->questService->getDungeonCooldowns($mainChar['id']);
            $dailyQuests = $this->questService->getDailyQuestStatus($mainChar['id']);
        }

        // Generate "Bugün Ne Yapmalıyım?" recommendations
        $todoList = $this->generateTodoList($mainChar, $biologist, $dungeons, $dailyQuests, $events);

        return [
            'timestamp' => date('Y-m-d H:i:s'),

            // Character Summary
            'character_summary' => [
                'total_characters' => count($this->characterService->getCharactersByAccountId($accountId)),
                'main_character' => $mainChar ? [
                    'id' => $mainChar['id'],
                    'name' => $mainChar['name'],
                    'level' => $mainChar['level'],
                    'job_name' => $mainChar['job_name'],
                    'exp_percent' => $mainChar['exp_percent'],
                ] : null,
                'total_gold' => $totalGold['combined']
            ],

            // Shop Summary
            'shop_summary' => [
                'has_shop' => $shop['has_shop'],
                'shop_name' => $shop['name'] ?? null,
                'total_items' => $shop['total_items'] ?? 0,
                'total_value' => $shop['total_value_formatted'] ?? '0 Yang',
                'gold_earned' => $shop['gold_earned_formatted'] ?? null
            ],

            // Biologist Status
            'biologist' => $biologist,

            // Dungeon Cooldowns
            'dungeons' => $dungeons,

            // Daily Quests
            'daily_quests' => $dailyQuests,

            // Active Events
            'active_events' => $events['active'] ?? [],
            'upcoming_events' => $events['upcoming'] ?? [],

            // "Bugün Ne Yapmalıyım?" List
            'todo_list' => $todoList,

            // Quick Stats
            'quick_stats' => [
                'items_in_shop' => $shop['total_items'] ?? 0,
                'biologist_ready' => $biologist['can_deliver'] ?? false,
                'available_dungeons' => count(array_filter($dungeons, fn($d) => $d['available'])),
                'active_events_count' => count($events['active'] ?? [])
            ]
        ];
    }

    /**
     * Generate "Bugün Ne Yapmalıyım?" recommendations
     */
    private function generateTodoList(?array $mainChar, array $biologist, array $dungeons, array $dailyQuests, array $events): array
    {
        $todos = [];

        // Biologist check
        if ($biologist['enabled'] && ($biologist['can_deliver'] ?? false)) {
            $todos[] = [
                'priority' => 'high',
                'icon' => '🧪',
                'title' => 'Biyolog Teslimata Hazır',
                'description' => "Aşama: {$biologist['stage_name']}",
                'action' => 'Biyoloğa git ve teslimatı yap'
            ];
        } elseif ($biologist['enabled'] && !($biologist['can_deliver'] ?? true)) {
            $todos[] = [
                'priority' => 'info',
                'icon' => '⏳',
                'title' => 'Biyolog Bekleniyor',
                'description' => "Kalan: {$biologist['remaining_formatted']}",
                'action' => null
            ];
        }

        // Dungeon checks
        foreach ($dungeons as $dungeon) {
            if ($dungeon['available']) {
                $todos[] = [
                    'priority' => 'medium',
                    'icon' => '⚔️',
                    'title' => "{$dungeon['name']} Müsait",
                    'description' => 'Günlük zindan hakkın var',
                    'action' => 'Zindana gir ve tamamla'
                ];
            }
        }

        // Active events
        foreach ($events['active'] ?? [] as $event) {
            $todos[] = [
                'priority' => 'high',
                'icon' => '🔥',
                'title' => "Etkinlik: {$event['name']}",
                'description' => $event['description'] ?? '',
                'action' => 'Etkinlikten faydalan'
            ];
        }

        // Daily quests not completed
        foreach ($dailyQuests as $quest) {
            if (!($quest['completed'] ?? true)) {
                $todos[] = [
                    'priority' => 'low',
                    'icon' => '📋',
                    'title' => "Günlük Görev: {$quest['name']}",
                    'description' => $quest['status'] ?? '',
                    'action' => 'Görevi tamamla'
                ];
            }
        }

        // Sort by priority
        usort($todos, function ($a, $b) {
            $order = ['high' => 0, 'medium' => 1, 'low' => 2, 'info' => 3];
            return ($order[$a['priority']] ?? 9) - ($order[$b['priority']] ?? 9);
        });

        return $todos;
    }
}
