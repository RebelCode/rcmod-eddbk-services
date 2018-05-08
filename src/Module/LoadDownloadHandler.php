<?php

namespace RebelCode\EddBookings\Services\Module;

use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateRuntimeExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Invocation\InvocableInterface;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use Psr\EventManager\EventInterface;
use RebelCode\Transformers\TransformerInterface;

/**
 * Handler for loading downloads.
 *
 * @since [*next-version*]
 */
class LoadDownloadHandler implements InvocableInterface
{
    /* @since [*next-version*] */
    use NormalizeArrayCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateRuntimeExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The event param key for the service ID.
     *
     * @since [*next-version*]
     */
    const K_EVENT_SERVICE_ID = 'id';

    /**
     * The services controller.
     *
     * @since [*next-version*]
     *
     * @var ServicesControllerInterface
     */
    protected $servicesController;

    /**
     * The transformer for transforming services.
     *
     * @since [*next-version*]
     *
     * @var TransformerInterface
     */
    protected $serviceTransformer;

    public function __construct(
        ServicesControllerInterface $servicesController,
        TransformerInterface $serviceTransformer
    ) {
        $this->servicesController = $servicesController;
        $this->serviceTransformer = $serviceTransformer;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function __invoke()
    {
        /* @var $event EventInterface */
        $event = func_get_arg(0);

        if (!($event instanceof EventInterface)) {
            throw $this->_createInvalidArgumentException(
                $this->__('Argument is not an event instance'), null, null, $event
            );
        }

        $serviceId = $event->getParam(static::K_EVENT_SERVICE_ID);

        if (empty($serviceId)) {
            throw $this->_createRuntimeException(
                $this->__('Invalid or no service ID was given in the event'), null, null
            );
        }

        $service = $this->servicesController->getService($serviceId);
        $service = $this->serviceTransformer->transform($service);

        $eventParams = $this->_normalizeArray($service);

        $event->setParams($eventParams + $event->getParams());
    }
}
