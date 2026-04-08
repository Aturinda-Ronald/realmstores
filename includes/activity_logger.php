<?php
// Activity Logger - Track user actions, sessions, and analytics

// Get user's real IP address
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Get geolocation from IP using free API
function getGeolocation($ip) {
    // Skip for localhost/private IPs
    if ($ip === '::1' || $ip === '127.0.0.1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        return ['country' => 'Local', 'city' => 'Development'];
    }
    
    try {
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,city";
        $context = stream_context_create([
            'http' => [
                'timeout' => 1,  // Reduced to 1 second
                'ignore_errors' => true
            ]
        ]);
        $response = @file_get_contents($url, false, $context);
        
        if ($response) {
            $data = @json_decode($response, true);
            if ($data && isset($data['status']) && $data['status'] === 'success') {
                return [
                    'country' => $data['country'] ?? 'Unknown',
                    'city' => $data['city'] ?? 'Unknown'
                ];
            }
        }
    } catch (Exception $e) {
        // Silently fail
    }
    
    return ['country' => 'Unknown', 'city' => 'Unknown'];
}

// Parse user agent for device information
function getDeviceInfo() {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Device type
    if (preg_match('/mobile/i', $ua)) {
        $device = 'Mobile';
    } elseif (preg_match('/tablet|ipad/i', $ua)) {
        $device = 'Tablet';
    } else {
        $device = 'Desktop';
    }
    
    // Browser
    if (strpos($ua, 'Edg') !== false) {
        $browser = 'Edge';
    } elseif (strpos($ua, 'Chrome') !== false) {
        $browser = 'Chrome';
    } elseif (strpos($ua, 'Safari') !== false) {
        $browser = 'Safari';
    } elseif (strpos($ua, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($ua, 'MSIE') !== false || strpos($ua, 'Trident') !== false) {
        $browser = 'IE';
    } else {
        $browser = 'Other';
    }
    
    // Operating System
    if (strpos($ua, 'Windows NT 10') !== false) {
        $os = 'Windows 10';
    } elseif (strpos($ua, 'Windows NT 6.3') !== false) {
        $os = 'Windows 8.1';
    } elseif (strpos($ua, 'Windows NT 6.2') !== false) {
        $os = 'Windows 8';
    } elseif (strpos($ua, 'Windows NT 6.1') !== false) {
        $os = 'Windows 7';
    } elseif (strpos($ua, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($ua, 'Mac OS X') !== false) {
        $os = 'macOS';
    } elseif (strpos($ua, 'Linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($ua, 'Android') !== false) {
        $os = 'Android';
    } elseif (strpos($ua, 'iPhone') !== false || strpos($ua, 'iPad') !== false) {
        $os = 'iOS';
    } else {
        $os = 'Other';
    }
    
    return [
        'device' => $device,
        'browser' => $browser,
        'os' => $os
    ];
}

// Initialize or update user session
function initSession($pdo) {
    global $_SESSION;
    
    $sessionId = session_id();
    $userId = $_SESSION['user_id'] ?? null;
    $ip = getUserIP();
    $geo = getGeolocation($ip);
    $deviceInfo = getDeviceInfo();
    
    // Check if session exists
    $stmt = $pdo->prepare("SELECT id FROM user_sessions WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    
    if ($stmt->fetch()) {
        // Update existing session
        $stmt = $pdo->prepare("
            UPDATE user_sessions 
            SET last_seen = NOW(), 
                page_views = page_views + 1,
                user_id = COALESCE(?, user_id)
            WHERE session_id = ?
        ");
        $stmt->execute([$userId, $sessionId]);
    } else {
        // Create new session
        $stmt = $pdo->prepare("
            INSERT INTO user_sessions 
            (session_id, user_id, ip_address, country, city, user_agent, device_type, browser, os, page_views)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $sessionId,
            $userId,
            $ip,
            $geo['country'],
            $geo['city'],
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $deviceInfo['device'],
            $deviceInfo['browser'],
            $deviceInfo['os']
        ]);
    }
}

// Log user activity
function logActivity($pdo, $actionType, $details = []) {
    global $_SESSION;
    
    $sessionId = session_id();
    $userId = $_SESSION['user_id'] ?? null;
    $ip = getUserIP();
    $geo = getGeolocation($ip);
    $deviceInfo = getDeviceInfo();
    
    // Get current page URL
    $pageUrl = $_SERVER['REQUEST_URI'] ?? '';
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    
    $stmt = $pdo->prepare("
        INSERT INTO user_activities 
        (session_id, user_id, action_type, page_url, product_id, order_id, 
         ip_address, country, city, user_agent, device_type, browser, os, referrer)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $sessionId,
        $userId,
        $actionType,
        $pageUrl,
        $details['product_id'] ?? null,
        $details['order_id'] ?? null,
        $ip,
        $geo['country'],
        $geo['city'],
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $deviceInfo['device'],
        $deviceInfo['browser'],
        $deviceInfo['os'],
        $referrer
    ]);
}

// Track page view
function trackPageView($pdo) {
    initSession($pdo);
    logActivity($pdo, 'page_view');
}
?>
