<?php
/* Copyright (C) 2020 ATM Consulting <support@atm-consulting.fr>
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

require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('paymentschedule/class/paymentschedule.class.php');
dol_include_once('paymentschedule/lib/paymentschedule.lib.php');
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('paymentschedule@paymentschedule', 'banks', 'categories', 'widthdrawals', 'companies', 'bills'));

//$result = restrictedArea($user, 'prelevement', '', '', 'bons');
if(empty($user->rights->paymentschedule->write)) accessforbidden();

$action = GETPOST('action');

/*
 * Actions
 */

$nb_error = 0;
$errors = array();

if($action === 'revertpaymentschedule') {
	$date_demande_start = dol_mktime(0, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
	$date_demande_end = dol_mktime(23, 59, 59, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));

	$date_changement_start = dol_mktime(0, 0, 0, GETPOST('changemonth'), GETPOST('changeday'), GETPOST('changeyear'));
	$date_changement_end = dol_mktime(23, 59, 59, GETPOST('changemonth'), GETPOST('changeday'), GETPOST('changeyear'));
	$results = array();

	// Suppression Bons de prélévement & Prelevement Facture Demande
	$sql = '
		SELECT rowid, ref FROM '.MAIN_DB_PREFIX.'prelevement_bons
		WHERE datec>=\''.$db->idate($date_demande_start).'\'
		AND datec<=\''.$db->idate($date_demande_end).'\'
		';

	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);

	$bprev = new BonPrelevement($db);
	$bon_rowid = $obj->rowid;
	$res = $bprev->fetch($obj->rowid, $obj->ref);

	$nb_element_ok = 0;
	$nb_delete = 0;

	if($res > 0) {
		if((float) DOL_VERSION >= 17.0) {
			$sql = '
			SELECT rowid FROM '.MAIN_DB_PREFIX.'prelevement_demande
			WHERE fk_prelevement_bons = '.$obj->rowid;
		}
		else {
			$sql = "
			SELECT rowid FROM ".MAIN_DB_PREFIX."prelevement_facture_demande
			WHERE fk_prelevement_bons = ".$obj->rowid;
		}
		$resql = $db->query($sql);
		if($resql) {
			$db->begin();
			while($obj = $db->fetch_object($resql)) {
				// Suppression liens element_element Prelevement Facture Demande
				$sql = '
				DELETE FROM '.MAIN_DB_PREFIX.'element_element
				WHERE sourcetype = \'prelevement_facture_demande\'
			  	AND fk_source = '.$obj->rowid.'
				';

				if($db->query($sql)) {
					$db->commit();
					$nb_element_ok++;
				}
				else {
					array_push($results, array('KO' => 'Suppression liaisons Demandes Prélévements Factures échouée'));
				}

				// Suppression Prelevement Facture Demande
				if((float) DOL_VERSION >= 17.0) {
					$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'prelevement_demande WHERE rowid = '.$obj->rowid;
				}
				else {
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."prelevement_facture_demande WHERE rowid = ".$obj->rowid;
				}
				if($db->query($sql)) {
					$db->commit();
					$nb_delete++;
				}
				else {
					array_push($results, array('KO' => 'Suppression Demandes Prélévements Factures de prelevement_bons '.$obj->rowid.' échouée'));
				}
			}
		}
		else {
			array_push($results, array('KO' => 'No data to '.$sql));
		}

		array_push($results, array('OK' => $nb_element_ok.' suppressions liaisons Demandes Prélévements Factures'));
		array_push($results, array('OK' => $nb_delete.' suppressions de Demandes Prélévements Factures'));

		// Suppression Bons de prélévement
		$res = $bprev->deleteObjectLinked();
		if($res > 0) {
			array_push($results, array('OK' => 'Suppression objets liés au bon de prélévement '.$obj->ref));
		}
		else {
			array_push($results, array('KO' => 'Erreur suppression objets liés au bon de prélévement '.$obj->ref));
		}
		$res = $bprev->delete($user);
		if($res > 0) {
			array_push($results, array('OK' => 'Bon de prélévement '.$obj->ref.' supprimé'));
			array_push($results, array('OK' => 'prelevement_facture supprimés'));
			array_push($results, array('OK' => 'prelevement_lignes supprimés'));
			array_push($results, array('OK' => 'prelevement_bons supprimés'));
			array_push($results, array('OK' => 'prelevement_facture_demande updated'));
		}
		else {
			array_push($results, array('KO' => 'Erreur suppression Bon de prélévement '.$obj->ref));
		}
	}
	else {
		array_push($results, array('KO' => 'Aucun bon de prélévement trouvé, supression des lignes sans correspondance :'));

		// Suppression liens element_element Prelevement Facture Demande
		if((float) DOL_VERSION >= 17.0) $tablePrelvDmnd = 'prelevement_demande';
		else $tablePrelvDmnd = 'prelevement_facture_demande';

		$sql = '
			DELETE FROM '.MAIN_DB_PREFIX.'element_element
			WHERE sourcetype = \'prelevement_facture_demande\'
			AND fk_source NOT IN
				(
				SELECT d.rowid
				FROM '.MAIN_DB_PREFIX.$tablePrelvDmnd.' as d
				)
			';

		if($db->query($sql)) {
			array_push($results, array('OK' => 'Lignes Sans Correspondance element_element supprimées'));
			$db->commit();
		}
		else {
			array_push($results, array('KO' => 'Erreur suppression des lignes Sans Correspondance element_element'));
		}

		// Suppression Prelevement Facture Demande
		$sql = '
			DELETE
			FROM '.MAIN_DB_PREFIX.$tablePrelvDmnd.'
			WHERE fk_prelevement_bons IS NULL
			OR fk_prelevement_bons NOT IN
			(
				SELECT rowid
				FROM '.MAIN_DB_PREFIX.'prelevement_bons
			)
		';

		if($db->query($sql)) {
			array_push($results, array('OK' => "\tLignes Sans Correspondance de prelevement_facture supprimées"));
			$db->commit();
		}
		else {
			array_push($results, array('KO' => "\tSuppression Demandes Prélévements Factures de prelevement_bons ".$obj->rowid." échouée"));
		}
	}

	$sql = '
		SELECT td.rowid as det_rowid, t.rowid as rowid
		FROM '.MAIN_DB_PREFIX.'paymentscheduledet td
		INNER JOIN '.MAIN_DB_PREFIX.'paymentschedule t ON (t.rowid = td.fk_payment_schedule)
		WHERE t.status = '.PaymentSchedule::STATUS_VALIDATED.'
		AND td.status = '.PaymentScheduleDet::STATUS_REQUESTED.'
		AND td.fk_mode_reglement = '.$conf->global->PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE.'
        AND td.date_demande >= \''.$db->idate($date_changement_start).'\' and td . date_demande <= \''.$db->idate($date_changement_end).'\'
    ';

	$resql = $db->query($sql);

	if($resql) {
		$db->begin();
		$nb_payment_ok = 0;
		$nb_payment_det_ok = 0;
		$nb_element_ok = 0;

		while($obj = $db->fetch_object($resql)) {
			// Suppression liens element_element paymentscheduledet
			$sql = '
				DELETE FROM '.MAIN_DB_PREFIX.'element_element
				WHERE sourcetype = \'widthdraw_line\'
				AND targettype = \'paymentscheduledet\'
			  	AND fk_target = '.$obj->det_rowid.'
				';

			if($db->query($sql)) {
				$db->commit();
				$nb_element_ok++;
			}
			else {
				array_push($results, array('KO' => 'Suppression liaisons Demandes Prélévements Factures échouée'));
			}

			// Changement status des lignes
			$res = forceSetValid($db, $obj->rowid);
			if($res > 0) {
				$db->commit();
				$nb_payment_ok++;
			}
			else {
				array_push($results, array('KO' => 'PaymentSchedule '.$obj->rowid.' setValid --- KO'));
			}

			$det = new PaymentScheduleDet($db);
			$det->fetch($obj->det_rowid);
			$res = $det->setWaiting($user);
			if($res > 0) {
				$db->commit();
				$nb_payment_det_ok++;
			}
			else {
				array_push($results, array('KO' => 'PaymentScheduleDet '.$obj->det_rowid.' setValid --- KO'));
			}
		}
	}
	else {
		array_push($results, array('KO' => 'No data to '.$sql));
	}

	array_push($results, array('OK' => $nb_element_ok.' element_element paymentscheduledet supprimés'));
	array_push($results, array('OK' => $nb_payment_ok.' PaymentSchedule setValid --- OK'));
	array_push($results, array('OK' => $nb_payment_det_ok.' PaymentScheduleDet setValid --- OK'));
}

