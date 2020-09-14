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
 * 	\file		core/triggers/interface_99_modMyodule_PaymentScheduletrigger.class.php
 * 	\ingroup	paymentschedule
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modPaymentschedule_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */

/**
 * Trigger class
 */
class InterfacePaymentScheduletrigger
{

    private $db;

    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "Triggers of this module are empty functions."
            . "They have no effect."
            . "They are provided for tutorial purpose only.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'paymentschedule@paymentschedule';
    }

    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental')

                return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
    }


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
	 *
	 * @param string $action code
	 * @param Object $object
	 * @param User $user user
	 * @param Translate $langs langs
	 * @param conf $conf conf
	 * @return int <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	function runTrigger($action, $object, $user, $langs, $conf) {
		//For 8.0 remove warning
		$result=$this->run_trigger($action, $object, $user, $langs, $conf);
		return $result;
	}


    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        // Users
        if ($action == 'BON_PRELEVEMENT_DELETE')
        {
            if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
            dol_include_once('/paymentschedule/config.php');
            dol_include_once('/paymentschedule/class/paymentschedule.class.php');

            $TDet = PaymentScheduleDet::getAllFromBonPrelevement($object);
            foreach ($TDet as $det)
            {
                $det->setRequested($user);
                if (empty($det->linkedObjectsIds['widthdraw_line'])) $det->fetchObjectLinked();
                if (!empty($det->linkedObjectsIds['widthdraw_line']))
                {
                    // Delete seulement le lien entre la ligne échéancier et la ligne de bon de prélèvement, car le paiment et le reste ne sont delete
                    foreach ($det->linkedObjectsIds['widthdraw_line'] as $fk_element_element => $fk_prelevement_lignes)
                    {
                        $det->deleteObjectLinked(null, null, null, null, $fk_element_element);
                    }
                }

                // TODO voir si il faut faire la suppression du paiement si faisable ou peut être delete la demande de prélèvement
            }

            $object->deleteObjectLinked();

        } elseif ($action == 'BON_PRELEVEMENT_CREATE' || $action == 'DIRECT_DEBIT_ORDER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );

            /** @var BonPrelevement $object */
            if (!empty($object->context['factures_prev']))
            {
                $sql = 'SELECT rowid AS fk_prelevement_lignes FROM '.MAIN_DB_PREFIX.'prelevement_lignes WHERE fk_prelevement_bons = '.$object->id;
                $resql = $this->db->query($sql);
                if ($resql)
                {
                    if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
                    dol_include_once('paymentschedule/config.php');
                    dol_include_once('paymentschedule/class/paymentschedule.class.php');

                    $factures_prev = $object->context['factures_prev'];
                    $i = 0;
                    while ($obj = $this->db->fetch_object($resql))
                    {
                        /** @var array $fac[x][1] = fk_prelevement_demande_facture */
                        $fac = $factures_prev[$i];
                        $fk_prelevement_demande_facture = $fac[1];

                        $paymentscheduledet = new PaymentScheduleDet($this->db);
                        if ($paymentscheduledet->fetchByPrelevementFactureDemandeId($fk_prelevement_demande_facture) > 0)
                        {
                            $paymentscheduledet->add_object_linked('widthdraw_line', $obj->fk_prelevement_lignes);
                        }

                        $i++;
                    }
                }
                else
                {
                    dol_print_error($this->db);
                }
            }
        }

        elseif ($action == 'USER_LOGIN') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_UPDATE_SESSION') {
            // Warning: To increase performances, this action is triggered only if
            // constant MAIN_ACTIVATE_UPDATESESSIONTRIGGER is set to 1.
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_CREATE_FROM_CONTACT') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_NEW_PASSWORD') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_ENABLEDISABLE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_LOGOUT') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_SETINGROUP') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_REMOVEFROMGROUP') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Groups
        elseif ($action == 'GROUP_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'GROUP_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'GROUP_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Companies
        elseif ($action == 'COMPANY_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'COMPANY_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'COMPANY_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Contacts
        elseif ($action == 'CONTACT_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTACT_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTACT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Products
        elseif ($action == 'PRODUCT_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PRODUCT_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PRODUCT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Customer orders
        elseif ($action == 'ORDER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_CLONE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_BUILDDOC') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_SENTBYMAIL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEORDER_INSERT') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEORDER_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Supplier orders
        elseif ($action == 'ORDER_SUPPLIER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_SUPPLIER_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_SUPPLIER_SENTBYMAIL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'SUPPLIER_ORDER_BUILDDOC') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Proposals
        elseif ($action == 'PROPAL_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_CLONE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_BUILDDOC') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_SENTBYMAIL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_CLOSE_SIGNED') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_CLOSE_REFUSED') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEPROPAL_INSERT') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEPROPAL_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEPROPAL_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Contracts
        elseif ($action == 'CONTRACT_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTRACT_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTRACT_ACTIVATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTRACT_CANCEL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTRACT_CLOSE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTRACT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Bills
        elseif ($action == 'BILL_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'BILL_CLONE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'BILL_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'BILL_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'BILL_BUILDDOC') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'BILL_SENTBYMAIL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'BILL_PAYED' || $action == 'BILL_CANCEL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );

            if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
            dol_include_once('paymentschedule/config.php');
            dol_include_once('paymentschedule/class/paymentschedule.class.php');

            $paymentschedule = new PaymentSchedule($this->db);
            if ($paymentschedule->fetchBy($object->id, 'fk_facture') > 0)
            {
                $paymentschedule->setClose($user);
            }

        } elseif ($action == 'BILL_UNPAYED') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );

            if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
            dol_include_once('paymentschedule/config.php');
            dol_include_once('paymentschedule/class/paymentschedule.class.php');

            $paymentschedule = new PaymentSchedule($this->db);
            if ($paymentschedule->fetchBy($object->id, 'fk_facture') > 0)
            {
                $paymentschedule->setReopen($user);
            }

        } elseif ($action == 'BILL_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
            if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
            dol_include_once('paymentschedule/config.php');
            dol_include_once('paymentschedule/class/paymentschedule.class.php');

            $paymentschedule = new PaymentSchedule($this->db);
            if ($paymentschedule->fetchBy($object->id, 'fk_facture') > 0) {
                if ($paymentschedule->delete($user) <= 0) {
                    setEventMessages(
                        $langs->trans('PaymentScheduleDeleteFailed', $paymentschedule->id, $object->id),
                        array(),
                        'errors'
                    );
                }
            }


        } elseif ($action == 'LINEBILL_INSERT') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEBILL_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Payments
        elseif ($action == 'PAYMENT_CUSTOMER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );

            if (!empty($object->id_prelevement))
            {
                global $paymentschedule_facs, $paymentschedule_facs_index;

                if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
                dol_include_once('paymentschedule/config.php');
                dol_include_once('paymentschedule/class/paymentschedule.class.php');


                foreach($object->amounts as $facid=>$amount)
                {
                    if (empty($paymentschedule_facs))
                    {
                        $psbonprelevement = new PaymentScheduleBonPrelevement($this->db);
                        $psbonprelevement->fetch($object->id_prelevement);

                        $paymentschedule_facs = $psbonprelevement->getListInvoices(1);
                        $paymentschedule_facs_index = 0;
                    }

                    // C'est pas terrible mais pas trop le choix, je récupère la dernière entrée pour l'utiliser et faire le lien
                    $fk_paiement_facture = null;
                    $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'paiement_facture WHERE fk_paiement = '.$object->id.' AND fk_facture ='.$facid.' ORDER BY rowid DESC LIMIT 1';
                    $resql = $this->db->query($sql);
                    if ($resql)
                    {
                        while ($obj = $this->db->fetch_object($resql))
                        {
                            $fk_paiement_facture = $obj->rowid;
                        }
                    }

                    foreach($paymentschedule_facs as $paymentschedule_fac){
                        if($paymentschedule_fac[0] == $facid){
                            $TInfo = $paymentschedule_fac;
                            break;
                        }
                    }

//                    $TInfo = $paymentschedule_facs[$paymentschedule_facs_index];
                    if (!empty($TInfo) && !empty($fk_paiement_facture))
                    {
                        $paymentscheduledet = new PaymentScheduleDet($this->db);
                        // [2] = fk_prelevement_lignes
                        if ($paymentscheduledet->fetchBySourceElement($TInfo[2], 'widthdraw_line') > 0)
                        {
                            $paymentscheduledet->add_object_linked('paymentdet', $fk_paiement_facture);
                            $paymentscheduledet->setAccepted($user);
                        }

//                        $paymentschedule_facs_index++;
                    }
                }

            }
            // CREATION LIEN ENTRE PAIEMENT ET DET DE L'ECHEANCIER SEPA en provenance du formulaire de création de réglement standard Dolibarr
            elseif (GETPOSTISSET('action') && GETPOST('action') == 'confirm_paiement')
            {
                if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
                dol_include_once('paymentschedule/config.php');
                dol_include_once('paymentschedule/class/paymentschedule.class.php');

				// chaque montant par fk_facture
				foreach ($object->amounts as $invoice_id => $amount)
				{
				    $fk_paymentscheduledet = GETPOST('paymentscheduledet_'.$invoice_id, 'int');
                    if (empty($fk_paymentscheduledet) || $fk_paymentscheduledet <= 0) continue;

					//dernier paiement associé à la facture
					$sql = 'SELECT rowid AS fk_paiement_facture FROM ' . MAIN_DB_PREFIX . 'paiement_facture WHERE fk_paiement = '.$object->id.' AND fk_facture = '.$invoice_id.' AND amount = '.$amount;
					$resql = $this->db->query($sql);
					if ($resql)
                    {
                        $obj = $this->db->fetch_object($resql);
                        if ($obj)
                        {
                            $origin_id = $obj->fk_paiement_facture;
                            $origin = 'paymentdet';

                            //recherche du det associé à la facture
                            $paymentscheduledet = new PaymentScheduleDet($this->db);
                            $paymentscheduledet->fetch($fk_paymentscheduledet, false);
                            $paymentscheduledet->add_object_linked($origin, $origin_id);
                            $paymentscheduledet->setAccepted($user);
                        }
                    }
					else
                    {
                        setEventMessage($this->db->lastqueryerror(), 'errors');
                        return -1;
                    }
				}
			}

        } elseif ($action == 'PAYMENT_CUSTOMER_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );

            $sql = 'SELECT ee.fk_source as fk_paiement_facture, ee.fk_target as fk_paymentscheduledet FROM '.MAIN_DB_PREFIX.'element_element ee';
            $sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'paiement_facture pf ON (pf.rowid = ee.fk_source)';
            $sql.= ' WHERE ee.sourcetype = \'paymentdet\'';
            $sql.= ' AND ee.targettype = \'paymentscheduledet\'';
            $sql.= ' AND pf.fk_paiement = '.$object->id;

            $resql = $this->db->query($sql);
            if ($resql)
            {
                if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
                dol_include_once('paymentschedule/config.php');
                dol_include_once('paymentschedule/class/paymentschedule.class.php');

                while ($obj = $this->db->fetch_object($resql))
                {
                    $paymentscheduledet = new PaymentScheduleDet($this->db);
                    $paymentscheduledet->fetch($obj->fk_paymentscheduledet, false);
                    $paymentscheduledet->deleteObjectLinked($obj->fk_paiement_facture, 'paymentdet');
                    $paymentscheduledet->setRefused($user);
                }
            }
        }elseif ($action == 'PAYMENT_SUPPLIER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );

        } elseif ($action == 'PAYMENT_ADD_TO_BANK') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PAYMENT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Interventions
        elseif ($action == 'FICHEINTER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'FICHEINTER_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'FICHEINTER_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'FICHEINTER_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Members
        elseif ($action == 'MEMBER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'MEMBER_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'MEMBER_SUBSCRIPTION') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'MEMBER_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'MEMBER_NEW_PASSWORD') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'MEMBER_RESILIATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'MEMBER_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Categories
        elseif ($action == 'CATEGORY_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CATEGORY_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CATEGORY_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Projects
        elseif ($action == 'PROJECT_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROJECT_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROJECT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Project tasks
        elseif ($action == 'TASK_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'TASK_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'TASK_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Task time spent
        elseif ($action == 'TASK_TIMESPENT_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'TASK_TIMESPENT_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'TASK_TIMESPENT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Shipping
        elseif ($action == 'SHIPPING_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'SHIPPING_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'SHIPPING_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'SHIPPING_SENTBYMAIL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'SHIPPING_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'SHIPPING_BUILDDOC') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // File
        elseif ($action == 'FILE_UPLOAD') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'FILE_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        return 0;
    }
}
