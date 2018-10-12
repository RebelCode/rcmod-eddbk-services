<?php

namespace RebelCode\EddBookings\Services\Storage;

use Dhii\Data\Container\ContainerGetCapableTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\CreateNotFoundExceptionCapableTrait;
use Dhii\Data\Container\NormalizeKeyCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\Expression\LogicalExpressionInterface;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Iterator\CountIterableCapableTrait;
use Dhii\Iterator\ResolveIteratorCapableTrait;
use Dhii\Storage\Resource\DeleteCapableInterface;
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Util\Normalization\NormalizeIntCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * A resource model implementation for deleting services that are `download` posts from EDD.
 *
 * @since [*next-version*]
 */
class ServicesDeleteResourceModel implements DeleteCapableInterface
{
    /* @since [*next-version*] */
    use ContainerGetCapableTrait;

    /* @since [*next-version*] */
    use CountIterableCapableTrait;

    /* @since [*next-version*] */
    use ResolveIteratorCapableTrait;

    /* @since [*next-version*] */
    use NormalizeIntCapableTrait;

    /* @since [*next-version*] */
    use NormalizeKeyCapableTrait;

    /* @since [*next-version*] */
    use NormalizeStringCapableTrait;

    /* @since [*next-version*] */
    use CreateContainerExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateNotFoundExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateOutOfRangeExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The SELECT resource model to get the posts to delete.
     *
     * @since [*next-version*]
     *
     * @var SelectCapableInterface
     */
    protected $selectRm;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param SelectCapableInterface $selectRm The SELECT resource model for getting the posts to delete.
     */
    public function __construct(
        SelectCapableInterface $selectRm
    ) {
        $this->selectRm = $selectRm;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function delete(
        LogicalExpressionInterface $condition = null,
        $ordering = null,
        $limit = null,
        $offset = null
    ) {
        $services = $this->selectRm->select($condition, $ordering, $limit, $offset);

        foreach ($services as $_service) {
            try {
                $id = $this->_containerGet($_service, 'id');

                $this->_wpDeletePost($id);
            } catch (NotFoundExceptionInterface $exception) {
                continue;
            }
        }

        return $this->_countIterable($services);
    }

    /**
     * Deletes a WordPress post.
     *
     * @since [*next-version*]
     *
     * @param int|string|StringableInterface $id The ID of the post to delete.
     */
    protected function _wpDeletePost($id)
    {
        \wp_delete_post($this->_normalizeInt($id), true);
    }
}
