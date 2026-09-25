<?php

declare(strict_types=1);

namespace Tests\Unit\Http\View;

use App\Localization\Locale;
use App\Localization\Translator;
use App\Http\View\ViewRenderer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViewRendererTest extends TestCase
{
    private string $temporaryViews;

    protected function setUp(): void
    {
        $this->temporaryViews = sys_get_temp_dir() . '/company-employee-views-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryViews, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryViews . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->temporaryViews);
    }

    public function testTrustedViewAndLayoutRenderEscapedData(): void
    {
        $renderer = new ViewRenderer(dirname(__DIR__, 4) . '/resources/views');

        $html = $renderer->renderPage('setup', [
            'appName' => '<script>alert(1)</script>',
            'requestPath' => '/<unsafe>',
        ]);

        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringContainsString('/&lt;unsafe&gt;', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function testJapaneseEmployeeFormUsesLocalizedLabels(): void
    {
        $root = dirname(__DIR__, 4);
        $translator = new Translator($root . '/resources/lang');
        $translator->setLocale(Locale::JAPANESE);
        $renderer = new ViewRenderer($root . '/resources/views', $translator);

        $html = $renderer->renderPage('employees/create', [
            'pageTitleKey' => 'employees.create_title',
            'activeNav' => 'employees',
            'currentPath' => '/employees/create',
            'values' => [],
            'errors' => [],
            'branches' => [],
            'departmentGroups' => [],
        ]);

        self::assertStringContainsString('<html lang="ja">', $html);
        self::assertStringContainsString('社員情報', $html);
        self::assertStringContainsString('社員番号', $html);
        self::assertStringContainsString('社員を登録', $html);
        self::assertStringNotContainsString('Employee code', $html);
    }

    public function testUntrustedOrTraversalViewIdentifiersAreRejected(): void
    {
        $renderer = new ViewRenderer($this->temporaryViews);

        $this->expectException(InvalidArgumentException::class);

        $renderer->render('../secret');
    }

    public function testRenderingFailureCleansTheOutputBuffer(): void
    {
        file_put_contents(
            $this->temporaryViews . '/broken.php',
            '<?php echo "partial"; throw new RuntimeException("broken view");',
        );
        file_put_contents($this->temporaryViews . '/after.php', '<?php echo "after";');
        $renderer = new ViewRenderer($this->temporaryViews);

        try {
            $renderer->render('broken');
            self::fail('Expected a view-rendering exception.');
        } catch (RuntimeException $exception) {
            self::assertSame('broken view', $exception->getMessage());
        }

        self::assertSame('after', $renderer->render('after'));
    }
}
