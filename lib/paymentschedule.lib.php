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
 *	\file		lib/paymentschedule.lib.php
 *	\ingroup	paymentschedule
 *	\brief		This file is an example module library
 *				Put some comments here
 */

/**
 * @return array
 */
function paymentscheduleAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load('paymentschedule@paymentschedule');

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/paymentschedule/admin/paymentschedule_setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/paymentschedule/admin/paymentschedule_extrafields.php", 1);
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'extrafields';
    $h++;

	$head[$h][0] = dol_buildpath("/paymentschedule/admin/paymentscheduledet_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$head[$h][2] = 'extrafieldslines';
	$h++;

    $head[$h][0] = dol_buildpath("/paymentschedule/admin/paymentschedule_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@paymentschedule:/paymentschedule/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@paymentschedule:/paymentschedule/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'PaymentSchedule');

    return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	PaymentSchedule $object Object company shown
 * @return 	array				Array of tabs
 */
function paymentschedule_prepare_head(PaymentSchedule $object)
{
    global $langs, $conf;
    $h = 0;
    $head = array();
    $head[$h][0] = dol_buildpath('/paymentschedule/card.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("PaymentScheduleCard");
    $head[$h][2] = 'card';
    $h++;
	
	// Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@paymentschedule:/paymentschedule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@paymentschedule:/paymentschedule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'PaymentSchedule');
	
	return $head;
}

/**
 * @param Form          $form   Form object
 * @param PaymentSchedule $object PaymentSchedule object
 * @param Facture $facture Facture object
 * @param string        $action Triggered action
 * @return string
 */
function getFormConfirmPaymentSchedule($form, $object, $facture, $action)
{
    global $langs, $user;

    $langs->load('main');
    $formconfirm = '';

    if ($action === 'validpaymentschedule' && !empty($user->rights->paymentschedule->write))
    {
        $body = $langs->trans('ConfirmValidatePaymentScheduleBody', $facture->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmValidatePaymentScheduleTitle'), $body, 'confirm_validatepaymentschedule', '', 0, 1);
    }
    elseif ($action === 'createpaymentschedule' && !empty($user->rights->paymentschedule->write))
    {
        $scriptjs = '
            <label>'.$langs->transnoentities('paymentschedule_dateEndPrelevement').' <span id="date_last_prelevement"></span></label>
            <script type="text/javascript">
                $("#date_start, #periodicity_unit, #periodicity_value, #nb_term").change(function(event) {
                    refreshDateEndPrelevement(event);
                });
                
                function refreshDateEndPrelevement(event) {
                    let jsDate = $("#date_start").datepicker("getDate");
                    if (jsDate instanceof Date) {
                        let periodicity_unit = $("#periodicity_unit").val();
                        let periodicity_value = parseInt($("#periodicity_value").val());
                        let nb_term = parseInt($("#nb_term").val());
                        if (periodicity_unit === "'.PaymentSchedule::PERIODICITY_VALUE_DAY.'") {
                            jsDate.setDate(jsDate.getDate()+periodicity_value*(nb_term-1));
                        } else if (periodicity_unit === "'.PaymentSchedule::PERIODICITY_VALUE_MONTH.'") {
                            jsDate.setMonth(jsDate.getMonth()+periodicity_value*(nb_term-1));
                        } else if (periodicity_unit === "'.PaymentSchedule::PERIODICITY_VALUE_YEAR.'") {
                            jsDate.setFullYear(jsDate.getFullYear()+periodicity_value*(nb_term-1));
                        }
                        
                        $("#date_last_prelevement").text(("0" + jsDate.getDate()).slice(-2) + "/" + ("0" + (jsDate.getMonth() + 1)).slice(-2) + "/" + jsDate.getFullYear());
                        let input_periodicity_value = $("#periodicity_value")
                        if (input_periodicity_value.attr("type") == "text") {
                            input_periodicity_value.attr("type", "number").attr("min", "1")
                        }
                        let input_nb_term = $("#nb_term")
                        if (input_nb_term.attr("type") == "text") {
                            input_nb_term.attr("type", "number").attr("min", "1")
                        }
                    } else {
                        setTimeout(refreshDateEndPrelevement, 150, event);
                    }
                }
                
                refreshDateEndPrelevement();
            </script>
        ';

        $values = PaymentSchedule::$TPeriodicityString;
        foreach ($values as &$v) $v = $langs->transnoentities(ucfirst($v));

        $formquestion = array(
            array('type' => 'date', 'label' => $langs->trans('paymentschedule_dateStartEcheance'), 'name' => 'date_start', 'value' => '')
            , array('type' => 'select', 'label' => $langs->trans('PeriodicityUnit'), 'name' => 'periodicity_unit', 'values' => $values, 'default' => PaymentSchedule::PERIODICITY_VALUE_MONTH)
            , array('type' => 'text', 'label' => $langs->trans('PeriodicityValue'), 'name' => 'periodicity_value', 'value' => '1', 'size' => '5')
            , array('type' => 'text', 'label' => $langs->trans('paymentschedule_numberOfEcheanceEcheance'), 'name' => 'nb_term', 'value' => '6', 'size' => '5')
            , array('type' => 'onecolumn', 'value' => $scriptjs)
        );
        $body = $langs->trans('ConfirmCreatePaymentScheduleBody', $facture->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $facture->id, $langs->trans('ConfirmCreatePaymentScheduleTitle'), $body, 'confirm_createpaymentschedule', $formquestion, 0, 1, 'auto');
    }
    elseif ($action === 'resetpaymentschedule' && !empty($user->rights->paymentschedule->write))
    {
        $formquestion = array(
            array('type' => 'checkbox', 'label' => $langs->trans('paymentschedule_fullReset'), 'name' => 'full_reset', 'value' => '1', 'moreattr' => 'value="1"')
        );
        $body = $langs->trans('ConfirmResetPaymentScheduleBody', $facture->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmResetPaymentScheduleTitle'), $body, 'confirm_resetpaymentschedule', $formquestion, 0, 1);
    }
    elseif ($action === 'deletepaymentschedule' && !empty($user->rights->paymentschedule->write))
    {
        $body = $langs->trans('ConfirmDeletePaymentScheduleBody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmDeletePaymentScheduleTitle'), $body, 'confirm_deletepaymentschedule', '', 0, 1);
    }

    return $formconfirm;
}


function createLinkedBonPrelevement($db, $user, $fk_prelevement_bons)
{
    $sql = 'SELECT pfd.rowid, ee.fk_target
            FROM '.MAIN_DB_PREFIX.'prelevement_facture_demande pfd
            INNER JOIN '.MAIN_DB_PREFIX.'element_element ee ON (ee.fk_source = pfd.rowid AND ee.sourcetype = \'prelevement_facture_demande\')
            WHERE pfd.fk_prelevement_bons = '.$fk_prelevement_bons.'
            AND ee.targettype = \'paymentscheduledet\'';

    $resql = $db->query($sql);
    if ($resql)
    {
        if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
        dol_include_once('paymentschedule/config.php');
        dol_include_once('paymentschedule/class/paymentschedule.class.php');

        while ($obj = $db->fetch_object($resql))
        {
            $det = new PaymentScheduleDet($db);
            $det->fetch($obj->fk_target);

            $det->setRequested($user, $fk_prelevement_bons);
        }

        return 1;
    }
    else
    {
        setEventMessage($db->lasterror(), 'errors');
        return -1;
    }

    return 0;
}
