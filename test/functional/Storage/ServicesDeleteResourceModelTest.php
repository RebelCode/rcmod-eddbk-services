<?php

namespace RebelCode\EddBookings\Services\FuncTest\Storage;

use Dhii\Storage\Resource\SelectCapableInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use RebelCode\EddBookings\Services\Storage\ServicesDeleteResourceModel as TestSubject;
use WP_Mock;
use Xpmock\TestCase;

/**
 * Tests the {@see ServicesDeleteResourceModel} class.
 *
 * @since [*next-version*]
 */
class ServicesDeleteResourceModelTest extends TestCase
{
    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function setUp()
    {
        WP_Mock::setUp();
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function tearDown()
    {
        WP_Mock::tearDown();
    }

    /**
     * Creates a SELECT resource model mock instance.
     *
     * @since [*next-version*]
     *
     * @return MockObject|SelectCapableInterface The created mock instance.
     */
    protected function createSelectRm()
    {
        return $this->getMockBuilder('Dhii\Storage\Resource\SelectCapableInterface')
                    ->setMethods(['select'])
                    ->getMockForAbstractClass();
    }

    /**
     * Tests whether a valid instance of the test subject can be created.
     *
     * @since [*next-version*]
     */
    public function testCanBeCreated()
    {
        $subject = new TestSubject($this->createSelectRm());

        $this->assertInstanceOf(
            'RebelCode\EddBookings\Services\Storage\ServicesDeleteResourceModel',
            $subject,
            'Created instance of the test subject is invalid.'
        );

        $this->assertInstanceOf(
            'Dhii\Storage\Resource\DeleteCapableInterface',
            $subject,
            'Test subject does not implement expected interface.'
        );
    }

    /**
     * Tests the delete functionality to assert whether the SELECT resource model is used to retrieve the posts for
     * deletion and whether the WordPress `wp_delete_post()` is called and with the correct arguments.
     *
     * @since [*next-version*]
     */
    public function testDelete()
    {
        $selectRm = $this->createSelectRm();
        $subject  = new TestSubject($selectRm);

        $condition = $this->getMockForAbstractClass('Dhii\Expression\LogicalExpressionInterface');
        $ordering  = [$this->getMockForAbstractClass('Dhii\Storage\Resource\Sql\OrderInterface')];
        $limit     = rand(1, 100);
        $offset    = rand(1, 100);
        $services  = [
            ['id' => rand(1, 100)],
            ['id' => rand(1, 100)],
            ['id' => rand(1, 100)],
        ];

        $selectRm->expects($this->once())
                 ->method('select')
                 ->with($condition, $ordering, $limit, $offset)
                 ->willReturn($services);

        foreach ($services as $service) {
            WP_Mock::wpFunction('wp_delete_post', [
                'times' => 1,
                'args'  => [$service['id'], true],
            ]);
        }

        $subject->delete($condition, $ordering, $limit, $offset);
    }
}
