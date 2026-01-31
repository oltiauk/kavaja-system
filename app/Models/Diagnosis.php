<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'name_normalized',
        'usage_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage_count' => 'integer',
        ];
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'usage_count' => 1,
    ];

    protected static function booted(): void
    {
        static::creating(function (Diagnosis $diagnosis) {
            $diagnosis->name = trim($diagnosis->name);
            $diagnosis->name_normalized = static::normalize($diagnosis->name);
        });

        static::updating(function (Diagnosis $diagnosis) {
            if ($diagnosis->isDirty('name')) {
                $diagnosis->name = trim($diagnosis->name);
                $diagnosis->name_normalized = static::normalize($diagnosis->name);
            }
        });
    }

    /**
     * Normalize a diagnosis name for searching and comparison.
     */
    public static function normalize(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    /**
     * Scope to search diagnoses by name with partial matching.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        $normalized = static::normalize($search);

        return $query->where('name_normalized', 'LIKE', "%{$normalized}%")
            ->orderByRaw('CASE WHEN name_normalized = ? THEN 0 WHEN name_normalized LIKE ? THEN 1 ELSE 2 END', [
                $normalized,
                "{$normalized}%",
            ])
            ->orderByDesc('usage_count')
            ->orderBy('name');
    }

    /**
     * Find similar diagnoses using Levenshtein distance.
     *
     * @return array<int, array{diagnosis: Diagnosis, similarity: float}>
     */
    public static function findSimilar(string $input, int $limit = 5): array
    {
        $normalizedInput = static::normalize($input);

        if (strlen($normalizedInput) < 3) {
            return [];
        }

        $candidates = static::query()
            ->where('name_normalized', 'LIKE', '%'.substr($normalizedInput, 0, 3).'%')
            ->orderByDesc('usage_count')
            ->limit(50)
            ->get();

        $results = [];

        foreach ($candidates as $diagnosis) {
            similar_text($normalizedInput, $diagnosis->name_normalized, $similarity);

            if ($similarity >= 70) {
                $results[] = [
                    'diagnosis' => $diagnosis,
                    'similarity' => $similarity,
                ];
            }
        }

        usort($results, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($results, 0, $limit);
    }

    /**
     * Get or create a diagnosis by name.
     */
    public static function findOrCreateByName(string $name): static
    {
        $normalized = static::normalize($name);

        $existing = static::where('name_normalized', $normalized)->first();

        if ($existing) {
            $existing->increment('usage_count');

            return $existing;
        }

        return static::create([
            'name' => trim($name),
            'usage_count' => 1,
        ]);
    }

    /**
     * Increment usage count for this diagnosis.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
