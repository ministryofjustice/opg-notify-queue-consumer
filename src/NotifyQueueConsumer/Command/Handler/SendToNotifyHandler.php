<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Command\Handler;

use Alphagov\Notifications\Client;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use NotifyQueueConsumer\Command\Model\UpdateDocumentStatus;
use NotifyQueueConsumer\Queue\DuplicateMessageException;
use UnexpectedValueException;
use Alphagov\Notifications\Exception;

class SendToNotifyHandler
{
    private Filesystem $filesystem;
    private Client $notifyClient;

    const NOTIFY_TEMPLATE_DOWNLOAD_A6_INVOICE = '9286a7db-a316-4103-a1c7-7bc1fdbbaa81';
    const NOTIFY_TEMPLATE_DOWNLOAD_AF_INVOICE = '017b664c-2776-497b-ad6e-b25b8a365ae0';
    const NOTIFY_TEMPLATE_DOWNLOAD_BS1_LETTER = '3dc53e2c-7e90-4e5f-95ef-8e7a98a6ee55';
    const NOTIFY_TEMPLATE_DOWNLOAD_BS2_LETTER = '228746a3-a445-412d-995e-ae60af86b63d';
    const NOTIFY_TEMPLATE_DOWNLOAD_RD1_LETTER = 'ed08b8c0-dcd6-4cd4-9798-779189e0abe8';
    const NOTIFY_TEMPLATE_DOWNLOAD_RD2_LETTER = '7bc45244-1545-4978-9a01-926d1291b1df';
    const NOTIFY_TEMPLATE_DOWNLOAD_RI2_LETTER = 'd17bb689-52d1-4d73-a501-9955282cfe2e';
    const NOTIFY_TEMPLATE_DOWNLOAD_RI3_LETTER = '473de8af-c59f-4b7e-8e12-240450ec3fb4';
    const NOTIFY_TEMPLATE_DOWNLOAD_RR1_LETTER = 'f93687ad-d1e3-4577-83e9-1f5db0748d38';
    const NOTIFY_TEMPLATE_DOWNLOAD_RR2_LETTER = 'd43958ef-4a93-4cd8-abda-c4001785e740';
    const NOTIFY_TEMPLATE_DOWNLOAD_RR3_LETTER = '19610ca0-0225-423a-8f83-729be739be66';
    const NOTIFY_TEMPLATE_DOWNLOAD_FN14_LETTER = '08a7256f-921c-4cff-a4ef-70d50e2b1847';
    const NOTIFY_EMAIL_HEALTH_AND_WELFARE = '9b1dfc66-8ccb-4db8-b4e7-17f03f487874';
    const NOTIFY_EMAIL_PFA_LAY = '27e5deb5-8ea0-4d91-83d1-ae4145c351f9';
    const NOTIFY_EMAIL_PFA_PRO = '3e6753b7-6602-4363-8c9a-c88d02b239ba';
    const NOTIFY_EMAIL_PFA_PA = 'd8b4e115-5688-4161-82ca-82a93344a21f';
    const NOTIFY_EMAIL_FINANCE = 'f1e5faf6-e6aa-4beb-b6b8-cfa418482653';

    public function __construct(Filesystem $filesystem, Client $notifyClient)
    {
        $this->filesystem = $filesystem;
        $this->notifyClient = $notifyClient;
    }

