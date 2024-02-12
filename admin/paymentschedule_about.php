<?php
/* Copyright (C) 2019 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/about.php
 * 	\ingroup	paymentschedule
 * 	\brief		This file is an example about page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include '../../main.inc.php'; // From htdocs directory
if (! $res) {
    $res = @include '../../../main.inc.php'; // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once '../lib/paymentschedule.lib.php';

// Translations
$langs->load('paymentschedule@paymentschedule');

// Access control
if (! $user->admin) {
    accessforbidden();
}

/*
 * View
 */
$page_name = 'PaymentScheduleAbout';
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'tools');

// Configuration header
$head = paymentscheduleAdminPrepareHead();
print dol_get_fiche_head(
    $head,
    'about',
    $langs->trans('Module104077Name'),
    0,
    'paymentschedule@paymentschedule'
);

// About page goes here
print '<div style="float: left;"><img src="../img/Dolibarr_Preferred_Partner_logo.png" /></div>';
print '<div>'.$langs->trans('ATMAbout').'</div>';

print dol_get_fiche_end();

require_once __DIR__ . '/../class/techatm.class.php';
$techATM = new \paymentschedule\TechATM($db);

require_once __DIR__ . '/../core/modules/modPaymentSchedule.class.php';
$moduleDescriptor = new modPaymentSchedule($db);

print $techATM->getAboutPage($moduleDescriptor);


llxFooter();
$db->close();
