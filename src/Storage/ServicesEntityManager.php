<?php

namespace RebelCode\EddBookings\Services\Storage;

use ArrayAccess;
use Carbon\Carbon;
use DateTimeZone;
use Dhii\Data\Container\ContainerGetCapableTrait;
use Dhii\Data\Container\ContainerHasCapableTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\CreateNotFoundExceptionCapableTrait;
use Dhii\Data\Container\NormalizeKeyCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\Exception\CreateRuntimeExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Storage\Resource\DeleteCapableInterface;
use Dhii\Storage\Resource\InsertCapableInterface;
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Storage\Resource\UpdateCapableInterface;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use Dhii\Util\Normalization\NormalizeIntCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use Exception;
use InvalidArgumentException;
use OutOfRangeException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RebelCode\Entity\EntityManagerInterface;
use stdClass;
use Traversable;
use WP_Error;
use WP_Post;

/**
 * An entity manager implementation for services.
 *
 * @since [*next-version*]
 */
class ServicesEntityManager implements EntityManagerInterface
{
    /* @since [*next-version*] */
    use ServicesPostKeyMapAwareTrait;

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
     * The services post type.
     *
     * @since [*next-version*]
     *
     * @var string|Stringable
     */
    protected $postType;

    /**
     * The prefix for post meta keys.
     *
     * @since [*next-version*]
     *
     * @var string|Stringable
     */
    protected $metaPrefix;

    /**
     * The availability rules select resource model.
     *
     * @since [*next-version*]
     *
     * @var SelectCapableInterface
     */
    protected $rulesSelectRm;

    /**
     * The availability rules insert resource model.
     *
     * @since [*next-version*]
     *
     * @var InsertCapableInterface
     */
    protected $rulesInsertRm;

    /**
     * The availability rules update resource model.
     *
     * @since [*next-version*]
     *
     * @var UpdateCapableInterface
     */
    protected $rulesUpdateRm;

    /**
     * The availability rules delete resource model.
     *
     * @since [*next-version*]
     *
     * @var DeleteCapableInterface
     */
    protected $rulesDeleteRm;

