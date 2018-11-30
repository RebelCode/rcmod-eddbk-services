<?php

namespace RebelCode\EddBookings\Services\Module;

use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Invocation\InvocableInterface;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use Psr\EventManager\EventInterface;

/**
 * The handler that changes the host custom post type query to exclude services.
 *
 * @since [*next-version*]
 */
class HideServicesFromDownloadsHandler implements InvocableInterface
{
    /* @since [*next-version*] */
    use NormalizeStringCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The slug of the services post type.
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
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $postType   The slug of the services post type.
     * @param string|Stringable $metaPrefix The prefix for post meta keys.
     */
    public function __construct($postType, $metaPrefix)
    {
        $this->postType   = $this->_normalizeString($postType);
        $this->metaPrefix = $this->_normalizeString($metaPrefix);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function __invoke()
    {
        $event = func_get_arg(0);

        if (!($event instanceof EventInterface)) {
            throw $this->_createInvalidArgumentException(
                $this->__('Argument is not an event instance'), null, null, $event
            );
        }

        if (!is_admin() || !function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();
        $query  = $event->getParam(0);

        if ($screen->post_type !== $this->postType || $screen->id !== 'edit-download' || $query === null) {
            return;
        }

        $query->query_vars['meta_key']     = $this->metaPrefix . 'bookings_enabled';
        $query->query_vars['meta_value']   = '1';
        $query->query_vars['meta_compare'] = '!=';
    }
}
