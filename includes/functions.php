<?php
// Public site helper functions

function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function getCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
    return $stmt->fetchAll();
}

function getCategoryById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND is_active = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getProductById($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getProductsByCategory($pdo, $categoryId, $limit = 6) {
    $stmt = $pdo->prepare("
        SELECT p.* FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ? AND p.is_active = 1 AND c.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$categoryId, $limit]);
    return $stmt->fetchAll();
}

function getFeaturedProducts($pdo, $limit = 4) {
    $stmt = $pdo->prepare("
        SELECT p.* FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.featured = 1 AND p.is_active = 1 AND c.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function formatPrice($price) {
    return 'Ush ' . number_format($price, 0, '.', ',');
}

function getVariants($variantValues) {
    if (empty($variantValues)) {
        return [];
    }
    $decoded = json_decode($variantValues, true);
    return is_array($decoded) ? $decoded : [];
}

// Cart Functions
function getCartSessionId() {
    if (!isset($_SESSION['cart_session_id'])) {
        $_SESSION['cart_session_id'] = session_id();
    }
    return $_SESSION['cart_session_id'];
}

function getCartCount($pdo) {
    $sessionId = getCartSessionId();
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE user_id = ?");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE session_id = ?");
        $stmt->execute([$sessionId]);
    }

    return (int)$stmt->fetchColumn();
}

function addToCart($pdo, $productId, $quantity = 1, $variant = null) {
    $sessionId = getCartSessionId();
    $userId = $_SESSION['user_id'] ?? null;

    // Check if item already in cart
    if ($userId) {
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? AND variant = ?");
        $stmt->execute([$userId, $productId, $variant]);
    } else {
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE session_id = ? AND product_id = ? AND variant = ?");
        $stmt->execute([$sessionId, $productId, $variant]);
    }

    $existing = $stmt->fetch();

    if ($existing) {
        // Update quantity
        $newQty = $existing['quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQty, $existing['id']]);
    } else {
        // Insert new
        $stmt = $pdo->prepare("INSERT INTO cart_items (session_id, user_id, product_id, quantity, variant) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$sessionId, $userId, $productId, $quantity, $variant]);
    }
}

function getCartItems($pdo) {
    $sessionId = getCartSessionId();
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        $stmt = $pdo->prepare("
            SELECT c.*, p.name, p.price, p.image1
            FROM cart_items c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, p.name, p.price, p.image1
            FROM cart_items c
            JOIN products p ON c.product_id = p.id
            WHERE c.session_id = ?
        ");
        $stmt->execute([$sessionId]);
    }

    return $stmt->fetchAll();
}

// Promotion Functions
function getActivePromotions($pdo) {
    $stmt = $pdo->query("SELECT * FROM promotions WHERE active = 1 ORDER BY display_order ASC");
    return $stmt->fetchAll();
}

// New Arrivals (recent products)
function getNewArrivals($pdo, $limit = 24) {
    $stmt = $pdo->prepare("
        SELECT p.* FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1 AND c.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Best Selling (featured products)
function getBestSelling($pdo, $limit = 24) {
    $stmt = $pdo->prepare("
        SELECT p.* FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.featured = 1 AND p.is_active = 1 AND c.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Sponsored Products
function getSponsoredProducts($pdo, $limit = 24) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.* FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_sponsored = 1 AND p.is_active = 1 AND c.is_active = 1
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Return empty array if column doesn't exist yet to prevent site crash
        return [];
    }
}

// Get all products with pagination
function getAllProducts($pdo, $page = 1, $perPage = 24, $categoryId = null) {
    $offset = ($page - 1) * $perPage;

    if ($categoryId) {
        $stmt = $pdo->prepare("
            SELECT p.* FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.category_id = ? AND p.is_active = 1 AND c.is_active = 1
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$categoryId, $perPage, $offset]);
    } else {
        $stmt = $pdo->prepare("
            SELECT p.* FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_active = 1 AND c.is_active = 1
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$perPage, $offset]);
    }

    return $stmt->fetchAll();
}

function getTotalProductCount($pdo, $categoryId = null) {
    if ($categoryId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$categoryId]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    }
    return (int)$stmt->fetchColumn();
}

// Transfer cart items from session to user account
function transferCartToUser($pdo, $userId) {
    $sessionId = getCartSessionId();

    // Get all session cart items
    $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE session_id = ? AND user_id IS NULL");
    $stmt->execute([$sessionId]);
    $sessionItems = $stmt->fetchAll();

    foreach ($sessionItems as $item) {
        // Check if user already has this item
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? AND variant = ?");
        $stmt->execute([$userId, $item['product_id'], $item['variant']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update quantity
            $newQty = $existing['quantity'] + $item['quantity'];
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $stmt->execute([$newQty, $existing['id']]);
        } else {
            // Transfer item to user
            $stmt = $pdo->prepare("UPDATE cart_items SET user_id = ? WHERE id = ?");
            $stmt->execute([$userId, $item['id']]);
        }
    }

    // Clean up any remaining session items
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE session_id = ? AND user_id IS NULL");
    $stmt->execute([$sessionId]);
}

// Remove item from cart
function removeFromCart($pdo, $cartItemId) {
    $sessionId = getCartSessionId();
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$cartItemId, $userId]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND session_id = ?");
        $stmt->execute([$cartItemId, $sessionId]);
    }
}

