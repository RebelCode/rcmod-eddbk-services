<?php

use Psr\Container\ContainerInterface;
use RebelCode\EddBookings\Services\Module\EddBkServicesModule;

define('RCMOD_EDDBK_SERVICES_DIR', __DIR__);
define('RCMOD_EDDBK_SERVICES_CONFIG_DIR', RCMOD_EDDBK_SERVICES_DIR);
define('RCMOD_EDDBK_SERVICES_CONFIG_FILE', RCMOD_EDDBK_SERVICES_CONFIG_DIR . '/config.php');
define('RCMOD_EDDBK_SERVICES_KEY', 'eddbk_services');

return function (ContainerInterface $c) {
    return new EddBkServicesModule(
        RCMOD_EDDBK_SERVICES_KEY,
        [],
        $c->get('config_factory'),
        $c->get('container_factory'),
        $c->get('composite_container_factory'),
        $c->get('event_manager'),
        $c->get('event_factory')
    );
};
