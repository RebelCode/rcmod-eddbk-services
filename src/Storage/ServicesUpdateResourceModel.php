<?php

namespace RebelCode\EddBookings\Services\Storage;

use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\Expression\LiteralTermInterface;
use Dhii\Expression\LogicalExpressionInterface;
use Dhii\Expression\Type\RelationalTypeInterface;
use Dhii\Expression\VariableTermInterface;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Storage\Resource\Sql\EntityFieldInterface;
use Dhii\Storage\Resource\UpdateCapableInterface;
use OutOfRangeException;

/**
 * A resource model implementation for updating services as `download` posts from EDD.
 *
 * @since [*next-version*]
 */
class ServicesUpdateResourceModel implements UpdateCapableInterface
{
    /* @since [*next-version*] */
    use CreateOutOfRangeExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function update($changeSet, LogicalExpressionInterface $condition = null, $ordering = null, $limit = null)
    {
        if ($condition === null) {
            throw $this->_createOutOfRangeException(
                $this->__('Null conditions are not supported'), null, null, $condition
            );
        }

        $id = $this->_getPostIdFromCondition($condition);

        foreach ($changeSet as $_key => $_value) {
            $this->_updatePostMeta($id, sprintf('eddbk_%s', $_key), $_value);
        }
    }

    /**
     * Retrieves the post ID from the condition.
     *
     * @since [*next-version*]
     *
     * @param LogicalExpressionInterface $condition The condition to search.
     *
     * @throws OutOfRangeException If the condition is not a valid post ID relational expression.
     *
     * @return int|string The post ID.
     */
    protected function _getPostIdFromCondition(LogicalExpressionInterface $condition)
    {
        $terms = $condition->getTerms();

        if ($condition->getType() !== RelationalTypeInterface::T_EQUAL_TO || count($terms) !== 2) {
            throw $this->_createOutOfRangeException(
                $this->__('Condition must be an equals expression'), null, null, $condition
            );
        }

        $value = null;
        $isId  = false;
        foreach ($terms as $_term) {
            if ($_term instanceof VariableTermInterface) {
                $isId = strtolower($_term->getKey()) === 'id';
                continue;
            }

            if ($_term instanceof EntityFieldInterface) {
                $isId = strtolower($_term->getField()) === 'id';
                continue;
            }

            if ($_term instanceof LiteralTermInterface) {
                $value = $_term->getValue();
                continue;
            }
        }

        if (!$isId) {
            throw $this->_createOutOfRangeException(
                $this->__('Condition does not contain an ID field'), null, null, $condition
            );
        }

        return $value;
    }

    /**
     * Updates the meta data for a WordPress post.
     *
     * @since [*next-version*]
     *
     * @param int|string $id        The ID of the service.
     * @param string     $metaKey   The meta key.
     * @param mixed      $metaValue The meta value.
     */
    protected function _updatePostMeta($id, $metaKey, $metaValue)
    {
        update_post_meta($id, $metaKey, $metaValue);
    }
}
