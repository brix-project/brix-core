<?php

declare(strict_types=1);

final class CreateProjectNoteInput
{
    /**
     * Technischer Name der Action für den generischen perform-Flow.
     * @var string|null
     */
    public ?string $action_name = null;

    /**
     * Optionaler Kontext für den generischen perform-Flow.
     * @var string|null
     */
    public ?string $context_id = null;

    /**
     * Fachlicher oder technischer Projektname.
     * @var string
     */
    public string $project;

    /**
     * Kurzer Titel der Notiz.
     * @var string
     */
    public string $title;

    /**
     * Freitext mit mehr Kontext.
     * @var string
     */
    public string $details;

    /**
     * Wer die Anforderung oder Beobachtung gemeldet hat.
     * @var string
     */
    public string $requestedBy;

    /**
     * Fachliche Priorität.
     * @var string
     */
    public string $priority = 'normal';
}
