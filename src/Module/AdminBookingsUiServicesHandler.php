<?php

namespace RebelCode\EddBookings\Services\Module;

use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Invocation\InvocableInterface;
use Dhii\Storage\Resource\SelectCapableInterface;
use Psr\EventManager\EventInterface;
use RebelCode\Transformers\TransformerInterface;

/**
 * The event handler that provides the services for the admin bookings UI.
 *
 * @since [*next-version*]
 */
class AdminBookingsUiServicesHandler implements InvocableInterface
{
    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The SELECT resource model for services.
     *
     * @since [*next-version*]
     *
     * @var SelectCapableInterface
     */
    protected $servicesSelectRm;

    /**
     * The transformer for transforming lists of services.
     *
     * @since [*next-version*]
     *
     * @var TransformerInterface
     */
    protected $servicesTransformer;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param SelectCapableInterface $servicesSelectRm    The SELECT resource model for services.
     * @param TransformerInterface   $servicesTransformer The transformer for transforming lists of services.
     */
    public function __construct($servicesSelectRm, $servicesTransformer)
    {
        $this->servicesSelectRm    = $servicesSelectRm;
        $this->servicesTransformer = $servicesTransformer;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function __invoke()
    {
        $event = func_get_arg(0);

        if (!($event instanceof EventInterface)) {
            throw $this->_createInvalidArgumentException(
                $this->__('Argument is not an event instance'), null, null, $event
            );
        }

        $services    = $this->servicesTransformer->transform($this->servicesSelectRm->select());
        $eventParams = [
            'services' => $services,
        ];

        $event->setParams($eventParams + $event->getParams());
    }
}
