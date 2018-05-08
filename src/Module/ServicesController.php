<?php

namespace RebelCode\EddBookings\Services\Module;

use ArrayAccess;
use Dhii\Cache\SimpleCacheInterface;
use Dhii\Data\Container\ContainerGetCapableTrait;
use Dhii\Data\Container\ContainerHasCapableTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\CreateNotFoundExceptionCapableTrait;
use Dhii\Data\Container\NormalizeKeyCapableTrait;
use Dhii\Exception\CreateInternalExceptionCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\Exception\CreateRuntimeExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Iterator\CountIterableCapableTrait;
use Dhii\Iterator\ResolveIteratorCapableTrait;
use Dhii\Storage\Resource\DeleteCapableInterface;
use Dhii\Storage\Resource\InsertCapableInterface;
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Storage\Resource\UpdateCapableInterface;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use Dhii\Util\Normalization\NormalizeIntCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use Psr\Container\ContainerInterface;
use stdClass;
use Traversable;

/**
 * The services controller class.
 *
 * @since [*next-version*]
 */
class ServicesController implements ServicesControllerInterface
{
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
    use NormalizeArrayCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateOutOfRangeExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateContainerExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateNotFoundExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateRuntimeExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateInternalExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The session rules SELECT RM.
     *
     * @since [*next-version*]
     *
     * @var SelectCapableInterface
     */
    protected $rulesSelectRm;

    /**
     * The session rules INSERT RM.
     *
     * @since [*next-version*]
     *
     * @var InsertCapableInterface
     */
    protected $rulesInsertRm;

    /**
     * The session rules UPDATE RM.
     *
     * @since [*next-version*]
     *
     * @var InsertCapableInterface
     */
    protected $rulesUpdateRm;

    /**
     * The session rules DELETE RM.
     *
     * @since [*next-version*]
     *
     * @var DeleteCapableInterface
     */
    protected $rulesDeleteRm;

    /**
     * The expression builder.
     *
     * @since [*next-version*]
     *
     * @var object
     */
    protected $exprBuilder;

