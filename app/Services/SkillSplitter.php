<?php

namespace App\Services;

/**
 * Expands compound skill strings returned by the LLM into atomic skill names.
 *
 * Rules applied in order:
 *   1. Content inside parentheses becomes one or more extra skills (comma-split).
 *   2. Slash-separated terms each become a separate skill UNLESS the whole string
 *      is a recognised compound (see SLASH_COMPOUNDS) — e.g. "CI/CD" stays intact.
 *   3. All results are trimmed; the list is deduplicated case-insensitively.
 *
 * Examples:
 *   "PHP (Laravel)"                    → ["PHP", "Laravel"]
 *   "JavaScript (Vue.js, React)"       → ["JavaScript", "Vue.js", "React"]
 *   "MySQL/PostgreSQL"                 → ["MySQL", "PostgreSQL"]
 *   "CI/CD (Jenkins, GitHub Actions)"  → ["CI/CD", "Jenkins", "GitHub Actions"]
 *   "TCP/IP"                           → ["TCP/IP"]
 */
class SkillSplitter
{
    /**
     * Slash-notation strings that represent a single concept and must not be split.
     * Stored lowercase; comparison is case-insensitive.
     *
     * @var string[]
     */
    private const SLASH_COMPOUNDS = [
        'ci/cd',
        'tcp/ip',
        'ui/ux',
        'a/b',
        'i/o',
        'r/w',
    ];

    /**
     * Expand a list of raw LLM skill strings into deduplicated atomic skills.
     *
     * @param  string[]  $skills
     * @return string[]
     */
    public function split(array $skills): array
    {
        $atoms = [];

        foreach ($skills as $skill) {
            foreach ($this->splitOne(trim((string) $skill)) as $atom) {
                $atoms[] = $atom;
            }
        }

        return $this->deduplicate($atoms);
    }

    /**
     * Split a single raw skill string into one or more atomic skill strings.
     *
     * @return string[]
     */
    private function splitOne(string $skill): array
    {
        if ($skill === '') {
            return [];
        }

        // Pull comma-separated items out of parentheses; keep the content.
        // "PHP (Laravel, Vue)" → base "PHP", extras ["Laravel", "Vue"]
        $extras = [];
        $base   = (string) preg_replace_callback(
            '/\(([^)]*)\)/',
            static function (array $m) use (&$extras): string {
                foreach (explode(',', $m[1]) as $item) {
                    $item = trim($item);
                    if ($item !== '') {
                        $extras[] = $item;
                    }
                }

                return '';
            },
            $skill
        );
        $base = trim($base);

        $atoms = $this->splitOnSlash($base);

        foreach ($extras as $extra) {
            foreach ($this->splitOnSlash($extra) as $atom) {
                $atoms[] = $atom;
            }
        }

        return array_values(array_filter($atoms, static fn ($s) => trim($s) !== ''));
    }

    /**
     * Split a skill name on '/' unless it is a recognised slash-compound.
     *
     * @return string[]
     */
    private function splitOnSlash(string $skill): array
    {
        $skill = trim($skill);

        if ($skill === '') {
            return [];
        }

        if (! str_contains($skill, '/')) {
            return [$skill];
        }

        if (in_array(mb_strtolower($skill), self::SLASH_COMPOUNDS, true)) {
            return [$skill];
        }

        return array_values(
            array_filter(
                array_map('trim', explode('/', $skill)),
                static fn ($s) => $s !== ''
            )
        );
    }

    /**
     * Remove duplicates from a flat skill list (case-insensitive).
     * First occurrence wins; original casing is preserved.
     *
     * @param  string[]  $skills
     * @return string[]
     */
    private function deduplicate(array $skills): array
    {
        $seen   = [];
        $result = [];

        foreach ($skills as $skill) {
            $key = mb_strtolower($skill);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $result[]   = $skill;
            }
        }

        return $result;
    }
}
