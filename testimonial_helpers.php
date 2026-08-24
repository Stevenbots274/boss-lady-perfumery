<?php
function testimonial_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function testimonial_first_name($name)
{
    $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
    return $parts[0] ?? 'Customer';
}

function testimonial_stars($rating)
{
    $rating = max(1, min(5, (int) $rating));
    return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
}

function testimonial_fetch_approved($pdo, $limit = 6, $productId = null)
{
    $limit = max(1, min(30, (int) $limit));
    $where = "t.status='approved' AND o.order_status='delivered'";
    $params = [];
    if ($productId !== null) {
        $where .= ' AND EXISTS (SELECT 1 FROM testimonial_products tp WHERE tp.testimonial_id=t.id AND tp.product_id=?)';
        $params[] = (int) $productId;
    }
    $query = "SELECT t.id,t.rating,t.message,t.created_at,o.customer_name,tm.media_type,tm.media_url,tm.thumbnail_url,tm.mime_type FROM testimonials t INNER JOIN orders o ON o.id=t.order_id LEFT JOIN testimonial_media tm ON tm.testimonial_id=t.id WHERE {$where} ORDER BY t.approved_at DESC NULLS LAST,t.created_at DESC LIMIT {$limit}";
    $statement = $pdo->prepare($query);
    $statement->execute($params);
    return $statement->fetchAll();
}

function testimonial_fetch_product_stats($pdo)
{
    $statement = $pdo->query("SELECT tp.product_id,COUNT(DISTINCT t.id) AS review_count,ROUND(AVG(t.rating)::numeric,1) AS rating FROM testimonial_products tp INNER JOIN testimonials t ON t.id=tp.testimonial_id INNER JOIN orders o ON o.id=t.order_id WHERE t.status='approved' AND o.order_status='delivered' GROUP BY tp.product_id");
    $stats = [];
    foreach ($statement->fetchAll() as $row) {
        $stats[(int) $row['product_id']] = ['review_count' => (int) $row['review_count'], 'rating' => (float) $row['rating']];
    }
    return $stats;
}

function testimonial_media_markup($testimonial)
{
    $type = $testimonial['media_type'] ?? '';
    $url = testimonial_h($testimonial['media_url'] ?? '');
    if (!$url) return '<div class="testimonial-no-media" aria-hidden="true">BL</div>';
    if ($type === 'image') {
        $alt = testimonial_h('Customer fragrance testimonial from ' . testimonial_first_name($testimonial['customer_name'] ?? 'Customer'));
        return '<button class="testimonial-image" type="button" data-lightbox-src="' . $url . '" data-lightbox-alt="' . $alt . '"><img src="' . $url . '" alt="' . $alt . '" loading="lazy" decoding="async"></button>';
    }
    if ($type === 'video') {
        $poster = !empty($testimonial['thumbnail_url']) ? ' poster="' . testimonial_h($testimonial['thumbnail_url']) . '"' : '';
        $mime = testimonial_h($testimonial['mime_type'] ?? 'video/mp4');
        return '<div class="testimonial-video"><video controls preload="none" playsinline' . $poster . '><source src="' . $url . '" type="' . $mime . '">Your browser does not support video playback.</video><span class="testimonial-play" aria-hidden="true">▶</span></div>';
    }
    return '<div class="testimonial-no-media" aria-hidden="true">BL</div>';
}

function testimonial_card_markup($testimonial, $compact = false)
{
    $rating = (int) ($testimonial['rating'] ?? 5);
    $class = $compact ? ' testimonial-card-compact' : '';
    return '<article class="testimonial-card' . $class . '"><div class="testimonial-media">' . testimonial_media_markup($testimonial) . '</div><div class="testimonial-card-body"><div class="testimonial-rating"><span class="testimonial-stars" aria-label="' . $rating . ' out of 5 stars">' . testimonial_stars($rating) . '</span><span>' . $rating . '.0</span></div><p class="testimonial-quote">&ldquo;' . testimonial_h($testimonial['message'] ?? '') . '&rdquo;</p><div class="testimonial-byline"><strong>' . testimonial_h(testimonial_first_name($testimonial['customer_name'] ?? 'Customer')) . '</strong><span class="testimonial-verified">&#10003; Verified Purchase</span></div></div></article>';
}
