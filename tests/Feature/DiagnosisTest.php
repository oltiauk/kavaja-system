<?php

use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Diagnosis Model', function () {
    it('normalizes name on creation', function () {
        $diagnosis = Diagnosis::create([
            'name' => '  Pneumonia  ',
        ]);

        expect($diagnosis->name)->toBe('Pneumonia');
        expect($diagnosis->name_normalized)->toBe('pneumonia');
    });

    it('sets default usage count to 1', function () {
        $diagnosis = Diagnosis::create(['name' => 'Bronchitis']);

        expect($diagnosis->usage_count)->toBe(1);
    });

    it('updates normalized name when name changes', function () {
        $diagnosis = Diagnosis::create(['name' => 'Pneumonia']);

        $diagnosis->update(['name' => 'Bronchitis Acuta']);

        expect($diagnosis->name_normalized)->toBe('bronchitis acuta');
    });
});

describe('Diagnosis Search Scope', function () {
    beforeEach(function () {
        Diagnosis::create(['name' => 'Pneumonia', 'usage_count' => 10]);
        Diagnosis::create(['name' => 'Bronchopneumonia', 'usage_count' => 5]);
        Diagnosis::create(['name' => 'Bronchitis Acuta', 'usage_count' => 8]);
        Diagnosis::create(['name' => 'Hepatitis', 'usage_count' => 3]);
    });

    it('finds exact matches first', function () {
        $results = Diagnosis::search('pneumonia')->get();

        expect($results->first()->name)->toBe('Pneumonia');
    });

    it('finds partial matches', function () {
        $results = Diagnosis::search('pneu')->get();

        expect($results)->toHaveCount(2);
        expect($results->pluck('name')->toArray())->toContain('Pneumonia');
        expect($results->pluck('name')->toArray())->toContain('Bronchopneumonia');
    });

    it('is case insensitive', function () {
        $results = Diagnosis::search('PNEUMONIA')->get();

        expect($results->first()->name)->toBe('Pneumonia');
    });

    it('orders by usage count for similar matches', function () {
        $results = Diagnosis::search('bronch')->get();

        expect($results)->toHaveCount(2);
        // Bronchitis has usage_count 8, Bronchopneumonia has 5
        expect($results->first()->name)->toBe('Bronchitis Acuta');
    });
});

describe('Diagnosis findSimilar', function () {
    beforeEach(function () {
        Diagnosis::create(['name' => 'Pneumonia', 'usage_count' => 10]);
        Diagnosis::create(['name' => 'Bronchitis', 'usage_count' => 5]);
    });

    it('returns empty array for short input', function () {
        $results = Diagnosis::findSimilar('ab');

        expect($results)->toBeEmpty();
    });

    it('finds similar diagnoses with typos', function () {
        $results = Diagnosis::findSimilar('Pneunomia');

        expect($results)->not->toBeEmpty();
        expect($results[0]['diagnosis']->name)->toBe('Pneumonia');
        expect($results[0]['similarity'])->toBeGreaterThanOrEqual(70);
    });

    it('returns results sorted by similarity', function () {
        Diagnosis::create(['name' => 'Pneumonitis', 'usage_count' => 3]);

        $results = Diagnosis::findSimilar('Pneumonia');

        // Exact match should have highest similarity
        expect($results[0]['diagnosis']->name)->toBe('Pneumonia');
    });
});

