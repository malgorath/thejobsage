<?php

use App\Services\SkillSplitter;

beforeEach(function () {
    $this->splitter = new SkillSplitter();
});

// ─── Parenthetical splitting ──────────────────────────────────────────────────

test('splits parenthetical into a separate skill', function () {
    expect($this->splitter->split(['PHP (Laravel)']))->toBe(['PHP', 'Laravel']);
});

test('splits multiple comma-separated items inside parentheses', function () {
    expect($this->splitter->split(['JavaScript (Vue.js, React)']))->toBe(['JavaScript', 'Vue.js', 'React']);
});

test('splits three items inside parentheses', function () {
    expect($this->splitter->split(['JavaScript (Vue.js, React, Angular)']))
        ->toBe(['JavaScript', 'Vue.js', 'React', 'Angular']);
});

test('parenthetical-only entry (no base) is handled gracefully', function () {
    // " (Laravel)" → base is empty after stripping parens, extras has "Laravel"
    $result = $this->splitter->split(['(Laravel)']);
    expect($result)->toBe(['Laravel']);
});

// ─── Slash splitting ──────────────────────────────────────────────────────────

test('splits slash-separated pair', function () {
    expect($this->splitter->split(['MySQL/PostgreSQL']))->toBe(['MySQL', 'PostgreSQL']);
});

test('splits three slash-separated terms', function () {
    expect($this->splitter->split(['MySQL/PostgreSQL/SQLite']))->toBe(['MySQL', 'PostgreSQL', 'SQLite']);
});

// ─── Known compound preservation ─────────────────────────────────────────────

test('CI/CD is preserved as a single skill', function () {
    expect($this->splitter->split(['CI/CD']))->toBe(['CI/CD']);
});

test('TCP/IP is preserved as a single skill', function () {
    expect($this->splitter->split(['TCP/IP']))->toBe(['TCP/IP']);
});

test('UI/UX is preserved as a single skill', function () {
    expect($this->splitter->split(['UI/UX']))->toBe(['UI/UX']);
});

test('known compound matching is case-insensitive', function () {
    expect($this->splitter->split(['ci/cd']))->toBe(['ci/cd']);
    expect($this->splitter->split(['Ci/Cd']))->toBe(['Ci/Cd']);
});

test('CI/CD with parenthetical extras becomes compound plus extras', function () {
    expect($this->splitter->split(['CI/CD (Jenkins, GitHub Actions)']))
        ->toBe(['CI/CD', 'Jenkins', 'GitHub Actions']);
});

test('TCP/IP with parenthetical extras stays intact plus extras', function () {
    expect($this->splitter->split(['TCP/IP (networking, sockets)']))
        ->toBe(['TCP/IP', 'networking', 'sockets']);
});

// ─── Deduplication ───────────────────────────────────────────────────────────

test('deduplicates case-insensitively across input entries', function () {
    // "PHP (Laravel)" produces "PHP" and "Laravel"; standalone "PHP" is duplicate
    expect($this->splitter->split(['PHP (Laravel)', 'PHP', 'laravel']))
        ->toBe(['PHP', 'Laravel']);
});

test('deduplication keeps first-occurrence casing', function () {
    expect($this->splitter->split(['React', 'REACT', 'react']))->toBe(['React']);
});

test('deduplication works across slash-split results', function () {
    // "MySQL/PostgreSQL" and standalone "MySQL" → deduplicated
    expect($this->splitter->split(['MySQL/PostgreSQL', 'MySQL']))->toBe(['MySQL', 'PostgreSQL']);
});

// ─── Whitespace handling ──────────────────────────────────────────────────────

test('trims leading and trailing whitespace from each skill', function () {
    expect($this->splitter->split(['  PHP (  Laravel  )  ']))->toBe(['PHP', 'Laravel']);
});

test('trims whitespace around slash-separated terms', function () {
    expect($this->splitter->split(['MySQL / PostgreSQL']))->toBe(['MySQL', 'PostgreSQL']);
});

test('trims comma-separated items inside parentheses', function () {
    expect($this->splitter->split(['PHP (  Laravel ,  Vue  )']))->toBe(['PHP', 'Laravel', 'Vue']);
});

// ─── Edge cases ───────────────────────────────────────────────────────────────

test('empty input returns empty array', function () {
    expect($this->splitter->split([]))->toBe([]);
});

test('empty strings in input are filtered out', function () {
    expect($this->splitter->split(['', 'PHP', '']))->toBe(['PHP']);
});

test('plain skill with no slash or parentheses passes through unchanged', function () {
    expect($this->splitter->split(['Python', 'Docker', 'Kubernetes']))
        ->toBe(['Python', 'Docker', 'Kubernetes']);
});

test('dot-notation skill is not split', function () {
    expect($this->splitter->split(['React.js', 'Node.js']))->toBe(['React.js', 'Node.js']);
});

test('C++ is not split', function () {
    expect($this->splitter->split(['C++']))->toBe(['C++']);
});

test('C# is not split', function () {
    expect($this->splitter->split(['C#']))->toBe(['C#']);
});

// ─── Realistic pipeline output ────────────────────────────────────────────────

// ─── Prompt-bleed sanitization ────────────────────────────────────────────────

test('filters out a full prompt-bleed sentence', function () {
    $result = $this->splitter->split([
        'here is the list of technical and professional skill words as a comma-separated array:',
    ]);
    expect($result)->toBe([]);
});

test('filters out a skill over 50 characters', function () {
    $longSkill = str_repeat('a', 51);
    $result = $this->splitter->split(['PHP', $longSkill]);
    expect($result)->toBe(['PHP']);
});

test('filters out a skill containing a colon', function () {
    $result = $this->splitter->split(['PHP', 'Note: this is not a skill']);
    expect($result)->toBe(['PHP']);
});

test('valid short skills pass through unchanged', function () {
    $result = $this->splitter->split(['PHP', 'Laravel', 'CI/CD']);
    expect($result)->toBe(['PHP', 'Laravel', 'CI/CD']);
});

test('processes realistic LLM output array correctly', function () {
    $input = [
        'PHP (Laravel)',
        'MySQL/PostgreSQL',
        'CI/CD (Jenkins, GitHub Actions)',
        'React.js',
        'Docker',
    ];

    expect($this->splitter->split($input))->toBe([
        'PHP',
        'Laravel',
        'MySQL',
        'PostgreSQL',
        'CI/CD',
        'Jenkins',
        'GitHub Actions',
        'React.js',
        'Docker',
    ]);
});
