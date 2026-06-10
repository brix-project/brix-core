<?php

declare(strict_types=1);

namespace Brix\MailSpool;

if (!class_exists(MailSpoolFacet::class)) {
    final class MailSpoolFacet
    {
        public static function getInstance(): self
        {
            static $instance = null;
            if ($instance === null) {
                $instance = new self();
            }
            return $instance;
        }

        public function hasUnsentMails(): bool
        {
            return false;
        }

        public function sendMail(): void
        {
        }
    }
}

if (!class_exists(Mailspool::class)) {
    final class Mailspool
    {
    }
}
