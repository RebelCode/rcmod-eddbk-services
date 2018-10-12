<?php

namespace RebelCode\EddBookings\Services\FuncTest;

use Dhii\Expression\LogicalExpressionInterface;
use Dhii\Factory\FactoryInterface;
use Dhii\Storage\Resource\DeleteCapableInterface;
use Dhii\Storage\Resource\InsertCapableInterface;
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Storage\Resource\Sql\OrderInterface;
use Dhii\Storage\Resource\UpdateCapableInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use RebelCode\EddBookings\Services\ServicesEntityManager as TestSubject;
use RebelCode\EddBookings\Services\ServicesInsertResourceModel;
use SebastianBergmann\Comparator\ArrayComparator;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory as ComparatorFactory;
use stdClass;
use WP_Mock;
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
            $this->createSelectRm(),
            $this->createInsertRm(),
            $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createOrderFactory(),
            $this->createExprBuilder()
        );

        $this->assertInstanceOf(
            'RebelCode\EddBookings\Services\ServicesEntityManager',
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
        $postType    = uniqid('post-type-');
        $metaPrefix  = uniqid('prefix-');
        $name        = uniqid('name-');
        $description = uniqid('description-');
        $imageId     = rand(1, 100);
        $meta1       = uniqid('meta-');
        $meta2       = uniqid('meta-');

        $subject = new TestSubject(
            $this->createSelectRm(),
            new ServicesInsertResourceModel($postType, $metaPrefix),
            $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createOrderFactory(),
            $this->createExprBuilder()
        );

        $expectedId       = rand(1, 100);
        $expectedPostData = [
            'post_title'   => $name,
            'post_excerpt' => $description,
            'post_type'    => $postType,
            'meta_input'   => [
                $metaPrefix . 'meta1' => $meta1,
                $metaPrefix . 'meta2' => $meta2,
            ],
        ];

        WP_Mock::wpFunction('wp_insert_post', [
            'times'  => 1,
            'return' => $expectedId,
            'args'   => [
                function ($arg) use ($expectedPostData) {
                    $comparator = new ArrayComparator();
                    $comparator->setFactory(ComparatorFactory::getInstance());

                    try {
                        $comparator->assertEquals($expectedPostData, $arg, 0);
                    } catch (ComparisonFailure $failure) {
                        echo $failure->getDiff();
                        throw $failure;
                    }

                    return true;
                },
            ],
        ]);

        WP_Mock::wpFunction('set_post_thumbnail', [
            'times'  => 1,
            'return' => rand(1, 100),
            'args'   => [$expectedId, $imageId],
        ]);

        $returnedId = $subject->add([
            'name'        => $name,
            'description' => $description,
            'image_id'    => $imageId,
            'meta1'       => $meta1,
            'meta2'       => $meta2,
        ]);

        $this->assertEquals($expectedId, $returnedId);
    }

    /**
     * Tests the querying functionality.
     *
     * @since [*next-version*]
     */
    public function testQuery()
    {
        $subject = new TestSubject(
            $rm = $this->createSelectRm(),
            $this->createInsertRm(),
            $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createOrderFactory(),
            $this->createExprBuilder()
        );

        // Prepare query vars
        $name      = uniqid('name-');
        $metaKey   = uniqid('meta-key-');
        $metaValue = uniqid('meta-value-');
        $orderBy   = uniqid('field-');
        $orderDesc = (bool) rand(0, 1);
        $limit     = rand(0, 100);
        $offset    = rand(0, 100);
        // Prepare query
        $query = [
            'name'   => $name,
            $metaKey => $metaValue,
        ];

        // Prepare expected mock results
        $expectedServices = [
            [
                'id'     => rand(1, 100),
                'name'   => $name,
                $metaKey => $metaKey,
            ],
            [
                'id'     => rand(1, 100),
                'name'   => $name,
                $metaKey => $metaKey,
            ],
        ];

        // Function to match the condition passed to SELECT RM
        $matchCondition = function ($condition) {
            /* @var $condition LogicalExpressionInterface */

            // Condition must be an AND expression
            if ($condition->getType() !== 'and') {
                return false;
            };

            // Condition must have two terms
            if (count($condition->getTerms()) !== 2) {
                return false;
            }

            // All terms are "eq" type expressions with two terms
            foreach ($condition->getTerms() as $term) {
                if ($term instanceof LogicalExpressionInterface &&
                    $term->getType() === 'eq' &&
                    count($term->getTerms()) === 2
                ) {
                    continue;
                }

                return false;
            }

            return true;
        };

        // Function to match the ordering
        $matchOrder = function ($ordering) use ($orderBy, $orderDesc) {
            if (count($ordering) === 0) {
                return false;
            }

            /* @var $order OrderInterface */
            $order = $ordering[0];

            return $order->getField() === $orderBy && $order->isAscending() !== $orderDesc;
        };

        // Expect SELECT resource model to be invoked
        $rm->expects($this->once())
           ->method('select')
           ->with($this->callback($matchCondition), $this->callback($matchOrder), $limit, $offset)
           ->willReturn($expectedServices);

        // Query and get services
        $returnedServices = $subject->query($query, $limit, $offset, $orderBy, $orderDesc);

        // Compare retrieved services with those returned by SELECT resource model
        $this->assertEquals($expectedServices, $returnedServices);
    }

    /**
     * Tests the retrieval of entities by ID.
     *
     * @since [*next-version*]
     */
    public function testGet()
    {
        $subject = new TestSubject(
            $rm = $this->createSelectRm(),
            $this->createInsertRm(),
            $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createOrderFactory(),
            $this->createExprBuilder()
        );

        $id = rand(1, 100);

        // Function to match the condition passed to SELECT RM
        $matchCondition = function ($condition) {
            /* @var $condition LogicalExpressionInterface */

            // Condition must be an AND expression
            if ($condition->getType() !== 'and') {
                return false;
            };

            // Condition must have only 1 term
            if (count($condition->getTerms()) !== 1) {
                return false;
            }

            $terms = $condition->getTerms();
            $term  = reset($terms);

            if (!($term instanceof LogicalExpressionInterface) ||
                $term->getType() !== 'eq' ||
                count($term->getTerms()) !== 2
            ) {
                return false;
            }

            return true;
        };

        // Prepare expected mock results
        $expectedService = [
            'id'   => rand(1, 100),
            'name' => uniqid('name-'),
            'meta' => uniqid('meta-'),
        ];

        // Expect SELECT resource model to be invoked
        $rm->expects($this->once())
           ->method('select')
           ->with($this->callback($matchCondition), [], 1, null)
           ->willReturn([$expectedService]);

        $returnedService = $subject->get($id);

        $this->assertEquals($expectedService, $returnedService);
    }

    /**
     * Tests the existence checking functionality for a true scenario.
     *
     * @since [*next-version*]
     */
    public function testHasTrue()
    {
        $subject = new TestSubject(
            $rm = $this->createSelectRm(),
            $this->createInsertRm(),
            $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createOrderFactory(),
            $this->createExprBuilder()
        );

        $id = rand(1, 100);

        // Prepare expected mock results
        $expectedService = [
            'id'   => rand(1, 100),
            'name' => uniqid('name-'),
            'meta' => uniqid('meta-'),
        ];

        // Expect SELECT resource model to be invoked
        $rm->expects($this->once())
           ->method('select')
           ->with($this->isInstanceOf('Dhii\Expression\LogicalExpressionInterface'), [], 1, null)
           ->willReturn([$expectedService]);

        $this->assertTrue($subject->has($id));
    }

    /**
     * Tests the existence checking functionality for a false scenario.
     *
     * @since [*next-version*]
     */
    public function testHasFalse()
    {
        $subject = new TestSubject(
            $rm = $this->createSelectRm(),
            $this->createInsertRm(),
            $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createOrderFactory(),
            $this->createExprBuilder()
        );

        $id = rand(1, 100);

        // Expect SELECT resource model to be invoked
        $rm->expects($this->once())
           ->method('select')
           ->with($this->isInstanceOf('Dhii\Expression\LogicalExpressionInterface'), [], 1, null)
           ->willReturn([]);

        $this->assertFalse($subject->has($id));
    }

    /**
     * Tests the updating functionality.
     *
     * @since [*next-version*]
     */
    public function testUpdate()
    {
        $subject = new TestSubject(
            $this->createSelectRm(),
            $this->createInsertRm(),
            $rm = $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createOrderFactory(),
            $this->createExprBuilder()
        );

        $id      = rand(1, 100);
        $changes = [
            'name'  => uniqid('name-'),
            'meta1' => uniqid('meta-'),
            'meta2' => uniqid('meta-'),
        ];

        // Function to match the condition passed to SELECT RM
        $matchCondition = function ($condition) {
            /* @var $condition LogicalExpressionInterface */

            // Condition must be an AND expression
            if ($condition->getType() !== 'and') {
                return false;
            };

            // Condition must have only 1 term
            if (count($condition->getTerms()) !== 1) {
                return false;
            }

            $terms = $condition->getTerms();
            $term  = reset($terms);

            if (!($term instanceof LogicalExpressionInterface) ||
                $term->getType() !== 'eq' ||
                count($term->getTerms()) !== 2
            ) {
                return false;
            }

            return true;
        };

        // Expect UPDATE resource model to be invoked
        $rm->expects($this->once())
           ->method('update')
           ->with($changes, $this->callback($matchCondition))
           ->willReturn(1);

        $subject->update($id, $changes);
    }

    /**
     * Tests the setting functionality.
     *
     * @since [*next-version*]
     */
    public function testSet()
    {
        $subject = new TestSubject(
            $this->createSelectRm(),
            $this->createInsertRm(),
            $rm = $this->createUpdateRm(),
            $this->createDeleteRm(),
            $this->createOrderFactory(),
            $this->createExprBuilder()
        );

        $id   = rand(1, 100);
        $data = [
            'name'        => uniqid('name-'),
            'description' => uniqid('description-'),
            'meta1'       => uniqid('meta-'),
            'meta2'       => uniqid('meta-'),
        ];

        // Function to match the condition passed to SELECT RM
        $matchCondition = function ($condition) {
            /* @var $condition LogicalExpressionInterface */

            // Condition must be an AND expression
            if ($condition->getType() !== 'and') {
                return false;
            };

            // Condition must have only 1 term
            if (count($condition->getTerms()) !== 1) {
                return false;
            }

            $terms = $condition->getTerms();
            $term  = reset($terms);

            if (!($term instanceof LogicalExpressionInterface) ||
                $term->getType() !== 'eq' ||
                count($term->getTerms()) !== 2
            ) {
                return false;
            }

            return true;
        };

        // Expect UPDATE resource model to be invoked
        $rm->expects($this->once())
           ->method('update')
           ->with($data, $this->callback($matchCondition))
           ->willReturn(1);

        $subject->set($id, $data);
    }

    /**
     * Tests the deletion functionality.
     *
     * @since [*next-version*]
     */
    public function testDelete()
    {
        $subject = new TestSubject(
            $this->createSelectRm(),
            $this->createInsertRm(),
            $this->createUpdateRm(),
            $rm = $this->createDeleteRm(),
            $this->createOrderFactory(),
            $this->createExprBuilder()
        );

        $id = rand(1, 100);

        // Function to match the condition passed to SELECT RM
        $matchCondition = function ($condition) {
            /* @var $condition LogicalExpressionInterface */

            // Condition must be an AND expression
            if ($condition->getType() !== 'and') {
                return false;
            };

            // Condition must have only 1 term
            if (count($condition->getTerms()) !== 1) {
                return false;
            }

            $terms = $condition->getTerms();
            $term  = reset($terms);

            if (!($term instanceof LogicalExpressionInterface) ||
                $term->getType() !== 'eq' ||
                count($term->getTerms()) !== 2
            ) {
                return false;
            }

            return true;
        };

        // Expect DELETE resource model to be invoked
        $rm->expects($this->once())
           ->method('delete')
           ->with($this->callback($matchCondition), null, 1, null)
           ->willReturn(1);

        $subject->delete($id);
    }
}
