<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resume>
 */
class ResumeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = $this->faker;

        // --- Generate Realistic Resume Text Content (Simulating AI Output) ---
        $name = $faker->name();
        $jobTitle = $faker->jobTitle();
        $email = $faker->email();
        $phone = $faker->phoneNumber();
        $location = $faker->city().', '.$faker->stateAbbr();

        $resumeTextContent = "## {$name}\n";
        $resumeTextContent .= "{$jobTitle}\n";
        $resumeTextContent .= "{$email} | {$phone} | {$location}\n\n";

        $resumeTextContent .= "### Summary\n";
        $resumeTextContent .= $faker->paragraphs(1, true)."\n\n";

        $resumeTextContent .= "### Experience\n";
        for ($i = 0; $i < $faker->numberBetween(2, 4); $i++) {
            $resumeTextContent .= "**{$faker->jobTitle()}** | {$faker->company()} | {$faker->year()} - {$faker->randomElement(['Present', $faker->year()])}\n";
            $resumeTextContent .= '- '.$faker->bs()."\n"; // Use bs for plausible responsibilities
            $resumeTextContent .= '- '.$faker->bs()."\n";
            if ($faker->boolean(30)) { // Occasionally add a third point
                $resumeTextContent .= '- '.$faker->bs()."\n";
            }
            $resumeTextContent .= "\n";
        }

        $resumeTextContent .= "### Education\n";
        $resumeTextContent .= "**{$faker->randomElement(['B.S.', 'B.A.', 'M.S.', 'MBA'])} in {$faker->bs()}** | {$faker->company()} University | {$faker->year()}\n\n"; // bs is okay for fake major

        $resumeTextContent .= "### Skills\n";
        $skills = $faker->randomElements([
            'PHP', 'Laravel', 'JavaScript', 'React', 'Vue.js', 'Node.js', 'Python', 'Django',
            'Java', 'Spring Boot', 'C#', '.NET', 'SQL', 'PostgreSQL', 'MySQL', 'MongoDB',
            'AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes', 'Terraform', 'Git', 'CI/CD',
            'Agile Methodologies', 'Problem Solving', 'Team Collaboration', 'Project Management',
        ], $faker->numberBetween(8, 15));
        $resumeTextContent .= implode(', ', $skills)."\n";
        // --- End of Simulated AI Content ---

        // --- Determine Filename and MimeType ---
        $fileExtension = $this->faker->randomElement(['pdf', 'docx']);
        $baseName = Str::slug($name.' '.$jobTitle.' Resume');
        $filename = $baseName.'.'.$fileExtension;
        $mimeType = match ($fileExtension) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };

        // --- Assign generated TEXT directly to file_data ---
        // PostgreSQL will accept this valid UTF-8 string for the bytea column.
        // The controller will later try (and fail) to parse this text as PDF/DOCX.
        $fileData = $resumeTextContent;

        return [
            'filename' => $filename,
            'mime_type' => $mimeType, // Keep the correct mime type
            'file_data' => $fileData, // Assign the TEXT content directly
            // 'user_id' is set automatically when using ->for($user) in the seeder
            // Timestamps ('created_at', 'updated_at') are handled by Eloquent automatically
        ];
    }
}
