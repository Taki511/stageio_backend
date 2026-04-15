<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use App\Models\InternshipOffer;
use App\Models\Skill;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InternshipOfferSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = CompanyProfile::first();

        if (!$company) {
            $this->command->warn('No company profile found. Skipping internship offers seeding.');
            return;
        }

        $offers = [
            [
                'title' => 'Développeur Web Full Stack',
                'description' => 'Stage en développement web utilisant Laravel et Vue.js. Vous travaillerez sur des projets réels avec une équipe agile.',
                'wilaya' => 'Constantine',
                'start_date' => '2025-06-01',
                'internship_type' => 'full_time',
                'duration' => 12,
                'status' => InternshipOffer::STATUS_OPEN,
                'max_students' => 2,
                'deadline' => '2025-05-15',
                'skills' => ['PHP', 'Laravel', 'Vue.js', 'JavaScript'],
            ],
            [
                'title' => 'Développeur Mobile Flutter',
                'description' => 'Conception et développement d\'applications mobiles cross-platform avec Flutter et Dart.',
                'wilaya' => 'Alger',
                'start_date' => '2025-07-01',
                'internship_type' => 'remote',
                'duration' => 16,
                'status' => InternshipOffer::STATUS_OPEN,
                'max_students' => 1,
                'deadline' => '2025-06-10',
                'skills' => ['Flutter', 'Dart', 'Firebase'],
            ],
            [
                'title' => 'Data Analyst Junior',
                'description' => 'Analyse de données et création de tableaux de bord avec Python, Pandas et Power BI.',
                'wilaya' => 'Oran',
                'start_date' => '2025-06-15',
                'internship_type' => 'part_time',
                'duration' => 8,
                'status' => InternshipOffer::STATUS_OPEN,
                'max_students' => 3,
                'deadline' => '2025-05-30',
                'skills' => ['Python', 'Pandas', 'Power BI', 'SQL'],
            ],
            [
                'title' => 'Ingénieur DevOps',
                'description' => 'Mise en place de pipelines CI/CD, gestion d\'infrastructure cloud et conteneurisation Docker.',
                'wilaya' => 'Constantine',
                'start_date' => '2025-08-01',
                'internship_type' => 'full_time',
                'duration' => 20,
                'status' => InternshipOffer::STATUS_OPEN,
                'max_students' => 1,
                'deadline' => '2025-07-15',
                'skills' => ['Docker', 'Kubernetes', 'AWS', 'CI/CD'],
            ],
            [
                'title' => 'UI/UX Designer',
                'description' => 'Conception d\'interfaces utilisateur modernes, prototypage avec Figma et tests utilisateurs.',
                'wilaya' => 'Alger',
                'start_date' => '2025-06-01',
                'internship_type' => 'full_time',
                'duration' => 10,
                'status' => InternshipOffer::STATUS_OPEN,
                'max_students' => 2,
                'deadline' => '2025-05-20',
                'skills' => ['Figma', 'Adobe XD', 'Prototyping', 'User Research'],
            ],
            [
                'title' => 'Développeur Backend Python',
                'description' => 'Développement d\'APIs REST avec Django et Django REST Framework. Intégration avec PostgreSQL.',
                'wilaya' => 'Oran',
                'start_date' => '2025-07-15',
                'internship_type' => 'remote',
                'duration' => 14,
                'status' => InternshipOffer::STATUS_OPEN,
                'max_students' => 2,
                'deadline' => '2025-06-30',
                'skills' => ['Python', 'Django', 'PostgreSQL', 'REST API'],
            ],
            [
                'title' => 'Testeur QA Automatisation',
                'description' => 'Écriture de tests automatisés avec Selenium et Cypress. Assurance qualité des livrables.',
                'wilaya' => 'Constantine',
                'start_date' => '2025-09-01',
                'internship_type' => 'full_time',
                'duration' => 12,
                'status' => InternshipOffer::STATUS_OPEN,
                'max_students' => 1,
                'deadline' => '2025-08-15',
                'skills' => ['Selenium', 'Cypress', 'Testing', 'Git'],
            ],
            [
                'title' => 'Administrateur Systèmes et Réseaux',
                'description' => 'Gestion de réseaux, administration de serveurs Linux et supervision avec Nagios.',
                'wilaya' => 'Alger',
                'start_date' => '2025-06-01',
                'internship_type' => 'full_time',
                'duration' => 16,
                'status' => InternshipOffer::STATUS_OPEN,
                'max_students' => 1,
                'deadline' => '2025-05-10',
                'skills' => ['Linux', 'Networking', 'Nagios', 'Bash'],
            ],
            [
                'title' => 'Développeur Frontend React',
                'description' => 'Construction d\'interfaces dynamiques avec React.js, Tailwind CSS et intégration d\'APIs.',
                'wilaya' => 'Oran',
                'start_date' => '2025-07-01',
                'internship_type' => 'part_time',
                'duration' => 10,
                'status' => InternshipOffer::STATUS_OPEN,
                'max_students' => 2,
                'deadline' => '2025-06-15',
                'skills' => ['React', 'Tailwind CSS', 'JavaScript', 'REST API'],
            ],
            [
                'title' => 'Consultant Cybersécurité',
                'description' => 'Audit de sécurité, tests d\'intrusion et mise en place de bonnes pratiques de sécurité.',
                'wilaya' => 'Constantine',
                'start_date' => '2025-08-15',
                'internship_type' => 'full_time',
                'duration' => 18,
                'status' => InternshipOffer::STATUS_OPEN,
                'max_students' => 1,
                'deadline' => '2025-07-31',
                'skills' => ['Penetration Testing', 'OWASP', 'Network Security', 'Linux'],
            ],
        ];

        foreach ($offers as $offerData) {
            $skills = $offerData['skills'];
            unset($offerData['skills']);

            $offerData['company_profile_id'] = $company->id;

            $offer = InternshipOffer::firstOrCreate(
                [
                    'company_profile_id' => $company->id,
                    'title' => $offerData['title'],
                ],
                $offerData
            );

            $skillIds = [];
            foreach ($skills as $skillName) {
                $skill = Skill::firstOrCreate(['name' => $skillName]);
                $skillIds[] = $skill->id;
            }

            $offer->skills()->sync($skillIds);
        }

        $this->command->info('10 internship offers seeded successfully!');
    }
}
