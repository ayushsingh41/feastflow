<?php
// ============================================================
// includes/functions.php — Core Business Logic
// ============================================================

// ── Products ──────────────────────────────────────────────────
function getProducts(array $filters = []): array {
    $pdo = getPDO();
    $sql = "SELECT p.*, c.name AS category_name, c.color AS cat_color 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            WHERE 1=1";
    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $s = '%' . $filters['search'] . '%';
        $params[] = $s; $params[] = $s;
    }
    if (!empty($filters['category'])) {
        $sql .= " AND p.category_id = ?";
        $params[] = $filters['category'];
    }
    if (!empty($filters['status'])) {
        $sql .= " AND p.status = ?";
        $params[] = $filters['status'];
    } else {
        $sql .= " AND p.status = 'active'";
    }
    if (!empty($filters['featured'])) {
        $sql .= " AND p.is_featured = 1";
    }
    if (!empty($filters['veg'])) {
        $sql .= " AND p.is_veg = 1";
    }
    if (!empty($filters['sort'])) {
        $sorts = ['price_asc' => 'p.price ASC', 'price_desc' => 'p.price DESC',
                  'rating' => 'p.rating DESC', 'popular' => 'p.total_orders DESC',
                  'newest' => 'p.created_at DESC'];
        $sql .= " ORDER BY " . ($sorts[$filters['sort']] ?? 'p.is_featured DESC, p.total_orders DESC');
    } else {
        $sql .= " ORDER BY p.is_featured DESC, p.total_orders DESC";
    }

    if (!empty($filters['limit'])) {
        $sql .= " LIMIT " . (int)$filters['limit'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getProductById(int $id): ?array {
    $stmt = getPDO()->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function createProduct(array $data): int {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, stock, is_featured, is_veg, status) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$data['category_id'], $data['name'], $data['description'], $data['price'], $data['stock'], $data['is_featured'] ?? 0, $data['is_veg'] ?? 0, $data['status'] ?? 'active']);
    return (int)$pdo->lastInsertId();
}

function updateProduct(int $id, array $data): bool {
    $pdo = getPDO();
    $sql = "UPDATE products SET category_id=?, name=?, description=?, price=?, stock=?, is_featured=?, is_veg=?, status=?";
    $params = [$data['category_id'], $data['name'], $data['description'], $data['price'], $data['stock'], $data['is_featured'] ?? 0, $data['is_veg'] ?? 0, $data['status']];
    if (!empty($data['image'])) { $sql .= ", image=?"; $params[] = $data['image']; }
    $sql .= " WHERE id=?"; $params[] = $id;
    return $pdo->prepare($sql)->execute($params);
}

function deleteProduct(int $id): bool {
    return getPDO()->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
}

// ── Categories ────────────────────────────────────────────────
function getCategories(bool $activeOnly = true): array {
    $sql = "SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id" . ($activeOnly ? " WHERE c.status='active'" : "") . " GROUP BY c.id ORDER BY c.sort_order";
    return getPDO()->query($sql)->fetchAll();
}