    /**
     * The cache.
     *
     * @since [*next-version*]
     *
     * @var SimpleCacheInterface
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param SelectCapableInterface $rulesSelectRm The rules SELECT RM.
     * @param InsertCapableInterface $rulesInsertRm The rules INSERT RM.
     * @param UpdateCapableInterface $rulesUpdateRm The rules UPDATE RM.
     * @param DeleteCapableInterface $rulesDeleteRm The rules DELETE RM.
     * @param SimpleCacheInterface   $cache         The cache to use for caching retrieved services.
     * @param object                 $exprBuilder   The expression builder.
     */
    public function __construct(
        SelectCapableInterface $rulesSelectRm,
        InsertCapableInterface $rulesInsertRm,
        UpdateCapableInterface $rulesUpdateRm,
        DeleteCapableInterface $rulesDeleteRm,
        SimpleCacheInterface $cache,
        $exprBuilder
    ) {
        $this->rulesSelectRm = $rulesSelectRm;
        $this->rulesInsertRm = $rulesInsertRm;
        $this->rulesUpdateRm = $rulesUpdateRm;
        $this->rulesDeleteRm = $rulesDeleteRm;
        $this->cache         = $cache;
        $this->exprBuilder   = $exprBuilder;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function getService($id)
    {
        $id = $this->_normalizeString($id);

        return $this->cache->get($id, function ($id) {
            return $this->_getServiceFromStorage($id);
        });
    }

    /**
     * Retrieves the service from storage.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable $id The service ID.
     *
     * @return array|stdClass|ArrayAccess|ContainerInterface
     */
    protected function _getServiceFromStorage($id)
    {
        $sessionLengths = $this->_getServiceMeta($id, 'eddbk_session_lengths', true);
        $displayOptions = $this->_getServiceMeta($id, 'eddbk_display_options', true);

        $b = $this->exprBuilder;
        // Fetch session rules from DB
        $sessionRules = $this->rulesSelectRm->select(
            $b->eq(
                $b->ef('session_rule', 'service_id'),
                $b->lit($id)
            )
        );
        $sessionRules = $this->_normalizeArray($sessionRules);

        return [
            'id'              => $id,
            'session_lengths' => $sessionLengths,
            'display_options' => $displayOptions,
            'session_rules'   => $sessionRules,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function updateService($serviceId, $params)
    {
        $sessionLengths = $this->_containerGet($params, 'sessionLengths');
        $displayOptions = $this->_containerGet($params, 'displayOptions');
        $availability   = $this->_containerGet($params, 'availability');

        // Get and process the rules
        $rulesData = $this->_containerGet($availability, 'rules');
        $rules     = $this->_processServiceRules($serviceId, $rulesData);

        // Expression builder
        $b = $this->exprBuilder;

        $rulesToInsert = [];
        foreach ($rules as $_rule) {
            $_id = $this->_containerGet($_rule, 'id');

            if ($_id !== null) {
                // Update the rule with this ID
                $this->rulesUpdateRm->update(
                    $_rule,
                    $b->eq($b->var('id'), $b->lit($_id))
                );

                continue;
            }

            $rulesToInsert[] = $_rule;
        }

        if (!empty($rulesToInsert)) {
            $this->rulesInsertRm->insert($rulesToInsert);
        }

        // Save the meta data for this service
        $this->_updateServiceMeta($serviceId, 'eddbk_session_lengths', $sessionLengths);
        $this->_updateServiceMeta($serviceId, 'eddbk_display_options', $displayOptions);
    }

    /**
     * Processes the service rule data in the request.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable      $serviceId The ID of the service.
     * @param array|stdClass|Traversable $rulesData The list of rule data containers.
     *
     * @return array|stdClass|Traversable The list of processed rules, each as data containers.
     */
    protected function _processServiceRules($serviceId, $rulesData)
    {
        $rules = [];

        foreach ($rulesData as $_ruleData) {
            $rules[] = [
                'id'                  => $this->_containerGet($_ruleData, 'id'),
                'service_id'          => $serviceId,
                'start'               => strtotime($this->_containerGet($_ruleData, 'start')),
                'end'                 => strtotime($this->_containerGet($_ruleData, 'end')),
                'all_day'             => $this->_containerGet($_ruleData, 'isAllDay'),
                'repeat'              => $this->_containerGet($_ruleData, 'repeat'),
                'repeat_period'       => $this->_containerGet($_ruleData, 'repeatPeriod'),
                'repeat_unit'         => $this->_containerGet($_ruleData, 'repeatUnit'),
                'repeat_until'        => $this->_containerGet($_ruleData, 'repeatUntil'),
                'repeat_until_period' => $this->_containerGet($_ruleData, 'repeatUntilPeriod'),
                'repeat_until_date'   => strtotime($this->_containerGet($_ruleData, 'repeatUntilDate')),
                'repeat_weekly_on'    => implode(',', $this->_containerGet($_ruleData, 'repeatWeeklyOn')),
                'repeat_monthly_on'   => implode(',', $this->_containerGet($_ruleData, 'repeatMonthlyOn')),
                'exclude_dates'       => implode(',', $this->_containerGet($_ruleData, 'excludesDates')),
            ];
        }

        return $rules;
    }

    /**
     * Retrieves meta data for a service.
     *
     * @since [*next-version*]
     *
     * @param int|string $id      The ID of the service.
     * @param string     $metaKey The meta key.
     * @param mixed      $default The default value to return.
     *
     * @return mixed The meta value.
     */
    protected function _getServiceMeta($id, $metaKey, $default = '')
    {
        $metaValue = get_post_meta($id, $metaKey, true);

        return ($metaValue === '')
            ? $default
            : $metaValue;
    }

    /**
     * Updates the meta data for a service.
     *
     * @since [*next-version*]
     *
     * @param int|string $id        The ID of the service.
     * @param string     $metaKey   The meta key.
     * @param mixed      $metaValue The meta value.
     */
    protected function _updateServiceMeta($id, $metaKey, $metaValue)
    {
        update_post_meta($id, $metaKey, $metaValue);
    }
}
