<?php

namespace RebelCode\EddBookings\Services\Module;

use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Invocation\InvocableInterface;
use Dhii\Transformer\TransformerInterface;
use Psr\EventManager\EventInterface;
use RebelCode\Entity\EntityManagerInterface;

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
     * The services entity manager.
     *
     * @since [*next-version*]
     *
     * @var EntityManagerInterface
     */
    protected $servicesEm;

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
     * @param EntityManagerInterface $servicesEm          The services entity manager.
     * @param TransformerInterface   $servicesTransformer The transformer for transforming lists of services.
     */
    public function __construct($servicesEm, $servicesTransformer)
    {
        $this->servicesEm          = $servicesEm;
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

        $services    = $this->servicesEm->query(['status' => 'any']);
        $services    = $this->servicesTransformer->transform($services);

        $eventParams = [
            'services' => $services,
        ];

        $event->setParams($eventParams + $event->getParams());
    }
}
