<?php

namespace Tests\Unit;

use App\Services\KontrakFormula;
use PHPUnit\Framework\TestCase;

class KontrakFormulaTest extends TestCase
{
    public function test_excel_arithmetic_percent_sum_and_parentheses(): void
    {
        $keys = ['root', 'a', 'b', 'c'];
        foreach ([
            ['=E2/E3*100', ['a' => 18, 'b' => 21], 85.7142857143],
            ['=SUM(E2:E4)', ['a' => 1, 'b' => 2, 'c' => 3], 6],
            ['=E2*40%+E3*40%+E4*20%', ['a' => 55, 'b' => 100, 'c' => 80.95], 78.19],
            ['=50+E2/E3*50', ['a' => 5, 'b' => 6], 91.6666666667],
            ['=E2*60', ['a' => 1], 60],
            ['=E2*40%', ['a' => 91.45], 36.58],
            ['=-(-E2)+3*(2+1)', ['a' => 2], 11],
            ['=SUM(E2;E3;E4)', ['a' => 1, 'b' => 2, 'c' => 3], 6],
            ['=SUM(E2,E3)', ['a' => 2, 'b' => 3], 5],
            ['=$E$2+e3', ['a' => 2, 'b' => 3], 5],
        ] as [$formula,$values,$expected]) {
            $engine = new KontrakFormula;
            $ast = $engine->parse($formula, $keys);
            $engine->validate($ast, $keys);
            $this->assertEqualsWithDelta($expected, $engine->evaluate($ast, fn ($id) => (float) $values[$id]), 0.000001, $formula);
            $rendered = $engine->render($ast, ['root' => 1, 'a' => 2, 'b' => 3, 'c' => 4]);
            $this->assertEqualsWithDelta($expected, $engine->evaluate($engine->parse($rendered, $keys), fn ($id) => (float) $values[$id]), 0.000001, $rendered);
        }
    }

    public function test_unknown_functions_external_references_and_invalid_input_are_rejected(): void
    {
        foreach (['=system("id")', '=HYPERLINK("x")', '=E999', '=SUM(E2:E999)', '=E2+', '=1^2', '=1+ABS(1)'] as $formula) {
            try {
                (new KontrakFormula)->parse($formula, ['root', 'a']);
                $this->fail('Accepted '.$formula);
            } catch (\DomainException $error) {
                $this->assertNotEmpty($error->getMessage());
            }
        }
    }

    public function test_reference_ids_survive_row_renumbering(): void
    {
        $engine = new KontrakFormula;
        $ast = $engine->parse('=E2/E3*100', ['root', 'numerator', 'denominator']);
        $mapped = $engine->mapReferences($ast, fn ($id) => ['numerator' => '123', 'denominator' => '456'][$id]);
        $this->assertSame(['123', '456'], $engine->references($mapped));
        $this->assertStringContainsString('E5/E9', $engine->render($mapped, ['123' => 5, '456' => 9]));
    }

    public function test_division_by_zero_remains_unavailable(): void
    {
        $engine = new KontrakFormula;
        $ast = $engine->parse('=E2/E3', ['root', 'a', 'b']);
        $this->expectException(\DomainException::class);
        $engine->evaluate($ast, fn ($id) => $id === 'a' ? 18.0 : 0.0);
    }
}
