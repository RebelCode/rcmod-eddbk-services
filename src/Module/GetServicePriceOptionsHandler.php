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
use Dhii\Iterator\CountIterableCapableTrait;
use Dhii\Iterator\ResolveIteratorCapableTrait;
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Util\Normalization\NormalizeIntCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use InvalidArgumentException;
use Psr\EventManager\EventInterface;
use RuntimeException;

/**
 * The handler that filters the price options for an EDD Download, if it is a bookable service.
 *
 * @since [*next-version*]
 */
class GetServicePriceOptionsHandler implements InvocableInterface
{
    /* @since [*next-version*] */
    use GetEddServicePricesCapableTrait;

    /* @since [*next-version*] */
    use ContainerGetCapableTrait;

    /* @since [*next-version*] */
    use CountIterableCapableTrait;

    /* @since [*next-version*] */
    use ResolveIteratorCapableTrait;

    /* @since [*next-version*] */
    use NormalizeIntCapableTrait;

    /* @since [*next-version*] */
    use NormalizeKeyCapableTrait;

    /* @since [*next-version*] */
    use NormalizeStringCapableTrait;

    /* @since [*next-version*] */
    use CreateContainerExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateNotFoundExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateOutOfRangeExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateRuntimeExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The services SELECT resource model.
     *
     * @since [*next-version*]
     *
     * @var SelectCapableInterface
     */
    protected $servicesSelectRm;

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
     * @param SelectCapableInterface $servicesSelectRm The services SELECT resource model.
     * @param object                 $exprBuilder      The expression builder.
     */
    public function __construct(SelectCapableInterface $servicesSelectRm, $exprBuilder)
    {
        $this->_setServicesSelectRm($servicesSelectRm);
        $this->_setExprBuilder($exprBuilder);
    }

    /**
     * Retrieves the services select resource model.
     *
     * @since [*next-version*]
     *
     * @return SelectCapableInterface The services select resource model instance.
     */
    protected function _getServicesSelectRm()
    {
        return $this->servicesSelectRm;
    }

    /**
     * Sets the services SELECT resource model.
     *
     * @since [*next-version*]
     *
     * @param SelectCapableInterface $servicesSelectRm The services SELECT resource model.
     *
     * @throws InvalidArgumentException If the argument is not a SELECT resource model.
     */
    protected function _setServicesSelectRm($servicesSelectRm)
    {
        if ($servicesSelectRm !== null && !($servicesSelectRm instanceof SelectCapableInterface)) {
            throw $this->_createInvalidArgumentException(
                $this->__('Argument is not a SELECT resource model.'), null, null, $servicesSelectRm
            );
        }

        $this->servicesSelectRm = $servicesSelectRm;
    }

    /**
     * Retrieves the expression builder.
     *
     * @since [*next-version*]
     *
     * @return object The expression builder instance.
     */
    protected function _getExprBuilder()
    {
        return $this->exprBuilder;
    }

    /**
     * Sets the expression builder.
     *
     * @since [*next-version*]
     *
     * @param mixed $exprBuilder The expression builder instance.
     */
    protected function _setExprBuilder($exprBuilder)
    {
        $this->exprBuilder = $exprBuilder;
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

        $serviceId = $event->getParam(1);

        try {
            $priceOptions = $this->_getEddServicePrices($serviceId);
        } catch (RuntimeException $exception) {
            return;
        }

        $event->setParams([0 => $priceOptions] + $event->getParams());
    }
}
