<?php

$I = new AcceptanceTester($scenario);

$I->resetCookie('ConcaEventi');
$I->amOnPage('/login/');
$I->submitForm('form', array(
    'email' => 'mail@mail.com',
    'password' => 'qwerty'
));

/* Create some events */

$initDateTime = new DateTime('now');
$initDateTime->add(new DateInterval('P1D'));
$finishDateTime = new DateTime('now');
$finishDateTime->add(new DateInterval('P2D'));

for ($i = 0; $i < 15; $i++) {
    $I->amOnPage('/events/create/');
    $I->submitForm('form', array(
        'titolo'         => 'Titolo',
        'descrizione'    => 'Descrizione.',
        'giornoInizio'   => $initDateTime->format('d'),
        'meseInizio'     => $initDateTime->format('m'),
        'annoInizio'     => $initDateTime->format('Y'),
        'oraInizio'      => $initDateTime->format('H'),
        'minutoInizio'   => $initDateTime->format('i'),
        'giornoFine'     => $finishDateTime->format('d'),
        'meseFine'       => $finishDateTime->format('m'),
        'annoFine'       => $finishDateTime->format('Y'),
        'oraFine'        => $finishDateTime->format('H'),
        'minutoFine'     => $finishDateTime->format('i'),
        'associazioni[]' => '1',
        'revisionato'    => 'on',
    ));
}

$I->amOnPage('/events/create/');
$I->submitForm('form', array(
    'titolo' => 'Titolo_history',
    'descrizione' => 'Descrizione_yrotsih.',
    'giornoInizio'   => $initDateTime->format('d'),
    'meseInizio'     => $initDateTime->format('m'),
    'annoInizio'     => $initDateTime->format('Y'),
    'oraInizio'      => $initDateTime->format('H'),
    'minutoInizio'   => $initDateTime->format('i'),
    'giornoFine'     => $finishDateTime->format('d'),
    'meseFine'       => $finishDateTime->format('m'),
    'annoFine'       => $finishDateTime->format('Y'),
    'oraFine'        => $finishDateTime->format('H'),
    'minutoFine'     => $finishDateTime->format('i'),
    'associazioni[]' => '1',
    'revisionato' => 'on',
));

/* Some events */

$I->amOnPage('/history/');
$I->dontSeeInSource('Non ci sono eventi.');
$I->seeInSource('pagination');
$I->seeInSource('&#x2F;history&#x2F;1');
$I->seeInSource('&#x2F;history&#x2F;2');

/* Basic search: title */

$I->amOnPage('/history/');
$I->fillField('search_query', 'history');
$I->click('Cerca');
$I->seeInCurrentUrl('/history/');
$I->seeInSource('Titolo_history');

/* Basic search: description */

$I->amOnPage('/history/');
$I->fillField('search_query', 'yrotsih');
$I->click('Cerca');
$I->seeInCurrentUrl('/history/');
$I->seeInSource('Titolo_history');

/* Basic search: wrong: already passed */

$I->amOnPage('/history/');
$I->fillField('search_query', 'history');
$I->selectOption("//select[@name='stato']", 'concluso');
$I->click('Cerca');
$I->seeInCurrentUrl('/history/');
$I->dontSeeInSource('Titolo_history');
$I->seeInSource('Non ci sono eventi.');