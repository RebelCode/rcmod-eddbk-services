<?php

namespace RebelCode\EddBookings\Services\FuncTest;

use RebelCode\EddBookings\Services\ServicesInsertResourceModel as TestSubject;
use WP_Mock;
use Xpmock\TestCase;

/**
 * Tests the {@see ServicesInsertResourceModel} class.
 *
 * @since [*next-version*]
 */
class ServicesInsertResourceModelTest extends TestCase
{
    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function setUp()
    {
        WP_Mock::setUp();
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function tearDown()
    {
        WP_Mock::tearDown();
    }

    /**
     * Tests whether a valid instance of the test subject can be created.
     *
     * @since [*next-version*]
     */
    public function testCanBeCreated()
    {
        $subject = new TestSubject('', '');

        $this->assertInstanceOf(
            'RebelCode\EddBookings\Services\ServicesInsertResourceModel',
            $subject,
            'Created instance of the test subject is invalid.'
        );

        $this->assertInstanceOf(
            'Dhii\Storage\Resource\InsertCapableInterface',
            $subject,
            'Test subject does not implement expected interface.'
        );
    }

    /**
     * Tests the insertion functionality to assert whether the WordPress `wp_insert_post()` function is called with
     * the right arguments.
     *
     * @since [*next-version*]
     */
    public function testInsert()
    {
        $postType   = 'download';
        $metaPrefix = 'eddbk_';

        $subject = new TestSubject($postType, $metaPrefix);

        $name  = uniqid('name-');
        $desc  = uniqid('description-');
        $meta1 = uniqid('meta-');
        $meta2 = uniqid('meta-');

        $services = [
            $service = [
                'name'             => $name,
                'description'      => $desc,
                'bookings_enabled' => $meta1,
                'timezone'         => $meta2,
            ],
        ];

        $expectedId = rand(1, 100);

        WP_Mock::wpFunction('wp_insert_post', [
            'times'  => 1,
            'return' => $expectedId,
            'args'   => [
                function ($arg) use ($metaPrefix, $postType, $name, $desc, $meta1, $meta2) {
                    $post = array_merge([
                        'post_title'   => null,
                        'post_excerpt' => null,
                        'post_type'    => null,
                        'meta_input'   => [],
                    ], $arg);

                    return $post['post_title'] === $name &&
                           $post['post_excerpt'] === $desc &&
                           $post['meta_input'][$metaPrefix . 'bookings_enabled'] === $meta1 &&
                           $post['meta_input'][$metaPrefix . 'timezone'] === $meta2 &&
                           $post['post_type'] === $postType;
                },
            ],
        ]);

        $returnedIds = $subject->insert($services);

        $this->assertEquals([$expectedId], $returnedIds);
    }

    /**
     * Tests the insertion functionality to assert whether the WordPress `wp_insert_post()` function is called with
     * the right arguments when the insertion method is given multiple services.
     *
     * @since [*next-version*]
     */
    public function testInsertMultiple()
    {
        $postType   = 'test';
        $metaPrefix = 'test_';
        $subject    = new TestSubject($postType, $metaPrefix);

        $titles = [uniqid('title-'), uniqid('title-'), uniqid('title-')];
        $metas  = [uniqid('meta-'), uniqid('meta-'), uniqid('meta-')];
        $ids    = [rand(1, 100), rand(1, 100), rand(1, 100)];

        $services = [
            $service1 = [
                'name'     => $titles[0],
                'some_key' => $metas[0],
            ],
            $service2 = [
                'name'     => $titles[1],
                'some_key' => $metas[1],
            ],
            $service3 = [
                'name'     => $titles[2],
                'some_key' => $metas[2],
            ],
        ];

        for ($i = 0; $i < 3; ++$i) {
            $id    = $ids[$i];
            $title = $titles[$i];
            $meta  = $metas[$i];
            WP_Mock::wpFunction('wp_insert_post', [
                'times'  => 1,
                'return' => $id,
                'args'   => [
                    function ($arg) use ($postType, $metaPrefix, $title, $meta) {
                        $post = array_merge([
                            'post_title' => null,
                            'post_type'  => null,
                            'meta_input' => [],
                        ], $arg);

                        return $post['post_title'] === $title &&
                               $post['meta_input'][$metaPrefix . 'some_key'] === $meta &&
                               $post['post_type'] === $postType;
                    },
                ],
            ]);
        }

        $returnedIds = $subject->insert($services);

        $this->assertEquals($ids, $returnedIds);
    }

    /**
     * Tests the insertion functionality to assert whether the WordPress `set_post_thumbnail()` function is called
     * and with the right arguments when the resource model is inserting a service with an image ID.
     *
     * @since [*next-version*]
     */
    public function testInsertWithImageId()
    {
        $postType   = 'download';
        $metaPrefix = 'eddbk_';

        $subject = new TestSubject($postType, $metaPrefix);

        $name    = uniqid('name-');
        $desc    = uniqid('description-');
        $meta    = uniqid('meta-');
        $imageId = rand(1, 100);

        $services = [
            $service = [
                'name'             => $name,
                'description'      => $desc,
                'bookings_enabled' => $meta,
                'image_id'         => $imageId,
            ],
        ];

        $expectedId = rand(1, 100);

        WP_Mock::wpFunction('wp_insert_post', [
            'times'  => 1,
            'return' => $expectedId,
            'args'   => [
                function ($arg) use ($metaPrefix, $postType, $name, $desc, $meta) {
                    $post = array_merge([
                        'post_title'   => null,
                        'post_excerpt' => null,
                        'post_type'    => null,
                        'meta_input'   => [],
                    ], $arg);

                    return $post['post_title'] === $name &&
                           $post['post_excerpt'] === $desc &&
                           $post['meta_input'][$metaPrefix . 'bookings_enabled'] === $meta &&
                           $post['post_type'] === $postType;
                },
            ],
        ]);

        WP_Mock::wpFunction('set_post_thumbnail', [
            'times'  => 1,
            'return' => rand(1, 100),
            'args'   => [$expectedId, $imageId],
        ]);

        $returnedIds = $subject->insert($services);

        $this->assertEquals([$expectedId], $returnedIds);
    }
}
