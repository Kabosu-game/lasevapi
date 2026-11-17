<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FACE À TOI — Les 7 Portes Intérieures : parcours d'introspection en 7 niveaux (chakras).
 * Chaque porte contient des étapes (enseignement, méditation, respiration, introspection, exercice).
 * Une porte s'ouvre quand la précédente est terminée ; une porte terminée le reste
 * (inner_gate_completions), même si l'admin ajoute ensuite des étapes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inner_gates', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique();
            $table->string('name');
            $table->string('theme');
            $table->string('color', 7)->default('#D4A853');
            $table->text('description')->nullable();
            $table->string('affirmation')->nullable();
            $table->string('companion_message', 500)->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inner_gate_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inner_gate_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('type', 20); // lesson, meditation, breathing, introspection, exercise
            $table->string('title');
            $table->text('content')->nullable();
            $table->json('prompts')->nullable(); // questions d'introspection
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('media_url', 500)->nullable();
            $table->string('media_path')->nullable();
            $table->timestamps();

            $table->index(['inner_gate_id', 'position']);
        });

        Schema::create('inner_gate_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inner_gate_step_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['user_id', 'inner_gate_step_id']);
        });

        Schema::create('inner_gate_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inner_gate_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['user_id', 'inner_gate_id']);
        });

        $this->seedGates();
    }

    public function down(): void
    {
        Schema::dropIfExists('inner_gate_completions');
        Schema::dropIfExists('inner_gate_progress');
        Schema::dropIfExists('inner_gate_steps');
        Schema::dropIfExists('inner_gates');
    }

    /** Contenu de départ (modifiable dans l'admin) : 7 portes de 4 étapes. */
    private function seedGates(): void
    {
        $privacy = "Prends le temps d'écrire, sans te juger. Tes réponses restent sur ton téléphone : personne d'autre que toi ne les lit.";

        $gates = [
            [
                'name' => 'Chakra Racine',
                'theme' => 'Mes fondations et mes peurs',
                'color' => '#E53935',
                'description' => "Première porte du parcours. Dans la tradition des chakras, la racine symbolise l'ancrage : ce qui te donne un sentiment de sécurité, d'appartenance et de stabilité. C'est aussi là que se logent nos peurs les plus anciennes. Cette porte t'invite à les regarder avec douceur, sans chercher à les faire disparaître.",
                'affirmation' => 'Je suis en sécurité dans mon corps et dans ma vie.',
                'companion_message' => 'Chaque niveau te rapproche de ta vérité. Tu es prêt(e) à affronter tes premiers blocages.',
                'steps' => [
                    ['lesson', 'Comprendre la porte de la racine', 5, "La racine représente symboliquement tes fondations : ton corps, ton foyer, tes ressources, les personnes sur qui tu peux compter. Quand ces fondations semblent fragiles, la peur prend souvent le relais : peur de manquer, d'être rejeté(e), de perdre le contrôle.\n\nCes peurs ne sont pas des ennemies. Elles ont souvent servi à te protéger. Les reconnaître est le premier pas pour qu'elles ne décident plus à ta place.\n\nDans cette porte, tu vas observer ce qui t'ancre, ce qui t'inquiète, et poser un geste concret pour renforcer ta stabilité."],
                    ['meditation', "Méditation d'ancrage", 7, "Assieds-toi, les pieds bien à plat sur le sol. Ferme les yeux si tu le souhaites.\n\n1. Prends trois respirations lentes et profondes.\n2. Porte ton attention sur le contact de tes pieds avec le sol, puis sur le poids de ton corps sur ton assise.\n3. Imagine des racines qui partent de la base de ta colonne et de tes pieds et s'enfoncent profondément dans la terre.\n4. À chaque expiration, laisse descendre un peu de tension le long de ces racines.\n5. Visualise une lumière rouge, chaude et stable, à la base de ta colonne.\n6. Répète intérieurement : « Je suis en sécurité dans mon corps et dans ma vie. »\n\nReste ainsi quelques minutes, puis reviens doucement en bougeant les doigts et les orteils."],
                    ['introspection', 'Regarder mes peurs', 10, $privacy, [
                        'Dans quels moments de ta vie te sens-tu vraiment en sécurité ?',
                        'Quelle peur revient le plus souvent dans tes pensées en ce moment ?',
                        'Si cette peur pouvait parler, que chercherait-elle à protéger en toi ?',
                    ]],
                    ['exercise', "Mise en pratique : un geste d'ancrage", 15, "Choisis aujourd'hui un geste simple qui renforce tes fondations :\n• marcher pieds nus quelques minutes (herbe, sable, sol de ta maison) ;\n• ranger et désencombrer un petit espace de ton foyer ;\n• faire le point sur une question matérielle que tu repousses (budget, papiers, rendez-vous).\n\nRemarque ce que tu ressens avant et après ce geste."],
                ],
            ],
            [
                'name' => 'Chakra Sacré',
                'theme' => 'Mes émotions et mes désirs',
                'color' => '#FB8C00',
                'description' => "Deuxième porte. Le chakra sacré est associé à l'eau, au mouvement, aux émotions, au plaisir et à la créativité. Cette porte t'invite à accueillir tes émotions au lieu de les juger, et à reconnaître tes désirs profonds.",
                'affirmation' => "J'accueille mes émotions et j'honore mes désirs.",
                'companion_message' => "Tes émotions sont des messagères. Écoute ce qu'elles ont à te dire.",
                'steps' => [
                    ['lesson', 'Comprendre la porte du sacré', 5, "Symboliquement, le chakra sacré représente ta relation à tes émotions, au plaisir et à la créativité. Beaucoup d'entre nous ont appris à cacher certaines émotions : la colère, la tristesse, parfois même la joie.\n\nUne émotion retenue ne disparaît pas : elle cherche un autre chemin. L'accueillir, c'est lui permettre de circuler.\n\nDans cette porte, tu vas apprendre à nommer ce que tu ressens et à reconnaître ce que tu désires vraiment, sans culpabilité."],
                    ['breathing', 'Respiration de la vague', 5, "Installe-toi confortablement, une main sur le bas du ventre.\n\n1. Inspire lentement par le nez en gonflant le ventre, comme une vague qui monte.\n2. Expire doucement par la bouche, comme une vague qui se retire.\n3. Imagine une lumière orange, fluide et chaude, sous ta main.\n4. À chaque expiration, laisse partir une émotion que tu retiens, sans avoir besoin de la nommer.\n\nContinue pendant quelques minutes, à ton rythme."],
                    ['introspection', 'Écouter mes émotions', 10, $privacy, [
                        'Quelle émotion as-tu le plus de mal à exprimer ? Pourquoi, selon toi ?',
                        "Qu'est-ce qui t'apporte un plaisir simple et sain dans ta vie ?",
                        'Quel désir profond as-tu mis de côté ces dernières années ?',
                    ]],
                    ['exercise', 'Mise en pratique : un moment de création', 15, "Offre-toi aujourd'hui 15 minutes de création libre, sans objectif de résultat : dessiner, danser, chanter, cuisiner, écrire, modeler…\n\nL'important n'est pas ce que tu produis, mais ce que tu ressens pendant ce moment."],
                ],
            ],
            [
                'name' => 'Chakra du Plexus Solaire',
                'theme' => 'Mon pouvoir personnel',
                'color' => '#FDD835',
                'description' => "Troisième porte. Le plexus solaire est associé au feu, à la volonté et à l'estime de soi. Cette porte t'invite à reprendre ta place, à poser tes limites et à agir selon tes valeurs.",
                'affirmation' => "Je m'autorise à prendre ma place.",
                'companion_message' => 'Ton pouvoir ne se prend pas aux autres : il se retrouve en toi.',
                'steps' => [
                    ['lesson', 'Comprendre la porte du plexus solaire', 5, "Le plexus solaire symbolise ton feu intérieur : ta capacité à décider, à agir et à t'affirmer. Quand l'estime de soi est fragile, on peut chercher l'approbation des autres, avoir du mal à dire non, ou au contraire vouloir tout contrôler.\n\nLe pouvoir personnel n'est pas la domination : c'est la capacité d'être fidèle à toi-même, avec calme et respect.\n\nDans cette porte, tu vas identifier où tu laisses ton pouvoir s'échapper et comment le retrouver."],
                    ['meditation', 'Le soleil intérieur', 6, "Assieds-toi le dos droit, les épaules relâchées.\n\n1. Respire profondément trois fois.\n2. Pose ton attention au-dessus du nombril, au centre du corps.\n3. Visualise un petit soleil jaune doré qui brille à cet endroit.\n4. À chaque inspiration, il grandit et se réchauffe ; à chaque expiration, sa chaleur se diffuse dans tout ton corps.\n5. Rappelle-toi une situation où tu as été fier(e) de toi. Laisse cette sensation nourrir ton soleil.\n6. Répète : « Je m'autorise à prendre ma place. »"],
                    ['introspection', 'Retrouver mon pouvoir', 10, $privacy, [
                        'Dans quelles situations as-tu du mal à dire non ?',
                        "De qui cherches-tu encore l'approbation ? Que crains-tu s'il ou elle n'était pas d'accord ?",
                        'Quelle décision repousses-tu, et quelle petite action pourrait la faire avancer ?',
                    ]],
                    ['exercise', 'Mise en pratique : poser une limite', 10, "Cette semaine, choisis une situation où tu poseras une limite claire et bienveillante. Par exemple : refuser une demande qui ne te convient pas, demander du temps avant de répondre, ou exprimer un besoin.\n\nFormule-la simplement : « Je ne peux pas cette fois-ci » ou « J'ai besoin de… ». Observe ensuite ce que tu ressens."],
                ],
            ],
            [
                'name' => 'Chakra du Cœur',
                'theme' => 'Mes blessures affectives',
                'color' => '#43A047',
                'description' => "Quatrième porte, au centre du parcours. Le chakra du cœur est associé à l'amour, à la compassion et au pardon. Cette porte t'invite à regarder tes blessures affectives avec tendresse, à ton rythme.",
                'affirmation' => "J'ouvre mon cœur avec douceur, à mon rythme.",
                'companion_message' => "Prends soin de toi : regarder une blessure, c'est déjà commencer à la soigner.",
                'steps' => [
                    ['lesson', 'Comprendre la porte du cœur', 5, "Le cœur, au centre des sept portes, relie symboliquement le corps et l'esprit. C'est le lieu de l'amour que l'on donne et que l'on reçoit, et des blessures que l'on porte : rejet, abandon, trahison, manque de reconnaissance.\n\nPour se protéger, il arrive que le cœur se ferme. Le rouvrir ne signifie pas oublier ou tout accepter, mais cesser de laisser la douleur passée décider de ta vie présente.\n\nAvance doucement dans cette porte. Si des souvenirs difficiles remontent, accorde-toi des pauses et n'hésite pas à te faire accompagner par un professionnel."],
                    ['meditation', 'Méditation de compassion', 8, "Installe-toi confortablement et pose une main sur ton cœur.\n\n1. Respire lentement et sens ta main se soulever.\n2. Visualise une lumière verte, douce et apaisante, au centre de ta poitrine.\n3. Adresse-toi intérieurement ces vœux : « Que je sois en paix. Que je sois bienveillant(e) envers moi-même. Que je guérisse à mon rythme. »\n4. Pense ensuite à une personne que tu aimes et adresse-lui les mêmes vœux.\n5. Si tu t'en sens capable, élargis ces vœux à une personne avec qui la relation est difficile.\n\nTermine en revenant à toi, la main toujours sur ton cœur."],
                    ['introspection', 'Regarder mes blessures', 10, $privacy, [
                        "Quelle blessure affective te semble encore vive aujourd'hui ?",
                        "Comment cette blessure influence-t-elle ta façon d'aimer ou de te laisser aimer ?",
                        "De quoi aurais-tu besoin pour avancer vers l'apaisement (pas forcément le pardon) ?",
                    ]],
                    ['exercise', 'Mise en pratique : une lettre du cœur', 15, "Écris une lettre que tu n'enverras pas, à l'enfant que tu étais ou à une personne liée à une blessure. Dis ce que tu n'as jamais pu dire.\n\nTermine ta lettre par une phrase de douceur envers toi-même. Tu peux ensuite la garder, la relire plus tard, ou la déchirer symboliquement."],
                ],
            ],
            [
                'name' => 'Chakra de la Gorge',
                'theme' => 'Ma vérité et mon expression',
                'color' => '#1E88E5',
                'description' => "Cinquième porte. Le chakra de la gorge est associé à la parole, à l'expression et à l'authenticité. Cette porte t'invite à trouver ta voix et à dire ta vérité avec justesse.",
                'affirmation' => "Ma voix compte, je l'exprime avec justesse.",
                'companion_message' => 'Ce que tu tais pèse souvent plus lourd que ce que tu dis.',
                'steps' => [
                    ['lesson', 'Comprendre la porte de la gorge', 5, "La gorge symbolise le passage entre ce que tu ressens et ce que tu exprimes. Quand on a appris à se taire pour éviter les conflits, ou au contraire à parler fort pour être entendu, la parole perd sa justesse.\n\nDire sa vérité, ce n'est pas tout dire, ni blesser : c'est exprimer ce qui est vrai pour toi, au bon moment, avec respect.\n\nDans cette porte, tu vas repérer ce que tu retiens et t'entraîner à t'exprimer avec authenticité."],
                    ['breathing', 'Respiration sonore', 5, "Assieds-toi le dos droit.\n\n1. Inspire profondément par le nez.\n2. En expirant, laisse sortir un son doux et continu : « Mmmm » ou « Aaaa ». Sens la vibration dans ta gorge et ta poitrine.\n3. Recommence une dizaine de fois, en laissant le son devenir de plus en plus naturel.\n4. Visualise une lumière bleue, claire et fluide, au niveau de ta gorge.\n\nTermine par quelques respirations silencieuses."],
                    ['introspection', "Ce que je n'ose pas dire", 10, $privacy, [
                        'Que retiens-tu de dire en ce moment, et à qui ?',
                        "Qu'est-ce qui te fait peur dans le fait de l'exprimer ?",
                        "Comment pourrais-tu le formuler avec respect, pour toi et pour l'autre ?",
                    ]],
                    ['exercise', 'Mise en pratique : une parole vraie', 10, "Dans les prochains jours, exprime une chose vraie que tu gardes pour toi : un remerciement, un besoin, un désaccord, une émotion.\n\nCommence par « Je ressens… » ou « J'ai besoin… » plutôt que « Tu… ». Note ensuite comment l'échange s'est passé et ce que tu as ressenti."],
                ],
            ],
            [
                'name' => 'Troisième Œil',
                'theme' => 'Mes perceptions et mes croyances',
                'color' => '#3949AB',
                'description' => "Sixième porte. Le troisième œil est associé à l'intuition, à la clarté et à la vision intérieure. Cette porte t'invite à questionner tes croyances et à écouter ton intuition.",
                'affirmation' => 'Je regarde au-delà de mes croyances limitantes.',
                'companion_message' => "Une croyance n'est qu'une pensée que tu as répétée longtemps. Tu peux en choisir d'autres.",
                'steps' => [
                    ['lesson', 'Comprendre la porte du troisième œil', 5, "Le troisième œil, situé symboliquement entre les sourcils, représente ta manière de percevoir le monde. Nos croyances sur nous-mêmes, sur les autres et sur la vie filtrent ce que nous voyons. Certaines nous portent ; d'autres nous limitent : « Je ne suis pas capable », « Je n'ai pas de chance », « On ne peut faire confiance à personne ».\n\nCette porte t'invite à observer tes croyances comme des hypothèses, et non comme des vérités, et à faire davantage confiance à ton intuition."],
                    ['meditation', 'Méditation de clarté', 7, "Assieds-toi confortablement et ferme les yeux.\n\n1. Respire calmement et observe tes pensées passer, comme des nuages dans le ciel.\n2. Porte doucement ton attention entre tes sourcils.\n3. Visualise une lumière indigo, profonde et calme, à cet endroit.\n4. Pose-toi intérieurement une question qui te préoccupe, puis reste simplement à l'écoute, sans chercher de réponse.\n5. Accueille les images, sensations ou mots qui viennent, sans les analyser.\n\nNote ensuite ce qui t'est venu, même si cela te paraît flou."],
                    ['introspection', 'Questionner mes croyances', 10, $privacy, [
                        "Quelle croyance sur toi-même te limite le plus aujourd'hui ?",
                        "D'où te vient cette croyance ? Est-elle toujours vraie ?",
                        'Quelle nouvelle croyance, plus juste, aimerais-tu adopter ?',
                    ]],
                    ['exercise', "Mise en pratique : le journal d'intuition", 10, "Pendant trois jours, note chaque soir une intuition que tu as eue dans la journée (une impression, un pressentiment, une idée soudaine) et ce que tu en as fait.\n\nAu bout de trois jours, relis tes notes : que t'apprennent-elles sur ta façon d'écouter ou d'ignorer ton intuition ?"],
                ],
            ],
            [
                'name' => 'Chakra Couronne',
                'theme' => 'Ma conscience et mon évolution',
                'color' => '#8E24AA',
                'description' => 'Septième et dernière porte. Le chakra couronne est associé à la conscience, au sens et à la connexion à plus grand que soi. Cette porte t\'invite à relier ton chemin à ce qui donne du sens à ta vie.',
                'affirmation' => 'Je suis relié(e) à plus grand que moi.',
                'companion_message' => 'Tu as traversé chaque porte. Regarde le chemin parcouru avec gratitude.',
                'steps' => [
                    ['lesson', 'Comprendre la porte de la couronne', 5, "La couronne, au sommet de la tête, symbolise l'ouverture de la conscience et le lien avec ce qui nous dépasse : la nature, l'humanité, le sacré, selon tes croyances.\n\nCette dernière porte n'est pas une fin, mais un point de vue : celui d'où tu peux regarder l'ensemble de ton chemin, intégrer ce que les six portes précédentes t'ont appris et choisir la direction de ton évolution."],
                    ['meditation', 'Méditation de la lumière', 10, "Installe-toi dans un endroit calme, le dos droit.\n\n1. Respire lentement et laisse ton corps se détendre de la tête aux pieds.\n2. Visualise une lumière violette ou blanche juste au-dessus de ta tête.\n3. À chaque inspiration, cette lumière descend doucement et traverse chacune des portes : le front, la gorge, le cœur, le plexus, le sacré, jusqu'à la racine.\n4. Ressens l'unité de ton corps, de tes émotions et de ta conscience.\n5. Reste quelques minutes dans ce silence, simplement présent(e).\n\nReviens doucement en prenant une grande inspiration."],
                    ['introspection', 'Le sens de mon chemin', 10, $privacy, [
                        "Qu'est-ce qui donne le plus de sens à ta vie aujourd'hui ?",
                        "Qu'as-tu découvert sur toi en traversant les sept portes ?",
                        'Quelle intention veux-tu garder pour la suite de ton évolution ?',
                    ]],
                    ['exercise', 'Mise en pratique : un rituel de gratitude', 10, "Prends un moment de calme pour écrire trois gratitudes : une pour ton corps, une pour une personne, une pour le chemin parcouru.\n\nTu peux ensuite allumer une bougie (sans jamais la laisser sans surveillance) ou simplement fermer les yeux quelques instants pour honorer ce que tu as accompli."],
                ],
            ],
        ];

        $now = now();
        foreach ($gates as $i => $gate) {
            $gateId = DB::table('inner_gates')->insertGetId([
                'number' => $i + 1,
                'name' => $gate['name'],
                'theme' => $gate['theme'],
                'color' => $gate['color'],
                'description' => $gate['description'],
                'affirmation' => $gate['affirmation'],
                'companion_message' => $gate['companion_message'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($gate['steps'] as $position => $step) {
                DB::table('inner_gate_steps')->insert([
                    'inner_gate_id' => $gateId,
                    'position' => $position + 1,
                    'type' => $step[0],
                    'title' => $step[1],
                    'duration_minutes' => $step[2],
                    'content' => $step[3],
                    'prompts' => isset($step[4]) ? json_encode($step[4], JSON_UNESCAPED_UNICODE) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
