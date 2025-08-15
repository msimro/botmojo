<?php

declare(strict_types=1);

namespace BotMojo\Bootstrap;

use BotMojo\Config\Config;
use BotMojo\Core\ContainerFactory;
use BotMojo\Core\ServiceContainer;
use BotMojo\Request\RequestPayload;
use BotMojo\Response\ResponseInterface;
use BotMojo\Response\ResponseFactory;
use BotMojo\Orchestration\Orchestrator;
use BotMojo\Request\Exception\ValidationException;
use Throwable;

class Bootstrap
{
    private Config $config;
    private ServiceContainer $container;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->container = ContainerFactory::create($config);
    }

    public function handle(): ResponseInterface
    {
        try {
            // Create and load request
            $request = new RequestPayload();
            
            try {
                $request->load();
            } catch (ValidationException $e) {
                return ResponseFactory::validationError([$e->getMessage()]);
            }

            // Get orchestrator from container
            /** @var Orchestrator $orchestrator */
            $orchestrator = $this->container->get(Orchestrator::class);
            
            // Process request
            $result = $orchestrator->process($request);
            
            // Create success response
            $response = ResponseFactory::success($result);
            
            // Add conversation ID if available
            if (isset($result['conversation_id'])) {
                $response->setConversationId($result['conversation_id']);
            }
            
            // Add components
            if (isset($result['components'])) {
                foreach ($result['components'] as $component) {
                    $response->addComponent($component['type'], $component['data']);
                }
            }
            
            // Add debug information in development
            if ($this->config->get('app.debug', false)) {
                $response->addDebugInfo('request_time', microtime(true));
                $response->addDebugInfo('memory_usage', memory_get_peak_usage(true));
            }
            
            return $response;

        } catch (Throwable $e) {
            // Log the error
            $logger = $this->container->get('logger');
            $logger->error('Application error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return error response
            return ResponseFactory::error(
                'An unexpected error occurred',
                'INTERNAL_ERROR',
                $this->config->get('app.debug', false) ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ] : [],
                500
            );
        }
    }
}
