<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ORACLES : jeu de cartes et tirages des utilisateurs.
 * L'index unique (user_id, oracle_card_id, cycle) garantit côté base
 * qu'une carte n'est jamais tirée deux fois par le même utilisateur dans le même mois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oracle_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('message');
            $table->text('interpretation');
            $table->string('keywords')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('oracle_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('oracle_card_id')->constrained()->cascadeOnDelete();
            $table->char('cycle', 7); // AAAA-MM
            $table->text('personalized_interpretation')->nullable();
            $table->timestamp('drawn_at');
            $table->timestamps();

            $table->unique(['user_id', 'oracle_card_id', 'cycle']);
            $table->index(['user_id', 'drawn_at']);
        });

        $this->seedDeck();
    }

    public function down(): void
    {
        Schema::dropIfExists('oracle_draws');
        Schema::dropIfExists('oracle_cards');
    }

    /** Jeu initial de 24 cartes (modifiable dans l'admin). Un tirage tous les 3 jours = 11 cartes max par mois. */
    private function seedDeck(): void
    {
        $cards = [
            ['L\'Aube', 'Un nouveau départ se dessine.', 'Cette carte symbolise le recommencement. Elle invite à accueillir ce qui commence avec curiosité plutôt qu\'avec crainte. Quelle petite action pourrait marquer ce nouveau départ aujourd\'hui ?', 'renouveau, commencement, élan'],
            ['La Source', 'Reviens à ce qui te nourrit.', 'La Source rappelle l\'importance de se ressourcer. Elle propose de repérer ce qui te recharge vraiment : un lieu, une personne, une pratique. Accorde-toi un moment pour y revenir.', 'ressourcement, énergie, essentiel'],
            ['Le Pont', 'Relie ce qui semblait séparé.', 'Le Pont évoque le passage d\'un état à un autre. Il suggère de chercher un lien entre deux aspects de ta vie qui semblent éloignés. Quelle transition est en train de s\'opérer en toi ?', 'transition, lien, passage'],
            ['Le Silence', 'La réponse naît dans le calme.', 'Cette carte invite à ralentir. Dans le silence, les pensées se déposent et la clarté peut émerger. Quelques minutes de calme aujourd\'hui pourraient t\'apporter un éclairage nouveau.', 'calme, écoute, clarté'],
            ['La Racine', 'Ancre-toi dans ce qui est stable.', 'La Racine parle de stabilité et d\'appartenance. Elle t\'invite à t\'appuyer sur tes valeurs et tes habitudes solides pour traverser les périodes de changement.', 'ancrage, stabilité, valeurs'],
            ['L\'Envol', 'Ose prendre ton élan.', 'L\'Envol symbolise l\'audace. Il propose de considérer un projet que tu repousses et de faire un premier pas, même modeste. Le mouvement crée souvent la confiance.', 'audace, liberté, projet'],
            ['Le Miroir', 'Regarde-toi avec bienveillance.', 'Le Miroir invite à l\'introspection douce. Que vois-tu en toi aujourd\'hui ? Cette carte suggère de remplacer le jugement par la compréhension.', 'introspection, bienveillance, acceptation'],
            ['La Graine', 'Ce que tu sèmes aujourd\'hui grandira.', 'La Graine évoque la patience. Les efforts présents portent leurs fruits avec le temps. Quelle intention aimerais-tu planter et entretenir ce mois-ci ?', 'patience, intention, croissance'],
            ['Le Feu', 'Ta passion éclaire le chemin.', 'Le Feu symbolise l\'enthousiasme et la volonté. Il t\'invite à reconnecter avec ce qui t\'anime profondément, tout en veillant à ne pas t\'épuiser.', 'passion, volonté, énergie'],
            ['L\'Eau', 'Laisse couler ce qui doit passer.', 'L\'Eau parle de fluidité et d\'adaptation. Elle propose de lâcher prise sur ce que tu ne contrôles pas et de t\'ajuster avec souplesse aux circonstances.', 'fluidité, lâcher-prise, émotions'],
            ['La Montagne', 'Avance pas à pas.', 'La Montagne rappelle que les grands objectifs se gravissent progressivement. Elle invite à découper ton défi actuel en étapes accessibles.', 'persévérance, objectif, effort'],
            ['L\'Étoile', 'Garde ta lumière intérieure.', 'L\'Étoile symbolise l\'espoir et l\'inspiration. Même dans les moments flous, elle t\'invite à garder en vue ce qui donne du sens à ta démarche.', 'espoir, inspiration, guidance'],
            ['La Lune', 'Écoute ton intuition.', 'La Lune évoque le monde intérieur et les ressentis. Elle suggère de prêter attention à tes intuitions et à tes rêves comme pistes de réflexion.', 'intuition, ressenti, cycles'],
            ['Le Soleil', 'Rayonne simplement.', 'Le Soleil symbolise la joie et la vitalité. Il t\'invite à célébrer ce qui va bien et à partager ta chaleur avec ton entourage.', 'joie, vitalité, rayonnement'],
            ['Le Souffle', 'Respire, tout commence là.', 'Le Souffle rappelle le pouvoir de la respiration consciente. Quelques respirations lentes peuvent apaiser le corps et l\'esprit avant une décision.', 'respiration, présence, apaisement'],
            ['Le Cercle', 'Tout revient à son équilibre.', 'Le Cercle parle des cycles et de l\'harmonie. Il invite à observer l\'équilibre entre donner et recevoir, agir et se reposer.', 'équilibre, cycles, harmonie'],
            ['La Clé', 'Tu as déjà ce qu\'il faut.', 'La Clé symbolise les ressources intérieures. Elle suggère qu\'une solution est peut-être déjà à ta portée, dans une compétence ou une expérience passée.', 'ressources, solution, confiance'],
            ['Le Jardin', 'Prends soin de ce qui compte.', 'Le Jardin évoque l\'entretien patient de ce que l\'on aime : relations, santé, projets. Quelle part de ton jardin mérite un peu d\'attention aujourd\'hui ?', 'soin, relations, entretien'],
            ['Le Chemin', 'Chaque pas a sa valeur.', 'Le Chemin rappelle que le voyage compte autant que la destination. Il t\'invite à reconnaître les progrès déjà accomplis.', 'parcours, progrès, gratitude'],
            ['La Plume', 'Allège ce qui pèse.', 'La Plume symbolise la légèreté. Elle propose d\'identifier une préoccupation que tu pourrais déposer, écrire ou partager pour t\'en alléger.', 'légèreté, libération, simplicité'],
            ['Le Phare', 'Garde le cap.', 'Le Phare évoque la direction et la constance. Il t\'invite à te rappeler tes priorités lorsque les distractions se multiplient.', 'direction, priorités, constance'],
            ['La Rivière', 'Accepte le mouvement de la vie.', 'La Rivière parle du changement continu. Elle propose d\'accueillir l\'évolution plutôt que de lui résister.', 'changement, acceptation, mouvement'],
            ['Le Cristal', 'Clarifie ton intention.', 'Le Cristal symbolise la transparence et la clarté. Il t\'invite à formuler simplement ce que tu souhaites vraiment, pour mieux t\'orienter.', 'clarté, intention, vérité'],
            ['L\'Arbre de Vie', 'Grandis à ton rythme.', 'L\'Arbre de Vie évoque la croissance harmonieuse entre ancrage et élévation. Il rappelle que chacun évolue à son propre rythme.', 'croissance, rythme, unité'],
        ];

        $now = now();
        DB::table('oracle_cards')->insert(array_map(fn ($c) => [
            'name' => $c[0],
            'message' => $c[1],
            'interpretation' => $c[2],
            'keywords' => $c[3],
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $cards));
    }
};