// ── Cart ──────────────────────────────────────────────────────
function getCart(int $userId): array {
    $stmt = getPDO()->prepare("SELECT c.*, p.name, p.price, p.image, p.stock, (c.quantity * p.price) AS subtotal FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function addToCart(int $userId, int $productId, int $qty = 1): bool {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
    return $stmt->execute([$userId, $productId, $qty, $qty]);
}

function updateCartItem(int $userId, int $productId, int $qty): bool {
    if ($qty <= 0) return removeFromCart($userId, $productId);
    return getPDO()->prepare("UPDATE cart SET quantity=? WHERE user_id=? AND product_id=?")->execute([$qty, $userId, $productId]);
}

function removeFromCart(int $userId, int $productId): bool {
    return getPDO()->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?")->execute([$userId, $productId]);
}

function clearCart(int $userId): bool {
    return getPDO()->prepare("DELETE FROM cart WHERE user_id=?")->execute([$userId]);
}

function getCartCount(int $userId): int {
    $stmt = getPDO()->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id=?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getCartTotal(array $cart): array {
    $subtotal = array_sum(array_column($cart, 'subtotal'));
    $delivery = ($subtotal >= FREE_DELIVERY_ABOVE) ? 0 : DELIVERY_FEE;
    $total    = $subtotal + $delivery;
    return compact('subtotal', 'delivery', 'total');
}

// ── Orders ────────────────────────────────────────────────────
function createOrder(int $userId, array $cart, array $totals, array $data): ?string {
    $pdo = getPDO();
    $num = 'FF-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, subtotal, delivery_fee, discount, total, payment_method, delivery_address, notes) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$userId, $num, $totals['subtotal'], $totals['delivery'], $totals['discount'] ?? 0, $totals['total'], $data['payment_method'], $data['delivery_address'], $data['notes'] ?? null]);
        $orderId = $pdo->lastInsertId();

        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, name, price, quantity, subtotal) VALUES (?,?,?,?,?,?)");
        foreach ($cart as $item) {
            $itemStmt->execute([$orderId, $item['product_id'], $item['name'], $item['price'], $item['quantity'], $item['subtotal']]);
            $pdo->prepare("UPDATE products SET total_orders = total_orders + ? WHERE id=?")->execute([$item['quantity'], $item['product_id']]);
        }

        clearCart($userId);
        $pdo->commit();
        logActivity($userId, 'ORDER_PLACED', "Order {$num} placed");
        return $num;
    } catch (Exception $e) {
        $pdo->rollBack();
        return null;
    }
}

function getOrders(array $filters = []): array {
    $sql = "SELECT o.*, u.name AS customer_name, u.email AS customer_email, COUNT(oi.id) AS item_count FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN order_items oi ON oi.order_id = o.id WHERE 1=1";
    $params = [];

    if (!empty($filters['user_id'])) { $sql .= " AND o.user_id=?"; $params[] = $filters['user_id']; }
    if (!empty($filters['status']))  { $sql .= " AND o.status=?";   $params[] = $filters['status']; }
    if (!empty($filters['search'])) {
        $sql .= " AND (o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $s = '%'.$filters['search'].'%';
        $params[] = $s; $params[] = $s; $params[] = $s;
    }
    if (!empty($filters['date_from'])) { $sql .= " AND DATE(o.created_at) >= ?"; $params[] = $filters['date_from']; }
    if (!empty($filters['date_to']))   { $sql .= " AND DATE(o.created_at) <= ?"; $params[] = $filters['date_to']; }

    $sql .= " GROUP BY o.id ORDER BY o.created_at DESC";

    if (!empty($filters['limit'])) $sql .= " LIMIT " . (int)$filters['limit'];

    $stmt = getPDO()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getOrderById(int $id, ?int $userId = null): ?array {
    $sql = "SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id=?";
    $params = [$id];
    if ($userId) { $sql .= " AND o.user_id=?"; $params[] = $userId; }
    $stmt = getPDO()->prepare($sql);
    $stmt->execute($params);
    $order = $stmt->fetch();
    if (!$order) return null;
    $stmt2 = getPDO()->prepare("SELECT oi.*, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id=?");
    $stmt2->execute([$id]);
    $order['items'] = $stmt2->fetchAll();
    return $order;
}

function updateOrderStatus(int $id, string $status): bool {
    $pdo = getPDO();
    $extra = ($status === 'delivered') ? ", delivered_at=NOW(), payment_status='paid'" : "";
    return $pdo->prepare("UPDATE orders SET status=?{$extra} WHERE id=?")->execute([$status, $id]);
}

// ── Dashboard Stats ───────────────────────────────────────────
function getDashboardStats(): array {
    $pdo = getPDO();
    $stats = [];
    $stats['total_users']    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
    $stats['total_products'] = $pdo->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn();
    $stats['total_orders']   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['total_revenue']  = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered'")->fetchColumn();
    $stats['pending_orders'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
    $stats['today_orders']   = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
    $stats['today_revenue']  = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status='delivered'")->fetchColumn();
    $stats['active_users']   = $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();

    // Revenue last 7 days
    $stmt = $pdo->query("SELECT DATE(created_at) AS date, COALESCE(SUM(total),0) AS revenue, COUNT(*) AS orders FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date");
    $stats['weekly_data'] = $stmt->fetchAll();

    // Top products
    $stmt = $pdo->query("SELECT p.name, p.total_orders, p.price, p.image FROM products p ORDER BY p.total_orders DESC LIMIT 5");
    $stats['top_products'] = $stmt->fetchAll();

    // Order status breakdown
    $stmt = $pdo->query("SELECT status, COUNT(*) AS count FROM orders GROUP BY status");
    $stats['order_status'] = $stmt->fetchAll();

    return $stats;
}

// ── Pagination ────────────────────────────────────────────────
function paginate(string $table, array $filters, int $page, int $perPage = ROWS_PER_PAGE): array {
    $pdo = getPDO();
    // Count
    $count = (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    $totalPages = max(1, ceil($count / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    return ['total' => $count, 'total_pages' => $totalPages, 'current_page' => $page, 'offset' => $offset, 'per_page' => $perPage];
}

// ── Image Upload ──────────────────────────────────────────────
function uploadImage(array $file, string $dir): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > MAX_FILE_SIZE) return null;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ALLOWED_TYPES)) return null;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . strtolower($ext);
    $path     = $dir . $filename;

    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $path)) return null;
    return $filename;
}

