<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plus aucun contenu écrit en dur dans l'app : les textes, l'ordre des rubriques de
 * l'accueil et les objectifs proposés à l'inscription sont gérés dans l'admin.
 * Les valeurs déjà modifiées par l'admin ne sont jamais écrasées.
 */
return new class extends Migration
{
    /** Ordre de l'accueil (nouvelle architecture), avec le nom par défaut des rubriques manquantes. */
    private array $menu = [
        'assistant' => 'Assistant IA',
        'oracles' => 'Oracles',
        'activities' => 'Activités',
        'fitness' => 'Fitness Center',
        'journal' => 'My Journal',
        'challenges' => 'Défis du mois',
        'inner-gates' => 'Face à toi',
        'sacred-space' => 'Espace Sacré des Lois',
        'articles' => 'Le pouvoir secret',
        'events' => 'Événements',
        'retreats' => 'Plan de retraite',
        'cuisine' => 'Cuisine',
    ];

    /** Rubriques remplacées (Oracles, Activités, My Journal) : gardées pour leurs images, masquées. */
    private array $legacy = ['affirmation', 'meditation', 'gratitude'];

    /** Objectifs proposés à l'inscription : nom => ancien nom sans accents. */
    private array $objectives = [
        'Paix intérieure' => 'Paix interieur',
        'Réduction du stress' => 'Reduction du stress',
        'Meilleur sommeil' => null,
        'Concentration' => null,
        'Énergie vitale' => 'Energie vitale',
        'Créativité' => 'Creativité',
        'Confiance en soi' => null,
        'Relations harmonieuses' => null,
        'Santé globale' => null,
        'Éveil spirituel' => null,
    ];

    public function up(): void
    {
        $now = now();
        $siteUrl = rtrim((string) config('app.url'), '/');

        // 1. Textes de l'app (onglet « Contenu Textuel » du CMS).
        foreach ($this->texts() as $key => [$label, $description, $value]) {
            $this->addSetting($key, 'content', $label, $description, $value, $now);
        }
        $this->addSetting(
            'privacy_policy_url', 'general', 'URL de la politique de confidentialité complète',
            'Lien « Lire la politique complète » dans l\'app.', $siteUrl . '/politique-confidentialite.html', $now
        );
        // Lien partagé par « Partager l'app ».
        DB::table('app_settings')->where('key', 'app_website_url')
            ->where(fn ($q) => $q->whereNull('value')->orWhere('value', ''))
            ->update(['value' => $siteUrl, 'updated_at' => $now]);

        // 2. Rubriques de l'accueil : visibilité et ordre gérés dans l'admin.
        if (!Schema::hasColumn('home_menu_items', 'is_visible')) {
            Schema::table('home_menu_items', function (Blueprint $table) {
                $table->boolean('is_visible')->default(true)->after('sort_order');
            });
        }
        $position = 0;
        foreach ($this->menu as $slug => $name) {
            $position++;
            if (DB::table('home_menu_items')->where('slug', $slug)->exists()) {
                DB::table('home_menu_items')->where('slug', $slug)->update(['sort_order' => $position, 'updated_at' => $now]);
            } else {
                DB::table('home_menu_items')->insert([
                    'slug' => $slug, 'name' => $name, 'sort_order' => $position, 'is_visible' => true,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
        foreach ($this->legacy as $i => $slug) {
            DB::table('home_menu_items')->where('slug', $slug)
                ->update(['is_visible' => false, 'sort_order' => 90 + $i, 'updated_at' => $now]);
        }

        // 3. Objectifs : ordre d'affichage, accents corrigés, objectifs manquants ajoutés.
        if (!Schema::hasColumn('objectives', 'sort_order')) {
            Schema::table('objectives', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('name');
            });
        }
        $position = 0;
        foreach ($this->objectives as $name => $oldName) {
            $position++;
            $row = DB::table('objectives')->where('name', $name)->first()
                ?? ($oldName ? DB::table('objectives')->where('name', $oldName)->first() : null);
            if ($row) {
                DB::table('objectives')->where('id', $row->id)
                    ->update(['name' => $name, 'sort_order' => $position, 'updated_at' => $now]);
            } else {
                DB::table('objectives')->insert([
                    'name' => $name, 'sort_order' => $position, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('app_settings')->whereIn('key', [...array_keys($this->texts()), 'privacy_policy_url'])->delete();
        if (Schema::hasColumn('home_menu_items', 'is_visible')) {
            Schema::table('home_menu_items', fn (Blueprint $table) => $table->dropColumn('is_visible'));
        }
        if (Schema::hasColumn('objectives', 'sort_order')) {
            Schema::table('objectives', fn (Blueprint $table) => $table->dropColumn('sort_order'));
        }
    }

    private function addSetting(string $key, string $group, string $label, string $description, string $value, $now): void
    {
        if (DB::table('app_settings')->where('key', $key)->exists()) {
            return;
        }
        DB::table('app_settings')->insert([
            'key' => $key, 'value' => $value, 'type' => 'text', 'group' => $group,
            'label' => $label, 'description' => $description, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /** Textes jusqu'ici écrits dans l'app : clé => [libellé, aide, valeur]. */
    private function texts(): array
    {
        return [
            'inner_gates_tagline' => [
                'Face à toi — phrase d\'intention',
                'Phrase affichée en haut de l\'écran des 7 Portes. Laisser vide pour la masquer.',
                'Explore tes ombres, libère ton potentiel, et révèle ton essence.',
            ],
            'inner_gates_about' => [
                'Face à toi — présentation du parcours',
                'Texte du bouton « À propos du parcours ». Laisser vide pour masquer le bouton.',
                "Sept portes, inspirées de la symbolique des chakras, pour explorer tes ombres, libérer ton potentiel et révéler ton essence.\n\n"
                . "Chaque porte propose un enseignement, une pratique guidée, des questions d'introspection et une mise en pratique. "
                . "Elle s'ouvre quand la précédente est terminée : avance à ton rythme, sans pression.\n\n"
                . "Tes réponses écrites restent sur ton téléphone. Ce parcours est un outil de développement personnel : "
                . "il ne remplace pas un accompagnement médical ou psychologique.",
            ],
            'inner_gates_safety_note' => [
                'Face à toi — note de bienveillance',
                'Affichée sous les questions d\'introspection. Laisser vide pour la masquer.',
                'Si des émotions difficiles remontent, fais une pause. Tu peux en parler à ton compagnon ou à un professionnel.',
            ],
            'oracle_disclaimer' => [
                'Oracles — avertissement',
                'Affiché sous l\'interprétation de chaque carte. Laisser vide pour le masquer.',
                "Les oracles proposent une interprétation symbolique et des pistes de réflexion. Ils ne prédisent pas d'événements certains.",
            ],
            'sacred_space_intro' => [
                'Espace Sacré des Lois — présentation',
                'Texte d\'accueil de la bibliothèque de rituels. Laisser vide pour le masquer.',
                "Un espace consacré à l'apprentissage des pratiques spirituelles, des traditions et des rituels. "
                . "Chaque tutoriel présente ses objectifs, le matériel, les étapes, les précautions et les traditions auxquelles il se rattache.",
            ],
            'sacred_space_caution' => [
                'Espace Sacré des Lois — avertissement',
                'Avertissement affiché sur la bibliothèque et sur chaque tutoriel. Laisser vide pour le masquer.',
                "Ces contenus transmettent des pratiques culturelles et spirituelles. Ils ne remplacent pas un avis médical : "
                . "ne consomme aucune plante et n'utilise aucune préparation sans l'avis d'un professionnel de santé, "
                . "en particulier pendant la grossesse ou avec des enfants.",
            ],
            'privacy_policy' => [
                'Politique de confidentialité (résumé dans l\'app)',
                'Texte affiché dans l\'app (menu Plus). Le lien vers la version complète se règle dans l\'onglet Général.',
                "Nous prenons votre confidentialité très au sérieux. Cette politique explique comment nous collectons, utilisons et protégeons vos informations personnelles.\n\n"
                . "Données collectées :\n• Informations de profil (nom, préférences)\n• Données d'utilisation de l'application\n• Informations de progression\n\n"
                . "Utilisation des données :\n• Personnaliser votre expérience\n• Améliorer nos services\n• Fournir un support client\n\n"
                . "Protection des données :\n• Chiffrement des données\n• Serveurs sécurisés\n• Conformité RGPD",
            ],
        ];
    }
};
