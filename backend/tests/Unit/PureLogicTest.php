<?php

namespace Tests\Unit;

use App\Models\Demarche;
use App\Models\DocumentValidityRule;
use App\Services\DocumentValidityCalculator;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/** Tests unitaires : logique pure, sans base de données ni application complète. */
class PureLogicTest extends TestCase
{
    private function rule(array $attributes): DocumentValidityRule
    {
        $rule = new DocumentValidityRule();
        $rule->forceFill($attributes + ['key' => 'test', 'validity_start' => 'issue_date', 'status' => 'secondary']);

        return $rule;
    }

    public function test_cost_text_gives_its_amount_in_fcfa(): void
    {
        $this->assertSame(5000, Demarche::amountFromCost('5 000 FCFA'));
        $this->assertSame(60000, Demarche::amountFromCost('60 000 FCFA'));
        $this->assertSame(0, Demarche::amountFromCost('Gratuit'));
        $this->assertNull(Demarche::amountFromCost(null));
    }

    public function test_a_permanent_rule_never_gives_an_expiry_date(): void
    {
        $result = (new DocumentValidityCalculator())->evaluate(
            $this->rule(['status' => 'permanent', 'validity_months' => null, 'validity_start' => 'not_applicable']),
            '2001-05-10',
            Carbon::parse('2026-11-06'),
        );

        $this->assertSame(DocumentValidityCalculator::STATE_PERMANENT, $result['state']);
        $this->assertNull($result['expiry_date']);
        $this->assertFalse($result['can_compute']);
    }

    public function test_a_status_based_rule_never_gives_an_expiry_date(): void
    {
        $result = (new DocumentValidityCalculator())->evaluate(
            $this->rule(['status' => 'status_based', 'validity_months' => null, 'validity_start' => 'not_applicable']),
            '2015-06-01',
            Carbon::parse('2026-11-06'),
        );

        $this->assertSame(DocumentValidityCalculator::STATE_STATUS_BASED, $result['state']);
        $this->assertNull($result['expiry_date']);
    }

    public function test_an_unsourced_duration_is_never_computed(): void
    {
        $result = (new DocumentValidityCalculator())->evaluate(
            $this->rule(['status' => 'to_verify', 'validity_months' => 120]),
            '2020-01-01',
            Carbon::parse('2026-11-06'),
        );

        $this->assertSame(DocumentValidityCalculator::STATE_UNAVAILABLE, $result['state']);
        $this->assertNull($result['expiry_date']);
    }

    public function test_expiry_is_computed_from_the_issue_date(): void
    {
        $result = (new DocumentValidityCalculator())->evaluate(
            $this->rule(['status' => 'secondary', 'validity_months' => 120]),
            '2016-03-01',
            Carbon::parse('2026-11-06'),
        );

        $this->assertSame('2026-03-01', $result['expiry_date']);
        $this->assertSame(DocumentValidityCalculator::STATE_EXPIRED, $result['state']);
    }

    public function test_an_issue_date_in_the_future_is_refused(): void
    {
        $result = (new DocumentValidityCalculator())->evaluate(
            $this->rule(['status' => 'secondary', 'validity_months' => 120]),
            '2030-01-01',
            Carbon::parse('2026-11-06'),
        );

        $this->assertSame(DocumentValidityCalculator::STATE_UNAVAILABLE, $result['state']);
        $this->assertFalse($result['can_compute']);
    }
}
