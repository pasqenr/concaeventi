<?php

$I = new AcceptanceTester($scenario);

$I->resetCookie('ConcaEventi');
$I->amOnPage('/login/');
$I->submitForm('form', array(
    'email' => 'mail@mail.com',
    'password' => 'qwerty'
));

/* =================================
 *              CREATE
 * =================================
 */

/* Create sponsor: correct */

$I->amOnPage('/sponsors/create/');
$I->fillField('nome', 'Sponsor Name');
$I->click('Crea', 'form');

$I->seeInCurrentUrl('/sponsors/');
$I->seeInSource('Sponsor Name');

/* Create sponsor: wrong empty name */

$I->amOnPage('/sponsors/create/');
$I->fillField('nome', '');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* =================================
 *              EDIT
 * =================================
 */

/* Edit sponsor: correct */

$I->amOnPage('/sponsors/');
$I->seeInSource('Sponsor');

$I->amOnPage('/sponsors/edit/2');
$I->fillField('//input[@name="nome"]', 'Sponsor 1');
$I->click('Modifica', 'form');

$I->seeInCurrentUrl('/sponsors/');

/* Edit sponsor: wrong empty name */

$I->amOnPage('/sponsors/edit/2');
$I->fillField('//input[@name="nome"]', '');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* =================================
 *              DELETE
 * =================================
 */

$I->amOnPage('/sponsors/delete/2');
$I->seeInSource('Sponsor 1');
$I->click('Elimina');

$I->seeInCurrentUrl('/sponsors/');
$I->dontSeeInSource('Sponsor 1');