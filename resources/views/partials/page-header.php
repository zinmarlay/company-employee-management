<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$eyebrow = isset($data['pageEyebrowKey']) ? $t((string) $data['pageEyebrowKey']) : ($data['pageEyebrow'] ?? null);
$description = isset($data['pageDescriptionKey']) ? $t((string) $data['pageDescriptionKey']) : ($data['pageDescription'] ?? null);
$actions = is_array($data['pageActions'] ?? null) ? $data['pageActions'] : [];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
?>
<div class="page-header">
    <div>
        <?php if (is_string($eyebrow) && $eyebrow !== ''): ?>
            <p class="eyebrow"><?= $escape($eyebrow) ?></p>
        <?php endif; ?>
        <h1><?= $escape($data['pageTitle'] ?? '') ?></h1>
        <?php if (is_string($description) && $description !== ''): ?>
            <p class="page-description"><?= $escape($description) ?></p>
        <?php endif; ?>
    </div>
    <?php if ($actions !== []): ?>
        <div class="page-actions">
            <?php foreach ($actions as $action): ?>
                <?php
                $href = (string) ($action['href'] ?? '#');
                $label = isset($action['labelKey']) ? $t((string) $action['labelKey']) : (string) ($action['label'] ?? 'Open');
                $variant = (string) ($action['variant'] ?? 'secondary');
                $allowedVariants = ['primary', 'secondary', 'text', 'danger'];
                $variant = in_array($variant, $allowedVariants, true) ? $variant : 'secondary';
                ?>
                <a class="button button--<?= $escape($variant) ?>" href="<?= $escape($href) ?>">
                    <?= $escape($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
