<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$chipLabel = isset($chipLabelKey) ? $t((string) $chipLabelKey) : (string) ($chipLabel ?? 'Status');
$chipTone = (string) ($chipTone ?? 'neutral');
$allowedTones = ['success', 'warning', 'danger', 'info', 'neutral', 'primary'];
$chipTone = in_array($chipTone, $allowedTones, true) ? $chipTone : 'neutral';
?>
<span class="status-chip status-chip--<?= HtmlEscaper::escape($chipTone) ?>">
    <span class="status-chip__dot" aria-hidden="true"></span>
    <?= HtmlEscaper::escape($chipLabel) ?>
</span>