    /**
     * @param SendToNotify $sendToNotifyCommand
     * @return UpdateDocumentStatus
     * @throws FileNotFoundException
     * @throws Exception\NotifyException|Exception\ApiException|Exception\UnexpectedValueException
     */
    public function handle(SendToNotify $sendToNotifyCommand): UpdateDocumentStatus
    {
        // 1. Check if message exists using our reference - Notify doesn't ignore duplicates!
        // https://docs.notifications.service.gov.uk/php.html#get-the-status-of-multiple-messages
        if ($this->isDuplicate($sendToNotifyCommand->getUuid())) {
            throw new DuplicateMessageException();
        }

        // 2. Fetch PDF for queued item
        $pdf = $sendToNotifyCommand->getFilename();
        $contents = $this->filesystem->read($pdf);

        if ($contents === false) {
            throw new UnexpectedValueException("Cannot read PDF");
        }

        // 3. Send to notify
        $sendBy = $sendToNotifyCommand->getSendBy();
        if ($sendBy['method'] === 'email' && $sendBy['documentType'] === 'letter') {
            $letterTemplate = match ($sendToNotifyCommand->getLetterType()) {
                'a6' => self::NOTIFY_TEMPLATE_DOWNLOAD_A6_INVOICE,
                'af1', 'af2', 'af3' => self::NOTIFY_TEMPLATE_DOWNLOAD_AF_INVOICE,
                'bs1' => self::NOTIFY_TEMPLATE_DOWNLOAD_BS1_LETTER,
                'bs2' => self::NOTIFY_TEMPLATE_DOWNLOAD_BS2_LETTER,
                'fn14' => self::NOTIFY_TEMPLATE_DOWNLOAD_FN14_LETTER,
                'rd1' => self::NOTIFY_TEMPLATE_DOWNLOAD_RD1_LETTER,
                'rd2' => self::NOTIFY_TEMPLATE_DOWNLOAD_RD2_LETTER,
                'ri2' => self::NOTIFY_TEMPLATE_DOWNLOAD_RI2_LETTER,
                'ri3' => self::NOTIFY_TEMPLATE_DOWNLOAD_RI3_LETTER,
                'rr1' => self::NOTIFY_TEMPLATE_DOWNLOAD_RR1_LETTER,
                'rr2' => self::NOTIFY_TEMPLATE_DOWNLOAD_RR2_LETTER,
                'rr3' => self::NOTIFY_TEMPLATE_DOWNLOAD_RR3_LETTER,
                default => null,
            };

            $replyToEmail = match ($sendToNotifyCommand->getReplyToType()) {
                'HW' => self::NOTIFY_EMAIL_HEALTH_AND_WELFARE,
                'PFA LAY' => self::NOTIFY_EMAIL_PFA_LAY,
                'PFA PRO' => self::NOTIFY_EMAIL_PFA_PRO,
                'PFA PA' => self::NOTIFY_EMAIL_PFA_PA,
                'FINANCE' => self::NOTIFY_EMAIL_FINANCE,
                default => null
            };
            $response = $this->sendEmailToNotify(
                $sendToNotifyCommand,
                $contents,
                $letterTemplate,
                $replyToEmail
            );
        } else {
            $response = $this->sendLetterToNotify($sendToNotifyCommand->getUuid(), $contents);
        }

        list('id' => $notifyId, 'status' => $notifyStatus) = $response;

        return UpdateDocumentStatus::fromArray(
            [
                'documentId' => $sendToNotifyCommand->getDocumentId(),
                'notifyId' => $notifyId,
                'notifyStatus' => $notifyStatus,
                'sendByMethod' => $sendBy['method'],
                'recipientEmailAddress' => $sendToNotifyCommand->getRecipientEmail()
            ]
        );
    }

    /**
     * @return array<string,string>
     * @throws Exception\NotifyException|Exception\ApiException|Exception\UnexpectedValueException
     */
    private function sendLetterToNotify(string $reference, string $contents): array
    {
        $sendResponse = $this->notifyClient->sendPrecompiledLetter($reference, $contents);

        if (empty($sendResponse['id'])) {
            throw new UnexpectedValueException("No Notify id returned");
        }

        $statusResponse = $this->notifyClient->getNotification($sendResponse['id']);

        if (empty($statusResponse['status'])) {
            throw new UnexpectedValueException(
                sprintf("No Notify status found for the ID: %s", $sendResponse['id'])
            );
        }

        return [
            'id' => $sendResponse['id'],
            'status' => $statusResponse['status']
        ];
    }

    /**
     * @return array<string,string>
     */
    private function sendEmailToNotify(
        SendToNotify $sendToNotifyCommand,
        string $contents,
        ?string $letterTemplate,
        ?string $replyToEmail
    ): array {
        $data = [
            'recipient_name' => $sendToNotifyCommand->getRecipientName(),
            'pending_or_due_report_type' => $sendToNotifyCommand->getPendingOrDueReportType(),
            'case_number' => $sendToNotifyCommand->getCaseNumber(),
            'client_first_name' => $sendToNotifyCommand->getClientFirstName(),
            'client_surname' => $sendToNotifyCommand->getClientSurname(),
            'link_to_file' => $this->notifyClient->prepareUpload($contents),
        ];

        $sendResponse = $this->notifyClient->sendEmail(
            $sendToNotifyCommand->getRecipientEmail(),
            $letterTemplate,
            $data,
            $sendToNotifyCommand->getUuid(),
            $replyToEmail
        );

        if (empty($sendResponse['id'])) {
            throw new UnexpectedValueException("No Notify id returned");
        }

        $statusResponse = $this->notifyClient->getNotification($sendResponse['id']);

        if (empty($statusResponse['status'])) {
            throw new UnexpectedValueException(
                sprintf("No Notify status found for the ID: %s", $sendResponse['id'])
            );
        }

        return [
            'id' => $sendResponse['id'],
            'status' => $statusResponse['status']
        ];
    }

    /**
     * @param string $reference
     * @return bool
     */
    private function isDuplicate(string $reference): bool
    {
        $response = $this->notifyClient->listNotifications(['reference' => $reference]);

        // NOTE we are sending one letter at a time and expecting one letter per reference
        if (!empty($response['notifications'][0]['id'])) {
            return true;
        }

        return false;
    }
}
