<?php namespace Anomaly\ContactPlugin\Form;

use Anomaly\ContactPlugin\Form\Command\BuildMessage;
use Anomaly\ContactPlugin\Form\Command\GetMessageData;
use Anomaly\ContactPlugin\Form\Command\GetMessageView;
use Anomaly\Streams\Platform\Message\MessageBag;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;

/**
 * Class ContactFormHandler
 *
 * @link          http://anomaly.is/streams-platform
 * @author        AnomalyLabs, Inc. <hello@anomaly.is>
 * @author        Ryan Thompson <ryan@anomaly.is>
 */
class ContactFormHandler
{
    /**
     * Handle the command.
     *
     * @param ContactFormBuilder $builder
     * @param MessageBag         $messages
     * @param Mailer             $mailer
     * @param RateLimiter        $limiter
     * @param Request            $request
     */
    public function handle(
        ContactFormBuilder $builder,
        MessageBag $messages,
        Mailer $mailer,
        RateLimiter $limiter,
        Request $request
    ) {
        // Validation failed!
        if ($builder->hasFormErrors()) {
            return;
        }

        $attempts = (int)$builder->getFormOption('throttle', config('anomaly.plugin.contact::contact.throttle.attempts', 5));
        $decay    = (int)$builder->getFormOption('throttle_decay', config('anomaly.plugin.contact::contact.throttle.decay', 600));
        $key      = 'anomaly.plugin.contact::' . sha1((string)$request->ip());

        if ($attempts > 0) {

            if ($limiter->tooManyAttempts($key, $attempts)) {

                $messages->error('anomaly.plugin.contact::error.throttled');

                return;
            }

            $limiter->hit($key, $decay);
        }

        // Delegate these for now.
        $view = dispatch_sync(new GetMessageView($builder));
        $data = dispatch_sync(new GetMessageData($builder));

        // Build the message object.
        $message = function (Message $message) use ($builder) {
            dispatch_sync(new BuildMessage($message, $builder));
        };

        // Send the email.
        try {
            $sent = $mailer->send($view, $data, $message);
        } catch (\Throwable $exception) {
            logger()->error('anomaly/contact-plugin: unable to send contact message', [
                'exception' => $exception,
            ]);

            $sent = null;
        }

        // If the message was not sent, report.
        if (!$sent) {
            $messages->error(
                $builder->getFormOption('error_message', 'anomaly.plugin.contact::error.send_message')
            );

            return;
        }

        $messages->success(
            $builder->getFormOption('success_message', 'anomaly.plugin.contact::success.send_message')
        );

        // Clear the form!
        $builder->resetForm();
    }
}
