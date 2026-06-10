<?php

declare(strict_types=1);

use Phore\Cli\Annotation\CliParameter;
use Phore\Cli\Output\Out;

final class Brokerdemo
{
    public function capture_note(
        #[CliParameter('project', 'Projektname oder fachlicher Scope')]
        string $project,
        #[CliParameter('title', 'Kurzer Titel der Notiz')]
        string $title,
        #[CliParameter('details', 'Freitext mit Kontext')]
        string $details,
        #[CliParameter('requestedBy', 'Wer die Anforderung gemeldet hat')]
        string $requestedBy,
        #[CliParameter('priority', 'Fachliche Priorität')]
        string $priority = 'normal'
    ): void {
        $payload = (object)[
            'action_name' => 'create_project_note',
            'context_id' => null,
            'project' => $project,
            'title' => $title,
            'details' => $details,
            'requestedBy' => $requestedBy,
            'priority' => $priority,
        ];

        $response = DemoBrokerBootstrap::broker()->performAction($payload);

        Out::TextSuccess('Broker-Action ausgeführt.');
        Out::Table([
            ['field' => 'action', 'value' => $payload->action_name],
            ['field' => 'message', 'value' => $response->message],
            ['field' => 'context', 'value' => $response->switchToContextId ?? '-'],
            ['field' => 'updates', 'value' => (string)count($response->context_updates)],
        ]);
        Out::TextInfo('Die CLI ist hier nur ein Adapter. Die Fachlogik steckt in einer registrierten Broker-Action.');
    }

    public function show_context(
        #[CliParameter('contextId', 'ID des Broker-Kontexts, z. B. project-webshop')]
        string $contextId
    ): void {
        $data = DemoBrokerBootstrap::broker()->getContextStorageDriver()->withContext($contextId)->getData();
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}
