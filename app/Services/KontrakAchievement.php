<?php

namespace App\Services;

/** Achievement is measured per input, independently of the target formula. */
class KontrakAchievement
{
    public function percentage($node, array $results, array $actuals): ?float
    {
        $calculator = new KontrakCalculator;
        $nodes = [];
        $collect = function ($item) use (&$collect, &$nodes) {
            $nodes[(string) $item->id] = $item;
            foreach ($item->childrenRecursive as $child) {
                $collect($child);
            }
        };
        $collect($node);
        $score = function ($item) use (&$score, $calculator, $results, $actuals, $nodes): ?float {
            $result = $results[(string) $item->id];
            if ($result['target'] === null || $result['error']) {
                return null;
            }
            $weights = $this->weights($result, $results);
            if ($weights) {
                $sum = $total = 0.0;
                foreach ($weights as $id => $weight) {
                    if ($weight <= 0 || ! isset($nodes[$id])) {
                        continue;
                    }
                    $value = $score($nodes[$id]);
                    if ($value !== null) {
                        $sum += $value * $weight;
                        $total += $weight;
                    }
                }

                return $total > 0 ? $sum / $total : null;
            }
            $values = [];
            foreach ($calculator->inputNodes($item, $results) as $input) {
                $target = $results[(string) $input->id]['target'];
                if ($target > 0) {
                    $values[] = (float) ($actuals[(string) $input->id] ?? 0) / $target * 100;
                }
            }

            return $values ? array_sum($values) / count($values) : null;
        };

        return $score($node);
    }

    private function weights(array $result, array $results): array
    {
        if ($result['type'] === 'weighted_sum') {
            return $result['config']['weights'] ?? [];
        }
        if ($result['type'] === 'formula') {
            $terms = $this->linearTerms($result['config']['ast'] ?? []);
            if ($terms && count(array_unique(array_values($terms))) > 1) {
                return $terms;
            }
            if ($terms && count($terms) > 1) {
                $derived = [];
                foreach ($terms as $id => $coefficient) {
                    $source = $results[$id];
                    if (in_array($source['type'], ['scale', 'ratio'], true)) {
                        $derived[$id] = (float) ($source['config']['factor'] ?? 1);
                    } elseif ($source['type'] === 'formula') {
                        $ast = $source['config']['ast'];
                        $factor = $this->outerFactor($ast);
                        if ($factor !== null) {
                            $derived[$id] = $factor;
                        }
                    }
                }
                if (count($derived) === count($terms)) {
                    return $derived;
                }
            }
            // Equal explicit coefficients are weights too; a plain SUM is not.
            if ($terms && array_values($terms)[0] != 1) {
                return $terms;
            }
        }
        if ($result['type'] === 'sum') {
            $weights = [];
            foreach ($result['source_ids'] as $id) {
                $source = $results[$id];
                if (! in_array($source['type'], ['scale', 'ratio'], true)) {
                    return [];
                }
                $weights[$id] = (float) ($source['config']['factor'] ?? 1);
            }

            return $weights;
        }

        return [];
    }

    private function outerFactor(array $ast): ?float
    {
        if (($ast['op'] ?? '') !== 'mul') {
            return null;
        }
        foreach (['left', 'right'] as $side) {
            try {
                return (new KontrakFormula)->evaluate($ast[$side], function () {
                    throw new \DomainException('Not a constant.');
                });
            } catch (\DomainException $error) {
                // Try the other operand.
            }
        }

        return null;
    }

    /** Extract positive linear weights, including Excel's ref * 40% notation. */
    private function linearTerms(array $ast): array
    {
        if (($ast['op'] ?? '') === 'ref') {
            return [(string) $ast['id'] => 1.0];
        }
        if (($ast['op'] ?? '') === 'add') {
            $left = $this->linearTerms($ast['left']);
            $right = $this->linearTerms($ast['right']);
            if (! $left || ! $right) {
                return [];
            }
            foreach ($right as $id => $weight) {
                $left[$id] = ($left[$id] ?? 0) + $weight;
            }

            return $left;
        }
        if (($ast['op'] ?? '') === 'mul') {
            foreach ([['left', 'right'], ['right', 'left']] as [$term, $constant]) {
                try {
                    $factor = (new KontrakFormula)->evaluate($ast[$constant], function () {
                        throw new \DomainException('Not a constant.');
                    });
                    $terms = $this->linearTerms($ast[$term]);
                    if ($terms && $factor >= 0) {
                        return array_map(fn ($weight) => $weight * $factor, $terms);
                    }
                } catch (\DomainException $error) {
                    // A referenced expression is not a constant weight.
                }
            }
        }

        return [];
    }
}
