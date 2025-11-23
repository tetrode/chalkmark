<?php

declare(strict_types=1);

namespace Chalkmark\Tests;

use Chalkmark\Chalkmark;
use PHPUnit\Framework\TestCase;

use function dirname;

final class TableTest extends TestCase
{
    private function fixturePath(string $name): string
    {
        return dirname(__DIR__).'/tests/fixtures/'.$name;
    }

    public function testMisformattedTableIsNormalized(): void
    {
        $renderer = new Chalkmark(false);
        $path = $this->fixturePath('misformatted_table.md');
        $actual = $renderer->renderFile($path);

        $expected = "".
            "Not Yet Correct\n".
            "\n".
            "Tables\n".
            "\n".
            "| Left aligned |   Centered    | Right aligned |\n".
            "| ------------ | ------------- | ------------- |\n".
            "| Apple        |      Red      |            10 |\n".
            "| Banana       |    Yellow     |             2 |\n".
            "| Cherry       | Very Dark Red |             6 |\n".
            "\n";

        $this->assertSame($expected, $actual);
    }
}
