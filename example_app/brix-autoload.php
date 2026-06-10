<?php

declare(strict_types=1);

require_once __DIR__ . '/.brix/Broker/MailSpoolStubs.php';
require_once __DIR__ . '/.brix/Broker/CreateProjectNoteInput.php';
require_once __DIR__ . '/.brix/Broker/CreateProjectNoteAction.php';
require_once __DIR__ . '/.brix/Broker/DemoBrokerBootstrap.php';
require_once __DIR__ . '/.brix/Cli/Direct.php';
require_once __DIR__ . '/.brix/Cli/Brokerdemo.php';

// Direkte CLI-Kommandos leben unter .brix/Cli
\Phore\Cli\CliDispatcher::addClass(Direct::class);
\Phore\Cli\CliDispatcher::addClass(Brokerdemo::class);

// Broker-Actions und Typen leben unter .brix/Broker
DemoBrokerBootstrap::boot();
