<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$locale = in_array((string) ($data['locale'] ?? 'en'), ['en', 'ja'], true) ? (string) $data['locale'] : 'en';
$title = HtmlEscaper::escape($data['pageTitle'] ?? 'Company Employee Management System');
$appNameValue = isset($data['appNameKey'])
    ? $t((string) $data['appNameKey'])
    : (string) ($data['appName'] ?? $t('shell.application_name'));
$appName = HtmlEscaper::escape($appNameValue);
$activeNav = (string) ($data['activeNav'] ?? '');
$currentPath = (string) ($data['currentPath'] ?? '/');
$content = (string) ($data['content'] ?? '');
$authenticatedUser = $data['authenticatedUser'] ?? null;
$isAdmin = is_object($authenticatedUser) && method_exists($authenticatedUser, 'isAdmin') && $authenticatedUser->isAdmin();
$navigation = [
    ['key' => 'dashboard', 'labelKey' => 'navigation.dashboard', 'href' => '/', 'icon' => '⌂'],
    ['key' => 'employees', 'labelKey' => 'navigation.employees', 'href' => '/employees', 'icon' => '●'],
    ['key' => 'branches', 'labelKey' => 'navigation.branches', 'href' => '/branches', 'icon' => '⌖'],
    ['key' => 'departments', 'labelKey' => 'navigation.departments', 'href' => '/departments', 'icon' => '▦'],
    ['key' => 'dispatch-companies', 'labelKey' => 'navigation.dispatch_companies', 'href' => '/dispatch-companies', 'icon' => '▱'],
    ['key' => 'dispatch-contracts', 'labelKey' => 'navigation.dispatch_contracts', 'href' => '/dispatch-contracts/create', 'icon' => '▤'],
];
if ($isAdmin) {
    $navigation[] = ['key' => 'system-users', 'labelKey' => 'navigation.system_users', 'href' => '/system-users', 'icon' => '◎'];
}
$localeUrl = static function (string $nextLocale) use ($currentPath): string {
    $separator = str_contains($currentPath, '?') ? '&' : '?';

    return $currentPath . $separator . 'lang=' . rawurlencode($nextLocale);
};
?>
<!doctype html>
<html lang="<?= HtmlEscaper::escape($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="app-body">
<div class="app-shell">
    <aside class="app-sidebar" aria-label="<?= HtmlEscaper::escape($t('shell.primary_navigation')) ?>">
        <div class="brand-block">
            <a class="brand-mark" href="/" aria-label="<?= HtmlEscaper::escape($t('navigation.dashboard')) ?>">C</a>
            <div>
                <p class="brand-name"><?= $appName ?></p>
                <p class="brand-caption"><?= HtmlEscaper::escape($t('shell.administration')) ?></p>
            </div>
        </div>

        <?php if (is_object($authenticatedUser)): ?>
            <nav class="sidebar-nav">
                <p class="nav-label"><?= HtmlEscaper::escape($t('shell.workspace')) ?></p>
                <?php foreach ($navigation as $item): ?>
                    <?php $isActive = $activeNav === $item['key']; ?>
                    <a class="nav-item<?= $isActive ? ' nav-item--active' : '' ?>"
                       href="<?= HtmlEscaper::escape($item['href']) ?>"
                       <?= $isActive ? 'aria-current="page"' : '' ?>>
                        <span class="nav-icon" aria-hidden="true"><?= HtmlEscaper::escape($item['icon']) ?></span>
                        <span><?= HtmlEscaper::escape($t($item['labelKey'])) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <div class="sidebar-footer">
            <span class="sidebar-footer__dot" aria-hidden="true"></span>
            <span><?= HtmlEscaper::escape($t('shell.local_workspace')) ?></span>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <div>
                <p class="topbar-kicker"><?= HtmlEscaper::escape($t('shell.company_workspace')) ?></p>
                <p class="topbar-context"><?= $title ?></p>
            </div>
            <div class="topbar-tools">
                <nav class="locale-switcher" aria-label="<?= HtmlEscaper::escape($t('shell.language_selector')) ?>">
                    <a class="locale-link<?= $locale === 'en' ? ' locale-link--active' : '' ?>" href="<?= HtmlEscaper::escape($localeUrl('en')) ?>"<?= $locale === 'en' ? ' aria-current="true"' : '' ?>>EN</a>
                    <a class="locale-link<?= $locale === 'ja' ? ' locale-link--active' : '' ?>" href="<?= HtmlEscaper::escape($localeUrl('ja')) ?>"<?= $locale === 'ja' ? ' aria-current="true"' : '' ?>>日本語</a>
                </nav>
                <?php if (is_object($authenticatedUser)): ?>
                    <div class="topbar-account" aria-label="<?= HtmlEscaper::escape($authenticatedUser->name) ?>">
                        <span class="avatar" aria-hidden="true"><?= HtmlEscaper::escape(mb_strtoupper(mb_substr($authenticatedUser->name, 0, 1))) ?></span>
                        <span class="topbar-account__text"><?= HtmlEscaper::escape($authenticatedUser->name) ?> · <?= HtmlEscaper::escape($t($authenticatedUser->isAdmin() ? 'system_users.admin' : 'system_users.user')) ?></span>
                        <form method="post" action="/logout" class="topbar-account__form">
                            <?php include __DIR__ . '/partials/csrf-field.php'; ?>
                            <button class="button button--text" type="submit"><?= HtmlEscaper::escape($t('auth.sign_out')) ?></button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <main class="main-content">
            <?= $content ?>
        </main>
    </div>
</div>
</body>
</html>
