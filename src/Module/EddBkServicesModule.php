<?php

namespace RebelCode\EddBookings\Services\Module;

use ArrayIterator;
use Dhii\Config\ConfigFactoryInterface;
use Dhii\Data\Container\ContainerFactoryInterface;
use Dhii\Event\EventFactoryInterface;
use Dhii\Exception\InternalException;
use Dhii\Iterator\NormalizeIteratorCapableTrait;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use IteratorIterator;
use Psr\Container\ContainerInterface;
use Psr\EventManager\EventManagerInterface;
use RebelCode\Modular\Module\AbstractBaseModule;
use RebelCode\Transformers\CallbackTransformer;
use RebelCode\Transformers\MapTransformer;
use RebelCode\Transformers\TransformerIterator;
use Traversable;

/**
 * Module class.
 *
 * @since [*next-version*]
 */
class EddBkServicesModule extends AbstractBaseModule
{
    /* @since [*next-version*] */
    use NormalizeArrayCapableTrait;

    /* @since [*next-version*] */
    use NormalizeIteratorCapableTrait;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable         $key                  The module key.
     * @param string[]|Stringable[]     $dependencies         The module dependencies.
     * @param ConfigFactoryInterface    $configFactory        The config factory.
     * @param ContainerFactoryInterface $containerFactory     The container factory.
     * @param ContainerFactoryInterface $compContainerFactory The composite container factory.
     * @param EventManagerInterface     $eventManager         The event manager.
     * @param EventFactoryInterface     $eventFactory         The event factory.
     */
    public function __construct(
        $key,
        $dependencies,
        $configFactory,
        $containerFactory,
        $compContainerFactory,
        $eventManager,
        $eventFactory
    ) {
        $this->_initModule($key, $dependencies, $configFactory, $containerFactory, $compContainerFactory);
        $this->_initModuleEvents($eventManager, $eventFactory);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     *
     * @throws InternalException If an error occurred while reading the config file.
     */
    public function setup()
    {
        return $this->_setupContainer(
            $this->_loadPhpConfigFile(RCMOD_EDDBK_SERVICES_CONFIG_FILE),
            [
                /*
                 * PSR-7 server request instance.
                 *
                 * @since [*next-version*]
                 */
                'server_request'                                   => function (ContainerInterface $c) {
                    return ServerRequest::fromGlobals();
                },

                /*
                 * PSR-7 server response instance.
                 *
                 * @since [*next-version*]
                 */
                'server_response'                                  => function (ContainerInterface $c) {
                    return new Response();
                },

                /*
                 * The SELECT RM for services.
                 */
                'eddbk_services_select_rm'                         => function (ContainerInterface $c) {
                    return new ServicesSelectResourceModel();
                },

                /*
                 * The UPDATE RM for services.
                 */
                'eddbk_services_update_rm'                         => function (ContainerInterface $c) {
                    return new ServicesUpdateResourceModel();
                },

                /*
                 * The handler for saving downloads.
                 *
                 * @since [*next-version*]
                 */
                'eddbk_admin_edit_services_ui_update_handler'      => function (ContainerInterface $c) {
                    return new AdminEditServiceUiUpdateHandler(
                        $c->get('server_request'),
                        $c->get('server_response'),
                        $c->get('eddbk_services_update_rm'),
                        $c->get('session_rules_insert_rm'),
                        $c->get('session_rules_update_rm'),
                        $c->get('session_rules_delete_rm'),
                        $c->get('sql_expression_builder'),
                        $c->get('event_manager'),
                        $c->get('event_factory')
                    );
                },

                /*
                 * The handler for providing the service data as state to the admin bookings UI.
                 *
                 * @since [*next-version*]
                 */
                'eddbk_admin_edit_services_ui_state_handler'       => function (ContainerInterface $c) {
                    return new AdminEditServiceUiStateHandler(
                        $c->get('eddbk_services_select_rm'),
                        $c->get('session_rules_select_rm'),
                        $c->get('eddbk_admin_edit_services_ui_state_transformer'),
                        $c->get('sql_expression_builder')
                    );
                },

                /*
                 * The handler for providing services to the admin bookings UI.
                 *
                 * @since [*next-version*]
                 */
                'eddbk_admin_bookings_ui_services_handler'         => function (ContainerInterface $c) {
                    return new AdminBookingsUiServicesHandler(
                        $c->get('eddbk_services_select_rm'),
                        $c->get('eddbk_service_list_transformer')
                    );
                },

                /*
                 * The transformer for transforming lists of services.
                 *
                 * @since [*next-version*]
                 */
                'eddbk_service_list_transformer'                   => function (ContainerInterface $c) {
                    return new CallbackTransformer(function ($list) use ($c) {
                        $iterator    = $this->_normalizeIterator($list);
                        $transformed = new TransformerIterator(
                            $iterator,
                            $c->get('eddbk_admin_edit_services_ui_state_transformer')
                        );
                        $array       = $this->_normalizeArray($transformed);

                        return $array;
                    });
                },

                /*
                 * The transformer for transforming services.
                 *
                 * @since [*next-version*]
                 */
                'eddbk_admin_edit_services_ui_state_transformer'   => function (ContainerInterface $c) {
                    return new MapTransformer([
                        [
                            MapTransformer::K_SOURCE => 'id',
                        ],
                        [
                            MapTransformer::K_SOURCE => 'session_lengths',
                            MapTransformer::K_TARGET => 'sessions',
                        ],
                        [
                            MapTransformer::K_SOURCE => 'display_options',
                            MapTransformer::K_TARGET => 'displayOptions',
                        ],
                        [
                            MapTransformer::K_SOURCE      => 'session_rules',
                            MapTransformer::K_TARGET      => 'availabilities',
                            MapTransformer::K_TRANSFORMER => $c->get('eddbk_session_rule_list_transformer'),
                        ],
                    ]);
                },

                /*
                 * The transformer for transforming lists of session rules.
                 *
                 * @since [*next-version*]
                 */
                'eddbk_session_rule_list_transformer'              => function (ContainerInterface $c) {
                    return new CallbackTransformer(function ($list) use ($c) {
                        if ($list === null) {
                            return [];
                        }

                        $iterator    = $this->_normalizeIterator($list);
                        $transformed = new TransformerIterator($iterator, $c->get('eddbk_session_rule_transformer'));
                        $array       = $this->_normalizeArray($transformed);

                        return $array;
                    });
                },

                /*
                 * The transformer for transforming session rules.
                 *
                 * @since [*next-version*]
                 */
                'eddbk_session_rule_transformer'                   => function (ContainerInterface $c) {
                    return new MapTransformer([
                        [
                            MapTransformer::K_SOURCE => 'id',
                        ],
                        [
                            MapTransformer::K_SOURCE      => 'start',
                            MapTransformer::K_TRANSFORMER => $c->get('eddbk_services_ui_timestamp_datetime_transformer'),
                        ],
                        [
                            MapTransformer::K_SOURCE      => 'end',
                            MapTransformer::K_TRANSFORMER => $c->get('eddbk_services_ui_timestamp_datetime_transformer'),
                        ],
                        [
                            MapTransformer::K_SOURCE      => 'all_day',
                            MapTransformer::K_TARGET      => 'isAllDay',
                            MapTransformer::K_TRANSFORMER => $c->get('eddbk_boolean_transformer'),
                        ],
                        [
                            MapTransformer::K_SOURCE      => 'repeat',
                            MapTransformer::K_TRANSFORMER => $c->get('eddbk_boolean_transformer'),
                        ],
                        [
                            MapTransformer::K_SOURCE => 'repeat_period',
                            MapTransformer::K_TARGET => 'repeatPeriod',
                        ],
                        [
                            MapTransformer::K_SOURCE => 'repeat_unit',
                            MapTransformer::K_TARGET => 'repeatUnit',
                        ],
                        [
                            MapTransformer::K_SOURCE => 'repeat_until',
                            MapTransformer::K_TARGET => 'repeatUntil',
                        ],
                        [
                            MapTransformer::K_SOURCE => 'repeat_until_period',
                            MapTransformer::K_TARGET => 'repeatUntilPeriod',
                        ],
                        [
                            MapTransformer::K_SOURCE      => 'repeat_until_date',
                            MapTransformer::K_TARGET      => 'repeatUntilDate',
                            MapTransformer::K_TRANSFORMER => $c->get('eddbk_services_ui_timestamp_date_transformer'),
                        ],
                        [
                            MapTransformer::K_SOURCE      => 'repeat_weekly_on',
                            MapTransformer::K_TARGET      => 'repeatWeeklyOn',
                            MapTransformer::K_TRANSFORMER => $c->get('eddbk_comma_list_array_transformer'),
                        ],
                        [
                            MapTransformer::K_SOURCE      => 'repeat_monthly_on',
                            MapTransformer::K_TARGET      => 'repeatMonthlyOn',
                            MapTransformer::K_TRANSFORMER => $c->get('eddbk_comma_list_array_transformer'),
                        ],
                        [
                            MapTransformer::K_SOURCE      => 'exclude_dates',
                            MapTransformer::K_TARGET      => 'excludeDates',
                            MapTransformer::K_TRANSFORMER => $c->get('eddbk_comma_list_array_transformer'),
                        ],
                    ]);
                },

                /*
                 * The transformer for transforming comma separating strings into arrays.
                 *
                 * @since [*next-version*]
                 */
                'eddbk_comma_list_array_transformer'               => function (ContainerInterface $c) {
                    return new CallbackTransformer(function ($commaList) {
                        return (strlen($commaList) > 0)
                            ? explode(',', $commaList)
                            : [];
                    });
                },

                /*
                 * The transformer for transforming values into booleans.
                 *
                 * @since [*next-version*]
                 */
                'eddbk_boolean_transformer'                        => function (ContainerInterface $c) {
                    return new CallbackTransformer(function ($value) {
                        return (bool) $value;
                    });
                },

                /*
                 * The transformer for transforming timestamps into date strings for the services UI.
                 *
                 * @since [*next-version*]
                 */
                'eddbk_services_ui_timestamp_date_transformer'     => function (ContainerInterface $c) {
                    return new CallbackTransformer(function ($value) {
                        return date('Y-m-d', $value);
                    });
                },

                /*
                 * The transformer for transforming timestamps into datetime strings for the services UI.
                 *
                 * @since [*next-version*]
                 */
                'eddbk_services_ui_timestamp_datetime_transformer' => function (ContainerInterface $c) {
                    return new CallbackTransformer(function ($value) {
                        return date('Y-m-d H:i:s', $value);
                    });
                },
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function run(ContainerInterface $c = null)
    {
        if ($c === null) {
            return;
        }

        // Event on EDD Download post save
        $this->_attach('save_post_download', $c->get('eddbk_admin_edit_services_ui_update_handler'));

        // Event to load EDD Download data for the New/Edit UI
        $this->_attach('eddbk_services_nedit_ui_state', $c->get('eddbk_admin_edit_services_ui_state_handler'));

        // Event for providing the booking services for the admin bookings UI
        $this->_attach('eddbk_admin_bookings_ui_services', $c->get('eddbk_admin_bookings_ui_services_handler'));
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _createArrayIterator(array $array)
    {
        return new ArrayIterator($array);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _createTraversableIterator(Traversable $traversable)
    {
        return new IteratorIterator($traversable);
    }
}