// ── Helpers ───────────────────────────────────────────────────
function formatPrice(float $price): string {
    return '₹' . number_format($price, 2);
}

function formatDate(string $date): string {
    return date('d M Y, h:i A', strtotime($date));
}

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
}

function statusBadge(string $status): string {
    $map = [
        'pending'          => ['class' => 'badge-warning',  'label' => 'Pending'],
        'confirmed'        => ['class' => 'badge-info',     'label' => 'Confirmed'],
        'preparing'        => ['class' => 'badge-primary',  'label' => 'Preparing'],
        'out_for_delivery' => ['class' => 'badge-purple',   'label' => 'Out for Delivery'],
        'delivered'        => ['class' => 'badge-success',  'label' => 'Delivered'],
        'cancelled'        => ['class' => 'badge-danger',   'label' => 'Cancelled'],
        'active'           => ['class' => 'badge-success',  'label' => 'Active'],
        'inactive'         => ['class' => 'badge-secondary','label' => 'Inactive'],
        'paid'             => ['class' => 'badge-success',  'label' => 'Paid'],
        'banned'           => ['class' => 'badge-danger',   'label' => 'Banned'],
    ];
    $b = $map[$status] ?? ['class' => 'badge-secondary', 'label' => ucfirst($status)];
    return "<span class=\"badge {$b['class']}\">{$b['label']}</span>";
}

function applyCoupon(string $code, float $subtotal): array {
    $stmt = getPDO()->prepare("SELECT * FROM coupons WHERE code=? AND status='active' AND (expires_at IS NULL OR expires_at >= CURDATE()) AND used_count < max_uses");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    if (!$coupon) return ['valid' => false, 'message' => 'Invalid or expired coupon.'];
    if ($subtotal < $coupon['min_order']) return ['valid' => false, 'message' => 'Minimum order ₹' . $coupon['min_order'] . ' required.'];
    $discount = $coupon['discount_type'] === 'percent' ? ($subtotal * $coupon['discount'] / 100) : $coupon['discount'];
    return ['valid' => true, 'discount' => min($discount, $subtotal), 'message' => 'Coupon applied!', 'coupon' => $coupon];
}
