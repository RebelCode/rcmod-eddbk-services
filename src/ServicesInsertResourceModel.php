<?php

namespace RebelCode\EddBookings\Services;

use Dhii\Data\Container\ContainerGetCapableTrait;
use Dhii\Data\Container\ContainerHasCapableTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\CreateNotFoundExceptionCapableTrait;
use Dhii\Data\Container\NormalizeKeyCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Storage\Resource\InsertCapableInterface;
use Dhii\Util\Normalization\NormalizeIntCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use RebelCode\Storage\Resource\WordPress\Posts\InsertCapableWpTrait;
use RebelCode\Storage\Resource\WordPress\Posts\NormalizeWpPostDataArrayCapableTrait;

/**
 * A resource model implementation for inserting services as EDD `download` posts.
 *
 * @since [*next-version*]
 */
class ServicesInsertResourceModel implements InsertCapableInterface
{
    /* @since [*next-version*] */
    use InsertCapableWpTrait;

    /* @since [*next-version*] */
    use NormalizeWpPostDataArrayCapableTrait;

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
     * The key for the image ID.
     *
     * @since [*next-version*]
     */
    const K_IMAGE_ID = 'image_id';

    /**
     * The post type for services.
     *
     * @since [*next-version*]
     *
     * @var string|Stringable
     */
    protected $postType;

    /**
     * The prefix to apply to post meta keys.
     *
     * @since [*next-version*]
     *
     * @var string|Stringable
     */
    protected $metaPrefix;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $postType   The post type.
     * @param string|Stringable $metaPrefix The prefix to apply to post meta keys.
     */
    public function __construct($postType, $metaPrefix = '')
    {
        $this->postType   = $postType;
        $this->metaPrefix = $metaPrefix;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function insert($services)
    {
        $ids = [];

        foreach ($services as $_service) {
            // Normalize post data, separating meta data into a `meta_input` key
            $postData = $this->_normalizeWpPostDataArray($_service);

            // Get the image ID, if any, and remove from the post data
            if (array_key_exists(static::K_IMAGE_ID, $postData)) {
                $imageId = $postData[static::K_IMAGE_ID];
                unset($postData[static::K_IMAGE_ID]);
            } else {
                $imageId = null;
            }

            // Insert the service post and attach an image to it, if any
            $id = $this->_wpInsertPost($this->_prefixPostMeta($postData));
            if ($imageId !== null) {
                $this->_wpSetPostThumbnail($id, $imageId);
            }

            // Add new service ID to the list
            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * Prefixes the meta data in a given post data array.
     *
     * @since [*next-version*]
     *
     * @param array $data The post data array.
     *
     * @return array The new post data array.
     */
    protected function _prefixPostMeta($data)
    {
        $prefix  = $this->metaPrefix;
        $metaKey = $this->_getPostMetaFieldKey();

        // Get meta keys and prefix them
        $keys = array_keys($data[$metaKey]);
        $keys = array_map(function ($key) use ($prefix) {
            return $prefix . $key;
        }, $keys);

        // Re-construct meta_input array
        $data[$metaKey] = array_combine($keys, $data[$metaKey]);

        return $data;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _wpInsertPost(array $post)
    {
        $post['post_type'] = $this->postType;

        return \wp_insert_post($post);
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

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getPostFieldKeyMap()
    {
        return [
            // Core service fields
            'id'               => 'ID',
            'name'             => 'post_title',
            'description'      => 'post_excerpt',
            'status'           => 'post_status',
            static::K_IMAGE_ID => static::K_IMAGE_ID,
            // Other post fields
            'guid'             => 'guid',
            'post_author'      => 'post_author',
            'type'             => 'post_type',
            'date'             => 'post_date',
            'date_gmt'         => 'post_date_gmt',
            'content'          => 'post_content',
            'content_filtered' => 'post_content_filtered',
            'category'         => 'post_category',
            'password'         => 'post_password',
            'parent'           => 'post_parent',
            'modified'         => 'post_modified',
            'modified_gmt'     => 'post_modified_gmt',
            'mime_type'        => 'post_mime_type',
            'tags_input'       => 'tags_input',
            'tax_input'        => 'tax_input',
            'meta_input'       => 'meta_input',
            'pinged'           => 'pinged',
            'ping_status'      => 'ping_status',
            'comment_count'    => 'comment_count',
            'comment_status'   => 'comment_status',
            'menu_order'       => 'menu_order',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function _getPostMetaFieldKey()
    {
        return 'meta_input';
    }
}