// Update cart item quantity
function updateCartQuantity($pdo, $cartItemId, $quantity) {
    $sessionId = getCartSessionId();
    $userId = $_SESSION['user_id'] ?? null;

    if ($quantity <= 0) {
        removeFromCart($pdo, $cartItemId);
        return;
    }

    if ($userId) {
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cartItemId, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND session_id = ?");
        $stmt->execute([$quantity, $cartItemId, $sessionId]);
    }
}

// Get cart total
function getCartTotal($pdo) {
    $items = getCartItems($pdo);
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

// Get related products (same category, excluding current product)
function getRelatedProducts($pdo, $productId, $categoryId, $limit = 4) {
    $stmt = $pdo->prepare("
        SELECT * FROM products
        WHERE category_id = ? AND id != ?
        ORDER BY RAND()
        LIMIT ?
    ");
    $stmt->execute([$categoryId, $productId, $limit]);
    return $stmt->fetchAll();
}

// Get hot deals (random featured products)
function getHotDeals($pdo, $limit = 4) {
    $stmt = $pdo->prepare("
        SELECT * FROM products
        WHERE featured = 1
        ORDER BY RAND()
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Recently Viewed Functions
function addToRecentlyViewed($pdo, $productId) {
    $sessionId = getCartSessionId();
    $userId = $_SESSION['user_id'] ?? null;

    try {
        // Check if already viewed recently
        if ($userId) {
            $stmt = $pdo->prepare("SELECT id FROM recently_viewed WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM recently_viewed WHERE session_id = ? AND product_id = ?");
            $stmt->execute([$sessionId, $productId]);
        }

        $existing = $stmt->fetch();

        if ($existing) {
            // Update viewed_at timestamp
            $stmt = $pdo->prepare("UPDATE recently_viewed SET viewed_at = NOW() WHERE id = ?");
            $stmt->execute([$existing['id']]);
        } else {
            // Insert new view
            $stmt = $pdo->prepare("INSERT INTO recently_viewed (session_id, user_id, product_id) VALUES (?, ?, ?)");
            $stmt->execute([$sessionId, $userId, $productId]);
        }

        // Clean up old entries (keep only last 20 per user/session)
        if ($userId) {
            $pdo->prepare("
                DELETE FROM recently_viewed
                WHERE user_id = ?
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM recently_viewed
                        WHERE user_id = ?
                        ORDER BY viewed_at DESC
                        LIMIT 20
                    ) tmp
                )
            ")->execute([$userId, $userId]);
        } else {
            $pdo->prepare("
                DELETE FROM recently_viewed
                WHERE session_id = ?
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM recently_viewed
                        WHERE session_id = ?
                        ORDER BY viewed_at DESC
                        LIMIT 20
                    ) tmp
                )
            ")->execute([$sessionId, $sessionId]);
        }
    } catch (Exception $e) {
        // Silently fail if table doesn't exist yet
        error_log("Recently viewed error: " . $e->getMessage());
    }
}

function getRecentlyViewed($pdo, $limit = 24, $excludeProductId = null) {
    $sessionId = getCartSessionId();
    $userId = $_SESSION['user_id'] ?? null;

    try {
        if ($userId) {
            $query = "
                SELECT p.*
                FROM recently_viewed rv
                JOIN products p ON rv.product_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE rv.user_id = ? AND p.is_active = 1 AND c.is_active = 1
            ";
            $params = [$userId];
        } else {
            $query = "
                SELECT p.*
                FROM recently_viewed rv
                JOIN products p ON rv.product_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE rv.session_id = ? AND p.is_active = 1 AND c.is_active = 1
            ";
            $params = [$sessionId];
        }

        if ($excludeProductId) {
            $query .= " AND p.id != ?";
            $params[] = $excludeProductId;
        }

        $query .= " ORDER BY rv.viewed_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        // Return empty array if table doesn't exist
        return [];
    }
}

function renderStarRating($rating, $size = '14px') {
    $html = '<div style="display: flex; align-items: center; gap: 2px;">';
    $rating = (float)$rating;
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            // Full star
            $html .= '<span style="color: #ffc107; font-size: ' . $size . ';">★</span>';
        } elseif ($i - 0.5 <= $rating) {
            // Half star (using full star for now as simple char, or could use CSS gradient)
            // For better visual, we can use a different character or just color it fully if > 0.5
            $html .= '<span style="color: #ffc107; font-size: ' . $size . ';">★</span>';
        } else {
            // Empty star
            $html .= '<span style="color: #ddd; font-size: ' . $size . ';">★</span>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

// Wishlist Functions
function getWishlistItems($pdo, $userId) {
    if (!$userId) return [];
    
    $stmt = $pdo->prepare("
        SELECT w.*, p.name, p.price, p.image1, p.rating, p.compare_price 
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function isInWishlist($pdo, $userId, $productId) {
    if (!$userId) return false;
    
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    return (bool)$stmt->fetch();
}
