<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$title = HtmlEscaper::escape($data['pageTitle'] ?? 'Company Employee Management System');
$content = (string) ($data['content'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?></title>
</head>
<body>
<main>
    <?= $content ?>
</main>
</body>
</html>
