<?php

use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Iterator\NormalizeIteratorCapableTrait;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use Psr\Container\ContainerInterface;
use RebelCode\EddBookings\Services\Module\AdminBookingsUiServicesHandler;
use RebelCode\EddBookings\Services\Module\AdminDeleteServiceHandler;
use RebelCode\EddBookings\Services\Module\GetServiceHasPriceOptionsHandler;
use RebelCode\EddBookings\Services\Module\GetServicePriceHandler;
use RebelCode\EddBookings\Services\Module\GetServicePriceOptionsHandler;
use RebelCode\EddBookings\Services\Module\HideServicesFromDownloadsHandler;
use RebelCode\EddBookings\Services\Storage\ServicesEntityManager;
use RebelCode\Transformers\CallbackTransformer;
use RebelCode\Transformers\MapTransformer;
use RebelCode\Transformers\TransformerIterator;

/**
 * The service list for the EDD Bookings Services module.
 *
 * @since [*next-version*]
 */
class EddBkServicesServiceList extends ArrayObject
{
    /* @since [*next-version*] */
    use NormalizeArrayCapableTrait;

    /* @since [*next-version*] */
    use NormalizeIteratorCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     */
    public function __construct()
    {
        parent::__construct($this->_getServices());
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

    /**
     * Retrieves the services.
     *
     * @since [*next-version*]
     *
     * @return array
     */
    protected function _getServices()
    {
        return [
            /**
             * The services manager.
             *
             * @since [*next-version*]
             */
            'eddbk_services_manager'                           => function (ContainerInterface $c) {
                return new ServicesEntityManager(
                    $c->get('eddbk_services/post_type'),
                    $c->get('eddbk_services/meta_prefix'),
                    $c->get('session_rules_select_rm'),
                    $c->get('session_rules_insert_rm'),
                    $c->get('session_rules_update_rm'),
                    $c->get('session_rules_delete_rm'),
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
                    $c->get('eddbk_services_manager'),
                    $c->get('eddbk_service_list_transformer')
                );
            },

            /*
             * The handler for handling service deletion.
             *
             * @since [*next-version*]
             */
            'eddbk_admin_delete_service_handler'               => function (ContainerInterface $c) {
                return new AdminDeleteServiceHandler(
                    $c->get('eddbk_services/post_type'),
                    $c->get('sessions_delete_rm'),
                    $c->get('session_rules_delete_rm'),
                    $c->get('sql_expression_builder')
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
                        MapTransformer::K_SOURCE => 'name',
                    ],
                    [
                        MapTransformer::K_SOURCE => 'description',
                    ],
                    [
                        MapTransformer::K_SOURCE => 'status',
                    ],
                    [
                        MapTransformer::K_SOURCE => 'image_id',
                        MapTransformer::K_TARGET => 'imageId',
                    ],
                    [
                        MapTransformer::K_SOURCE => 'image_url',
                        MapTransformer::K_TARGET => 'imageSrc',
                    ],
                    [
                        MapTransformer::K_SOURCE => 'bookings_enabled',
                        MapTransformer::K_TARGET => 'bookingsEnabled',
                    ],
                    [
                        MapTransformer::K_SOURCE      => 'session_lengths',
                        MapTransformer::K_TARGET      => 'sessionLengths',
                        MapTransformer::K_TRANSFORMER => $c->get('eddbk_session_length_list_transformer'),
                    ],
                    [
                        MapTransformer::K_SOURCE => 'display_options',
                        MapTransformer::K_TARGET => 'displayOptions',
                    ],
                    [
                        MapTransformer::K_SOURCE => 'timezone',
                    ],
                    [
                        MapTransformer::K_SOURCE      => 'availability',
                        MapTransformer::K_TARGET      => 'availability',
                        MapTransformer::K_TRANSFORMER => $c->get('eddbk_session_rule_list_transformer'),
                    ],
                ]);
            },

            /*
             * The transformer for transforming a list of session length configs.
             *
             * @since [*next-version*]
             */
            'eddbk_session_length_list_transformer'            => function (ContainerInterface $c) {
                return new CallbackTransformer(function ($sessionLengths) use ($c) {
                    $iterator    = $this->_normalizeIterator($sessionLengths);
                    $transformer = $c->get('eddbk_session_length_transformer');
                    $result      = new TransformerIterator($iterator, $transformer);

                    return iterator_to_array($result);
                });
            },

            /*
             * The transformer for transforming a session length config.
             *
             * @since [*next-version*]
             */
            'eddbk_session_length_transformer'                 => function (ContainerInterface $c) {
                return new MapTransformer([
                    [
                        MapTransformer::K_SOURCE => 'sessionLength',
                    ],
                    [
                        MapTransformer::K_SOURCE      => 'price',
                        MapTransformer::K_TRANSFORMER => $c->get('eddbk_session_length_price_transformer'),
                    ],
                ]);
            },

            /*
             * The transformer for transforming a session length's price.
             *
             * @since [*next-version*]
             */
            'eddbk_session_length_price_transformer'           => function (ContainerInterface $c) {
                return new CallbackTransformer(function ($price) use ($c) {
                    return [
                        'amount'    => $price,
                        'currency'  => edd_get_currency(),
                        'formatted' => $c->get('eddbk_price_transformer')->transform($price),
                    ];
                });
            },

            /*
             * The transformer for transforming a price amount into its formatted counterpart.
             *
             * @since [*next-version*]
             */
            'eddbk_price_transformer'                          => function (ContainerInterface $c) {
                return new CallbackTransformer(function ($price) use ($c) {
                    return html_entity_decode(edd_currency_filter(edd_format_amount($price)));
                });
            },

            /*
             * The transformer for transforming lists of session rules.
             *
             * @since [*next-version*]
             */
            'eddbk_session_rule_list_transformer'              => function (ContainerInterface $c) {
                return new CallbackTransformer(function ($list) use ($c) {
                    $rules = [];

                    if ($list !== null) {
                        $iterator    = $this->_normalizeIterator($list);
                        $transformed = new TransformerIterator($iterator, $c->get('eddbk_session_rule_transformer'));
                        $rules       = $this->_normalizeArray($transformed);
                    }

                    return [
                        'rules' => $rules,
                    ];
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
                        MapTransformer::K_TRANSFORMER => $c->get('eddbk_services_ui_exlude_dates_transformer'),
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
                return new CallbackTransformer(function ($value) use ($c) {
                    return date($c->get('eddbk_services/session_rules/datetime_format'), $value);
                });
            },

            /*
             * The transformer for transforming timestamps into datetime strings for the services UI.
             *
             * @since [*next-version*]
             */
            'eddbk_services_ui_timestamp_datetime_transformer' => function (ContainerInterface $c) {
                return new CallbackTransformer(function ($value) use ($c) {
                    return date($c->get('eddbk_services/session_rules/datetime_format'), $value);
                });
            },

            /*
             * the transformer for transforming the session rule excluded dates for the services UI.
             *
             * @since [*next-version*]
             */
            'eddbk_services_ui_exlude_dates_transformer'       => function (ContainerInterface $c) {
                $commaListTransformer = $c->get('eddbk_comma_list_array_transformer');
                $datetimeTransformer  = $c->get('eddbk_services_ui_timestamp_datetime_transformer');

                return new CallbackTransformer(function ($value) use ($commaListTransformer, $datetimeTransformer) {
                    // Transform comma list to an iterator
                    $array    = $commaListTransformer->transform($value);
                    $iterator = $this->_normalizeIterator($array);
                    // Create the transformer iterator, to transform each timestamp into a datetime string
                    $transformIterator = new TransformerIterator($iterator, $datetimeTransformer);

                    // Reduce to an array and return
                    return $this->_normalizeArray($transformIterator);
                });
            },

            /*
             * The handler that changes the Download price to the smallest session length's price.
             *
             * @since [*next-version*]
             */
            'eddbk_get_service_price_handler'                  => function (ContainerInterface $c) {
                return new GetServicePriceHandler(
                    $c->get('eddbk_services_manager')
                );
            },

            /*
             * The handler that changes the Download price options to the session length prices.
             *
             * @since [*next-version*]
             */
            'eddbk_get_service_price_options_handler'          => function (ContainerInterface $c) {
                return new GetServicePriceOptionsHandler(
                    $c->get('eddbk_services_manager')
                );
            },

            /*
             * The handler that filters the Download price options flag.
             *
             * @since [*next-version*]
             */
            'eddbk_get_service_has_price_options_handler'      => function (ContainerInterface $c) {
                return new GetServiceHasPriceOptionsHandler(
                    $c->get('eddbk_services_manager')
                );
            },

            /**
             * The handler that hides services from the downloads list.
             *
             * @since [*next-version*]
             */
            'eddbk_hide_services_from_downloads_list_handler' => function (ContainerInterface $c) {
                return new HideServicesFromDownloadsHandler(
                    $c->get('eddbk_services/post_type'),
                    $c->get('eddbk_services/meta_prefix')
                );
            }
        ];
    }
}

return new EddBkServicesServiceList();
