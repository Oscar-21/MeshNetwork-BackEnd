<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
 */

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\User::class, function (Faker\Generator $faker) {

    /**
     * get sensible signUp dates
     */
    // instantiate new DateTime instance
    $now = new DateTime();
    // range of days relative to current date
    $rangeOfDays = rand(-730, 730);
    // number of days we will add relative to $now
    $addDays = DateInterval::createFromDateString($rangeOfDays.' days');
    // add days and get unix timestamp
    $unixTimeStamp = '@'.$now->add($addDays)->getTimeStamp();
    // add days and get unix timestamp
    $signUpDate = new DateTime($unixTimeStamp);
    // get timestamp
    $timeStamp = $signUpDate->getTimeStamp();
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => 'password' ,
        'spaceID' => 1,
        'roleID' => 1,
        'title' => $faker->title,
        'company' => $faker->company,
        'website' => $faker->domainName,
        'phoneNumber' => 1234567,
        'bio' => $faker->sentence($nbWords = 20, $variableNbWords = true),
        'avatar' => $faker->imageUrl($width = 640, $height = 480),
        'subscriber' => true,
        'score' => rand(100,5000),
        'created_at' => $timeStamp,
    ];

});

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Event::class, function (Faker\Generator $faker) {
    
    // constants
    $USERS = 750;
    $SPACES = 5;
    $EVENTS = 200;
    $OPTS = ($EVENTS * $SPACES);   
    $APPEARANCES = ($USERS * 10);
    /**
     * get sensible start and end dates
     */

    // instantiate new DateTime instance
    $now = new DateTime();
    // range of days relative to current date
    $rangeOfDays = rand(-730, 730);
    // number of days we will add relative to $now
    $addDays = DateInterval::createFromDateString($rangeOfDays.' days');
    // add days and get unix timestamp
    $unixTimeStamp = '@'.$now->add($addDays)->getTimeStamp();
    // add days and get unix timestamp
    $prestartDate = new DateTime($unixTimeStamp);
    // hours to append to start time
    $addStart = rand(-12, 12);
    $startDate = date_modify($prestartDate, '+'.$addStart.' hours');
    // hours to append to end time
    $addEnd = rand(1, 6);
    // end date time
    $endDate = date_modify($now, '+'.$addEnd.' hours');

    return [
        'spaceID' => 1,
        'userID' => rand(1,750),
        'multiday' => rand(0, 1),
        'title' => $faker->word(),
        'description' => $faker->sentence($nbWords = 20, $variableNbWords = true),
        'challenge' => rand(0,1),
    ];

    //'tags' => implode($faker->randomElements($array = array('html','css,linux', 'taxes,finance', 'python,marketing', 'writing,fitness', 'education,budgeting', 'health,engineering', 'robotics,cloud'))), // 'b'
});


// opts
$factory->define(App\Opt::class, function (Faker\Generator $faker) {

    // constants
    $USERS = 750;
    $SPACES = 5;
    $EVENTS = 200;
    $OPTS = ($EVENTS * $SPACES);   
    $APPEARANCES = ($USERS * 10);

    for ($spaceID = 1; $spaceID <= $SPACES; $spaceID++) {
        if ( App\Opt::find($spaceID) == null ) {

            for ($eventID = 1; $eventID <= $EVENTS; $eventID++) {
                $check = App\Opt::where('eventID', $eventID)->first();

                if (empty($check)) {
                    $event = $eventID;
                    $space = $spaceID;
                    break;
                }
            }
        }
    }                
    return [
        'eventID' => $eventID,
        'spaceID' => $spaceID,

    ];
});


$factory->define(App\Appearance::class, function (Faker\Generator $faker) {
    // constants
    // instantiate new DateTime instance
    $now = new DateTime();
    // range of days relative to current date
    $rangeOfDays = rand(-730, 730);
    // number of days we will add relative to $now
    $addDays = DateInterval::createFromDateString($rangeOfDays.' days');
    // add days and get unix timestamp
    $unixTimeStamp = '@'.$now->add($addDays)->getTimeStamp();
    // add days and get unix timestamp
    $appearanceDate = new DateTime($unixTimeStamp);

   $checkEvent = (rand(1,100) < 75 ? null : rand(1,$EVENTS)); 
    return [
        'userID' => rand(1, $USERS),
        'spaceID' => rand(1,$SPACES),
        'eventID' => ($checkEvent == null ? null : $checkEvent), 
        'occasion' => ( $checkEvent == null ? $faker->randomElement($array = array ('work','booking', 'student', 'invite')) : 'event'  ),
        'created_at' => ($checkEvent != null ? App\Event::find($checkEvent)->start : $appearanceDate ),
    ];
});

$factory->define(App\Workspace::class, function (Faker\Generator $faker) { 
    // constants
    $USERS = 750;
    return [
        'userID' => rand(1, $USERS),
        'name' => $faker->company,
        'city' => $faker->city,
        'address' => $faker->address,
        'state' => $faker->state,
        'zipcode' => 30909,
        'email' => $faker->unique()->safeEmail,
        'website' => $faker->domainName,
        'phone_number' => 777444333,
        'description' => $faker->sentence($nbWords = 20, $variableNbWords = true),
        //TODO: should we insert a generic logo deafult
        // if they don't provide one for some reason?
        'logo' => (rand(1,100) > 75 ? $faker->imageUrl($width = 640, $height = 480) : null),
        'status' => ( rand(1,100) > 20 ? 'approved' : 'pending' ), 
        'organizers' => 'bob-dole,cindy-cole,joe-doe'
    ];
});

$factory->define(App\Calendar::class, function (Faker\Generator $faker) { 
        // constants
        $USERS = 750;
        $SPACES = 5;
        $EVENTS = 200;
        $OPTS = ($EVENTS * 5);   
        $APPEARANCES = ($USERS * 10);

        $table->integer('userID');
        $table->integer('eventID');
        $table->timestamps();

});