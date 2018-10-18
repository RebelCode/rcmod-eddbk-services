<?php

namespace RebelCode\EddBookings\Services\TestStub;

/**
 * Stub class for an expression builder.
 *
 * Required since PHPUnit cannot mock the magic `__call()` method, so we make a stub implementation that makes
 * `__call()` invoke another method, in this case `build()`. We can now create mocks for this stub and mock this
 * method instead.
 *
 * @since [*next-version*]
 */
class ExprBuilderStub
{
    public function __call($name, $arguments)
    {
        return $this->build($name, $arguments);
    }

    public function build()
    {
        return null;
    }
}
