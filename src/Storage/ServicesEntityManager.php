<?php

namespace RebelCode\EddBookings\Services\Storage;

use Dhii\Data\Container\ContainerGetCapableTrait;
use Dhii\Data\Container\ContainerHasCapableTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\CreateNotFoundExceptionCapableTrait;
use Dhii\Data\Container\NormalizeKeyCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\Exception\CreateRuntimeExceptionCapableTrait;
use Dhii\Expression\LogicalExpressionInterface;
use Dhii\Factory\FactoryInterface;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Storage\Resource\DeleteCapableInterface;
use Dhii\Storage\Resource\InsertCapableInterface;
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Storage\Resource\Sql\OrderInterface;
use Dhii\Storage\Resource\UpdateCapableInterface;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use Dhii\Util\Normalization\NormalizeIntCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use Exception;
use Psr\Container\NotFoundExceptionInterface;
use RebelCode\Entity\EntityManagerInterface;
use stdClass;
use Traversable;

/**
 * An entity manager implementation for services.
 *
 * @since [*next-version*]
 */
class ServicesEntityManager implements EntityManagerInterface
{
    /* @since [*next-version*] */
    use ServicesFieldKeyMapAwareTrait;

    /* @since [*next-version*] */
    use ContainerGetCapableTrait;

    /* @since [*next-version*] */
    use ContainerHasCapableTrait;

    /* @since [*next-version*] */
    use NormalizeIntCapableTrait;

    /* @since [*next-version*] */
    use NormalizeKeyCapableTrait;

    /* @since [*next-version*] */
    use NormalizeStringCapableTrait;

    /* @since [*next-version*] */
    use NormalizeArrayCapableTrait;

    /* @since [*next-version*] */
    use CreateContainerExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateNotFoundExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateOutOfRangeExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateRuntimeExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The select resource model.
     *
     * @since [*next-version*]
     *
     * @var SelectCapableInterface
     */
    protected $selectRm;

    /**
     * The insert resource model.
     *
     * @since [*next-version*]
     *
     * @var InsertCapableInterface
     */
    protected $insertRm;

    /**
     * The update resource model.
     *
     * @since [*next-version*]
     *
     * @var UpdateCapableInterface
     */
    protected $updateRm;

    /**
     * The delete resource model.
     *
     * @since [*next-version*]
     *
     * @var DeleteCapableInterface
     */
    protected $deleteRm;

    /**
     * The expression builder instance.
     *
     * @since [*next-version*]
     *
     * @var object
     */
    protected $exprBuilder;

    /**
     * The factory for creating order instances.
     *
     * @since [*next-version*]
     *
     * @var FactoryInterface
     */
    protected $orderFactory;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param SelectCapableInterface $selectRm     The SELECT resource model for services.
     * @param InsertCapableInterface $insertRm     The INSERT resource model for services.
     * @param UpdateCapableInterface $updateRm     The UPDATE resource model for services.
     * @param DeleteCapableInterface $deleteRm     The DELETE resource model for services.
     * @param FactoryInterface       $orderFactory The factory for creating ordering instances.
     * @param object                 $exprBuilder  The expression builder instances for creating query conditions.
     */
    public function __construct(
        SelectCapableInterface $selectRm,
        InsertCapableInterface $insertRm,
        UpdateCapableInterface $updateRm,
        DeleteCapableInterface $deleteRm,
        FactoryInterface $orderFactory,
        $exprBuilder
    ) {
        $this->selectRm     = $selectRm;
        $this->insertRm     = $insertRm;
        $this->updateRm     = $updateRm;
        $this->deleteRm     = $deleteRm;
        $this->orderFactory = $orderFactory;
        $this->exprBuilder  = $exprBuilder;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function add($entity)
    {
        $exception = null;

        try {
            $ids = $this->insertRm->insert([$entity]);
        } catch (Exception $exception) {
            $ids = [];
        }

        $ids = $this->_normalizeArray($ids);

        if (count($ids) === 0) {
            throw $this->_createRuntimeException(
                $this->__('Failed to insert entity'), null, $exception
            );
        }

        return $ids[0];
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function get($id)
    {
        $results = $this->query(['id' => $id], 1);
        $results = $this->_normalizeArray($results);

        if (count($results) === 0) {
            throw $this->_createNotFoundException(
                $this->__('Service entity with ID %s was not found', [$id]), null, null, $this, (string) $id
            );
        }

        return reset($results);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function has($id)
    {
        try {
            $this->get($id);
        } catch (NotFoundExceptionInterface $exception) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function query($query = [], $limit = null, $offset = null, $orderBy = null, $desc = false)
    {
        $condition = $this->_buildQueryCondition($query);
        $ordering  = ($orderBy !== null)
            ? [$this->_buildOrder($orderBy, $desc)]
            : [];

        return $this->selectRm->select($condition, $ordering, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function set($id, $entity)
    {
        $this->update($id, $entity);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function update($id, $data)
    {
        $b = $this->exprBuilder;
        $e = $b->and(
            $b->eq(
                $b->ef('service', 'id'),
                $b->lit($id)
            )
        );

        $this->updateRm->update($data, $e);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function delete($id)
    {
        $b = $this->exprBuilder;
        $e = $b->and(
            $b->eq(
                $b->ef('service', 'id'),
                $b->lit($id)
            )
        );

        $this->deleteRm->delete($e, null, 1, null);
    }

    /**
     * Builds the query condition for the given query filters.
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|Traversable $queryFilters The query filters.
     *
     * @return LogicalExpressionInterface The build query condition.
     */
    protected function _buildQueryCondition($queryFilters)
    {
        $b = $this->exprBuilder;
        $e = [];

        foreach ($queryFilters as $key => $value) {
            $e[] = $b->eq(
                $b->var($key),
                $b->lit($value)
            );
        }

        return call_user_func_array([$b, 'and'], $e);
    }

    /**
     * Builds the ordering instance for queries.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $orderBy The field to order by.
     * @param bool              $desc    Optional boolean which if true will create a descending order instance.
     *
     * @return OrderInterface|null The created order instance, or null for no ordering.
     */
    protected function _buildOrder($orderBy, $desc = false)
    {
        return $this->orderFactory->make([
            'field'     => $orderBy,
            'ascending' => !$desc,
        ]);
    }
}
