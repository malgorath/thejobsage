<?php

use App\Models\Skill;
use Database\Seeders\SkillSeeder;
use Illuminate\Support\Facades\Artisan;

test('skill seeder populates skills table', function () {
    Artisan::call('db:seed', ['--class' => SkillSeeder::class]);

    expect(Skill::count())->toBeGreaterThan(0);
});

test('skill seeder is idempotent when run twice', function () {
    Artisan::call('db:seed', ['--class' => SkillSeeder::class]);
    $firstCount = Skill::count();

    Artisan::call('db:seed', ['--class' => SkillSeeder::class]);

    expect(Skill::count())->toBe($firstCount);
});
