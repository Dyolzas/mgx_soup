<?php

    include __dir__.'/../vendor/autoload.php';
    include __dir__.'/Soup.php';
    include __dir__.'/SoupGenerator.php';
    include __dir__.'/secrets.php'; //This is excluded on git. It has the secret keys of Yandex and Slack (TRANSLATION_KEY and SLACK_KEY)

    setlocale(LC_TIME, array('nl_NL.utf8','nl_NL@euro','nl_NL', 'nld_NLD', 'dutch'));
    date_default_timezone_set('UTC');

    header('Content-Type: application/json');
    $soup = SoupGenerator::getSoupOfTheDay();
    if(is_null($soup))
        echo '{"text": "Soup of the day: `Royco`"}';
    else
        echo '{"text": "Soup of the day: '. $soup->printSoup(false).'"}';