<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some companies first
        $companies = [
            Company::firstOrCreate(['name' => 'TechCorp Solutions'], [
                'website' => 'https://techcorp.example.com',
                'description' => 'Leading technology solutions provider specializing in enterprise software.',
            ]),
            Company::firstOrCreate(['name' => 'Digital Innovations Inc'], [
                'website' => 'https://digitalinnovations.example.com',
                'description' => 'Innovative digital agency focused on cutting-edge web and mobile applications.',
            ]),
            Company::firstOrCreate(['name' => 'Cloud Systems Ltd'], [
                'website' => 'https://cloudsystems.example.com',
                'description' => 'Cloud infrastructure and DevOps solutions for modern businesses.',
            ]),
            Company::firstOrCreate(['name' => 'Data Analytics Pro'], [
                'website' => 'https://dataanalytics.example.com',
                'description' => 'Data science and analytics consulting firm.',
            ]),
            Company::firstOrCreate(['name' => 'SecureNet Technologies'], [
                'website' => 'https://securenet.example.com',
                'description' => 'Cybersecurity and network infrastructure specialists.',
            ]),
        ];

        // Create demo jobs
        $jobs = [
            [
                'title' => 'Senior Laravel Developer',
                'company' => 'TechCorp Solutions',
                'location' => 'San Francisco, CA',
                'description' => 'We are seeking an experienced Laravel developer to join our growing team. You will be responsible for developing and maintaining high-quality web applications using Laravel framework. The ideal candidate should have strong PHP skills, experience with RESTful APIs, and a passion for clean, maintainable code.',
                'requirements' => [
                    '5+ years of PHP/Laravel experience',
                    'Strong knowledge of MySQL and database design',
                    'Experience with Git version control',
                    'Understanding of RESTful API design',
                    'Familiarity with front-end technologies (Vue.js, React)',
                    'Excellent problem-solving skills',
                ],
                'company_id' => $companies[0]->id,
            ],
            [
                'title' => 'Full Stack Developer',
                'company' => 'Digital Innovations Inc',
                'location' => 'New York, NY',
                'description' => 'Join our dynamic team as a Full Stack Developer working on innovative web applications. You will work on both front-end and back-end development, collaborating with designers and product managers to deliver exceptional user experiences.',
                'requirements' => [
                    '3+ years of full-stack development experience',
                    'Proficiency in PHP, JavaScript, and modern frameworks',
                    'Experience with Laravel or similar MVC frameworks',
                    'Strong understanding of HTML, CSS, and responsive design',
                    'Knowledge of database design and optimization',
                    'Ability to work in an agile environment',
                ],
                'company_id' => $companies[1]->id,
            ],
            [
                'title' => 'DevOps Engineer',
                'company' => 'Cloud Systems Ltd',
                'location' => 'Austin, TX',
                'description' => 'We are looking for a DevOps Engineer to help us build and maintain our cloud infrastructure. You will work with Docker, Kubernetes, CI/CD pipelines, and cloud platforms to ensure our applications run smoothly and scale efficiently.',
                'requirements' => [
                    '4+ years of DevOps or infrastructure experience',
                    'Strong knowledge of Docker and containerization',
                    'Experience with Kubernetes or similar orchestration tools',
                    'Familiarity with AWS, Azure, or GCP',
                    'CI/CD pipeline design and implementation',
                    'Scripting skills (Bash, Python, or similar)',
                ],
                'company_id' => $companies[2]->id,
            ],
            [
                'title' => 'Data Scientist',
                'company' => 'Data Analytics Pro',
                'location' => 'Boston, MA',
                'description' => 'Seeking a Data Scientist to analyze complex datasets and provide actionable insights. You will work with machine learning models, statistical analysis, and data visualization to help drive business decisions.',
                'requirements' => [
                    'Master\'s degree in Data Science, Statistics, or related field',
                    '3+ years of experience in data analysis',
                    'Proficiency in Python or R',
                    'Experience with machine learning frameworks',
                    'Strong statistical analysis skills',
                    'Excellent communication and presentation skills',
                ],
                'company_id' => $companies[3]->id,
            ],
            [
                'title' => 'Cybersecurity Specialist',
                'company' => 'SecureNet Technologies',
                'location' => 'Washington, DC',
                'description' => 'Join our security team to protect our clients from cyber threats. You will conduct security audits, implement security measures, and respond to security incidents. This role requires staying current with the latest security threats and technologies.',
                'requirements' => [
                    '5+ years of cybersecurity experience',
                    'Certifications: CISSP, CEH, or similar',
                    'Experience with penetration testing',
                    'Knowledge of network security and firewalls',
                    'Familiarity with security frameworks (NIST, ISO 27001)',
                    'Strong analytical and problem-solving skills',
                ],
                'company_id' => $companies[4]->id,
            ],
            [
                'title' => 'PHP Backend Developer',
                'company' => 'TechCorp Solutions',
                'location' => 'Remote',
                'description' => 'Remote opportunity for a PHP Backend Developer to work on scalable web applications. You will design and implement backend services, work with APIs, and optimize database queries for performance.',
                'requirements' => [
                    '3+ years of PHP development experience',
                    'Experience with Laravel or Symfony',
                    'Strong SQL and database optimization skills',
                    'Understanding of API design and RESTful principles',
                    'Experience with version control (Git)',
                    'Ability to work independently and remotely',
                ],
                'company_id' => $companies[0]->id,
            ],
            [
                'title' => 'Frontend Developer',
                'company' => 'Digital Innovations Inc',
                'location' => 'Seattle, WA',
                'description' => 'We are looking for a talented Frontend Developer to create beautiful and functional user interfaces. You will work with modern JavaScript frameworks, CSS preprocessors, and design systems to build responsive web applications.',
                'requirements' => [
                    '3+ years of frontend development experience',
                    'Proficiency in JavaScript, HTML, and CSS',
                    'Experience with React, Vue.js, or Angular',
                    'Knowledge of responsive design principles',
                    'Familiarity with build tools (Webpack, Vite)',
                    'Strong attention to detail and design aesthetics',
                ],
                'company_id' => $companies[1]->id,
            ],
            [
                'title' => 'Cloud Architect',
                'company' => 'Cloud Systems Ltd',
                'location' => 'Denver, CO',
                'description' => 'Lead cloud architecture initiatives for enterprise clients. You will design scalable cloud solutions, migrate legacy systems, and provide technical leadership to development teams.',
                'requirements' => [
                    '7+ years of IT infrastructure experience',
                    '5+ years of cloud architecture experience',
                    'Expert knowledge of AWS, Azure, or GCP',
                    'Experience with infrastructure as code (Terraform, CloudFormation)',
                    'Strong understanding of microservices architecture',
                    'Excellent communication and leadership skills',
                ],
                'company_id' => $companies[2]->id,
            ],
            [
                'title' => 'Machine Learning Engineer',
                'company' => 'Data Analytics Pro',
                'location' => 'Chicago, IL',
                'description' => 'Develop and deploy machine learning models to solve complex business problems. You will work with large datasets, implement ML algorithms, and collaborate with data scientists and engineers.',
                'requirements' => [
                    'Master\'s or PhD in Computer Science, ML, or related field',
                    '3+ years of ML engineering experience',
                    'Strong Python programming skills',
                    'Experience with TensorFlow, PyTorch, or similar',
                    'Knowledge of MLOps and model deployment',
                    'Understanding of deep learning architectures',
                ],
                'company_id' => $companies[3]->id,
            ],
            [
                'title' => 'Security Engineer',
                'company' => 'SecureNet Technologies',
                'location' => 'Atlanta, GA',
                'description' => 'Design and implement security solutions to protect our infrastructure and applications. You will work on security automation, threat detection, and incident response systems.',
                'requirements' => [
                    '4+ years of security engineering experience',
                    'Experience with security tools (SIEM, IDS/IPS)',
                    'Knowledge of security automation and scripting',
                    'Understanding of cloud security best practices',
                    'Familiarity with compliance requirements (SOC 2, GDPR)',
                    'Strong problem-solving and analytical skills',
                ],
                'company_id' => $companies[4]->id,
            ],
            [
                'title' => 'Junior Software Developer',
                'company' => 'TechCorp Solutions',
                'location' => 'Portland, OR',
                'description' => 'Entry-level position for a motivated developer to learn and grow. You will work on bug fixes, feature development, and learn best practices from senior developers. Great opportunity for recent graduates or career changers.',
                'requirements' => [
                    'Bachelor\'s degree in Computer Science or related field',
                    'Basic knowledge of PHP and web development',
                    'Understanding of HTML, CSS, and JavaScript',
                    'Willingness to learn and adapt',
                    'Strong problem-solving skills',
                    'Good communication and teamwork abilities',
                ],
                'company_id' => $companies[0]->id,
            ],
            [
                'title' => 'UI/UX Designer',
                'company' => 'Digital Innovations Inc',
                'location' => 'Los Angeles, CA',
                'description' => 'Create intuitive and visually appealing user interfaces for web and mobile applications. You will collaborate with developers and product managers to design user experiences that delight users and drive engagement.',
                'requirements' => [
                    '3+ years of UI/UX design experience',
                    'Proficiency in design tools (Figma, Sketch, Adobe XD)',
                    'Strong portfolio demonstrating design skills',
                    'Understanding of user research and usability testing',
                    'Knowledge of design systems and component libraries',
                    'Excellent visual design and typography skills',
                ],
                'company_id' => $companies[1]->id,
            ],
            [
                'title' => 'Database Administrator',
                'company' => 'Cloud Systems Ltd',
                'location' => 'Phoenix, AZ',
                'description' => 'Manage and optimize our database infrastructure. You will ensure database performance, implement backup strategies, and work with development teams to design efficient database schemas.',
                'requirements' => [
                    '5+ years of database administration experience',
                    'Expert knowledge of MySQL, PostgreSQL, or similar',
                    'Experience with database optimization and tuning',
                    'Knowledge of backup and recovery procedures',
                    'Understanding of database security best practices',
                    'Strong troubleshooting and problem-solving skills',
                ],
                'company_id' => $companies[2]->id,
            ],
            [
                'title' => 'Business Intelligence Analyst',
                'company' => 'Data Analytics Pro',
                'location' => 'Miami, FL',
                'description' => 'Transform data into actionable business insights. You will create reports, dashboards, and data visualizations to help stakeholders make informed decisions. Experience with BI tools and SQL is essential.',
                'requirements' => [
                    '3+ years of BI or data analysis experience',
                    'Proficiency in SQL and data querying',
                    'Experience with BI tools (Tableau, Power BI, Looker)',
                    'Strong analytical and critical thinking skills',
                    'Ability to translate data into business insights',
                    'Excellent presentation and communication skills',
                ],
                'company_id' => $companies[3]->id,
            ],
            [
                'title' => 'Network Security Administrator',
                'company' => 'SecureNet Technologies',
                'location' => 'Dallas, TX',
                'description' => 'Maintain and secure our network infrastructure. You will configure firewalls, monitor network traffic, and respond to security incidents. This role requires strong networking knowledge and security expertise.',
                'requirements' => [
                    '4+ years of network administration experience',
                    'Strong knowledge of network protocols and security',
                    'Experience with firewall configuration and management',
                    'Familiarity with network monitoring tools',
                    'Understanding of VPN and remote access solutions',
                    'Relevant certifications (CCNA, Network+, Security+)',
                ],
                'company_id' => $companies[4]->id,
            ],
        ];

        foreach ($jobs as $jobData) {
            Job::firstOrCreate(
                [
                    'title' => $jobData['title'],
                    'company' => $jobData['company'],
                ],
                $jobData
            );
        }

        $this->command->info('Seeded '.count($jobs).' job listings.');
    }
}
