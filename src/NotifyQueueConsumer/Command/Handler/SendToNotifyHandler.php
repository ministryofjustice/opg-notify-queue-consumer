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

use function PHPUnit\Framework\matches;

class SendToNotifyHandler
{
    private Filesystem $filesystem;
    private Client $notifyClient;

    const NOTIFY_TEMPLATE_DOWNLOAD_FF1_INVOICE = 'daef7d83-9874-4dd8-ac60-d92646e7aaaa';
    const NOTIFY_TEMPLATE_DOWNLOAD_A6_INVOICE = '9286a7db-a316-4103-a1c7-7bc1fdbbaa81';

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
            switch ($sendToNotifyCommand->getLetterType()) {
                case 'ff1':
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_FF1_INVOICE;
                    break;
                default:
                    $letterTemplate = self::NOTIFY_TEMPLATE_DOWNLOAD_A6_INVOICE;
                    break;
            }

            $response = $this->sendInvoiceToNotify(
                $sendToNotifyCommand->getUuid(),
                $sendToNotifyCommand->getRecipientName(),
                $sendToNotifyCommand->getRecipientEmail(),
                $sendToNotifyCommand->getClientFirstname(),
                $sendToNotifyCommand->getClientSurname(),
                $contents,
                $letterTemplate,
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
        string $clientFirstName,
        string $clientSurname,
        string $contents,
        string $letterTemplate
    ): array {
        $data = [
            'name' => $recipientName,
            'client_first_name' => $clientFirstName,
            'client_surname' => $clientSurname,
            'link_to_file' => $this->notifyClient->prepareUpload($contents)
        ];

        $sendResponse = $this->notifyClient->sendEmail(
            $recipientEmail,
            $letterTemplate,
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