/**
 * View
 */
$form = new Form($db);

$title = $langs->trans('RevertPaymentSchedule');
llxHeader('', $title);

print load_fiche_titre($langs->trans('RevertPaymentSchedule'), '', 'paymentschedule@paymentschedule');

dol_fiche_head(array(), '');

if($action === 'revertpaymentschedule') {
	print '<p>Recherche Date : '.date('d/m/Y', $date_changement_end).'</p>';
	print '<p>Modif Date : '.date('d/m/Y', $date_demande_start).'</p>';

	//print '<p>'.$sql.'</p>';
	foreach($results as $result) {
		$type = array_keys($result);
		$type = $type[0];
		if($type == 'OK') {
			print '<p>'.$result['OK'].'</p>';
		}
		else if($type == 'KO') {
			print '<p style="color:red;">'.$result['KO'].'</p>';
		}
		else {
			var_dump($result);
		}
	}
}
else {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="revertpaymentschedule">';

	print '<table class="border centpercent">'."\n";

	print '<tr>';
	print '<td class="titlefield selectdate">'.$langs->trans('PaymentScheduleRevert_requestSelectDate').'</td>';
	print '<td>'.$form->selectDate().'</td>';
	print '</tr>';

	print '<tr>';
	print '<td class="titlefield selectdate">'.$langs->trans('PaymentScheduleRevert_changeSelectDate').'</td>';
	print '<td>'.$form->selectDate('', 'change').'</td>';
	print '</tr>';

	print '</table>'."\n";

	print '<div class="center">';
	print '<input type="submit" class="button" name="search" value="'.dol_escape_htmltag($langs->trans('Revert')).'">';
	print '</div>';

	print '</form>';
}

dol_fiche_end();

llxFooter();
$db->close();

function forceSetValid($db, $rowid) {
	global $user;
	$ps = new PaymentSchedule($db);
	$ps->fetch($rowid);
	$ps->status = PaymentSchedule::STATUS_VALIDATED;
	$ps->withChild = false;

	return $ps->update($user);
}
