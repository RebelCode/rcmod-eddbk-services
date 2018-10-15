<?php

namespace RebelCode\EddBookings\Services\FuncTest\Storage;

use Dhii\Expression\LogicalExpressionInterface;
use Dhii\Factory\FactoryInterface;
use Dhii\Storage\Resource\DeleteCapableInterface;
use Dhii\Storage\Resource\InsertCapableInterface;
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Storage\Resource\Sql\OrderInterface;
use Dhii\Storage\Resource\UpdateCapableInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use RebelCode\EddBookings\Services\Storage\ServicesEntityManager as TestSubject;
use RebelCode\EddBookings\Services\Storage\ServicesInsertResourceModel;
use SebastianBergmann\Comparator\ArrayComparator;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory as ComparatorFactory;
use stdClass;
use WP_Mock;
use WP_Mock\Functions;
use Xpmock\TestCase;

/**
 * Tests the {@see ServicesEntityManager} class.
 *
 * @since [*next-version*]
 */
class ServicesEntityManagerTest extends TestCase
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
     * Creates a INSERT resource model mock instance.
     *
     * @since [*next-version*]
     *
     * @return MockObject|InsertCapableInterface The created mock instance.
     */
    protected function createInsertRm()
    {
        return $this->getMockBuilder('Dhii\Storage\Resource\InsertCapableInterface')
                    ->setMethods(['insert'])
                    ->getMockForAbstractClass();
    }

    /**
     * Creates a UPDATE resource model mock instance.
     *
     * @since [*next-version*]
     *
     * @return MockObject|UpdateCapableInterface The created mock instance.
     */
    protected function createUpdateRm()
    {
        return $this->getMockBuilder('Dhii\Storage\Resource\UpdateCapableInterface')
                    ->setMethods(['update'])
                    ->getMockForAbstractClass();
    }

    /**
     * Creates a DELETE resource model mock instance.
     *
     * @since [*next-version*]
     *
     * @return MockObject|DeleteCapableInterface The created mock instance.
     */
    protected function createDeleteRm()
    {
        return $this->getMockBuilder('Dhii\Storage\Resource\DeleteCapableInterface')
                    ->setMethods(['delete'])
                    ->getMockForAbstractClass();
    }

    /**
     * Creates a mock order factory instance.
     *
     * @since [*next-version*]
     *
     * @return MockObject|FactoryInterface The created mock instance.
     */
    protected function createOrderFactory()
    {
        $mock = $this->getMockBuilder('Dhii\Factory\FactoryInterface')
                     ->setMethods(['make'])
                     ->getMockForAbstractClass();

        $mock->method('make')->willReturnCallback(function ($cfg) {
            $order = $this->getMockForAbstractClass('Dhii\Storage\Resource\Sql\OrderInterface');

            isset($cfg['field']) && $order->method('getField')->willReturn($cfg['field']);
            isset($cfg['ascending']) && $order->method('isAscending')->willReturn($cfg['ascending']);

            return $order;
        });

        return $mock;
    }

    /**
     * Creates a mock expression builder instance.
     *
     * @since [*next-version*]
     *
     * @return MockObject|stdClass The created mock instance.
     */
    protected function createExprBuilder()
    {
        $mock = $this->getMockBuilder('RebelCode\EddBookings\Services\TestStub\ExprBuilderStub')
                     ->setMethods(['build'])
                     ->getMock();

        $mock->method('build')
             ->with($this->anything(), $this->anything())
             ->willReturnCallback(function ($method, $args) {
                 $expr = $this->getMockForAbstractClass('Dhii\Expression\LogicalExpressionInterface');
                 $expr->method('getType')->willReturn($method);
                 $expr->method('getTerms')->willReturn($args);

                 return $expr;
             });

        return $mock;
    }

    /**
     * Tests whether a valid instance of the test subject can be created.
     *
     * @since [*next-version*]
     */
    public function testCanBeCreated()
    {
        $subject = new TestSubject(
            '',
            '',
            $this->createSelectRm(),
            $this->createInsertRm(),
            $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createExprBuilder()
        );

        $this->assertInstanceOf(
            'RebelCode\EddBookings\Services\Storage\ServicesEntityManager',
            $subject,
            'Failed to create an instance of the test subject.'
        );

        $this->assertInstanceOf(
            'RebelCode\Entity\EntityManagerInterface',
            $subject,
            'Test subject does not implement expected interface.'
        );
    }

    /**
     * Tests the entity adding functionality.
     *
     * @since [*next-version*]
     */
    public function testAdd()
    {
        // The service to add
        $service = [
            'name'             => 'Test Service',
            'description'      => 'A service for testing',
            'image_id'         => 15,
            'bookings_enabled' => true,
            'timezone'         => 'Europe/Paris',
            'session_lengths'  => [
                [
                    'length' => 1800,
                    'price'  => 15.00,
                ],
                [
                    'length' => 3600,
                    'price'  => 25.00,
                ],
            ],
            'displayOptions'   => [
                'allowClientChangeTimezone' => true,
            ],
            'availability'     => [
                $rule1 = [
                    'id'                => 82,
                    'isAllDay'          => false,
                    'start'             => '2018-10-10T10:00:00+02:00',
                    'end'               => '2018-10-10T18:00:00+02:00',
                    'repeat'            => true,
                    'repeatUnit'        => 'day',
                    'repeatPeriod'      => 1,
                    'repeatUntil'       => 'period',
                    'repeatUntilPeriod' => 5,
                    'repeatUntilDate'   => 0,
                    'repeatWeeklyOn'    => [],
                    'repeatMonthlyOn'   => [],
                    'excludeDates'      => [
                        '2018-10-12T00:00:00+02:00',
                    ],
                ],
                $rule2 = [
                    'isAllDay'          => false,
                    'start'             => '2018-10-11T18:00:00+02:00',
                    'end'               => '2018-10-11T20:00:00+02:00',
                    'repeat'            => false,
                    'repeatUnit'        => 'day',
                    'repeatPeriod'      => 1,
                    'repeatUntil'       => 'period',
                    'repeatUntilPeriod' => 0,
                    'repeatUntilDate'   => 0,
                    'repeatWeeklyOn'    => [],
                    'repeatMonthlyOn'   => [],
                    'excludeDates'      => [],
                ],
            ],
        ];

        $insertedId = 50;

        WP_Mock::wpFunction('wp_insert_post', [
            'times'  => 1,
            'args'   => [
                function ($arg) {
                    $expected = [
                        'post_title'   => 'Test Service',
                        'post_excerpt' => 'A service for testing',
                        'post_type'    => 'download',
                        'meta_input'   => [
                            'eddbk_bookings_enabled' => true,
                            'eddbk_timezone'         => 'Europe/Paris',
                            'eddbk_session_lengths'  => [
                                [
                                    'length' => 1800,
                                    'price'  => 15.00,
                                ],
                                [
                                    'length' => 3600,
                                    'price'  => 25.00,
                                ],
                            ],
                            'eddbk_displayOptions'   => [
                                'allowClientChangeTimezone' => true,
                            ],
                        ],
                    ];

                    $this->assertEquals($expected, $arg);

                    return true;
                },
            ],
            'return' => $insertedId,
        ]);
        WP_Mock::wpFunction('set_post_thumbnail', [
            'times' => 1,
            'args'  => [
                $insertedId,
                $service['image_id'],
            ],
        ]);

        $subject = new TestSubject(
            'download',
            'eddbk_',
            $this->createSelectRm(),
            $insertRm = $this->createInsertRm(),
            $updateRm = $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createExprBuilder()
        );

        $actualId = $subject->add($service);

        $this->assertEquals($insertedId, $actualId);
    }

    /**
     * Tests the querying functionality.
     *
     * @since [*next-version*]
     */
    public function testQuery()
    {
        $filter    = [
            'name'     => 'Test Service',
            'timezone' => 'Europe/Malta',
        ];
        $limit     = 5;
        $offset    = 1;
        $orderBy   = 'name';
        $orderDesc = true;

        $subject = new TestSubject(
            'download',
            'eddbk_',
            $selectRm = $this->createSelectRm(),
            $this->createInsertRm(),
            $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createExprBuilder()
        );

        WP_Mock::wpFunction('get_posts', [
            'times'  => 1,
            'args'   => [
                function ($arg) {
                    $expected = [
                        'post_title'     => 'Test Service',
                        'post_type'      => 'download',
                        'post_status'    => 'publish',
                        'posts_per_page' => 5,
                        'offset'         => 1,
                        'orderby'        => 'post_title',
                        'order'          => 'DESC',
                        'meta_query'     => [
                            'relation'         => 'AND',
                            'bookings_enabled' => [
                                'key'   => 'eddbk_bookings_enabled',
                                'value' => '1',
                            ],
                            'timezone'         => [
                                'key'   => 'eddbk_timezone',
                                'value' => 'Europe/Malta',
                            ],
                        ],
                    ];

                    $this->assertEquals($arg, $expected);

                    return true;
                },
            ],
            'return' => [
                (object) [
                    'ID'           => 112,
                    'post_title'   => 'Test Service',
                    'post_excerpt' => 'A test service',
                ],
            ],
        ]);

        WP_Mock::wpFunction('get_the_post_thumbnail_url', [
            'times'  => 1,
            'args'   => [
                112,
            ],
            'return' => 68,
        ]);

        WP_Mock::wpFunction('get_post_meta', [
            'times'  => '4-', // 4 or more times (four meta keys are known at the time of writing this test)
            'args'   => [112, Functions::type('string'), true],
            'return' => 'test_meta',
        ]);

        $selectRm->expects($this->once())
                 ->method('select')
                 ->willReturn($rules = [
                     [
                         'start' => '2018-10-10T10:00:00+02:00',
                         'end'   => '2018-10-10T15:00:00+02:00',
                     ],
                 ]);

        $actual   = $subject->query($filter, $limit, $offset, $orderBy, $orderDesc);
        $expected = [
            [
                'id'               => 112,
                'name'             => 'Test Service',
                'description'      => 'A test service',
                'bookings_enabled' => 'test_meta',
                'timezone'         => 'test_meta',
                'display_options'  => 'test_meta',
                'session_lengths'  => 'test_meta',
                'image_url'        => 68,
                'availability'     => $rules,
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests the querying functionality without any arguments.
     *
     * @since [*next-version*]
     */
    public function testQueryNoArgs()
    {
        $subject = new TestSubject(
            'download',
            'eddbk_',
            $selectRm = $this->createSelectRm(),
            $this->createInsertRm(),
            $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createExprBuilder()
        );

        WP_Mock::wpFunction('get_posts', [
            'times'  => 1,
            'args'   => [
                function ($arg) {
                    $expected = [
                        'post_type'      => 'download',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'meta_query'     => [
                            'relation'         => 'AND',
                            'bookings_enabled' => [
                                'key'   => 'eddbk_bookings_enabled',
                                'value' => '1',
                            ],
                        ],
                    ];

                    $this->assertEquals($arg, $expected);

                    return true;
                },
            ],
            'return' => [
                (object) [
                    'ID'           => 112,
                    'post_title'   => 'Test Service',
                    'post_excerpt' => 'A test service',
                ],
            ],
        ]);

        WP_Mock::wpFunction('get_the_post_thumbnail_url', [
            'times'  => 1,
            'args'   => [
                112,
            ],
            'return' => 68,
        ]);

        WP_Mock::wpFunction('get_post_meta', [
            'times'  => '4-', // 4 or more times (four meta keys are known at the time of writing this test)
            'args'   => [112, Functions::type('string'), true],
            'return' => 'test_meta',
        ]);

        $selectRm->expects($this->once())
                 ->method('select')
                 ->willReturn($rules = [
                     [
                         'start' => '2018-10-10T10:00:00+02:00',
                         'end'   => '2018-10-10T15:00:00+02:00',
                     ],
                 ]);

        $actual   = $subject->query();
        $expected = [
            [
                'id'               => 112,
                'name'             => 'Test Service',
                'description'      => 'A test service',
                'bookings_enabled' => 'test_meta',
                'timezone'         => 'test_meta',
                'display_options'  => 'test_meta',
                'session_lengths'  => 'test_meta',
                'image_url'        => 68,
                'availability'     => $rules,
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests the retrieval of entities by ID.
     *
     * @since [*next-version*]
     */
    public function testGet()
    {
        $id = rand(1, 100);

        $subject = new TestSubject(
            'download',
            'eddbk_',
            $selectRm = $this->createSelectRm(),
            $this->createInsertRm(),
            $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createExprBuilder()
        );

        WP_Mock::wpFunction('get_posts', [
            'times'  => 1,
            'args'   => [
                function ($arg) use ($id) {
                    $expected = [
                        'post__in'       => [$id],
                        'post_type'      => 'download',
                        'post_status'    => 'publish',
                        'posts_per_page' => 1,
                        'meta_query'     => [
                            'relation'         => 'AND',
                            'bookings_enabled' => [
                                'key'   => 'eddbk_bookings_enabled',
                                'value' => '1',
                            ],
                        ],
                    ];

                    $this->assertEquals($arg, $expected);

                    return true;
                },
            ],
            'return' => [
                (object) [
                    'ID'           => $id,
                    'post_title'   => 'Test Service',
                    'post_excerpt' => 'A test service',
                ],
            ],
        ]);

        WP_Mock::wpFunction('get_the_post_thumbnail_url', [
            'times'  => 1,
            'args'   => [
                $id,
            ],
            'return' => 68,
        ]);

        WP_Mock::wpFunction('get_post_meta', [
            'times'  => '4-', // 4 or more times (four meta keys are known at the time of writing this test)
            'args'   => [$id, Functions::type('string'), true],
            'return' => 'test_meta',
        ]);

        $selectRm->expects($this->once())
                 ->method('select')
                 ->willReturn($rules = [
                     [
                         'start' => '2018-10-10T10:00:00+02:00',
                         'end'   => '2018-10-10T15:00:00+02:00',
                     ],
                 ]);

        $actual   = $subject->get($id);
        $expected = [
            'id'               => $id,
            'name'             => 'Test Service',
            'description'      => 'A test service',
            'bookings_enabled' => 'test_meta',
            'timezone'         => 'test_meta',
            'display_options'  => 'test_meta',
            'session_lengths'  => 'test_meta',
            'image_url'        => 68,
            'availability'     => $rules,
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests the existence checking functionality for a true scenario.
     *
     * @since [*next-version*]
     */
    public function testHasTrue()
    {
        $id = rand(1, 100);

        $subject = new TestSubject(
            'download',
            'eddbk_',
            $selectRm = $this->createSelectRm(),
            $this->createInsertRm(),
            $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createExprBuilder()
        );

        WP_Mock::wpFunction('get_posts', [
            'times'  => 1,
            'args'   => [
                function ($arg) use ($id) {
                    $expected = [
                        'post__in'       => [$id],
                        'post_type'      => 'download',
                        'post_status'    => 'publish',
                        'posts_per_page' => 1,
                        'meta_query'     => [
                            'relation'         => 'AND',
                            'bookings_enabled' => [
                                'key'   => 'eddbk_bookings_enabled',
                                'value' => '1',
                            ],
                        ],
                    ];

                    $this->assertEquals($arg, $expected);

                    return true;
                },
            ],
            'return' => [
                (object) [
                    'ID'           => $id,
                    'post_title'   => 'Test Service',
                    'post_excerpt' => 'A test service',
                ],
            ],
        ]);

        WP_Mock::wpFunction('get_the_post_thumbnail_url', [
            'times'  => 1,
            'args'   => [
                $id,
            ],
            'return' => 68,
        ]);

        WP_Mock::wpFunction('get_post_meta', [
            'times'  => '4-', // 4 or more times (four meta keys are known at the time of writing this test)
            'args'   => [$id, Functions::type('string'), true],
            'return' => 'test_meta',
        ]);

        $selectRm->expects($this->once())
                 ->method('select')
                 ->willReturn($rules = [
                     [
                         'start' => '2018-10-10T10:00:00+02:00',
                         'end'   => '2018-10-10T15:00:00+02:00',
                     ],
                 ]);

        $actual = $subject->has($id);

        $this->assertTrue($actual);
    }

    /**
     * Tests the existence checking functionality for a false scenario.
     *
     * @since [*next-version*]
     */
    public function testHasFalse()
    {
        $id = rand(1, 100);

        $subject = new TestSubject(
            'download',
            'eddbk_',
            $selectRm = $this->createSelectRm(),
            $this->createInsertRm(),
            $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createExprBuilder()
        );

        WP_Mock::wpFunction('get_posts', [
            'times'  => 1,
            'args'   => [
                function ($arg) use ($id) {
                    $expected = [
                        'post__in'       => [$id],
                        'post_type'      => 'download',
                        'post_status'    => 'publish',
                        'posts_per_page' => 1,
                        'meta_query'     => [
                            'relation'         => 'AND',
                            'bookings_enabled' => [
                                'key'   => 'eddbk_bookings_enabled',
                                'value' => '1',
                            ],
                        ],
                    ];

                    $this->assertEquals($arg, $expected);

                    return true;
                },
            ],
            'return' => [],
        ]);

        WP_Mock::wpFunction('get_the_post_thumbnail_url', [
            'times' => 0,
        ]);

        WP_Mock::wpFunction('get_post_meta', [
            'times' => '0',
        ]);

        $selectRm->expects($this->never())
                 ->method('select');

        $actual = $subject->has($id);

        $this->assertFalse($actual);
    }

    /**
     * Tests the updating functionality.
     *
     * @since [*next-version*]
     */
    public function testUpdate()
    {
        $serviceId = 552;
        $imageId   = 336;

        $data = [
            'name'             => 'New name',
            'bookings_enabled' => false,
            'image_id'         => $imageId,
        ];

        WP_Mock::wpFunction('wp_update_post', [
            'times' => 1,
            'args'  => [
                $serviceId,
                function ($arg) {
                    $expected = [
                        'post_title' => 'New name',
                        'meta_input' => [
                            'eddbk_bookings_enabled' => false,
                        ],
                    ];

                    $this->assertEquals($arg, $expected);

                    return true;
                },
            ],
        ]);

        WP_Mock::wpFunction('set_post_thumbnail', [
            'times' => 1,
            'args'  => [
                $serviceId,
                $imageId,
            ],
        ]);

        $subject = new TestSubject(
            'download',
            'eddbk_',
            $this->createSelectRm(),
            $insertRm = $this->createInsertRm(),
            $updateRm = $this->createUpdateRm(),
            $deleteRm = $this->createDeleteRm(),
            $this->createExprBuilder()
        );

        $subject->update($serviceId, $data);
    }

    /**
     * Tests the setting functionality.
     *
     * @since [*next-version*]
     */
    public function testSet()
    {
        $this->testUpdate();
    }

    /**
     * Tests the deletion functionality.
     *
     * @since [*next-version*]
     */
    public function testDelete()
    {
        $id = 447;

        $subject = new TestSubject(
            'download',
            'eddbk_',
            $this->createSelectRm(),
            $insertRm = $this->createInsertRm(),
            $updateRm = $this->createUpdateRm(),
            $deleteRm = $this->createDeleteRm(),
            $this->createExprBuilder()
        );

        WP_Mock::wpFunction('wp_delete_post', [
            'times' => 1,
            'args'  => [$id, true],
        ]);

        $deleteRm->expects($this->once())
                 ->method('delete');

        $subject->delete($id);
    }
}
