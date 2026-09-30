<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$data['pageEyebrowKey'] = 'system_users.title';
$data['pageDescriptionKey'] = 'system_users.create_description';
$data['pageActions'] = [['href' => '/system-users', 'labelKey' => 'actions.back_to_system_users', 'variant' => 'text']];
?>
<section class="page-section">
    <?php include __DIR__ . '/../partials/page-header.php'; ?>
    <?php include __DIR__ . '/_form.php'; ?>
</section>
