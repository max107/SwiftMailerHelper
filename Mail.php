<?php

declare(strict_types=1);

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwiftMailerHelper;

class Mail
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * Mail constructor.
     *
     * @param \Swift_Mailer      $mailer
     * @param \Twig_Environment $twig
     */
    public function __construct(\Swift_Mailer $mailer, \Twig_Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * @param string $subject
     * @param string $to
     * @param string $template
     * @param array  $data
     *
     * @throws \Exception
     *
     * @return \Swift_Message
     */
    public function createMessage(string $subject, string $to, string $template, array $data = []): \Swift_Message
    {
        $message = new \Swift_Message();

        $message->setSubject($subject);
        $message->setTo($to);

        $htmlBody = null;
        try {
            $htmlBody = $this->twig->render(sprintf('%s.html', $template), $data);
            // @codeCoverageIgnoreStart
        } catch (\RuntimeException $e) {
            // @codeCoverageIgnoreEnd
        }

        $txtBody = null;
        try {
            $txtBody = $this->twig->render(sprintf('%s.txt', $template), $data);
            // @codeCoverageIgnoreStart
        } catch (\Twig_Error_Loader $e) {
            // @codeCoverageIgnoreEnd
        }

        if ($htmlBody && $txtBody) {
            $message->setBody($htmlBody, 'text/html');
            $message->addPart($txtBody, 'text/plain');
        } else {
            if ($htmlBody) {
                $message->setBody($htmlBody, 'text/html');
            } else {
                $message->setBody($txtBody, 'text/plain');
            }
        }

        return $message;
    }

    /**
     * @param \Swift_Message $message
     * @param array         $attachments
     *
     * @return \Swift_Message
     */
    public function attach(\Swift_Message $message, array $attachments = []): \Swift_Message
    {
        foreach ($attachments as $fileName => $options) {
            if (is_string($options)) {
                $fileName = $options;
                $options = [];
            }

            $attachment = \Swift_Attachment::fromPath($fileName);
            if (!empty($options['fileName'])) {
                $attachment->setFilename($options['fileName']);
            }
            if (!empty($options['contentType'])) {
                $attachment->setContentType($options['contentType']);
            }
            $message->attach($attachment);
        }

        return $message;
    }

    /**
     * @param $subject
     * @param $to
     * @param $template
     * @param array $data
     * @param array $attachments
     *
     * @throws \Exception
     *
     * @return int
     */
    public function send($subject, $to, $template, array $data = [], array $attachments = []): int
    {
        $message = $this->createMessage($subject, $to, $template, $data);
        $message = $this->attach($message, $attachments);

        return $this->mailer->send($message);
    }
}
