<?php

namespace RebelCode\EddBookings\Services\Module;

use ArrayAccess;
use Dhii\Util\String\StringableInterface as Stringable;
use Psr\Container\ContainerInterface;
use stdClass;

/**
 * Something that can act as a services controller.
 *
 * @since [*next-version*]
 */
interface ServicesControllerInterface
{
    /**
     * Retrieves the data for a service.
     *
     * @since [*next-version*]
     *
     * @return array|stdClass|ArrayAccess|ContainerInterface
     */
    public function getService($id);

    /**
     * Updates the data for a service.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable                         $id   The service ID.
     * @param array|stdClass|ArrayAccess|ContainerInterface $data The data to update.
     */
    public function updateService($id, $data);
}
