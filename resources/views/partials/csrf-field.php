<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$csrfToken = is_string($data['csrfToken'] ?? null) ? $data['csrfToken'] : '';
?>
<input type="hidden" name="csrf_token" value="<?= HtmlEscaper::escape($csrfToken) ?>">
