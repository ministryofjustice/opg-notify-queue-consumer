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

    const NOTIFY_TEMPLATE_DOWNLOAD_INVOICE = 'daef7d83-9874-4dd8-ac60-d92646e7aaaa';

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
        if ($sendBy['method'] === 'email' && $sendBy['documentType'] === 'invoice') {
            $response = $this->sendInvoiceToNotify(
                $sendToNotifyCommand->getUuid(),
                $sendToNotifyCommand->getRecipientName(),
                $sendToNotifyCommand->getRecipientEmail(),
                $contents
            );
        } else {
            $response = $this->sendLetterToNotify($sendToNotifyCommand->getUuid(), $contents);
        }

        list('id' => $notifyId, 'status' => $notifyStatus) = $response;

        return UpdateDocumentStatus::fromArray(
            [
                'notifyId' => $notifyId,
                'notifyStatus' => $notifyStatus,
                'documentId' => $sendToNotifyCommand->getDocumentId(),
            ]
        );
    }

    /**
     * @param string $reference
     * @param string $contents
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
     * @param string $reference
     * @param string $recipientName
     * @param string $recipientEmail
     * @param string $contents
     *
     * @return array<string,string>
     * @throws Exception\NotifyException|Exception\ApiException|Exception\UnexpectedValueException
     */
    private function sendInvoiceToNotify(
        string $reference,
        string $recipientName,
        string $recipientEmail,
        string $contents
    ): array {
        $data = [
            'name' => $recipientName,
            'link_to_file' => $this->notifyClient->prepareUpload($contents)
        ];

        $sendResponse = $this->notifyClient->sendEmail(
            $recipientEmail,
            self::NOTIFY_TEMPLATE_DOWNLOAD_INVOICE,
            $data,
            $reference
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
