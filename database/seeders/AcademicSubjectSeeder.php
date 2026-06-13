<?php

namespace Database\Seeders;

use App\Enums\Academic\AcademicSubjectCategory;
use App\Models\AcademicSubject;
use App\Models\School;
use Illuminate\Database\Seeder;

class AcademicSubjectSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::query()->where('is_default', true)->first();

        if (! $school) {
            return;
        }

        $subjects = [
            ['name' => 'Français', 'code' => 'FR', 'short_name' => 'Français', 'category' => AcademicSubjectCategory::Language->value, 'sort_order' => 10],
            ['name' => 'Mathématiques', 'code' => 'MATH', 'short_name' => 'Maths', 'category' => AcademicSubjectCategory::Science->value, 'sort_order' => 20],
            ['name' => 'Anglais', 'code' => 'ANG', 'short_name' => 'Anglais', 'category' => AcademicSubjectCategory::Language->value, 'sort_order' => 30],
            ['name' => 'Histoire-Géographie', 'code' => 'HG', 'short_name' => 'H-G', 'category' => AcademicSubjectCategory::SocialScience->value, 'sort_order' => 40],
            ['name' => 'SVT', 'code' => 'SVT', 'short_name' => 'SVT', 'category' => AcademicSubjectCategory::Science->value, 'sort_order' => 50],
            ['name' => 'PCT', 'code' => 'PCT', 'short_name' => 'PCT', 'category' => AcademicSubjectCategory::Science->value, 'sort_order' => 60],
            ['name' => 'EPS', 'code' => 'EPS', 'short_name' => 'EPS', 'category' => AcademicSubjectCategory::Sport->value, 'sort_order' => 70],
            ['name' => 'Informatique', 'code' => 'INFO', 'short_name' => 'Info', 'category' => AcademicSubjectCategory::Technical->value, 'sort_order' => 80],
            ['name' => 'Conduite', 'code' => 'COND', 'short_name' => 'Conduite', 'category' => AcademicSubjectCategory::LifeSkills->value, 'sort_order' => 90, 'is_optional_by_default' => true, 'is_conduct' => true],
        ];

        foreach ($subjects as $subject) {
            AcademicSubject::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'code' => $subject['code'],
                ],
                [
                    'name' => $subject['name'],
                    'short_name' => $subject['short_name'],
                    'category' => $subject['category'],
                    'description' => null,
                    'is_optional_by_default' => $subject['is_optional_by_default'] ?? false,
                    'is_conduct' => $subject['is_conduct'] ?? false,
                    'is_active' => true,
                    'sort_order' => $subject['sort_order'],
                ]
            );
        }
    }
}
