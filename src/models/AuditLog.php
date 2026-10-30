<?php

/**
 * Log đăng nhập và thao tác admin: ai làm, lúc nào, dữ liệu trước/sau.
 * Chỉ có ghi và đọc, không có sửa/xóa.
 */
class AuditLog extends Model
{
    public static function write(string $action, string $entity, $entityId = null, ?array $before = null, ?array $after = null, ?int $userId = null): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO audit_logs (user_id, action, entity, entity_id, before_data, after_data, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId ?? Auth::id(),
            $action,
            $entity,
            $entityId !== null ? (string) $entityId : null,
            $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            $after !== null ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public static function latest(int $limit = 200, ?string $entity = null): array
    {
        $sql = 'SELECT l.*, u.email AS user_email FROM audit_logs l
                LEFT JOIN users u ON u.id = l.user_id';
        $params = [];
        if ($entity) {
            $sql .= ' WHERE l.entity = ?';
            $params[] = $entity;
        }
        $sql .= ' ORDER BY l.id DESC LIMIT ' . max(1, min(1000, $limit));

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
