<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$emptyTitle = isset($data['emptyTitleKey']) ? $t((string) $data['emptyTitleKey']) : (string) ($data['emptyTitle'] ?? 'Nothing here yet');
$emptyMessage = isset($data['emptyMessageKey']) ? $t((string) $data['emptyMessageKey']) : (string) ($data['emptyMessage'] ?? 'There are no records to display.');
$emptyActionHref = $data['emptyActionHref'] ?? null;
$emptyActionLabel = isset($data['emptyActionLabelKey']) ? $t((string) $data['emptyActionLabelKey']) : (string) ($data['emptyActionLabel'] ?? 'Create');
?>
<div class="empty-state" role="status">
    <div class="empty-state__icon" aria-hidden="true">＋</div>
    <h2><?= HtmlEscaper::escape($emptyTitle) ?></h2>
    <p><?= HtmlEscaper::escape($emptyMessage) ?></p>
    <?php if (is_string($emptyActionHref) && $emptyActionHref !== ''): ?>
        <a class="button button--primary" href="<?= HtmlEscaper::escape($emptyActionHref) ?>">
            <?= HtmlEscaper::escape($emptyActionLabel) ?>
        </a>
    <?php endif; ?>
</div>
