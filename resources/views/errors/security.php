<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
?>
<section class="page-section">
    <div class="card empty-state">
        <p class="eyebrow"><?= $escape($t('security.title')) ?></p>
        <h1><?= $escape($t((string) ($data['messageKey'] ?? 'security.forbidden_message'))) ?></h1>
        <p class="muted-text"><?= $escape($t('security.safe_instruction')) ?></p>
        <div class="form-actions"><a class="button button--primary" href="/"><?= $escape($t('navigation.dashboard')) ?></a></div>
    </div>
</section>
