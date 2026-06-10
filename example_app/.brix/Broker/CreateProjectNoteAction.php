<?php

declare(strict_types=1);

use Brix\Core\Broker\AbstractBrokerAction;
use Brix\Core\Broker\Broker;
use Brix\Core\Broker\BrokerActionResponse;
use Brix\Core\Broker\Log\Logger;

final class CreateProjectNoteAction extends AbstractBrokerAction
{
    public function getName(): string
    {
        return 'create_project_note';
    }

    public function getDescription(): string
    {
        return 'Speichert eine Projektnotiz und aktualisiert den zugehörigen Projekt-Kontext.';
    }

    public function getInputClass(): string
    {
        return CreateProjectNoteInput::class;
    }

    public function needsContext(): bool
    {
        return false;
    }

    public function performAction(object $input, Broker $broker, Logger $logger, ?string $contextId): BrokerActionResponse
    {
        $contextId = 'project-' . self::slug($input->project);
        $contextDriver = $broker->getContextStorageDriver()->withContext($contextId);

        if (!$contextDriver->exists()) {
            $contextDriver->setData([
                '__shortInfo' => "Projekt {$input->project}",
                '__created' => phore_datetime(),
            ]);
        }

        $noteId = date('YmdHis') . '-' . substr(sha1($input->project . $input->title . microtime(true)), 0, 8);
        $note = [
            'noteId' => $noteId,
            'project' => $input->project,
            'title' => $input->title,
            'details' => $input->details,
            'requestedBy' => $input->requestedBy,
            'priority' => $input->priority,
            'createdAt' => phore_datetime(),
            'contextId' => $contextId,
        ];

        $targetDir = __DIR__ . '/../../runtime/broker-notes';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        file_put_contents(
            $targetDir . '/' . $noteId . '.json',
            json_encode($note, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $logger->logInfo('Broker-Action ausgeführt', [
            'action' => $this->getName(),
            'contextId' => $contextId,
            'noteId' => $noteId,
        ]);

        $response = new BrokerActionResponse('success', "Notiz {$noteId} gespeichert", [], $contextId);
        $response->addContextUpdate('project.meta', 'Zusammenfassung zum Projekt', [
            'project' => $input->project,
            'last_note_id' => $noteId,
            'last_priority' => $input->priority,
            'last_requested_by' => $input->requestedBy,
        ]);
        $response->addContextUpdate('project.note.' . $noteId, 'Gespeicherte Projektnotiz', $note);

        return $response;
    }

    private static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-') ?: 'project';
    }
}
