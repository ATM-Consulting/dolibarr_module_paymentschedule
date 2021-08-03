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
 * \file    class/actions_paymentschedule.class.php
 * \ingroup paymentschedule
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsPaymentSchedule
 */
class ActionsPaymentSchedule
{
    /**
     * @var DoliDb		Database handler (result of a new DoliDB)
     */
    public $db;

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
     * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $conf;

		$TContext = explode(':', $parameters['context']);

		if (in_array('invoicecard', $TContext))
		{
			if ($action == 'confirm_createpaymentschedule')
			{
                if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
				dol_include_once('paymentschedule/class/paymentschedule.class.php');

				$date_start = dol_mktime(12, 0, 0, GETPOST('date_startmonth', 'int'), GETPOST('date_startday', 'int'), GETPOST('date_startyear', 'int'));
				$periodicity_unit = GETPOST('periodicity_unit', 'alphanohtml');
				$periodicity_value = GETPOST('periodicity_value', 'int');
				$nb_term = GETPOST('nb_term', 'int');

				$echeancier = new PaymentSchedule($this->db);
				$ret = $echeancier->createFromFacture($object, $date_start, $periodicity_unit, $periodicity_value, $nb_term);
				if ($ret < 0)
				{
					setEventMessage($echeancier->errors, "errors");
				}
				else
                {
                    header('Location: '.dol_buildpath('/paymentschedule/card.php?id='.$echeancier->id, 1));
                    exit;
                }
			}
		}
		elseif (in_array('directdebitcard', $TContext))
        {
            if ($action === 'delete')
            {
                $did = GETPOST('did', 'int');
                if ($did > 0)
                {
                    $sql = 'SELECT fk_target FROM '.MAIN_DB_PREFIX.'element_element
                        WHERE fk_source = '.$did.' AND sourcetype = \'prelevement_facture_demande\'
                        AND targettype = \'paymentscheduledet\'';

                    $resql = $this->db->query($sql);
                    if ($resql)
                    {
                        $obj = $this->db->fetch_object($resql);
                        if ($obj)
                        {
                            if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
                            dol_include_once('paymentschedule/class/paymentschedule.class.php');
                            $det = new PaymentScheduleDet($this->db);
                            $det->fetch($obj->fk_target);

                            $det->setWaiting($user);
                            $det->deleteObjectLinked($did, 'prelevement_facture_demande');
                        }
                    }
                    else
                    {
                        setEventMessage($this->db->lasterror(), 'errors');
                    }

                }
            }
        }
        elseif (in_array('directdebitprevcard', $TContext))
        {
            // PRELEVEMENT_ID_BANKACCOUNT => si non paramétré alors nous avons une erreur sur le passage en credité, donc je teste la conf ici aussi pour éviter de classer Accepté
            if ($action == 'infocredit' && !empty($user->rights->prelevement->bons->credit) && !empty($conf->global->PRELEVEMENT_ID_BANKACCOUNT) && $conf->global->PRELEVEMENT_ID_BANKACCOUNT > 0)
            {
                // Théoriquement je n'ai plus besoin de ce bout code car il a été remplacé par ce qu'il y a dans le trigger PAYMENT_CUSTOMER_CREATE
//                if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
//                dol_include_once('paymentschedule/config.php');
//                dol_include_once('paymentschedule/class/paymentschedule.class.php');

//                $TDet = PaymentScheduleDet::getAllFromBonPrelevement($object);
//                foreach ($TDet as $det)
//                {
//                    $det->setAccepted($user);
//                }
            }
        }

