<?php

$I = new AcceptanceTester($scenario);
$I->resetCookie('ConcaEventi');

$I->amOnPage('/events/');
$I->seeInCurrentUrl('auth-error');

$I->amOnPage('/login/');
$I->submitForm('form', array(
    'email' => 'mail@mail.com',
    'password' => 'qwerty'
));

/* =================================
 *              CREATE
 * =================================
 */

/* Create page: correct */

$I->amOnPage('/associations/');
$I->seeInSource('Comune');

$I->amOnPage('/associations/create/');
$I->fillField('nomeAssociazione', 'Z Associazione');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', '1234567890');
//$I->fillField('stile', '#ff0000');
$I->click('Crea', 'form');

$I->seeInCurrentUrl('/associations/');
$I->seeInSource('Z Associazione');

/* Create page: wrong empty name */

$I->amOnPage('/associations/create/');
$I->fillField('nomeAssociazione', '');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', '1234567890');
$I->fillField('stile', '#ff0000');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Create page: wrong empty membri */

$I->amOnPage('/associations/create/');
$I->fillField('nomeAssociazione', 'Z Associazione 1');
$I->fillField('telefono', '1234567890');
$I->fillField('stile', '#ff0000');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Create page: wrong tel number */

$I->amOnPage('/associations/create/');
$I->fillField('nomeAssociazione', 'Z Associazione 1');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', 'abc13s$');
$I->fillField('stile', '#ff0000');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Create page: wrong tel number - less numbers */

$I->amOnPage('/associations/create/');
$I->fillField('nomeAssociazione', 'Z Associazione 1');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', '123');
$I->fillField('stile', '#ff0000');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Create page: wrong tel number - more numbers */

$I->amOnPage('/associations/create/');
$I->fillField('nomeAssociazione', 'Z Associazione 1');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', '1234567890123');
$I->fillField('stile', '#ff0000');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Create page: wrong hex */

$I->amOnPage('/associations/create/');
$I->fillField('nomeAssociazione', 'Z Associazione 1');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', '1234567890');
$I->fillField('stile', '#gg99zz');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* =================================
 *              EDIT
 * =================================
 */

/* Edit page: correct */

$I->amOnPage('/associations/');
$I->seeInSource('Z Associazione');

$I->amOnPage('/associations/edit/4');
$I->fillField('nomeAssociazione', 'Z Associazione 1');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', '0123456789');
$I->fillField('stile', '#00ff00');
$I->click('Modifica', 'form');

$I->seeInCurrentUrl('/associations/');

/* Edit page: wrong empty name */

$I->amOnPage('/associations/edit/4');
$I->fillField('nomeAssociazione', '');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', '0123456789');
$I->fillField('stile', '#00ff00');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Create page: wrong tel number */

$I->amOnPage('/associations/edit/4');
$I->fillField('nomeAssociazione', 'Z Associazione 2');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', 'abc13s$');
$I->fillField('stile', '#00ff00');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Create page: wrong tel number - less numbers */

$I->amOnPage('/associations/edit/4');
$I->fillField('nomeAssociazione', 'Z Associazione 2');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', '123');
$I->fillField('stile', '#00ff00');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Create page: wrong tel number - more numbers */

$I->amOnPage('/associations/edit/4');
$I->fillField('nomeAssociazione', 'Z Associazione 2');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', '1234567890123');
$I->fillField('stile', '#00ff00');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Create page: wrong hex */

$I->amOnPage('/associations/edit/4');
$I->fillField('nomeAssociazione', 'Z Associazione 2');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', '0123456789');
$I->fillField('stile', '#gg99zz');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* =================================
 *              DELETE
 * =================================
 */

$I->amOnPage('/associations/delete/4');
$I->seeInSource('Z Associazione 1');
$I->click('Elimina');

$I->seeInCurrentUrl('/associations/');
$I->dontSeeInSource('Z Associazione 1');