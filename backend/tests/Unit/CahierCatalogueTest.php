<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Contrôle le catalogue du cahier des charges v2.0 sans base de données.
 */
class CahierCatalogueTest extends TestCase
{
    private const CATEGORIES = [
        'identite', 'etat-civil', 'voyage', 'etudes', 'travail',
        'sante', 'justice', 'legalisations', 'commerce', 'transport',
    ];

    public function test_catalogue_contains_the_80_numbered_procedures(): void
    {
        $records = $this->catalogue();

        $this->assertCount(80, $records);
        $numbers = array_column($records, 'number');
        sort($numbers);
        $this->assertSame(range(1, 80), $numbers);
    }

    public function test_every_procedure_has_complete_content_and_a_known_category(): void
    {
        foreach ($this->catalogue() as $record) {
            $label = "fiche {$record['number']}";

            $this->assertContains($record['category'], self::CATEGORIES, $label);
            foreach (['title', 'location', 'institution', 'cost', 'processing_time', 'validity', 'source_page'] as $field) {
                $this->assertNotEmpty($record[$field] ?? null, "{$label} : {$field}");
            }
            foreach (['required_documents', 'conditions', 'procedure_steps'] as $list) {
                $this->assertNotEmpty($record[$list] ?? [], "{$label} : {$list}");
            }
        }
    }

    public function test_slugs_are_unique(): void
    {
        $slugs = array_column($this->catalogue(), 'slug');

        $this->assertSame(count($slugs), count(array_unique($slugs)));
    }

    public function test_phase_2_drafts_are_the_twelve_procedures_listed_in_the_specification(): void
    {
        $records = json_decode(file_get_contents(dirname(__DIR__, 2).'/database/data/cahier_phase2.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(12, $records);
        foreach ($records as $record) {
            $this->assertContains($record['category'], self::CATEGORIES, $record['title']);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalogue(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__, 2).'/database/data/cahier_demarches.json'), true, 512, JSON_THROW_ON_ERROR);
    }
}
