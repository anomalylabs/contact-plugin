<?php namespace Anomaly\ContactPlugin\Form\Command;

use Anomaly\SettingsModule\Setting\Contract\SettingRepositoryInterface;
use Anomaly\Streams\Platform\Support\Parser;
use Anomaly\Streams\Platform\Ui\Form\FormBuilder;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Mail\Message;

/**
 * Class BuildMessage
 *
 * @link          http://anomaly.is/streams-platform
 * @author        AnomalyLabs, Inc. <hello@anomaly.is>
 * @author        Ryan Thompson <ryan@anomaly.is>
 */
class BuildMessage
{

    /**
     * The message object.
     *
     * @var Message
     */
    protected $message;

    /**
     * The form builder.
     *
     * @var FormBuilder
     */
    protected $builder;

    /**
     * Create a new BuildMessage instance.
     *
     * @param Message     $message
     * @param FormBuilder $builder
     */
    public function __construct(Message $message, FormBuilder $builder)
    {
        $this->message = $message;
        $this->builder = $builder;
    }

    /**
     * Handle the command.
     *
     * @param  SettingRepositoryInterface  $settings
     * @param  Parser                      $parser
     * @param  Repository                  $config
     */
    public function handle(SettingRepositoryInterface $settings, Parser $parser, Repository $config)
    {
        $input = $this->builder->getFormValues()->all();

        $recipient = $config->get('anomaly.plugin.contact::contact.email');

        $to = $this->addresses(
            $parser->parse(
                (array)$this->builder->getOption(
                    'to',
                    $settings->value('streams::contact_email', $recipient) ?: $recipient
                ),
                $input
            )
        );

        if (!$to) {
            throw new \RuntimeException(
                'No contact recipient is configured. Set the contact_email setting, CONTACT_EMAIL, or the form\'s to option.'
            );
        }

        foreach ($to as $address) {
            $this->message->to($address);
        }

        foreach ($this->addresses($parser->parse((array)$this->builder->getOption('cc', null), $input)) as $address) {
            $this->message->cc($address);
        }

        foreach ($this->addresses($parser->parse((array)$this->builder->getOption('bcc', null), $input)) as $address) {
            $this->message->bcc($address);
        }

        $sender = $config->get('mail.from.address');

        call_user_func_array(
            [$this->message, 'from'],
            $parser->parse(
                (array)$this->builder->getOption(
                    'from',
                    $settings->value('streams::server_email', $sender) ?: $sender
                ),
                $input
            )
        );

        call_user_func_array(
            [$this->message, 'subject'],
            (array)$parser->parse(
                $this->builder->getOption('subject', 'Contact Request'),
                $input
            )
        );
    }

    /**
     * Return the valid email addresses from a list.
     *
     * @param  array $values
     * @return array
     */
    protected function addresses(array $values)
    {
        return array_values(
            array_filter(
                array_map('trim', array_filter($values, 'is_string')),
                function ($value) {
                    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                }
            )
        );
    }
}
