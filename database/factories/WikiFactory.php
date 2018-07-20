<?php

use Faker\Generator as Faker;

$factory->define(App\Wiki::class, function (Faker $faker) {

    $array = array(
        'title' => e($faker->sentence(3)),
        'subtitle' => e($faker->sentence()),
        'description' => e($faker->realText()),
        'fake_wiki' => true,
        'locale' => $faker->randomElement(['en', 'es', 'fr', 'jp', 'ru']),
        'permissions' => array(
            'guest' => array(
                'can_view' => $faker->boolean(),
                'create_page' => false,
                'edit_page' => false,
            ),
            'registered' => array(
                'can_view' => true,
                'create_page' => true,
                'edit_page' => true,
            ),
        ),
    );
    $json = json_encode($array);
    return [
        'subdomain' => $faker->word,
        'WikiJson' => $json
    ];
});
