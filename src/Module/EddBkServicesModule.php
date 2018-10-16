<?php

namespace RebelCode\EddBookings\Services\Module;

use ArrayIterator;
use Dhii\Config\ConfigFactoryInterface;
use Dhii\Data\Container\ContainerFactoryInterface;
use Dhii\Event\EventFactoryInterface;
use Dhii\Exception\InternalException;
use Dhii\Iterator\NormalizeIteratorCapableTrait;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use IteratorIterator;
use Psr\Container\ContainerInterface;
use Psr\EventManager\EventManagerInterface;
use RebelCode\Modular\Module\AbstractBaseModule;
use Traversable;

/**
 * Module class.
 *
 * @since [*next-version*]
 */
class EddBkServicesModule extends AbstractBaseModule
{
    /* @since [*next-version*] */
    use NormalizeArrayCapableTrait;

    /* @since [*next-version*] */
    use NormalizeIteratorCapableTrait;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable         $key                  The module key.
     * @param string[]|Stringable[]     $dependencies         The module dependencies.
     * @param ConfigFactoryInterface    $configFactory        The config factory.
     * @param ContainerFactoryInterface $containerFactory     The container factory.
     * @param ContainerFactoryInterface $compContainerFactory The composite container factory.
     * @param EventManagerInterface     $eventManager         The event manager.
     * @param EventFactoryInterface     $eventFactory         The event factory.
     */
    public function __construct(
        $key,
        $dependencies,
        $configFactory,
        $containerFactory,
        $compContainerFactory,
        $eventManager,
        $eventFactory
    ) {
        $this->_initModule($key, $dependencies, $configFactory, $containerFactory, $compContainerFactory);
        $this->_initModuleEvents($eventManager, $eventFactory);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     *
     * @throws InternalException If an error occurred while reading the config file.
     */
    public function setup()
    {
        return $this->_setupContainer(
            $this->_loadPhpConfigFile(RCMOD_EDDBK_SERVICES_CONFIG_FILE),
            $this->_loadPhpConfigFile(RCMOD_EDDBK_SERVICES_SERVICES_FILE)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function run(ContainerInterface $c = null)
    {
        if ($c === null) {
            return;
        }

        // Event for providing the booking services for the admin bookings UI
        $this->_attach('eddbk_admin_bookings_ui_services', $c->get('eddbk_admin_bookings_ui_services_handler'));

        // Event for deleting service-related entities when a Download is deleted
        $this->_attach('before_delete_post', $c->get('eddbk_admin_delete_service_handler'));

        // Event for the filtering a Download's price
        $this->_attach('edd_get_download_price', $c->get('eddbk_get_service_price_handler'));

        // Event for the filtering a Download's price options
        $this->_attach('edd_get_variable_prices', $c->get('eddbk_get_service_price_options_handler'));

        // Event for the filtering a Download's price options flag
        $this->_attach('edd_has_variable_prices', $c->get('eddbk_get_service_has_price_options_handler'));
    }
}
