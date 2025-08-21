<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

try {
    $pdo = getConnection();
    $userId = $_SESSION['user_id'];
    
    // Obtener notificaciones basadas en actividad del usuario
    $notifications = [];
    
    // Nuevos contenidos en géneros que le gustan al usuario
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.genre 
        FROM viewing_history vh 
        JOIN content c ON vh.content_id = c.id 
        WHERE vh.user_id = ? 
        ORDER BY vh.last_watched DESC 
        LIMIT 3
    ");
    $stmt->execute([$userId]);
    $favoriteGenres = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($favoriteGenres)) {
        $placeholders = str_repeat('?,', count($favoriteGenres) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT title, type, created_at 
            FROM content 
            WHERE genre IN ($placeholders) 
            AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $stmt->execute($favoriteGenres);
        $newContent = $stmt->fetchAll();
        
        foreach ($newContent as $content) {
            $notifications[] = [
                'id' => uniqid(),
                'type' => 'new_content',
                'title' => 'Nuevo contenido disponible',
                'message' => "Nueva {$content['type']}: {$content['title']}",
                'time' => time_elapsed_string($content['created_at']),
                'icon' => 'fas fa-plus-circle'
            ];
        }
    }
    
    // Contenido popular que no ha visto
    $stmt = $pdo->prepare("
        SELECT c.title, c.type, c.view_count
        FROM content c
        LEFT JOIN viewing_history vh ON c.id = vh.content_id AND vh.user_id = ?
        WHERE vh.id IS NULL 
        AND c.view_count > 500000
        ORDER BY c.view_count DESC
        LIMIT 2
    ");
    $stmt->execute([$userId]);
    $popularContent = $stmt->fetchAll();
    
    foreach ($popularContent as $content) {
        $notifications[] = [
            'id' => uniqid(),
            'type' => 'trending',
            'title' => 'Tendencia popular',
            'message' => "No te pierdas: {$content['title']}",
            'time' => 'Ahora',
            'icon' => 'fas fa-fire'
        ];
    }
    
    // Recordatorios de contenido a medio ver
    $stmt = $pdo->prepare("
        SELECT c.title, c.type, vh.progress
        FROM viewing_history vh
        JOIN content c ON vh.content_id = c.id
        WHERE vh.user_id = ? 
        AND vh.progress > 10 
        AND vh.progress < 90
        AND vh.last_watched > DATE_SUB(NOW(), INTERVAL 3 DAY)
        ORDER BY vh.last_watched DESC
        LIMIT 2
    ");
    $stmt->execute([$userId]);
    $incompleteContent = $stmt->fetchAll();
    
    foreach ($incompleteContent as $content) {
        $notifications[] = [
            'id' => uniqid(),
            'type' => 'continue_watching',
            'title' => 'Continúa viendo',
            'message' => "Continúa viendo {$content['title']} ({$content['progress']}% completado)",
            'time' => 'Hace 2 días',
            'icon' => 'fas fa-play-circle'
        ];
    }
    
    // Si no hay notificaciones, agregar algunas por defecto
    if (empty($notifications)) {
        $notifications = [
            [
                'id' => 'default1',
                'type' => 'welcome',
                'title' => '¡Bienvenido a Netflix!',
                'message' => 'Explora nuestro catálogo de películas y series',
                'time' => 'Ahora',
                'icon' => 'fas fa-star'
            ],
            [
                'id' => 'default2',
                'type' => 'tip',
                'title' => 'Consejo',
                'message' => 'Agrega contenido a tu lista para verlo más tarde',
                'time' => 'Hace 1 hora',
                'icon' => 'fas fa-lightbulb'
            ]
        ];
    }
    
    echo json_encode(['notifications' => array_slice($notifications, 0, 5)]);
    
} catch (Exception $e) {
    error_log("Error en notifications: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'año',
        'm' => 'mes',
        'w' => 'semana',
        'd' => 'día',
        'h' => 'hora',
        'i' => 'minuto',
        's' => 'segundo',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? 'Hace ' . implode(', ', $string) : 'Ahora mismo';
}
?>
