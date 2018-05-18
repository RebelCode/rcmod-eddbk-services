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
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Transformer\TransformerInterface;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Psr\EventManager\EventInterface;

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
     * The services SELECT resource model.
     *
     * @since [*next-version*]
     *
     * @var SelectCapableInterface
     */
    protected $servicesSelectRm;

    /**
     * The services SELECT resource model.
     *
     * @since [*next-version*]
     *
     * @var SelectCapableInterface
     */
    protected $sessionRulesSelectRm;

    /**
     * The transformer for transforming services and session rules into the UI state.
     *
     * @since [*next-version*]
     *
     * @var TransformerInterface
     */
    protected $stateTransformer;

    /**
     * The expression builder.
     *
     * @since [*next-version*]
     *
     * @var object
     */
    protected $exprBuilder;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param SelectCapableInterface $servicesSelectRm     The services SELECT resource model.
     * @param SelectCapableInterface $sessionRulesSelectRm The session rules SELECT resource model.
     * @param TransformerInterface   $stateTransformer     The transformer for the UI state.
     * @param object                 $exprBuilder          The expression builder.
     */
    public function __construct(
        SelectCapableInterface $servicesSelectRm,
        SelectCapableInterface $sessionRulesSelectRm,
        TransformerInterface $stateTransformer,
        $exprBuilder
    ) {
        $this->servicesSelectRm     = $servicesSelectRm;
        $this->sessionRulesSelectRm = $sessionRulesSelectRm;
        $this->stateTransformer     = $stateTransformer;
        $this->exprBuilder          = $exprBuilder;
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

        $b = $this->exprBuilder;

        // Get the service
        $services = $this->servicesSelectRm->select($b->and(
            $b->eq(
                $b->ef('post', 'id'),
                $b->lit($serviceId)
            )
        ));
        $service = null;
        foreach ($services as $service) {
            break;
        }

        // If service was not found, ignore
        if ($service === null) {
            return;
        }

        // Get the session rules
        $rules = $this->sessionRulesSelectRm->select(
            $b->eq(
                $b->ef('session_rule', 'service_id'),
                $b->lit($serviceId)
            )
        );

        // Create the data, transform and normalize to array
        $data = [
            'id'               => $serviceId,
            'name'             => $this->_containerGet($service, 'name'),
            'bookings_enabled' => $this->_containerGet($service, 'bookings_enabled'),
            'session_lengths'  => $this->_containerGet($service, 'session_lengths'),
            'display_options'  => $this->_containerGet($service, 'display_options'),
            'timezone'         => $this->_containerGet($service, 'timezone'),
            'session_rules'    => $rules,
        ];
        $state = $this->stateTransformer->transform($data);
        $state = $this->_normalizeArray($state);

        // Add to event params
        $event->setParams($state + $event->getParams());
    }
}
