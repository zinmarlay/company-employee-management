<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$appName = HtmlEscaper::escape($data['appName'] ?? 'Company Employee Management System');
$requestPath = HtmlEscaper::escape($data['requestPath'] ?? '/');
?>
<section>
    <h1><?= $appName ?></h1>
    <p>Phase 02 HTTP and presentation architecture is active.</p>
    <p>Request path: <code><?= $requestPath ?></code></p>
    <p>Business features are intentionally deferred to later phases.</p>
</section>
