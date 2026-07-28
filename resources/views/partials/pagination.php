<?php
/**
 * Pagination partial
 * Requires: $paginator (from DB::paginate()), $baseUrl
 */
if (empty($paginator) || ($paginator['last_page'] ?? 1) <= 1) return;
$current  = $paginator['current_page'];
$last     = $paginator['last_page'];
$from     = $paginator['from'] ?? 0;
$to       = $paginator['to']   ?? 0;
$total    = $paginator['total'] ?? 0;
$url      = rtrim($baseUrl ?? request_url(), '?');
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
    <div style="font-size:.8rem;color:var(--text-muted);">
        Showing <strong><?= $from ?></strong> to <strong><?= $to ?></strong> of <strong><?= number_format($total) ?></strong> results
    </div>
    <nav>
        <ul class="pagination mb-0">
            <!-- Previous -->
            <li class="page-item <?= $current <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $url ?>?page=<?= $current - 1 ?>">
                    <i class="bi bi-chevron-left" style="font-size:.75rem;"></i>
                </a>
            </li>

            <!-- First page -->
            <?php if ($current > 3): ?>
            <li class="page-item"><a class="page-link" href="<?= $url ?>?page=1">1</a></li>
            <?php if ($current > 4): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Pages around current -->
            <?php for ($i = max(1, $current - 2); $i <= min($last, $current + 2); $i++): ?>
            <li class="page-item <?= $i === $current ? 'active' : '' ?>">
                <a class="page-link" href="<?= $url ?>?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>

            <!-- Last page -->
            <?php if ($current < $last - 2): ?>
            <?php if ($current < $last - 3): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?= $url ?>?page=<?= $last ?>"><?= $last ?></a></li>
            <?php endif; ?>

            <!-- Next -->
            <li class="page-item <?= $current >= $last ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $url ?>?page=<?= $current + 1 ?>">
                    <i class="bi bi-chevron-right" style="font-size:.75rem;"></i>
                </a>
            </li>
        </ul>
    </nav>
</div>
<?php
function request_url(): string {
    return strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
}
