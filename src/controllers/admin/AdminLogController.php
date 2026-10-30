<?php

require_once __DIR__ . '/../../models/AuditLog.php';

/** Nhật ký hệ thống: admin chỉ xem, không có thao tác sửa / xóa. */
class AdminLogController extends Controller
{
    public const ENTITIES = [
        'auth'     => 'Đăng nhập',
        'movie'    => 'Phim',
        'room'     => 'Phòng chiếu',
        'cinema'   => 'Rạp',
        'showtime' => 'Suất chiếu',
        'booking'  => 'Đơn vé',
        'user'     => 'Tài khoản',
    ];

    public function index(): void
    {
        Auth::requireAdmin();

        $entity = (string) $this->input('entity', '');
        if (!isset(self::ENTITIES[$entity])) {
            $entity = '';
        }

        $this->adminView('admin/logs/index', [
            'pageTitle' => 'Nhật ký hệ thống',
            'logs'      => AuditLog::latest(300, $entity ?: null),
            'entity'    => $entity,
            'entities'  => self::ENTITIES,
        ]);
    }
}
