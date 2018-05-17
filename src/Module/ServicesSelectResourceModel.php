<?php

namespace RebelCode\EddBookings\Services\Module;

use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\Expression\LiteralTermInterface;
use Dhii\Expression\LogicalExpressionInterface;
use Dhii\Expression\VariableTermInterface;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Storage\Resource\Sql\EntityFieldInterface;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use Dhii\Util\Normalization\NormalizeIntCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use RebelCode\Storage\Resource\WordPress\Posts\ExtractPostIdsFromExpressionCapableTrait;
use RebelCode\WordPress\Query\Builder\BuildWpQueryArgsCapableTrait;
use RebelCode\WordPress\Query\Builder\BuildWpQueryCompareCapableTrait;
use RebelCode\WordPress\Query\Builder\BuildWpQueryMetaCompareCapableTrait;
use RebelCode\WordPress\Query\Builder\BuildWpQueryRelationCapableTrait;
use RebelCode\WordPress\Query\Builder\BuildWpQueryRelationTermCapableTrait;
use RebelCode\WordPress\Query\Builder\BuildWpQueryTaxCompareCapableTrait;
use RebelCode\WordPress\Query\Builder\GetWpQueryMetaCompareOperatorCapableTrait;
use RebelCode\WordPress\Query\Builder\GetWpQueryMetaCompareTypeCapableTrait;
use RebelCode\WordPress\Query\Builder\GetWpQueryRelationOperatorCapableTrait;
use RebelCode\WordPress\Query\Builder\GetWpQueryTaxCompareOperatorCapableTrait;
use stdClass;
use Traversable;

/**
 * A resource model for querying services as `download` posts from EDD.
 *
 * @since [*next-version*]
 */
class ServicesSelectResourceModel implements SelectCapableInterface
{
    /* @since [*next-version*] */
    use ExtractPostIdsFromExpressionCapableTrait;

    /* @since [*next-version*] */
    use BuildWpQueryArgsCapableTrait {
        BuildWpQueryArgsCapableTrait::_buildWpQueryCompare as _traitBuildWpQueryCompare;
    }

    /* @since [*next-version*] */
    use BuildWpQueryCompareCapableTrait {
        BuildWpQueryCompareCapableTrait::_buildWpQueryCompare as _traitBuildWpQueryCompare;
    }

    /* @since [*next-version*] */
    use BuildWpQueryRelationCapableTrait;

    /* @since [*next-version*] */
    use BuildWpQueryRelationTermCapableTrait;

    /* @since [*next-version*] */
    use BuildWpQueryMetaCompareCapableTrait;

    /* @since [*next-version*] */
    use BuildWpQueryTaxCompareCapableTrait;

    /* @since [*next-version*] */
    use GetWpQueryMetaCompareOperatorCapableTrait;

    /* @since [*next-version*] */
    use GetWpQueryMetaCompareTypeCapableTrait;

    /* @since [*next-version*] */
    use GetWpQueryTaxCompareOperatorCapableTrait;

    /* @since [*next-version*] */
    use GetWpQueryRelationOperatorCapableTrait;

    /* @since [*next-version*] */
    use NormalizeIntCapableTrait;

    /* @since [*next-version*] */
    use NormalizeStringCapableTrait;

