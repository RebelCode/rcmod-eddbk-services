<?php

namespace RebelCode\EddBookings\Services\Module;

use ArrayAccess;
use Dhii\Data\Container\ContainerGetCapableTrait;
use Dhii\Data\Container\ContainerHasCapableTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\CreateNotFoundExceptionCapableTrait;
use Dhii\Data\Container\NormalizeKeyCapableTrait;
use Dhii\Event\EventFactoryInterface;
use Dhii\Exception\CreateInternalExceptionCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\Exception\CreateRuntimeExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Invocation\InvocableInterface;
use Dhii\Iterator\CountIterableCapableTrait;
use Dhii\Iterator\ResolveIteratorCapableTrait;
use Dhii\Storage\Resource\DeleteCapableInterface;
use Dhii\Storage\Resource\InsertCapableInterface;
use Dhii\Storage\Resource\UpdateCapableInterface;
use Dhii\Util\Normalization\NormalizeIntCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use Psr\Container\ContainerInterface;
use Psr\EventManager\EventInterface;
use Psr\EventManager\EventManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RebelCode\Modular\Events\EventsFunctionalityTrait;
use stdClass;
use Traversable;

/**
 * Handler that processes the update service request from the admin edit service page UI.
 *
 * @since [*next-version*]
 */
class AdminEditServiceUiUpdateHandler implements InvocableInterface
{
    /* @since [*next-version*] */
    use EventsFunctionalityTrait;

    /* @since [*next-version*] */
    use ContainerGetCapableTrait;

    /* @since [*next-version*] */
    use ContainerHasCapableTrait;

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
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateOutOfRangeExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateContainerExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateNotFoundExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateInternalExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateRuntimeExceptionCapableTrait;

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
     * The services UPDATE RM.
     *
     * @since [*next-version*]
     *
     * @var UpdateCapableInterface
     */
    protected $servicesUpdateRm;

    /**
     * The INSERT resource model for session rules.
     *
     * @since [*next-version*]
     *
     * @var InsertCapableInterface
     */
    protected $sessionRulesInsertRm;

    /**
     * The UPDATE resource model for session rules.
     *
     * @since [*next-version*]
     *
     * @var UpdateCapableInterface
     */
    protected $sessionRulesUpdateRm;

    /**
     * The DELETE resource model for session rules.
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
     * @param ServerRequestInterface $request              The request.
     * @param ResponseInterface      $response             The response.
     * @param UpdateCapableInterface $servicesUpdateRm     The UPDATE resource model for services.
     * @param InsertCapableInterface $sessionRulesInsertRm The INSERT resource model for session rules.
     * @param UpdateCapableInterface $sessionRulesUpdateRm The UPDATE resource model for session rules.
     * @param DeleteCapableInterface $sessionRulesDeleteRm The DELETE resource model for session rules.
     * @param object                 $exprBuilder          The expression builder.
     * @param EventManagerInterface  $eventManager         The event manager.
     * @param EventFactoryInterface  $eventFactory         The event factory.
     */
    public function __construct(
        ServerRequestInterface $request,
        ResponseInterface $response,
        UpdateCapableInterface $servicesUpdateRm,
        InsertCapableInterface $sessionRulesInsertRm,
        UpdateCapableInterface $sessionRulesUpdateRm,
        DeleteCapableInterface $sessionRulesDeleteRm,
        $exprBuilder,
        EventManagerInterface $eventManager,
        EventFactoryInterface $eventFactory
    ) {
        $this->_setEventManager($eventManager);
        $this->_setEventFactory($eventFactory);

        $this->request              = $request;
        $this->response             = $response;
        $this->servicesUpdateRm     = $servicesUpdateRm;
        $this->exprBuilder          = $exprBuilder;
        $this->sessionRulesInsertRm = $sessionRulesInsertRm;
        $this->sessionRulesUpdateRm = $sessionRulesUpdateRm;
        $this->sessionRulesDeleteRm = $sessionRulesDeleteRm;
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

        $eddbkData       = $this->_containerGet($body, 'eddbk');
        $serviceDataJson = $this->_containerGet($eddbkData, 'service_options');
        $serviceData     = json_decode($serviceDataJson);

        // Build change set
        $changeSet = [];
        if ($this->_containerHas($serviceData, 'sessionLengths')) {
            $changeSet['session_lengths'] = $this->_containerGet($serviceData, 'sessionLengths');
        }
        if ($this->_containerHas($serviceData, 'displayOptions')) {
            $changeSet['display_options'] = $this->_containerGet($serviceData, 'displayOptions');
        }

        // Build condition
        $b    = $this->exprBuilder;
        $expr = $b->eq($b->var('id'), $b->lit($postId));

        // Update service
        $this->servicesUpdateRm->update($changeSet, $expr);

        // Get the availability, if any
        $availability = $this->_containerHas($serviceData, 'availability')
            ? $this->_containerGet($serviceData, 'availability')
            : [];
        // If session rules were given in the availability, update them
        if ($this->_containerHas($availability, 'rules')) {
            $this->_updateSessionRules($postId, $this->_containerGet($availability, 'rules'));
        }
    }

