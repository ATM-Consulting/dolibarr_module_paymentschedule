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
 * 	\file		admin/paymentschedule.php
 * 	\ingroup	paymentschedule
 * 	\brief		This file is an example module setup page
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
dol_include_once('abricot/includes/lib/admin.lib.php');

// Translations
$langs->loadLangs(array('paymentschedule@paymentschedule', 'admin', 'other'));

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/', $action, $reg))
{
	$code=$reg[1];
	$val = GETPOST($code);
	if ($code === 'PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE_SECOND' && !empty($val))
	{
		$val = implode(',', $val);
	}

	if (dolibarr_set_const($db, $code, $val, 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
	
if (preg_match('/del_(.*)/', $action, $reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "PaymentScheduleSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = paymentscheduleAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104077Name"),
    -1,
    "paymentschedule@paymentschedule"
);

// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';


if(!function_exists('setup_print_title')){
    print '<div class="error" >'.$langs->trans('AbricotNeedUpdate').' : <a href="http://wiki.atm-consulting.fr/index.php/Accueil#Abricot" target="_blank"><i class="fa fa-info"></i> Wiki</a></div>';
    exit;
}

setup_print_title("Parameters");

// Example with a yes / no select
//setup_print_on_off('CONSTNAME', $langs->trans('ParamLabel'), 'ParamDesc');

// Example with imput
//setup_print_input_form_part('CONSTNAME', $langs->trans('ParamLabel'));

// Example with color
//setup_print_input_form_part('CONSTNAME', $langs->trans('ParamLabel'), 'ParamDesc', array('type'=>'color'), 'input', 'ParamHelp');

$langs->load('bills');
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans('PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE').'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE">';
print $form->select_types_paiements($conf->global->PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE, 'PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE');
print '<input type="submit" class="butAction" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// MODES DE PAIEMENT SECONDAIRES
print '<td>'.$langs->trans('PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE_SECOND').'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE_SECOND">';
$form->load_cache_types_paiements();
$TPaiementId = array();
foreach ($form->cache_types_paiements as $info)
{
    $TPaiementId[$info['id']] = $info['label'];
}
print Form::multiselectarray('PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE_SECOND', $TPaiementId, explode(',', $conf->global->PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE_SECOND), 0, 0, 'minwidth200');
print '<input type="submit" class="butAction" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

setup_print_input_form_part('PAYMENTSCHEDULE_LABEL_PATTERN', $langs->trans('PAYMENTSCHEDULE_LABEL_PATTERN'), 'PAYMENTSCHEDULE_LABEL_PATTERN_HELP', array('placeholder' => 'Prélèvement {SOCNAME} - {FACNUMBER}', 'size' => '40'));

// Example with placeholder
//setup_print_input_form_part('CONSTNAME',$langs->trans('ParamLabel'),'ParamDesc',array('placeholder'=>'http://'),'input','ParamHelp');

// Example with textarea
//setup_print_input_form_part('CONSTNAME',$langs->trans('ParamLabel'),'ParamDesc',array(),'textarea');

setup_print_on_off('PAYMENTSCHEDULE_DISABLE_RESTRICTION_ON_IBAN');

print '</table>';

dol_fiche_end(-1);

llxFooter();

$db->close();
