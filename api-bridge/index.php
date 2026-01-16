<?php
/**
 * Metin2 Web Panel - API Entry Point
 * 
 * All requests come through here
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Load core
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/DatabaseManager.php';
require_once __DIR__ . '/auth/AuthManager.php';

// Load services
require_once __DIR__ . '/services/CharacterService.php';
require_once __DIR__ . '/services/QuestService.php';
require_once __DIR__ . '/services/ShopService.php';
require_once __DIR__ . '/services/EventService.php';
require_once __DIR__ . '/services/MessageService.php';
require_once __DIR__ . '/services/DashboardService.php';

try {
    $action = $_GET['action'] ?? 'status';
    $auth = new AuthManager();

    switch ($action) {
        // ========================================
        // PUBLIC ENDPOINTS
        // ========================================

        case 'status':
            $db = DatabaseManager::getInstance();
            Response::success([
                'agent_version' => '2.0.0',
                'database_connected' => $db->isConnected(),
                'discovery_log' => $db->getLog(),
                'config' => $db->getConfig(),
                'server' => php_uname()
            ]);
            break;

        case 'login':
            $input = json_decode(file_get_contents('php://input'), true);
            $login = $input['login'] ?? '';
            $password = $input['password'] ?? '';

            $result = $auth->login($login, $password);

            if ($result['success']) {
                Response::success($result);
            } else {
                Response::error($result['error'], 401);
            }
            break;

        // ========================================
        // PROTECTED ENDPOINTS (require auth)
        // ========================================

        case 'characters':
            $accountId = $auth->requireAuth();
            $service = new CharacterService();
            $characters = $service->getCharactersByAccountId($accountId);
            Response::success(['characters' => $characters]);
            break;

        case 'character':
            $accountId = $auth->requireAuth();
            $charId = (int) ($_GET['id'] ?? 0);
            $service = new CharacterService();
            $character = $service->getCharacterById($charId, $accountId);

            if ($character) {
                Response::success(['character' => $character]);
            } else {
                Response::error('Karakter bulunamadı', 404);
            }
            break;

        case 'dashboard':
            $accountId = $auth->requireAuth();
            $service = new DashboardService();
            $dashboard = $service->getDashboardData($accountId);
            Response::success(['dashboard' => $dashboard]);
            break;

        case 'shop':
            $accountId = $auth->requireAuth();
            $service = new ShopService();
            $shop = $service->getShopByAccountId($accountId);
            Response::success(['shop' => $shop]);
            break;

        case 'events':
            $accountId = $auth->requireAuth();
            $service = new EventService();
            $events = $service->getAllEvents();
            Response::success(['events' => $events]);
            break;

        case 'messages':
            $accountId = $auth->requireAuth();
            $charName = $_GET['character'] ?? '';

            if (empty($charName)) {
                Response::error('Karakter adı gerekli', 400);
            }

            // Verify character belongs to account
            $charService = new CharacterService();
            $chars = $charService->getCharactersByAccountId($accountId);
            $found = false;
            foreach ($chars as $c) {
                if ($c['name'] === $charName) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                Response::error('Bu karakter size ait değil', 403);
            }

            $service = new MessageService();
            $messages = $service->getMessageHistory($charName);
            Response::success(['conversations' => $messages]);
            break;

        case 'biologist':
            $accountId = $auth->requireAuth();
            $charId = (int) ($_GET['character_id'] ?? 0);

            if (!$charId) {
                Response::error('Karakter ID gerekli', 400);
            }

            // Verify ownership
            $charService = new CharacterService();
            $char = $charService->getCharacterById($charId, $accountId);

            if (!$char) {
                Response::error('Karakter bulunamadı', 404);
            }

            $questService = new QuestService();
            $biologist = $questService->getBiologistStatus($charId);
            Response::success(['biologist' => $biologist]);
            break;

        case 'dungeons':
            $accountId = $auth->requireAuth();
            $charId = (int) ($_GET['character_id'] ?? 0);

            if (!$charId) {
                Response::error('Karakter ID gerekli', 400);
            }

            $charService = new CharacterService();
            $char = $charService->getCharacterById($charId, $accountId);

            if (!$char) {
                Response::error('Karakter bulunamadı', 404);
            }

            $questService = new QuestService();
            $dungeons = $questService->getDungeonCooldowns($charId);
            Response::success(['dungeons' => $dungeons]);
            break;

        case 'ranking':
            $accountId = $auth->requireAuth();
            $type = $_GET['type'] ?? 'level';

            require_once __DIR__ . '/services/RankingService.php';
            $service = new RankingService();

            switch ($type) {
                case 'level':
                    $ranking = $service->getTopLevel();
                    break;
                case 'gold':
                    $ranking = $service->getTopGold();
                    break;
                case 'alignment':
                    $ranking = $service->getTopAlignment();
                    break;
                default:
                    Response::error('Geçersiz sıralama tipi', 400);
            }

            Response::success(['ranking' => $ranking]);
            break;

        default:
            Response::error('Geçersiz işlem', 400);
            break;
    }

} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}
