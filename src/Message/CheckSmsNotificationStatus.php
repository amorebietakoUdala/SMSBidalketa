<?php

namespace App\Message;

final class CheckSmsNotificationStatus
{

    public function __construct(
        private readonly int $auditId,
    ) {
    }

        public function getAuditId()
        {
                return $this->auditId;
        }

        public function setAuditId($auditId)
        {
                $this->auditId = $auditId;

                return $this;
        }
}
