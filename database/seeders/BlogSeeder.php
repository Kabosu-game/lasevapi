<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer l'admin ou le premier utilisateur comme auteur
        $author = User::where('role', 'admin')->first() ?? User::first();
        
        if (!$author) {
            $this->command->warn('Aucun utilisateur trouvé pour créer les blogs. Veuillez d\'abord exécuter UserSeeder.');
            return;
        }

        $blogs = [
            [
                'title' => 'Les bienfaits de la méditation quotidienne',
                'description' => 'Découvrez comment la méditation peut transformer votre vie quotidienne.',
                'body' => 'La méditation est une pratique ancestrale qui offre de nombreux bienfaits pour la santé mentale et physique. Pratiquer quotidiennement peut réduire le stress, améliorer la concentration et favoriser un meilleur sommeil. Dans cet article, nous explorons les multiples avantages de cette pratique.',
                'category' => 'Bien-être',
                'is_premium' => false,
            ],
            [
                'title' => '10 façons de réduire le stress naturellement',
                'description' => 'Des techniques simples et efficaces pour gérer votre stress au quotidien.',
                'body' => 'Le stress fait partie intégrante de notre vie moderne. Cependant, il existe de nombreuses façons naturelles de le gérer. De la respiration profonde à l\'exercice physique, découvrez des techniques accessibles à tous pour retrouver votre sérénité.',
                'category' => 'Santé',
                'is_premium' => false,
            ],
            [
                'title' => 'Guide complet du développement personnel',
                'description' => 'Un guide pratique pour améliorer votre développement personnel et atteindre vos objectifs.',
                'body' => 'Le développement personnel est un voyage continu vers une meilleure version de soi-même. Ce guide vous propose des stratégies concrètes pour développer vos compétences, surmonter vos peurs et réaliser vos rêves les plus profonds.',
                'category' => 'Développement personnel',
                'is_premium' => true,
            ],
            [
                'title' => 'La spiritualité dans la vie moderne',
                'description' => 'Comment intégrer la spiritualité dans votre vie quotidienne moderne.',
                'body' => 'La spiritualité n\'est pas réservée aux moines dans des monastères. Elle peut être intégrée dans notre vie moderne trépidante. Découvrez comment trouver un équilibre spirituel qui s\'harmonise avec votre style de vie actuel.',
                'category' => 'Spiritualité',
                'is_premium' => false,
            ],
            [
                'title' => 'Techniques de méditation pour débutants',
                'description' => 'Un guide étape par étape pour commencer votre pratique de la méditation.',
                'body' => 'Vous êtes nouveau dans la méditation ? Pas de problème ! Ce guide vous accompagne dans vos premiers pas. Nous couvrons les bases : posture, respiration, concentration et comment créer une routine durable.',
                'category' => 'Méditation',
                'is_premium' => false,
            ],
            [
                'title' => 'L\'importance de la gratitude dans le bien-être',
                'description' => 'Comment cultiver la gratitude peut améliorer significativement votre bien-être.',
                'body' => 'La gratitude est plus qu\'un simple sentiment de remerciement. Des études montrent qu\'elle a un impact profond sur notre bien-être mental et physique. Apprenez à cultiver cette pratique et transformez votre perspective sur la vie.',
                'category' => 'Bien-être',
                'is_premium' => false,
            ],
        ];

        foreach ($blogs as $blogData) {
            $slug = Str::slug($blogData['title']);
            
            Blog::updateOrCreate(
                ['slug' => $slug],
                array_merge($blogData, [
                    'slug' => $slug,
                    'author_id' => $author->id,
                ])
            );
        }
    }
}

