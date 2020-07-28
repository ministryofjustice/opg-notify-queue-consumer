<?php

declare(strict_types=1);

namespace Opg\Mapper;

use UnexpectedValueException;

class NotifyStatus
{
    public const SIRIUS_REJECTED = 'rejected';
    public const SIRIUS_QUEUED = 'queued';
    public const SIRIUS_POSTING = 'posting';
    public const SIRIUS_POSTED = 'posted';

    public function toSirius(string $notifyStatus): string
    {
        // TODO update Sirius status strings to match these short ones below
        // TODO the notifications documentation is unclear on the exact on the statuses for precompiled letters -
        // there's only 3 statuses which can't be right, and the ones listed
        // here https://docs.notifications.service.gov.uk/rest-api.html#status-precompiled-letter don't match up the
        // ones here https://docs.notifications.service.gov.uk/rest-api.html#get-the-status-of-one-message-response
        // Assumption is that statuses will be a combination of letter and precompiled letter statuses...
        switch ($notifyStatus) {
            case 'Failed':
            case 'Virus scan failed':
            case 'Validation failed':
                return self::SIRIUS_REJECTED;

            case 'Pending virus check':
                return self::SIRIUS_QUEUED;

            case 'Accepted':
                return self::SIRIUS_POSTING;

            case 'Received':
                return self::SIRIUS_POSTED;

            default:
                throw new UnexpectedValueException(sprintf('Unknown Notify status "%s"', $notifyStatus));
        }
    }
}
