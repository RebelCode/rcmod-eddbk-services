<?php

namespace RebelCode\EddBookings\Services\Module;

use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Invocation\InvocableInterface;
use Dhii\Storage\Resource\DeleteCapableInterface;
use Psr\EventManager\EventInterface;

/**
 * Handler for handling the deletion of services.
 *
 * @since [*next-version*]
 */
class AdminDeleteServiceHandler implements InvocableInterface
{
    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The sessions DELETE resource model.
     *
     * @since [*next-version*]
     *
     * @var DeleteCapableInterface
     */
    protected $sessionsDeleteRm;

    /**
     * The session rules DELETE resource model.
     *
     * @since [*next-version*]
     *
     * @var DeleteCapableInterface
     */
    protected $sessionRulesDeleteRm;

    /**
     * The expression builder.
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
     * @param DeleteCapableInterface $sessionsDeleteRm     The sessions DELETE resource model.
     * @param DeleteCapableInterface $sessionRulesDeleteRm The session rules DELETE resource model.
     * @param object                 $exprBuilder          The expression builder.
     */
    public function __construct(
        DeleteCapableInterface $sessionsDeleteRm,
        DeleteCapableInterface $sessionRulesDeleteRm,
        $exprBuilder
    ) {
        $this->sessionsDeleteRm     = $sessionsDeleteRm;
        $this->sessionRulesDeleteRm = $sessionRulesDeleteRm;
        $this->exprBuilder          = $exprBuilder;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function __invoke()
    {
        /* @var $event EventInterface */
        $event = func_get_arg(0);

        if (!($event instanceof EventInterface)) {
            throw $this->_createInvalidArgumentException(
                $this->__('Argument is not an event instance'), null, null, $event
            );
        }

        $postId   = $event->getParam(0);
        $postType = $this->_getPostType($postId);

        if ($postType === 'download') {
            $b = $this->exprBuilder;

            $this->sessionsDeleteRm->delete(
                $b->eq(
                    $b->var('service_id'),
                    $b->lit($postId)
                )
            );
            $this->sessionRulesDeleteRm->delete(
                $b->eq(
                    $b->var('service_id'),
                    $b->lit($postId)
                )
            );
        }
    }

    /**
     * Retrieves the post type for a WordPress post by ID.
     *
     * @since [*next-version*]
     *
     * @param int|string $postId The post ID.
     *
     * @return string The post type slug.
     */
    protected function _getPostType($postId)
    {
        return get_post_type($postId);
    }
}
