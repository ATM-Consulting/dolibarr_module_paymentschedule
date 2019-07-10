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
 *	\file		lib/timetablesepa.lib.php
 *	\ingroup	timetablesepa
 *	\brief		This file is an example module library
 *				Put some comments here
 */

/**
 * @return array
 */
function timetablesepaAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load('timetablesepa@timetablesepa');

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/timetablesepa/admin/timetablesepa_setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/timetablesepa/admin/timetablesepa_extrafields.php", 1);
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'extrafields';
    $h++;
    $head[$h][0] = dol_buildpath("/timetablesepa/admin/timetablesepa_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@timetablesepa:/timetablesepa/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@timetablesepa:/timetablesepa/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'TimetableSEPA');

    return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	TimetableSEPA $object Object company shown
 * @return 	array				Array of tabs
 */
function timetablesepa_prepare_head(TimetableSEPA $object)
{
    global $langs, $conf;
    $h = 0;
    $head = array();
    $head[$h][0] = dol_buildpath('/timetablesepa/card.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("timetableSEPACard");
    $head[$h][2] = 'card';
    $h++;
	
	// Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@timetablesepa:/timetablesepa/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@timetablesepa:/timetablesepa/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'TimetableSEPA');
	
	return $head;
}

/**
 * @param Form          $form   Form object
 * @param TimetableSEPA $object timetableSEPA object
 * @param Facture $facture Facture object
 * @param string        $action Triggered action
 * @return string
 */
function getFormConfirmtimetableSEPA($form, $object, $facture, $action)
{
    global $langs, $user;

    $formconfirm = '';

    if ($action === 'valid' && !empty($user->rights->timetablesepa->write))
    {
        $body = $langs->trans('ConfirmValidatetimetableSEPABody', $facture->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmValidatetimetableSEPATitle'), $body, 'confirm_validate', '', 0, 1);
    }
    elseif ($action === 'createtimetablesepa' && !empty($user->rights->timetablesepa->write))
    {
        // TODO load la valeur depuis le contrat
        $formquestion = array(
            array('type' => 'date', 'label' => $langs->trans('timetablesepa_dateStartEcheance'), 'name' => 'date_start', 'value' => '')
        );
        $body = $langs->trans('ConfirmCreatetimetableSEPABody', $facture->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $facture->id, $langs->trans('ConfirmCreatetimetableSEPATitle'), $body, 'confirm_createtimetablesepa', $formquestion, 0, 1);
    }
    elseif ($action === 'reset' && !empty($user->rights->timetablesepa->write))
    {
        $formquestion = array(
            array('type' => 'checkbox', 'label' => $langs->trans('timetablesepa_fullReset'), 'name' => 'full_reset', 'value' => '1', 'moreattr' => 'value="1"')
        );
        $body = $langs->trans('ConfirmResettimetableSEPABody', $facture->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmResettimetableSEPATitle'), $body, 'confirm_reset', $formquestion, 0, 1);
    }
    elseif ($action === 'delete' && !empty($user->rights->timetablesepa->write))
    {
        $body = $langs->trans('ConfirmDeletetimetableSEPABody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmDeletetimetableSEPATitle'), $body, 'confirm_delete', '', 0, 1);
    }

    return $formconfirm;
}
