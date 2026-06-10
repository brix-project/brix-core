<?php

declare(strict_types=1);

use Phore\Cli\Annotation\CliParameter;
use Phore\Cli\Output\Out;

final class Direct
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
        $targetDir = __DIR__ . '/../../runtime/direct';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $entry = [
            'mode' => 'direct-cli',
            'project' => $project,
            'title' => $title,
            'details' => $details,
            'requestedBy' => $requestedBy,
            'priority' => $priority,
            'createdAt' => date(DATE_ATOM),
        ];

        file_put_contents(
            $targetDir . '/notes.jsonl',
            json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );

        Out::TextSuccess('Direktes CLI-Kommando ausgeführt.');
        Out::TextInfo('Die CLI ist hier gleichzeitig Interface und Business-Logik.');
        Out::Table(array_map(
            static fn(string $key, mixed $value): array => ['field' => $key, 'value' => is_scalar($value) ? (string)$value : json_encode($value)],
            array_keys($entry),
            array_values($entry)
        ));
    }

    public function list_notes(): void
    {
        $file = __DIR__ . '/../../runtime/direct/notes.jsonl';
        if (!file_exists($file)) {
            Out::TextWarning('Noch keine direkten Notizen vorhanden.');
            return;
        }

        $rows = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $rows[] = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        }

        Out::Table($rows, false, ['project', 'title', 'priority', 'requestedBy', 'createdAt']);
    }
}
