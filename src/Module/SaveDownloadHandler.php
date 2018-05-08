<?php

namespace RebelCode\EddBookings\Services\Module;

use Dhii\Data\Container\ContainerGetCapableTrait;
use Dhii\Data\Container\ContainerHasCapableTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\CreateNotFoundExceptionCapableTrait;
use Dhii\Data\Container\NormalizeKeyCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Invocation\InvocableInterface;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Psr\EventManager\EventInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SaveDownloadHandler implements InvocableInterface
{
    /* @since [*next-version*] */
    use ContainerGetCapableTrait;

    /* @since [*next-version*] */
    use ContainerHasCapableTrait;

    /* @since [*next-version*] */
    use NormalizeKeyCapableTrait;

    /* @since [*next-version*] */
    use NormalizeStringCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateOutOfRangeExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateContainerExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateNotFoundExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The key in the request body of the wp referer.
     *
     * @since [*next-version*]
     */
    const K_REFERER = '_wp_http_referer';

    /**
     * The request.
     *
     * @since [*next-version*]
     *
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * The response.
     *
     * @since [*next-version*]
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * The services controller.
     *
     * @since [*next-version*]
     *
     * @var ServicesControllerInterface
     */
    protected $servicesController;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param ServerRequestInterface      $request            The request.
     * @param ResponseInterface           $response           The response.
     * @param ServicesControllerInterface $servicesController The services controller.
     */
    public function __construct(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ServicesControllerInterface $servicesController
    ) {
        $this->request            = $request;
        $this->response           = $response;
        $this->servicesController = $servicesController;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function __invoke(EventInterface $event = null)
    {
        $postId = $event->getParam(0);
        $body   = $this->request->getParsedBody();

        if ($postId === null || empty($body) || !$this->_containerHas($body, static::K_REFERER)) {
            return;
        }

        $eddbkParams      = $this->_containerGet($body, 'eddbk');
        $serviceParamsRaw = $this->_containerGet($eddbkParams, 'service_options');
        $serviceParams    = json_decode($serviceParamsRaw);

        $this->servicesController->updateService($postId, $serviceParams);
    }
}
