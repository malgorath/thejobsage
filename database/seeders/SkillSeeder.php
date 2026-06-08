<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Import DB facade

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        // Safely clear skills while respecting foreign keys
        if ($driver === 'mysql') {
            $connection->statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('skills')->truncate();
            $connection->statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            DB::table('skills')->delete();
        }

        $skills = [
            // Programming Languages
            'PHP', 'JavaScript', 'Python', 'Java', 'C#', 'C++', 'Ruby', 'Go', 'Swift', 'Kotlin', 'TypeScript',
            // Frontend Frameworks/Libraries
            'React', 'Vue.js', 'Angular', 'Svelte', 'jQuery', 'HTML5', 'CSS3', 'Tailwind CSS', 'Bootstrap', 'Sass/Less',
            // Backend Frameworks/Libraries
            'Laravel', 'Symfony', 'Node.js', 'Express.js', 'Django', 'Flask', 'Ruby on Rails', 'Spring Boot', '.NET Core',
            // Databases
            'MySQL', 'PostgreSQL', 'SQLite', 'MongoDB', 'Redis', 'Microsoft SQL Server', 'Oracle Database', 'Cassandra',
            // DevOps & Cloud
            'Docker', 'Kubernetes', 'AWS', 'Google Cloud Platform (GCP)', 'Microsoft Azure', 'Terraform', 'Ansible', 'Jenkins', 'GitLab CI/CD', 'Linux/Unix Administration', 'Nginx', 'Apache',
            // Data Science & ML
            'Pandas', 'NumPy', 'Scikit-learn', 'TensorFlow', 'PyTorch', 'SQL', 'Data Visualization', 'Big Data (Hadoop/Spark)',
            // Other IT Skills
            'Git', 'REST APIs', 'GraphQL', 'Agile Methodologies', 'Scrum', 'JIRA', 'Network Configuration', 'Cybersecurity Principles', 'Penetration Testing', 'System Design', 'Microservices Architecture', 'Testing (Unit, Integration, E2E)', 'Problem Solving', 'Communication',
        ];

        $skillData = [];
        $now = now();
        foreach ($skills as $skillName) {
            // Use firstOrCreate to avoid duplicates if you don't truncate
            // Skill::firstOrCreate(['name' => $skillName]);

            // Or build array for mass insert (more efficient)
            $skillData[] = ['name' => $skillName, 'created_at' => $now, 'updated_at' => $now];
        }

        // Mass insert the skills
        Skill::insert($skillData);

        $this->command->info('IT Skills table seeded!');
    }
}
