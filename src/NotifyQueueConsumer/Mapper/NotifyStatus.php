<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Mapper;

use UnexpectedValueException;

class NotifyStatus
{
    public const SIRIUS_REJECTED = 'rejected';
    public const SIRIUS_QUEUED = 'queued';
    public const SIRIUS_POSTING = 'posting';
    public const SIRIUS_POSTED = 'posted';

    public const STATUSES = [
        'failed' => self::SIRIUS_REJECTED,
        'virus-scan-failed' => self::SIRIUS_REJECTED,
        'validation-failed' => self::SIRIUS_REJECTED,
        'pending-virus-check' => self::SIRIUS_QUEUED,
        'accepted' => self::SIRIUS_POSTING,
        'received' => self::SIRIUS_POSTED,
        'cancelled' => self::SIRIUS_REJECTED,
        'technical-failure' => self::SIRIUS_REJECTED,
        'permanent-failure' => self::SIRIUS_REJECTED,
        'temporary-failure' => self::SIRIUS_REJECTED,
        'created' => self::SIRIUS_POSTING,
        'sending' => self::SIRIUS_POSTING,
        'delivered' => self::SIRIUS_POSTED
    ];

    public function toSirius(string $notifyStatus): string
    {
        // NOTE the notifications documentation is unclear on the exact on the statuses for precompiled letters -
        // there's only 3 statuses which can't be right, and the ones listed
        // here https://docs.notifications.service.gov.uk/rest-api.html#status-precompiled-letter don't match up the
        // ones here https://docs.notifications.service.gov.uk/rest-api.html#get-the-status-of-one-message-response
        // Assumption is that statuses will be a combination of letter and precompiled letter statuses...
        if (!array_key_exists($notifyStatus, self::STATUSES)) {
            throw new UnexpectedValueException(sprintf('Unknown Notify status "%s"', $notifyStatus));
        }

        return self::STATUSES[$notifyStatus];
    }
}
