<?php

namespace RebelCode\EddBookings\Services\FuncTest\Storage;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Xpmock\TestCase;

/**
 * Tests {@see TestSubject}.
 *
 * @since [*next-version*]
 */
class ServicesFieldKeyMapAwareTraitTest extends TestCase
{
    /**
     * The class name of the test subject.
     *
     * @since [*next-version*]
     */
    const TEST_SUBJECT_CLASSNAME = 'RebelCode\EddBookings\Services\Storage\ServicesFieldKeyMapAwareTrait';

    /**
     * Creates a new instance of the test subject.
     *
     * @since [*next-version*]
     *
     * @return MockObject
     */
    public function createInstance()
    {
        // Create mock
        $mock = $this->getMockBuilder(static::TEST_SUBJECT_CLASSNAME)
                     ->setMethods([])
                     ->getMockForTrait();

        return $mock;
    }

    /**
     * Tests whether a valid instance of the test subject can be created.
     *
     * @since [*next-version*]
     */
    public function testCanBeCreated()
    {
        $subject = $this->createInstance();

        $this->assertInternalType(
            'object',
            $subject,
            'An instance of the test subject could not be created'
        );
    }

    /**
     * Tests the getter method to ensure correct retrieval of the field-key map.
     *
     * @since [*next-version*]
     */
    public function testGetServicesFieldKeyMap()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);

        $fkm = $reflect->_getServicesFieldKeyMap();

        $this->assertInternalType('array', $fkm, 'Field key map is not an array!');
        $this->assertNotEmpty($fkm, 'Field key map is empty!');
    }
}