    /**
     * Updates the session rules for a service.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable      $serviceId The ID of the service.
     * @param array|stdClass|Traversable $newRules  The new session rules.
     */
    protected function _updateSessionRules($serviceId, $newRules)
    {
        $b = $this->exprBuilder;

        $ruleIds = [];

        foreach ($newRules as $_ruleData) {
            $_rule   = $this->_processSessionRuleData($serviceId, $_ruleData);
            $_ruleId = $this->_containerHas($_rule, 'id')
                ? $this->_containerGet($_rule, 'id')
                : null;

            if ($_ruleId === null) {
                // If rule has no ID, insert as a new rule
                $_newRuleId = $this->sessionRulesInsertRm->insert([$_rule]);
                $_ruleId    = $_newRuleId[0];
            } else {
                // If rule has an ID, update the existing rule
                $this->sessionRulesUpdateRm->update($_rule, $b->eq(
                    $b->var('id'),
                    $b->lit($_ruleId)
                ));
            }

            $ruleIds[] = $_ruleId;
        }

        // Condition to remove the rules for this service
        $expr = $b->eq($b->var('service_id'), $b->lit($serviceId));
        // If rules were added/updated, ignore them in the condition
        if (count($ruleIds) > 0) {
            $expr = $b->and($expr, $b->not(
                $b->in(
                    $b->var('id'),
                    $b->set($ruleIds)
                )
            ));
        }
        // Delete the sessions according to the above condition
        $this->sessionRulesDeleteRm->delete($expr);

        // Trigger session generation
        $this->_trigger('eddbk_generate_sessions', [
            'service_id' => $serviceId,
        ]);
    }

    /**
     * Processes the session rule data that was received in the request.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable      $serviceId The ID of the service.
     * @param array|stdClass|Traversable $ruleData  The session rule data that was received.
     *
     * @return array|stdClass|ArrayAccess|ContainerInterface The processed session rule data.
     */
    protected function _processSessionRuleData($serviceId, $ruleData)
    {
        return [
            'id'                  => $this->_containerHas($ruleData, 'id')
                ? $this->_containerGet($ruleData, 'id')
                : null,
            'service_id'          => $serviceId,
            'start'               => strtotime($this->_containerGet($ruleData, 'start')),
            'end'                 => strtotime($this->_containerGet($ruleData, 'end')),
            'all_day'             => $this->_containerGet($ruleData, 'isAllDay'),
            'repeat'              => $this->_containerGet($ruleData, 'repeat'),
            'repeat_period'       => $this->_containerGet($ruleData, 'repeatPeriod'),
            'repeat_unit'         => $this->_containerGet($ruleData, 'repeatUnit'),
            'repeat_until'        => $this->_containerGet($ruleData, 'repeatUntil'),
            'repeat_until_period' => $this->_containerGet($ruleData, 'repeatUntilPeriod'),
            'repeat_until_date'   => strtotime($this->_containerGet($ruleData, 'repeatUntilDate')),
            'repeat_weekly_on'    => implode(',', $this->_containerGet($ruleData, 'repeatWeeklyOn')),
            'repeat_monthly_on'   => implode(',', $this->_containerGet($ruleData, 'repeatMonthlyOn')),
            'exclude_dates'       => implode(',', $this->_containerGet($ruleData, 'excludeDates')),
        ];
    }
}