    /**
     * The expression builder instance.
     *
     * @since [*next-version*]
     *
     * @var object
     */
    protected $exprBuilder;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable      $postType      The services post type.
     * @param string|Stringable      $metaPrefix    The prefix for post meta keys.
     * @param SelectCapableInterface $rulesSelectRm The SELECT resource model for availability rules.
     * @param InsertCapableInterface $rulesInsertRm The INSERT resource model for availability rules.
     * @param UpdateCapableInterface $rulesUpdateRm The UPDATE resource model for availability rules.
     * @param DeleteCapableInterface $rulesDeleteRm The DELETE resource model for availability rules.
     * @param object                 $exprBuilder   The expression builder instances for creating query conditions.
     */
    public function __construct(
        $postType,
        $metaPrefix,
        SelectCapableInterface $rulesSelectRm,
        InsertCapableInterface $rulesInsertRm,
        UpdateCapableInterface $rulesUpdateRm,
        DeleteCapableInterface $rulesDeleteRm,
        $exprBuilder
    ) {
        $this->postType      = $postType;
        $this->metaPrefix    = $metaPrefix;
        $this->rulesSelectRm = $rulesSelectRm;
        $this->rulesInsertRm = $rulesInsertRm;
        $this->rulesUpdateRm = $rulesUpdateRm;
        $this->rulesDeleteRm = $rulesDeleteRm;
        $this->exprBuilder   = $exprBuilder;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function add($entity)
    {
        $ir   = $this->_entityToServiceIr($entity);
        $post = $this->_serviceIrToPost($ir);

        // Ensure the post type is correct
        $post['post_type'] = $this->postType;

        $id = $this->_wpInsertPost($post);
        $this->_updateServiceExternals($id, $ir);

        return $id;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function query($query = [], $limit = null, $offset = null, $orderBy = null, $desc = false)
    {
        $args     = $this->_buildWpQueryArgs($query, $limit, $offset, $orderBy, $desc);
        $posts    = $this->_queryPosts($args);
        $services = array_map([$this, '_postToService'], $posts);

        return $services;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function get($id)
    {
        $results = $this->query([
            'id' => $id,
        ], 1);

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
        $ir   = $this->_entityToServiceIr($data);
        $post = $this->_serviceIrToPost($ir);

        $this->_wpUpdatePost($id, $post);

        $this->_updateServiceExternals($id, $ir);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function delete($id)
    {
        $this->_wpDeletePost($id);
        $this->_updateServiceExternals($id, [
            'availability' => [],
        ]);
    }

    /**
     * Builds the WordPress query args for the given query filters.
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|Traversable $query   Optional map of query filters.
     * @param int|null                   $limit   Optional maximum number of entities to return.
     * @param int|null                   $offset  Optional number of entities to offset for the result.
     * @param string|Stringable          $orderBy Optional name of the entity property by which to sort.
     * @param bool                       $desc    Optional flag to sort in descending order.
     *
     * @return array The WordPress query args.
     */
    protected function _buildWpQueryArgs($query = [], $limit = null, $offset = null, $orderBy = null, $desc = false)
    {
        // Get post key field map
        $postKeyMap = $this->_getServicesPostKeyMap();

        // Convert query filter to IR for meta data processing
        $ir = $this->_entityToServiceIr($query);

        // Create post args, since it closely matches the WP Query format
        $args = $this->_serviceIrToPost($ir);

        // Remove the keys for insertion/updating from post args
        unset($args['meta_input']);
        unset($args['tax_input']);
        unset($args['tags_input']);

        // Add the meta query to post args, based on meta in the IR
        $args['meta_query'] = ['relation' => 'AND'];
        foreach ($ir['meta'] as $_key => $_value) {
            $args['meta_query'][$_key] = [
                'key'   => $this->metaPrefix . $_key,
                'value' => $_value,
            ];
        }

        // Set default post status
        if (!isset($args['post_status'])) {
            $args['post_status'] = ['publish', 'private', 'protected', 'draft', 'trash', 'pending', 'future'];
        }

        // Move id to `p` index
        if (isset($args['ID'])) {
            $args['p'] = $args['ID'];
            unset($args['ID']);
        }
        // Add limit if provided
        $args['posts_per_page'] = ($limit !== null)
            ? $this->_normalizeInt($limit)
            : -1;

        // Add offset if provided
        if ($offset !== null) {
            $args['offset'] = $this->_normalizeInt($offset);
        }
        // Add ordering field if provided, converting to a post field is necessary
        if ($orderBy !== null) {
            // Map field to order by to post field, if applicable
            $args['orderby'] = isset($postKeyMap[$orderBy])
                ? $postKeyMap[$orderBy]
                : $this->metaPrefix . $orderBy;

            // Add the order mode
            $args['order'] = ($desc) ? 'DESC' : 'ASC';
        }

        return $args;
    }

    /**
     * Converts a post to a service.
     *
     * @since [*next-version*]
     *
     * @param object|WP_Post $post The WordPress post.
     *
     * @return array The converted service data.
     */
    protected function _postToService($post)
    {
        $b = $this->exprBuilder;

        $sessionRules = $this->rulesSelectRm->select(
            $b->eq(
                $b->ef('session_rule', 'service_id'),
                $b->lit($post->ID)
            )
        );

        $service = [
            'id'               => $post->ID,
            'name'             => $post->post_title,
            'description'      => $post->post_excerpt,
            'status'           => $post->post_status,
            'image_url'        => $this->_getPostImageUrl($post->ID),
            'bookings_enabled' => $this->_getPostMeta($post->ID, $this->metaPrefix . 'bookings_enabled', false),
            'session_lengths'  => $this->_getPostMeta($post->ID, $this->metaPrefix . 'session_lengths', []),
            'display_options'  => $this->_getPostMeta($post->ID, $this->metaPrefix . 'display_options', []),
            'timezone'         => $this->_getPostMeta($post->ID, $this->metaPrefix . 'timezone', 'UTC'),
            'availability'     => $sessionRules,
        ];

        return $service;
    }

    /**
     * Converts an entity to an intermediate representation of a service.
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|Traversable $entity The entity data.
     *
     * @return array The intermediate representation.
     */
    protected function _entityToServiceIr($entity)
    {
        $eArray   = $this->_normalizeArray($entity);
        $postKeys = $this->_getServicesPostKeyMap();

        $ir = [
            'post'         => [
                'post_type' => $this->postType,
            ],
            'meta'         => [
                'bookings_enabled' => '1',
            ],
            'availability' => null,
            'image_id'     => null,
        ];

        foreach ($eArray as $_key => $_value) {
            // If entity key is in post keys map ...
            if (array_key_exists($_key, $postKeys)) {
                // Get mapped post key
                $_pKey = $postKeys[$_key];
                // Add to IR "post" level
                $ir['post'][$_pKey] = $eArray[$_key];

                continue;
            }

            // If the key is for availability data or image ID
            if ($_key === 'availability' || $_key === 'image_id') {
                // Add to IR top level
                $ir[$_key] = $eArray[$_key];

                continue;
            }

            // Otherwise, add to IR "meta" level
            $ir['meta'][$_key] = $eArray[$_key];
        }

        return $ir;
    }

    /**
     * Converts the service intermediate representation to WordPress post data usable for insertion and updating.
     *
     * @since [*next-version*]
     *
     * @param array $ir The intermediate representation.
     *
     * @return array The post data.
     */
    protected function _serviceIrToPost($ir)
    {
        $post = $ir['post'];

        $post['meta_input'] = [];
        foreach ($ir['meta'] as $_key => $_value) {
            $post['meta_input'][$this->metaPrefix . $_key] = $_value;
        }

        return $post;
    }

    /**
     * Updates the service's external data.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable $id The ID of the service.
     * @param array                 $ir The intermediate representation of the service.
     */
    protected function _updateServiceExternals($id, $ir)
    {
        if (isset($ir['image_id'])) {
            $this->_wpSetPostThumbnail($id, $ir['image_id']);
        }

        if (isset($ir['availability'])) {
            $this->_updateAvailability($id, $ir);
        }
    }

    /**
     * Updates the session rules for a service.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable $id The ID of the service.
     * @param array                 $ir The intermediate representation of the service.
     */
    protected function _updateAvailability($id, $ir)
    {
        $b = $this->exprBuilder;

        // Get the service's timezone and availability
        $availability = $ir['availability'];
        $timezone     = isset($ir['meta']['timezone'])
            ? $ir['meta']['timezone']
            : 'UTC';

        $ruleIds = [];

        foreach ($availability as $_ruleData) {
            $_rule = $this->_processSessionRuleData($id, $_ruleData, $timezone);

            // If rule has an ID, update the existing rule
            if ($this->_containerHas($_rule, 'id')) {
                $_ruleId  = $this->_containerGet($_rule, 'id');
                $_ruleExp = $b->eq(
                    $b->var('id'),
                    $b->lit($_ruleId)
                );

                $this->rulesUpdateRm->update($_rule, $_ruleExp);
            } else {
                // If rule has no ID, insert as a new rule
                $_newRuleIds = $this->rulesInsertRm->insert([$_rule]);
                $_ruleId     = $_newRuleIds[0];
            }

            $ruleIds[] = $_ruleId;
        }

        // Expression for matching the service by its ID
        $serviceIdExpr = $b->eq($b->var('service_id'), $b->lit($id));

        // If rules were added/updated, ignore them in the condition
        if (count($ruleIds) > 0) {
            $serviceIdExpr = $b->and(
                $serviceIdExpr,
                $b->not(
                    $b->in(
                        $b->var('id'),
                        $b->set($ruleIds)
                    )
                )
            );
        }

        // Delete the sessions rules according to the above condition
        $this->rulesDeleteRm->delete($serviceIdExpr);
    }

    /**
     * Processes the session rule data that was received in the request.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable      $serviceId The ID of the service.
     * @param array|stdClass|Traversable $ruleData  The session rule data that was received.
     * @param string|Stringable          $serviceTz The service timezone name.
     *
     * @return array|stdClass|ArrayAccess|ContainerInterface The processed session rule data.
     */
    protected function _processSessionRuleData($serviceId, $ruleData, $serviceTz)
    {
        $allDay = $this->_containerGet($ruleData, 'isAllDay');

        // Parse the service timezone name into a timezone object
        $timezoneName = $this->_normalizeString($serviceTz);
        $timezone     = empty($timezoneName) ? null : $this->_createDateTimeZone($timezoneName);

        // Get the start ISO 8601 string, parse it and normalize it to the beginning of the day if required
        $startIso8601  = $this->_containerGet($ruleData, 'start');
        $startDatetime = Carbon::parse($startIso8601, $timezone);

        // Get the end ISO 8601 string, parse it and normalize it to the end of the day if required
        $endIso8601  = $this->_containerGet($ruleData, 'end');
        $endDateTime = Carbon::parse($endIso8601, $timezone);

        $data = [
            'id'                  => $this->_containerHas($ruleData, 'id')
                ? $this->_containerGet($ruleData, 'id')
                : null,
            'service_id'          => $serviceId,
            'start'               => $startDatetime->getTimestamp(),
            'end'                 => $endDateTime->getTimestamp(),
            'all_day'             => $allDay,
            'repeat'              => $this->_containerGet($ruleData, 'repeat'),
            'repeat_period'       => $this->_containerGet($ruleData, 'repeatPeriod'),
            'repeat_unit'         => $this->_containerGet($ruleData, 'repeatUnit'),
            'repeat_until'        => $this->_containerGet($ruleData, 'repeatUntil'),
            'repeat_until_period' => $this->_containerGet($ruleData, 'repeatUntilPeriod'),
            'repeat_until_date'   => strtotime($this->_containerGet($ruleData, 'repeatUntilDate')),
            'repeat_weekly_on'    => implode(',', $this->_containerGet($ruleData, 'repeatWeeklyOn')),
            'repeat_monthly_on'   => implode(',', $this->_containerGet($ruleData, 'repeatMonthlyOn')),
        ];

        $excludeDates = [];
        foreach ($this->_containerGet($ruleData, 'excludeDates') as $_excludeDate) {
            $excludeDates[] = $this->_processExcludeDate($_excludeDate, $timezone);
        }

        $data['exclude_dates'] = implode(',', $excludeDates);

        return $data;
    }

    /**
     * Processes an excluded date to transform it into a timestamp.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $excludeDate The exclude date string, in ISO8601 format.
     * @param DateTimeZone      $timezone    The service timezone.
     *
     * @return int|false The timestamp.
     */
    protected function _processExcludeDate($excludeDate, $timezone)
    {
        $datetime  = Carbon::parse($this->_normalizeString($excludeDate), $timezone);
        $timestamp = $datetime->getTimestamp();

        return $timestamp;
    }

    /**
     * Creates a {@link DateTimeZone} object for a timezone, by name.
     *
     * @see   DateTimeZone
     * @since [*next-version*]
     *
     * @param string|Stringable $tzName The name of the timezone.
     *
     * @throws InvalidArgumentException If the timezone name is not a string or stringable object.
     * @throws OutOfRangeException      If the timezone name is invalid and does not represent a valid timezone.
     *
     * @return DateTimeZone The created {@link DateTimeZone} instance.
     */
    protected function _createDateTimeZone($tzName)
    {
        $argTz  = $tzName;
        $tzName = $this->_normalizeString($tzName);

        // If the timezone is a UTC offset timezone, transform into a valid DateTimeZone offset.
        // See http://php.net/manual/en/datetimezone.construct.php
        if (preg_match('/^UTC(\+|\-)(\d{1,2})(:?(\d{2}))?$/', $tzName, $matches) && count($matches) >= 2) {
            $sign    = $matches[1];
            $hours   = (int) $matches[2];
            $minutes = count($matches) >= 4 ? (int) $matches[4] : 0;
            $tzName  = sprintf('%s%02d%02d', $sign, $hours, $minutes);
        }

        try {
            return new DateTimeZone($tzName);
        } catch (Exception $exception) {
            throw $this->_createOutOfRangeException(
                $this->__('Invalid timezone name: "%1$s"', [$argTz]), null, $exception, $argTz
            );
        }
    }

    /**
     * Inserts the given post into the WordPress database.
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|Traversable $post The post to insert.
     *
     * @return int|WP_Error The inserted post ID on success, or a WP_Error instance on failure.
     */
    protected function _wpInsertPost($post)
    {
        $post = $this->_normalizeArray($post);

        return \wp_insert_post($post);
    }

    /**
     * Updates the given post into the WordPress database.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable      $id   The ID of the post to update.
     * @param array|stdClass|Traversable $data The post data to update the post with.
     *
     * @return int|WP_Error The inserted post ID on success, or a WP_Error instance on failure.
     */
    protected function _wpUpdatePost($id, $data)
    {
        $id   = $this->_normalizeInt($id);
        $data = $this->_normalizeArray($data);

        $data['ID'] = $id;

        return \wp_update_post($data);
    }

    /**
     * Deletes a WordPress post.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable $id The ID of the post to delete.
     */
    protected function _wpDeletePost($id)
    {
        \wp_delete_post($this->_normalizeInt($id), true);
    }

    /**
     * Queries WordPress posts.
     *
     * @since [*next-version*]
     *
     * @param array $args The query arguments.
     *
     * @return WP_Post[]|stdClass|Traversable
     */
    protected function _queryPosts($args)
    {
        return \get_posts($args);
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
        $metaValue = \get_post_meta($id, $metaKey, true);

        return ($metaValue === '')
            ? $default
            : $metaValue;
    }

    /**
     * Retrieves the featured image url for a WordPress post.
     *
     * @since [*next-version*]
     *
     * @param int|string $id The ID of the service.
     *
     * @return string The post image source url.
     */
    protected function _getPostImageUrl($id)
    {
        return \get_the_post_thumbnail_url($id);
    }

    /**
     * Sets a post thumbnail image.
     *
     * @since [*next-version*]
     *
     * @param int|string $postId  Post ID or object where thumbnail should be attached.
     * @param int|string $imageId Thumbnail to attach.
     *
     * @return int|false Post meta ID on success, false on failure.
     */
    protected function _wpSetPostThumbnail($postId, $imageId)
    {
        $postId  = $this->_normalizeInt($postId);
        $imageId = $this->_normalizeInt($imageId);

        return \set_post_thumbnail($postId, $imageId);
    }
}
