<?php

namespace Database\Seeders;

use App\Models\Diagnosis;
use Illuminate\Database\Seeder;

class DiagnosisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $diagnoses = $this->getCommonDiagnoses();

        foreach ($diagnoses as $name) {
            Diagnosis::firstOrCreate(
                ['name_normalized' => Diagnosis::normalize($name)],
                [
                    'name' => $name,
                    'usage_count' => 1,
                ]
            );
        }
    }

    /**
     * @return list<string>
     */
    private function getCommonDiagnoses(): array
    {
        return [
            // Respiratory System
            'Pneumonia',
            'Bronchitis Acuta',
            'Bronchitis Chronica',
            'Asthma Bronchiale',
            'Emphysema Pulmonum',
            'Pleuritis',
            'Tuberculosis Pulmonum',
            'Bronchopneumonia',
            'Influenza',
            'Pharyngitis Acuta',
            'Tonsillitis Acuta',
            'Laryngitis',
            'Sinusitis',
            'Rhinitis',
            'COPD',

            // Cardiovascular System
            'Hypertensio Arterialis',
            'Insufficientia Cordis',
            'Infarctus Myocardii',
            'Angina Pectoris',
            'Arrhythmia Cordis',
            'Fibrillatio Atriorum',
            'Cardiomyopathia',
            'Endocarditis',
            'Pericarditis',
            'Thrombosis Venosa Profunda',
            'Embolia Pulmonalis',
            'Atherosclerosis',
            'Aneurysma Aortae',
            'Insufficientia Valvulae Mitralis',
            'Stenosis Aortae',

            // Gastrointestinal System
            'Gastritis',
            'Ulcus Ventriculi',
            'Ulcus Duodeni',
            'Colitis',
            'Appendicitis Acuta',
            'Cholecystitis',
            'Cholelithiasis',
            'Pancreatitis Acuta',
            'Pancreatitis Chronica',
            'Hepatitis',
            'Cirrhosis Hepatis',
            'Hernia Inguinalis',
            'Hernia Umbilicalis',
            'Hernia Incisionalis',
            'Ileus',
            'Peritonitis',
            'Diverticulitis',
            'Morbus Crohn',
            'Colitis Ulcerosa',
            'Haemorrhoides',
            'Fissura Ani',

            // Metabolic and Endocrine
            'Diabetes Mellitus Type I',
            'Diabetes Mellitus Type II',
            'Hyperthyreosis',
            'Hypothyreosis',
            'Struma',
            'Obesitas',
            'Hyperlipidaemia',
            'Gutta',
            'Osteoporosis',

            // Musculoskeletal System
            'Fractura Femoris',
            'Fractura Tibiae',
            'Fractura Radii',
            'Fractura Humeri',
            'Fractura Costae',
            'Fractura Claviculae',
            'Fractura Vertebrae',
            'Luxatio',
            'Distorsio',
            'Contusio',
            'Arthritis',
            'Arthrosis',
            'Coxarthrosis',
            'Gonarthrosis',
            'Lumbago',
            'Hernia Disci Intervertebralis',
            'Scoliosis',
            'Osteomyelitis',
            'Tendinitis',
            'Bursitis',

            // Urological System
            'Pyelonephritis',
            'Cystitis',
            'Urolithiasis',
            'Nephritis',
            'Insufficientia Renalis Acuta',
            'Insufficientia Renalis Chronica',
            'Hyperplasia Prostatae Benigna',
            'Prostatitis',
            'Hydronephrosis',

            // Neurological System
            'Ictus Cerebri',
            'Epilepsia',
            'Morbus Parkinson',
            'Sclerosis Multiplex',
            'Meningitis',
            'Encephalitis',
            'Cephalea',
            'Migraine',
            'Neuralgia',
            'Polyneuropathia',
            'Dementia',
            'Morbus Alzheimer',

            // Psychiatric
            'Depressio',
            'Anxietas',
            'Schizophrenia',
            'Psychosis',
            'Insomnia',

            // Infectious Diseases
            'Sepsis',
            'COVID-19',
            'Gastroenteritis',
            'Cellulitis',
            'Abscessus',
            'Erysipelas',
            'Herpes Zoster',

            // Oncology
            'Carcinoma Pulmonis',
            'Carcinoma Mammae',
            'Carcinoma Coli',
            'Carcinoma Prostatae',
            'Carcinoma Gastricae',
            'Carcinoma Hepatis',
            'Carcinoma Pancreatis',
            'Carcinoma Vesicae',
            'Lymphoma',
            'Leukaemia',

            // Gynecological
            'Myoma Uteri',
            'Endometriosis',
            'Cystis Ovarii',
            'Graviditas',
            'Abortus',
            'Partus',

            // Dermatological
            'Dermatitis',
            'Eczema',
            'Psoriasis',
            'Urticaria',

            // Ophthalmic
            'Cataracta',
            'Glaucoma',
            'Conjunctivitis',

            // ENT
            'Otitis Media',
            'Otitis Externa',
            'Vertigo',

            // Hematological
            'Anaemia',
            'Anaemia Ferripriva',
            'Thrombocytopenia',
            'Coagulopathia',

            // Allergic
            'Allergia',
            'Anaphylaxis',

            // Trauma
            'Vulnus',
            'Combustio',
            'Trauma Capitis',
            'Polytrauma',

            // Other Common
            'Syncope',
            'Dolor Thoracis',
            'Dolor Abdominis',
            'Febris',
            'Dehydratio',
            'Intoxicatio',
        ];
    }
}
