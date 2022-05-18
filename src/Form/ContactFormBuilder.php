<?php namespace Anomaly\ContactPlugin\Form;

use Anomaly\Streams\Platform\Ui\Form\FormBuilder;

/**
 * Class ContactFormBuilder
 *
 * @link          http://anomaly.is/streams-platform
 * @author        AnomalyLabs, Inc. <hello@anomaly.is>
 * @author        Ryan Thompson <ryan@anomaly.is>
 */
class ContactFormBuilder extends FormBuilder
{

    /**
     * The form handler.
     *
     * @var ContactFormHandler
     */
    protected $handler = ContactFormHandler::class;

    /**
     * The form fields.
     *
     * @var array
     */
    protected $fields = [
        'name'    => [
            'label'    => 'anomaly.plugin.contact::field.name.name',
            'type'     => 'anomaly.field_type.text',
            'required' => true,
        ],
        'email'   => [
            'label'    => 'anomaly.plugin.contact::field.email.name',
            'type'     => 'anomaly.field_type.email',
            'required' => true,
        ],
        'subject' => [
            'label'    => 'anomaly.plugin.contact::field.subject.name',
            'type'     => 'anomaly.field_type.select',
            'required' => true,
            'config'   => [
                'options' => [
                    'anomaly.plugin.contact::field.subject.options.support',
                    'anomaly.plugin.contact::field.subject.options.sales',
                    'anomaly.plugin.contact::field.subject.options.feedback',
                    'anomaly.plugin.contact::field.subject.options.other',
                ],
            ],
        ],
        'message' => [
            'label'    => 'anomaly.plugin.contact::field.message.name',
            'type'     => 'anomaly.field_type.textarea',
            'required' => true,
        ],
    ];

    /**
     * The form actions.
     *
     * @var array
     */
    protected $actions = [
        'submit',
    ];

    /**
     * The form options.
     *
     * @var array
     */
    protected $options = [
        'breadcrumb' => false,
    ];

}