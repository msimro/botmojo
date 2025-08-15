<?php

declare(strict_types=1);

namespace BotMojo\Bootstrap;

use BotMojo\Config\Config;
use BotMojo\Request\RequestPayload;
use BotMojo\Response\ResponsePayload;
use BotMojo\Orchestration\Orchestrator;

class Application
{
    private Config $config;
    private Orchestrator $orchestrator;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->orchestrator = new Orchestrator($config);
    }

    public function handleRequest(): ResponsePayload
    {
        // Create request payload from incoming data
        $request = new RequestPayload();
        $request->load();

        // Process request through orchestrator
        $result = $this->orchestrator->process($request);

        // Create and return response
        $response = new ResponsePayload();
        $response->setData($result);
        
        return $response;
    }
}