		return 0;
	}

	public function printFieldListValue($parameters, &$object, &$action, $hookmanager)
    {
	    global $langs, $form, $conf;

		$TContext = explode(':',$parameters['context']);
		if(in_array('paiementcard', $TContext))
		{
			//AJOUT COLONNE "Prélévement prévu"
			if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
			dol_include_once('paymentschedule/class/paymentschedule.class.php');

			$tableSEPA = new PaymentSchedule($this->db);
			$tableSEPA->fetchBy($object->facid, 'fk_facture');
            $form->load_cache_types_paiements();

			print '<td class="right">';
			if (!empty($tableSEPA->id))
            {
                print '<select id="" class="paymentschedule-select2 minwidth200 maxwidth300" name="paymentscheduledet_'.$object->facid.'" onchange="$(\'[name=amount_'.$object->facid.']\').val($(this).find(\'option:selected\').data(\'amount\')); $(\'[name=amount_'.$object->facid.']\').trigger(\'change\')">';
                print '<option value="" data-amount="">&nbsp;</option>';
                foreach ($tableSEPA->TPaymentScheduleDet as $det)
                {
                    $disabled = '';
                    // Si le statut de la ligne de l'échéancier est en cours
                    if (in_array($det->status, array(PaymentScheduleDet::STATUS_IN_PROCESS, PaymentScheduleDet::STATUS_REQUESTED, PaymentScheduleDet::STATUS_ACCEPTED)) || $det->fk_mode_reglement == $conf->global->PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE) $disabled = 'disabled';

                    $selected = '';
                    if (GETPOST('paymentscheduledet_'. $object->facid, 'alphanohtml') == $det->id ) $selected = 'selected';

                    print '<option '.$disabled.' '.$selected.' value="' . $det->id . '" data-amount="' . $det->amount_ttc . '">' . $det->label . ' ('.$form->cache_types_paiements[$det->fk_mode_reglement]['label'].' - '.dol_print_date($det->date_demande, 'day').')</option>';
                }
                print '</select>';
            }
			else
            {
                print $langs->trans('NotPaymentSchedule');
            }
			print '</td>';
		}
	}

	public function printFieldListTitle($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;

		$TContext = explode(':',$parameters['context']);

		if(in_array('paiementcard', $TContext))
		{
			print '<td class="right">'.$langs->trans('PaymentScheduleDetLinked').'</td>';
		}

		return 0;
	}

	/**
	 * @param array $parameters
	 * @param Facture $object
	 * @param string $action
	 * @param $hookmanager
	 * @return int
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user;

		$TContext = explode(':',$parameters['context']);

		if (in_array('invoicecard', $TContext))
		{
			if (empty($object->array_options))
			{
				$object->fetch_optionals();
			}

			// vérifier qu'on a bien l'extrafield isecheancier à true
			if (!empty($object->array_options['options_isecheancier']) && !empty($user->rights->paymentschedule->write))
			{
                if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
				dol_include_once('/paymentschedule/class/paymentschedule.class.php');

				$TRestrictMessage = PaymentSchedule::checkFactureCondition($object);
				if (empty($TRestrictMessage))
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&action=createpaymentschedule">'.$langs->trans('PaymentScheduleCreate').'</a></div>';
				}
				else
				{
					print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.implode('<br />', $TRestrictMessage).'">'.$langs->trans('PaymentScheduleCreate').'</a></div>';
				}

				// blocage réouverture facture si échéancier validé
				$echeancier = new PaymentSchedule($object->db);
				$echeancier->fetchBy($object->id, 'fk_facture');

				if ($echeancier->status > PaymentSchedule::STATUS_DRAFT && $object->statut > 0)
				{
					?>
					<script type="text/javascript">
						$(document).ready(function(){
						    console.log("a[href=\"<?php echo $_SERVER['PHP_SELF']."?facid=".$object->id."action=modif"; ?>\"]");
							$("a[href=\"<?php echo $_SERVER['PHP_SELF']."?facid=".$object->id."&action=modif"; ?>\"]")
								.removeClass('butAction')
								.addClass('butActionRefused')
								.attr('title', "<?php echo $langs->transnoentities('PaymentScheduleIsValidated'); ?>")
								.attr('href', '#');
						})
					</script>
					<?php
				}
			}
		}

		return 0;
	}

	public function formConfirm($parameters, &$object, &$action, $hookmanager)
    {
        $TContext = explode(':',$parameters['context']);

        if (in_array('invoicecard', $TContext))
        {
            if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
            dol_include_once('paymentschedule/lib/paymentschedule.lib.php');

            $form = new Form($this->db);
            $this->resprints = getFormConfirmPaymentSchedule($form, null, $object, $action);
        }

        return 0;
    }

    /**
     * Permet de retirer l'onglet "Echéancier" sur la fiche d'une facture s'il n'en a pas de créé pour éviter à l'utilisateur de cliquer dessus
     * @param $parameters
     * @param $object
     * @param $action
     * @param $hookmanager
     * @return int
     */
	public function completeTabsHead($parameters, &$object, &$action, $hookmanager)
    {
        $TContext = explode(':',$parameters['context']);
        if (array_intersect(array('invoicecard', 'directdebitcard', 'paymentschedulecard'), $TContext) && $parameters['mode'] == 'remove')
        {
            $head = $parameters['head'];
            if (!empty($head))
            {
                foreach ($head as $k => $info)
                {
                    if ($info[2] === 'paymentschedulecard')
                    {
                        if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
                        dol_include_once('/paymentschedule/class/paymentschedule.class.php');
                        $paymentSchedule = new PaymentSchedule($this->db);
                        $paymentSchedule->fetchBy($parameters['object']->id, 'fk_facture');

                        if (empty($paymentSchedule->id))
                        {
                            unset($head[$k]);
                            $this->results = $head;
                            return 1;
                        }
                        elseif (count($paymentSchedule->TPaymentScheduleDet) > 0)
                        {
                            $head[$k][1].= ' <span class="badge">'.count($paymentSchedule->TPaymentScheduleDet).'</span>';
                            $this->results = $head;
                            return 1;
                        }

                        break;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Permet de retirer l'onglet "Echéancier" sur la fiche d'une facture s'il n'en a pas de créé pour éviter à l'utilisateur de cliquer dessus
     * @param $parameters
     * @param $object
     * @param $action
     * @param $hookmanager
     * @return int
     */
    public function printCommonFooter($parameters, &$object, &$action, $hookmanager)
    {
        global $user;

        $TContext = explode(':',$parameters['context']);
        if (in_array('directdebitcreatecard', $TContext))
        {
            if (GETPOST('action', 'aZ09') === 'create')
            {
                dol_include_once('paymentschedule/lib/paymentschedule.lib.php');

                $sql = 'SELECT MAX(rowid) as last_id FROM '.MAIN_DB_PREFIX.'prelevement_bons';
                $resql = $this->db->query($sql);
                if ($resql)
                {
                    $obj = $this->db->fetch_object($resql);
                    $fk_prelevement_bons = $obj->last_id;

                    createLinkedBonPrelevement($this->db, $user, $fk_prelevement_bons);
                }
                else
                {
                    setEventMessage($this->db->lasterror(), 'errors');
                }
            }
        }
        elseif(in_array('paiementcard', $TContext))
        {
            print '<script type="text/javascript">
                    $(function() {
                        $(".paymentschedule-select2").select2();
                    });
                </script>';
        }

        return 0;
    }

    /**
     * Permet de retirer l'onglet "Echéancier" sur la fiche d'une facture s'il n'en a pas de créé pour éviter à l'utilisateur de cliquer dessus
     * @param $parameters
     * @param $object
     * @param $action
     * @param $hookmanager
     * @return int
     */
    public function getFormMail($parameters, &$object, &$action, $hookmanager)
    {
        if ($parameters['currentcontext'] === 'invoicecard')
        {
            if (GETPOST('action', 'aZ09') === 'presend' && GETPOST('from', 'alphanohtml') === 'paymentschedule')
            {
                $fk_facture = GETPOST('facid', 'int');
                if ($fk_facture > 0)
                {
                    global $conf;

                    $listofpaths = array();
                    $listofnames = array();
                    $listofmimes = array();
                    $keytoavoidconflict = empty($object->trackid)?'':'-'.$object->trackid;

                    if (! empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths=explode(';', $_SESSION["listofpaths".$keytoavoidconflict]);
                    if (! empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames=explode(';', $_SESSION["listofnames".$keytoavoidconflict]);
                    if (! empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes=explode(';', $_SESSION["listofmimes".$keytoavoidconflict]);


                    $facture = new Facture($this->db);
                    $facture->fetch($fk_facture);

                    $filename = dol_sanitizeFileName($facture->ref.'_ps');
                    $filedir = $conf->paymentschedule->dir_output . '/' . dol_sanitizeFileName($facture->ref.'_ps');

                    $listofpaths[] = $filedir.'/'.$filename.'.pdf';
                    $listofnames[] = $filename.'.pdf';
                    $listofmimes[] = mime_content_type($filedir.'/'.$filename.'.pdf');

                    $_SESSION["listofpaths".$keytoavoidconflict] = implode(';', $listofpaths);
                    $_SESSION["listofnames".$keytoavoidconflict] = implode(';', $listofnames);
                    $_SESSION["listofmimes".$keytoavoidconflict] = implode(';', $listofmimes);
                }
            }
        }

        return 0;
    }
}
