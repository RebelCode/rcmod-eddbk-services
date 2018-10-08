<?php

namespace RebelCode\EddBookings\Services\Module;

use ArrayAccess;
use Carbon\Carbon;
use DateTimeZone;
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
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Storage\Resource\UpdateCapableInterface;
use Dhii\Util\Normalization\NormalizeIntCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use Exception;
use InvalidArgumentException;
use OutOfRangeException;
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
     * The services SELECT RM.
     *
     * @since [*next-version*]
     *
     * @var SelectCapableInterface
     */
    protected $servicesSelectRm;

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
     * @param SelectCapableInterface $servicesSelectRm     The SELECT resource model for services.
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
        SelectCapableInterface $servicesSelectRm,
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
        $this->servicesSelectRm = $servicesSelectRm;
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
        if ($this->_containerHas($serviceData, 'bookingsEnabled')) {
            $changeSet['bookings_enabled'] = $this->_containerGet($serviceData, 'bookingsEnabled');
        }
        if ($this->_containerHas($serviceData, 'sessionLengths')) {
            $changeSet['session_lengths'] = $this->_containerGet($serviceData, 'sessionLengths');
        }
        if ($this->_containerHas($serviceData, 'displayOptions')) {
            $changeSet['display_options'] = $this->_containerGet($serviceData, 'displayOptions');
        }
        if ($this->_containerHas($serviceData, 'timezone')) {
            $changeSet['timezone'] = $this->_containerGet($serviceData, 'timezone');
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
        // Expression for matching the service by its ID
        $serviceIdExpr = $b->eq($b->var('service_id'), $b->lit($serviceId));

        // Get the service's timezone
        $services  = $this->servicesSelectRm->select($b->and(
            $serviceIdExpr,
            $b->eq($b->var('post_status'), $b->lit('any'))
        ));
        $service   = reset($services);
        $serviceTz = $this->_containerGet($service, 'timezone');

        $ruleIds = [];

        foreach ($newRules as $_ruleData) {
            $_rule   = $this->_processSessionRuleData($serviceId, $_ruleData, $serviceTz);
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

        // If rules were added/updated, ignore them in the condition
        if (count($ruleIds) > 0) {
            $serviceIdExpr = $b->and($serviceIdExpr, $b->not(
                $b->in(
                    $b->var('id'),
                    $b->set($ruleIds)
                )
            ));
        }
        // Delete the sessions according to the above condition
        $this->sessionRulesDeleteRm->delete($serviceIdExpr);

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
     * @param string|Stringable          $serviceTz The service timezone name.
     *
     * @return array|stdClass|ArrayAccess|ContainerInterface The processed session rule data.
     */
    protected function _processSessionRuleData($serviceId, $ruleData, $serviceTz)
    {
        $allDay   = $this->_containerGet($ruleData, 'isAllDay');

        // Parse the service timezone name into a timezone object
        $timezoneName = $this->_normalizeString($serviceTz);
        $timezone     = empty($timezoneName) ? null : $this->_createDateTimeZone($timezoneName);

        // Get the start ISO 8601 string, parse it and normalize it to the beginning of the day if required
        $startIso8601    = $this->_containerGet($ruleData, 'start');
        $startDatetime   = Carbon::parse($startIso8601, $timezone);

        // Get the end ISO 8601 string, parse it and normalize it to the end of the day if required
        $endIso8601    = $this->_containerGet($ruleData, 'end');
        $endDateTime   = Carbon::parse($endIso8601, $timezone);

        $data = [
            'id' => $this->_containerHas($ruleData, 'id')
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
     * @see DateTimeZone
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $tzName The name of the timezone.
     *
     * @return DateTimeZone The created {@link DateTimeZone} instance.
     *
     * @throws InvalidArgumentException If the timezone name is not a string or stringable object.
     * @throws OutOfRangeException If the timezone name is invalid and does not represent a valid timezone.
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
}
