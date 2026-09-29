<?php

declare(strict_types=1);

namespace Tests\Unit\Http\View;

use App\Http\View\ViewRenderer;
use App\Localization\Translator;
use PHPUnit\Framework\TestCase;

final class EmployeeDetailPortfolioSummaryTest extends TestCase
{
    public function testEnglishSummaryUsesPluralLabelsSeparatedCountsAndResourceLinks(): void
    {
        $html = $this->render('en', ['skills' => 0, 'projects' => 2, 'certifications' => 0]);

        self::assertSummaryTile($html, 'Skills', '0', '/employees/42/skills', 'View skills');
        self::assertSummaryTile($html, 'Projects', '2', '/employees/42/projects', 'View projects');
        self::assertSummaryTile($html, 'Certifications', '0', '/employees/42/certifications', 'View certifications');
        self::assertStringNotContainsString('>Skill</a>', $html);
        self::assertStringNotContainsString('>Project name</a>', $html);
        self::assertStringNotContainsString('>Certification name</a>', $html);
    }

    public function testJapaneseSummaryUsesLocalizedLabelsAndResourceLinks(): void
    {
        $html = $this->render('ja', ['skills' => 1, 'projects' => 0, 'certifications' => 3]);

        self::assertSummaryTile($html, 'スキル', '1', '/employees/42/skills', 'スキルを表示');
        self::assertSummaryTile($html, 'プロジェクト', '0', '/employees/42/projects', 'プロジェクトを表示');
        self::assertSummaryTile($html, '資格', '3', '/employees/42/certifications', '資格を表示');
        self::assertStringNotContainsString('Project name', $html);
        self::assertStringNotContainsString('Certification name', $html);
    }

    /** @param array<string, int> $summary */
    private function render(string $locale, array $summary): string
    {
        $root = dirname(__DIR__, 4);
        $translator = new Translator($root . '/resources/lang');
        $translator->setLocale($locale);
        $renderer = new ViewRenderer($root . '/resources/views', $translator);

        return $renderer->renderPage('employees/show', [
            'pageTitle' => 'Employee Detail',
            'activeNav' => 'employees',
            'currentPath' => '/employees/42',
            'employee' => [
                'id' => 42,
                'first_name' => 'Aiko',
                'last_name' => 'Sato',
                'first_name_kana' => 'アイコ',
                'last_name_kana' => 'サトウ',
                'employee_code' => 'EMP000042',
                'email' => 'aiko@example.test',
                'phone' => null,
                'branch_code' => 'TOKYO',
                'branch_name' => 'Tokyo',
                'department_code' => 'DEV',
                'department_name' => 'Development',
                'position_title' => 'Engineer',
                'employee_type' => 'permanent',
                'hire_date' => '2026-01-01',
                'status' => 'active',
                'created_at' => '2026-01-01 00:00',
                'updated_at' => '2026-01-01 00:00',
                'dispatch' => ['is_dispatched' => false],
            ],
            'portfolioSummary' => $summary,
        ]);
    }

    private static function assertSummaryTile(string $html, string $label, string $count, string $href, string $linkLabel): void
    {
        $pattern = sprintf(
            '~<div class="portfolio-summary-item">.*?<h3>%s</h3>.*?<strong class="portfolio-summary-item__count">%s</strong>.*?<a class="button button--text button--small portfolio-summary-item__link" href="%s">%s</a>~s',
            preg_quote($label, '~'),
            preg_quote($count, '~'),
            preg_quote($href, '~'),
            preg_quote($linkLabel, '~'),
        );

        self::assertSame(1, preg_match($pattern, $html));
    }
}
