<?php

declare(strict_types=1);

use Monolog\Processor\PsrLogMessageProcessor;
use Terlicko\Web\Services\Doctrine\FixDoctrineMigrationTableSchema;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function(ContainerConfigurator $configurator): void
{
    $parameters = $configurator->parameters();

    # https://symfony.com/doc/current/performance.html#dump-the-service-container-into-a-single-file
    $parameters->set('.container.dumper.inline_factories', true);

    $parameters->set('doctrine.orm.enable_lazy_ghost_objects', true);

    $parameters->set('strapi.api_uri', env('STRAPI_API_URL'));
    $parameters->set('strapi.api_key', env('STRAPI_API_KEY'));

    # AI Chatbot parameters
    $parameters->set('openai.api_key', env('OPENAI_API_KEY'));
    $parameters->set('redis.host', env('REDIS_HOST'));
    $parameters->set('redis.port', env('REDIS_PORT'));
    $parameters->set('ai.embedding_model', env('AI_EMBEDDING_MODEL'));
    $parameters->set('ai.chat_model', env('AI_CHAT_MODEL'));
    $parameters->set('ai.chunk_size', env('AI_CHUNK_SIZE')->int());
    $parameters->set('ai.chunk_overlap', env('AI_CHUNK_OVERLAP')->int());

    $services = $configurator->services();

    $services->defaults()
        ->autoconfigure()
        ->autowire()
        ->public();

    $services->set(PdoSessionHandler::class)
        ->args([
            env('DATABASE_URL'),
        ]);

    $services->set(PsrLogMessageProcessor::class)
        ->tag('monolog.processor');

    $services->set(\Smalot\PdfParser\Parser::class);

    $services->set(\Terlicko\Web\Services\Ai\TextChunker::class)
        ->autowire(false)
        ->arg('$chunkSize', '%ai.chunk_size%')
        ->arg('$chunkOverlap', '%ai.chunk_overlap%');

    $services->set(\Terlicko\Web\Services\Ai\EmbeddingService::class)
        ->autowire(false)
        ->arg('$openaiClient', service('openai.client'))
        ->arg('$embeddingModel', '%ai.embedding_model%');

    $services->set(\Terlicko\Web\Services\Ai\OpenAiChatService::class)
        ->autowire(false)
        ->arg('$openaiClient', service('openai.client'))
        ->arg('$chatModel', '%ai.chat_model%');

    $services->set(\Terlicko\Web\Services\Ai\ModerationService::class)
        ->autowire(false)
        ->arg('$openaiClient', service('openai.client'));

    $services->set(\Terlicko\Web\Services\Ai\ImageOcrService::class)
        ->autowire(false)
        ->arg('$openaiClient', service('openai.client'))
        ->arg('$visionModel', '%ai.chat_model%');

    $services->set(\Terlicko\Web\Services\Ai\QueryNormalizerService::class)
        ->autowire(false)
        ->arg('$openaiClient', service('openai.client'))
        ->arg('$cache', service('cache.app'))
        ->arg('$chatModel', '%ai.chat_model%');

    // Controllers
    $services->load('Terlicko\\Web\\Controller\\', __DIR__ . '/../src/Controller/**/{*.php}');

    // Components
    $services->load('Terlicko\\Web\\Components\\', __DIR__ . '/../src/Components/**/{*.php}');

    // Repositories
    $services->load('Terlicko\\Web\\Repository\\', __DIR__ . '/../src/Repository/{*Repository.php}');

    // Form types
    $services->load('Terlicko\\Web\\FormType\\', __DIR__ . '/../src/FormType/**/{*.php}');

    // Message handlers
    $services->load('Terlicko\\Web\\MessageHandler\\', __DIR__ . '/../src/MessageHandler/**/{*.php}');

    // Console commands
    $services->load('Terlicko\\Web\\ConsoleCommands\\', __DIR__ . '/../src/ConsoleCommands/**/{*.php}');

    // Validators
    $services->load('Terlicko\\Web\\Validation\\', __DIR__ . '/../src/Validation/**/{*Validator.php}');

    // Services (exclude manually configured AI services)
    $services->load('Terlicko\\Web\\Services\\', __DIR__ . '/../src/Services/**/{*.php}')
        ->exclude([
            __DIR__ . '/../src/Services/Ai/TextChunker.php',
            __DIR__ . '/../src/Services/Ai/EmbeddingService.php',
            __DIR__ . '/../src/Services/Ai/OpenAiChatService.php',
            __DIR__ . '/../src/Services/Ai/ModerationService.php',
            __DIR__ . '/../src/Services/Ai/ImageOcrService.php',
            __DIR__ . '/../src/Services/Ai/QueryNormalizerService.php',
        ]);
    $services->load('Terlicko\\Web\\Query\\', __DIR__ . '/../src/Query/**/{*.php}');

    /** @see https://github.com/doctrine/migrations/issues/1406 */
    $services->set(FixDoctrineMigrationTableSchema::class)
        ->autoconfigure(false)
        ->arg('$dependencyFactory', service('doctrine.migrations.dependency_factory'))
        ->tag('doctrine.event_listener', ['event' => 'postGenerateSchema']);
};
