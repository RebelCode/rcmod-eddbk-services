<?php

namespace RebelCode\EddBookings\Services\Module;

use Dhii\Data\Container\ContainerGetCapableTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\CreateNotFoundExceptionCapableTrait;
use Dhii\Data\Container\NormalizeKeyCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\Exception\CreateRuntimeExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Invocation\InvocableInterface;
use Dhii\Transformer\TransformerInterface;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventManager\EventInterface;
use RebelCode\Entity\EntityManagerInterface;

/**
 * Handler for providing the state to the admin edit service page UI.
 *
 * @since [*next-version*]
 */
class AdminEditServiceUiStateHandler implements InvocableInterface
{
    /* @since [*next-version*] */
    use ContainerGetCapableTrait;

    /* @since [*next-version*] */
    use NormalizeKeyCapableTrait;

    /* @since [*next-version*] */
    use NormalizeStringCapableTrait;

    /* @since [*next-version*] */
    use NormalizeArrayCapableTrait;

    /* @since [*next-version*] */
    use CreateContainerExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateNotFoundExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateOutOfRangeExceptionCapableTrait;

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
     * The services entity manager.
     *
     * @since [*next-version*]
     *
     * @var EntityManagerInterface
     */
    protected $servicesEm;

    /**
     * The transformer for transforming services and session rules into the UI state.
     *
     * @since [*next-version*]
     *
     * @var TransformerInterface
     */
    protected $stateTransformer;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param EntityManagerInterface $servicesEm       The services entity manager.
     * @param TransformerInterface   $stateTransformer The transformer for the UI state.
     */
    public function __construct(
        EntityManagerInterface $servicesEm,
        TransformerInterface $stateTransformer
    ) {
        $this->servicesEm       = $servicesEm;
        $this->stateTransformer = $stateTransformer;
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

        // Only continue for valid service IDs
        if (empty($serviceId)) {
            return;
        }

        try {
            // Get the service
            $service = $this->servicesEm->get($serviceId);
        } catch (NotFoundExceptionInterface $exception) {
            // If service was not found, ignore
            return;
        }

        // Create the data, transform and normalize to array
        $data        = $this->_normalizeArray($service);
        $transformed = $this->stateTransformer->transform($data);
        $state       = $this->_normalizeArray($transformed);

        // Add to event params
        $event->setParams($state + $event->getParams());
    }
}
