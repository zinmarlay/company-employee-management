<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$id = (int) ($data['systemUser']['id'] ?? 0);
$data['pageEyebrowKey'] = 'system_users.title';
$data['pageDescriptionKey'] = 'system_users.edit_description';
$data['pageActions'] = [['href' => '/system-users/' . $id, 'labelKey' => 'actions.back_to_system_user', 'variant' => 'text']];
?>
<section class="page-section">
    <?php include __DIR__ . '/../partials/page-header.php'; ?>
    <?php include __DIR__ . '/_form.php'; ?>
</section>
