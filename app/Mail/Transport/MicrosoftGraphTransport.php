<?php

namespace App\Mail\Transport;

use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\EmailAddress;
use Microsoft\Graph\Generated\Models\ItemBody;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\Recipient;
use Microsoft\Graph\Generated\Users\Item\SendMail\SendMailPostRequestBody;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class MicrosoftGraphTransport extends AbstractTransport
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $tenantId;
    protected string $fromAddress;
    protected string $fromName;
    protected bool $saveToSentItems;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $tenantId,
        string $fromAddress,
        string $fromName,
        bool $saveToSentItems = false
    ) {
        parent::__construct();

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tenantId = $tenantId;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
        $this->saveToSentItems = $saveToSentItems;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $tokenRequestContext = new ClientCredentialContext(
            $this->tenantId,
            $this->clientId,
            $this->clientSecret
        );

        $graphServiceClient = new GraphServiceClient($tokenRequestContext);

        $graphMessage = new Message();
        $graphMessage->setSubject($email->getSubject());

        $body = new ItemBody();
        $body->setContentType(new BodyType($email->getHtmlBody() ? BodyType::HTML : BodyType::TEXT));
        $body->setContent($email->getHtmlBody() ?? $email->getTextBody());
        $graphMessage->setBody($body);

        // Set recipients
        $toRecipients = [];
        foreach ($email->getTo() as $address) {
            $toRecipients[] = $this->createRecipient($address);
        }
        $graphMessage->setToRecipients($toRecipients);

        // Set CC recipients
        if ($email->getCc()) {
            $ccRecipients = [];
            foreach ($email->getCc() as $address) {
                $ccRecipients[] = $this->createRecipient($address);
            }
            $graphMessage->setCcRecipients($ccRecipients);
        }

        // Set BCC recipients
        if ($email->getBcc()) {
            $bccRecipients = [];
            foreach ($email->getBcc() as $address) {
                $bccRecipients[] = $this->createRecipient($address);
            }
            $graphMessage->setBccRecipients($bccRecipients);
        }

        $requestBody = new SendMailPostRequestBody();
        $requestBody->setMessage($graphMessage);
        $requestBody->setSaveToSentItems($this->saveToSentItems);

        $graphServiceClient->users()
            ->byUserId($this->fromAddress)
            ->sendMail()
            ->post($requestBody)
            ->wait();
    }

    protected function createRecipient(Address $address): Recipient
    {
        $recipient = new Recipient();
        $emailAddress = new EmailAddress();
        $emailAddress->setAddress($address->getAddress());
        if ($address->getName()) {
            $emailAddress->setName($address->getName());
        }
        $recipient->setEmailAddress($emailAddress);
        return $recipient;
    }

    public function __toString(): string
    {
        return 'microsoft-graph';
    }
}
