<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;
use Illuminate\Support\Facades\Log;

/**
 * Accès à Claude pour l'Assistant IA et la personnalisation des Oracles.
 *
 * La clé reste sur le serveur (ANTHROPIC_API_KEY dans .env), jamais dans l'app.
 * Si la clé est absente ou si l'appel échoue, les méthodes renvoient null et
 * l'appelant utilise un texte de repli : l'app reste utilisable sans IA.
 */
class AiCompanion
{
    /** Ton commun à toutes les réponses de LASÈV. */
    public const PERSONA = <<<'TXT'
Tu es le compagnon personnel de l'application LASÈV, un sanctuaire numérique de bien-être et de transformation personnelle (méditation, respiration, yoga, journal, oracles, défis).
Tu t'exprimes en français, en tutoyant l'utilisateur, avec chaleur, douceur et concision.
Tu n'es ni médecin ni thérapeute : tu ne poses aucun diagnostic, tu ne promets aucun résultat et tu présentes tout ce qui touche aux oracles comme des interprétations symboliques et des pistes de réflexion, jamais comme des prédictions certaines.
Si l'utilisateur évoque une détresse importante, des idées noires ou un danger, tu l'invites avec bienveillance à contacter rapidement un professionnel de santé ou les services d'urgence de son pays.
TXT;

    private ?Client $client = null;

    public function enabled(): bool
    {
        return (string) config('services.anthropic.key') !== '';
    }

    private function client(): Client
    {
        return $this->client ??= new Client(
            apiKey: config('services.anthropic.key'),
            requestOptions: ['timeout' => 45.0, 'maxRetries' => 1],
        );
    }

    /**
     * Réponse structurée conforme à $schema (JSON Schema), ou null en cas d'échec.
     *
     * @param array<string,mixed> $schema
     * @return array<string,mixed>|null
     */
    public function json(string $instructions, string $input, array $schema, int $maxTokens = 4000): ?array
    {
        $text = $this->request(
            system: self::PERSONA . "\n\n" . $instructions,
            messages: [['role' => 'user', 'content' => $input]],
            maxTokens: $maxTokens,
            outputConfig: [
                'effort' => 'low',
                'format' => ['type' => 'json_schema', 'schema' => $schema],
            ],
        );
        if ($text === null) {
            return null;
        }
        $data = json_decode($text, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Réponse libre à une conversation.
     *
     * @param list<array{role:string,content:string}> $messages
     */
    public function chat(string $instructions, array $messages, int $maxTokens = 2000): ?string
    {
        return $this->request(
            system: self::PERSONA . "\n\n" . $instructions,
            messages: $messages,
            maxTokens: $maxTokens,
            outputConfig: ['effort' => 'low'],
        );
    }

    /**
     * @param list<array{role:string,content:string}> $messages
     * @param array<string,mixed> $outputConfig
     */
    private function request(string $system, array $messages, int $maxTokens, array $outputConfig): ?string
    {
        if (!$this->enabled()) {
            return null;
        }

        try {
            // Beta messages : nécessaire pour la bascule automatique (fallbacks) si le modèle décline.
            $message = $this->client()->beta->messages->create(
                model: config('services.anthropic.model'),
                maxTokens: $maxTokens,
                system: $system,
                messages: $messages,
                outputConfig: $outputConfig,
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (AuthenticationException $e) {
            Log::error('AiCompanion: clé ANTHROPIC_API_KEY invalide', ['message' => $e->getMessage()]);
            return null;
        } catch (RateLimitException $e) {
            Log::warning('AiCompanion: limite de débit atteinte', ['message' => $e->getMessage()]);
            return null;
        } catch (APIStatusException $e) {
            Log::error('AiCompanion: erreur API', ['type' => $e->type?->value, 'message' => $e->getMessage()]);
            return null;
        } catch (APIConnectionException $e) {
            Log::warning('AiCompanion: connexion impossible', ['message' => $e->getMessage()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('AiCompanion: erreur inattendue', ['message' => $e->getMessage()]);
            return null;
        }

        if ($message->stopReason === 'refusal') {
            Log::info('AiCompanion: requête déclinée', ['category' => $message->stopDetails?->category ?? null]);
            return null;
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }
        $text = trim($text);
        return $text === '' ? null : $text;
    }
}
