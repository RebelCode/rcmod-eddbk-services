<?php

namespace RebelCode\EddBookings\Services\Module;

use ArrayAccess;
use Dhii\Storage\Resource\SelectCapableInterface;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Exception as RootException;
use Dhii\Util\String\StringableInterface as Stringable;
use stdClass;
use Traversable;

/**
 * Functionality for retrieving the prices for a service, in the format required by EDD.
 *
 * @since [*next-version*]
 */
trait GetEddServicePricesCapableTrait
{
    /**
     * Retrieves the service prices in the format required by EDD.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable $serviceId The ID of the service.
     *
     * @return array An array with price data elements. Each element has the keys 'index', 'name' and 'amount' which
     *               correspond to an ordinal identifier, human-friendly name and price amount respectively.
     */
    protected function _getEddServicePrices($serviceId)
    {
        $b = $this->_getExprBuilder();
        $s = $this->_getServicesSelectRm();

        $services = $s->select($b->and(
            $b->eq(
                $b->ef('service', 'id'),
                $b->lit($this->_normalizeString($serviceId))
            )
        ));

        if (($c = $this->_countIterable($services)) !== 1) {
            throw $this->_createRuntimeException(
                $this->__('Service ID "%1$s" matched %2$d services', [$serviceId, $c])
            );
        }

        $service = reset($services);
        $lengths = $this->_containerGet($service, 'session_lengths');
        $prices  = [];
        $index   = 0;

        foreach ($lengths as $_lengthInfo) {
            $prices[] = [
                'index'  => ++$index,
                'name'   => $this->_containerGet($_lengthInfo, 'sessionLength'),
                'amount' => $this->_containerGet($_lengthInfo, 'price'),
            ];
        }

        return $prices;
    }

    /**
     * Retrieves the expression builder.
     *
     * @since [*next-version*]
     *
     * @return object The expression builder instance.
     */
    abstract protected function _getExprBuilder();

    /**
     * Retrieves the services SELECT resource model.
     *
     * @since [*next-version*]
     *
     * @return SelectCapableInterface The services SELECT resource model instance.
     */
    abstract protected function _getServicesSelectRm();

    /**
     * Normalizes a value to its string representation.
     *
     * The values that can be normalized are any scalar values, as well as
     * {@see StringableInterface).
     *
     * @since [*next-version*]
     *
     * @param Stringable|string|int|float|bool $subject The value to normalize to string.
     *
     * @throws InvalidArgumentException If the value cannot be normalized.
     *
     * @return string The string that resulted from normalization.
     */
    abstract protected function _normalizeString($subject);

    /**
     * Counts the elements in an iterable.
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|Traversable $iterable The iterable to count.
     *
     * @return int The amount of elements.
     */
    abstract protected function _countIterable($iterable);

    /**
     * Retrieves a value from a container or data set.
     *
     * @since [*next-version*]
     *
     * @param array|ArrayAccess|stdClass|ContainerInterface $container The container to read from.
     * @param string|int|float|bool|Stringable              $key       The key of the value to retrieve.
     *
     * @throws InvalidArgumentException    If container is invalid.
     * @throws ContainerExceptionInterface If an error occurred while reading from the container.
     * @throws NotFoundExceptionInterface  If the key was not found in the container.
     *
     * @return mixed The value mapped to the given key.
     */
    abstract protected function _containerGet($container, $key);

    /**
     * Creates a new Runtime exception.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable|int|float|bool|null $message  The message, if any.
     * @param int|float|string|Stringable|null      $code     The numeric error code, if any.
     * @param RootException|null                    $previous The inner exception, if any.
     *
     * @return RuntimeException The new exception.
     */
    abstract protected function _createRuntimeException($message = null, $code = null, $previous = null);

    /**
     * Translates a string, and replaces placeholders.
     *
     * @since [*next-version*]
     * @see   sprintf()
     * @see   _translate()
     *
     * @param string $string  The format string to translate.
     * @param array  $args    Placeholder values to replace in the string.
     * @param mixed  $context The context for translation.
     *
     * @return string The translated string.
     */
    abstract protected function __($string, $args = [], $context = null);
}