    /* @since [*next-version*] */
    use NormalizeArrayCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateOutOfRangeExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The slug of the services post type.
     *
     * @since [*next-version*]
     *
     * @var string
     */
    protected $postType;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $postType The slug of the services post type.
     */
    public function __construct($postType)
    {
        $this->postType = $this->_normalizeString($postType);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function select(
        LogicalExpressionInterface $condition = null,
        $ordering = null,
        $limit = null,
        $offset = null
    ) {
        $queryArgs = ($condition !== null)
            ? $this->_buildWpQueryArgs($condition)
            : [];

        $fullArgs = array_merge($this->_getDefaultWpQueryArgs(), $queryArgs);
        $posts    = $this->_queryPosts($fullArgs);
        $services = [];

        foreach ($posts as $_post) {
            $_id = $_post->ID;

            $services[] = [
                'id'               => $_id,
                'name'             => html_entity_decode($this->_getPostTitle($_id)),
                'bookings_enabled' => $this->_getPostMeta($_id, 'eddbk_bookings_enabled', false),
                'session_lengths'  => $this->_getPostMeta($_id, 'eddbk_session_lengths', []),
                'display_options'  => $this->_getPostMeta($_id, 'eddbk_display_options', []),
                'timezone'         => $this->_getPostMeta($_id, 'eddbk_timezone', 'UTC'),
            ];
        }

        return $services;
    }

    /**
     * Retrieves the default WP_Query args.
     *
     * @since [*next-version*]
     *
     * @return array
     */
    protected function _getDefaultWpQueryArgs()
    {
        return [
            'post_type'   => $this->postType,
            'post_status' => ['publish', 'draft', 'private', 'future'],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _buildWpQueryCompare(LogicalExpressionInterface $expression)
    {
        $postIds = $this->_extractPostIdsFromExpression($expression);

        if (count($postIds) > 0) {
            return ['post__in' => $postIds];
        }

        return $this->_traitBuildWpQueryCompare($expression);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _buildWpQueryMetaRelation(LogicalExpressionInterface $expression)
    {
        return $this->_buildWpQueryRelation($expression, 'meta');
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _buildWpQueryTaxRelation(LogicalExpressionInterface $expression)
    {
        return $this->_buildWpQueryRelation($expression, 'tax');
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getWpQueryCompareKey(LogicalExpressionInterface $expression)
    {
        foreach ($expression->getTerms() as $_term) {
            if ($_term instanceof VariableTermInterface) {
                return $_term->getKey();
            }
            if ($_term instanceof EntityFieldInterface) {
                return $_term->getEntity();
            }
        }

        return;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getWpQueryCompareValue(LogicalExpressionInterface $expression)
    {
        foreach ($expression->getTerms() as $_term) {
            if ($_term instanceof LiteralTermInterface) {
                return $_term->getValue();
            }
        }

        return;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getWpQueryMetaCompareKey(LogicalExpressionInterface $expression)
    {
        return $this->_getWpQueryCompareKey($expression);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getWpQueryMetaCompareValue(LogicalExpressionInterface $expression)
    {
        return $this->_getWpQueryCompareValue($expression);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getWpQueryTaxCompareTaxonomy(LogicalExpressionInterface $expression)
    {
        foreach ($expression->getTerms() as $_term) {
            if ($_term instanceof EntityFieldInterface) {
                return $_term->getEntity();
            }
        }

        return;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getWpQueryTaxCompareField(LogicalExpressionInterface $expression)
    {
        foreach ($expression->getTerms() as $_term) {
            if ($_term instanceof EntityFieldInterface) {
                return $_term->getField();
            }
        }

        return;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getWpQueryTaxCompareTerms(LogicalExpressionInterface $expression)
    {
        foreach ($expression->getTerms() as $_term) {
            if ($_term instanceof LiteralTermInterface) {
                return $_term->getValue();
            }
        }

        return;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getPostEntityName()
    {
        return 'service';
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getPostIdFieldName()
    {
        return 'id';
    }

    /**
     * Retrieves the title for a WordPress post.
     *
     * @since [*next-version*]
     *
     * @param int|string $id The ID of the service.
     *
     * @return string The post title.
     */
    protected function _getPostTitle($id)
    {
        return get_the_title($id);
    }

    /**
     * Retrieves meta data for a WordPress post.
     *
     * @since [*next-version*]
     *
     * @param int|string $id      The ID of the service.
     * @param string     $metaKey The meta key.
     * @param mixed      $default The default value to return.
     *
     * @return mixed The meta value.
     */
    protected function _getPostMeta($id, $metaKey, $default = '')
    {
        $metaValue = get_post_meta($id, $metaKey, true);

        return ($metaValue === '')
            ? $default
            : $metaValue;
    }

    /**
     * Queries WordPress posts.
     *
     * @since [*next-version*]
     *
     * @param array $args The arguments.
     *
     * @return WP_Post[]|stdClass|Traversable
     */
    protected function _queryPosts($args)
    {
        return get_posts($args);
    }
}