describe('Diagnosis findOrCreateByName', function () {
    it('creates new diagnosis if not exists', function () {
        expect(Diagnosis::count())->toBe(0);

        $diagnosis = Diagnosis::findOrCreateByName('New Diagnosis');

        expect(Diagnosis::count())->toBe(1);
        expect($diagnosis->name)->toBe('New Diagnosis');
        expect($diagnosis->usage_count)->toBe(1);
    });

    it('returns existing diagnosis and increments usage count', function () {
        $existing = Diagnosis::create(['name' => 'Pneumonia', 'usage_count' => 5]);

        $diagnosis = Diagnosis::findOrCreateByName('pneumonia');

        expect(Diagnosis::count())->toBe(1);
        expect($diagnosis->id)->toBe($existing->id);
        expect($diagnosis->fresh()->usage_count)->toBe(6);
    });

    it('matches case-insensitively', function () {
        Diagnosis::create(['name' => 'Pneumonia', 'usage_count' => 1]);

        $diagnosis = Diagnosis::findOrCreateByName('PNEUMONIA');

        expect(Diagnosis::count())->toBe(1);
        expect($diagnosis->name)->toBe('Pneumonia');
    });

    it('trims whitespace', function () {
        Diagnosis::create(['name' => 'Pneumonia', 'usage_count' => 1]);

        $diagnosis = Diagnosis::findOrCreateByName('  Pneumonia  ');

        expect(Diagnosis::count())->toBe(1);
    });
});

describe('DiagnosisSeeder', function () {
    it('seeds common diagnoses', function () {
        $this->artisan('db:seed', ['--class' => 'DiagnosisSeeder', '--no-interaction' => true]);

        expect(Diagnosis::count())->toBeGreaterThan(100);
        expect(Diagnosis::where('name', 'Pneumonia')->exists())->toBeTrue();
        expect(Diagnosis::where('name', 'Diabetes Mellitus Type II')->exists())->toBeTrue();
    });

    it('does not duplicate diagnoses on re-run', function () {
        $this->artisan('db:seed', ['--class' => 'DiagnosisSeeder', '--no-interaction' => true]);
        $initialCount = Diagnosis::count();

        $this->artisan('db:seed', ['--class' => 'DiagnosisSeeder', '--no-interaction' => true]);

        expect(Diagnosis::count())->toBe($initialCount);
    });
});

describe('Diagnosis API', function () {
    beforeEach(function () {
        Diagnosis::create(['name' => 'Pneumonia', 'usage_count' => 10]);
        Diagnosis::create(['name' => 'Bronchopneumonia', 'usage_count' => 5]);
        Diagnosis::create(['name' => 'Bronchitis Acuta', 'usage_count' => 8]);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/diagnoses/search?q=pneum');

        $response->assertUnauthorized();
    });

    it('returns suggestions for authenticated users', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/diagnoses/search?q=pneum');

        $response->assertSuccessful();
        $response->assertJsonStructure(['suggestions']);
        expect($response->json('suggestions'))->toContain('Pneumonia');
        expect($response->json('suggestions'))->toContain('Bronchopneumonia');
    });

    it('returns empty array for short queries', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/diagnoses/search?q=a');

        $response->assertSuccessful();
        expect($response->json('suggestions'))->toBeEmpty();
    });

    it('respects limit parameter', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/diagnoses/search?q=bronch&limit=1');

        $response->assertSuccessful();
        expect($response->json('suggestions'))->toHaveCount(1);
    });

    it('caps limit at 20', function () {
        $user = User::factory()->create();

        // Create 25 diagnoses
        for ($i = 1; $i <= 25; $i++) {
            Diagnosis::create(['name' => "Test Diagnosis {$i}"]);
        }

        $response = $this->actingAs($user)
            ->getJson('/api/diagnoses/search?q=Test&limit=100');

        $response->assertSuccessful();
        expect(count($response->json('suggestions')))->toBeLessThanOrEqual(20);
    });

    it('stores a new diagnosis', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/diagnoses', ['name' => 'Morbus Novus']);

        $response->assertSuccessful();
        expect($response->json('name'))->toBe('Morbus Novus');
        expect(Diagnosis::where('name', 'Morbus Novus')->exists())->toBeTrue();
    });

    it('returns existing diagnosis when storing duplicate', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/diagnoses', ['name' => 'pneumonia']);

        $response->assertSuccessful();
        expect($response->json('name'))->toBe('Pneumonia');
        expect(Diagnosis::where('name', 'Pneumonia')->count())->toBe(1);
    });

    it('validates name is required when storing', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/diagnoses', ['name' => '']);

        $response->assertUnprocessable();
    });
});
