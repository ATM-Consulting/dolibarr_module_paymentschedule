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

if (!class_exists('SeedObject'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call or for session timeout on our module page
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}


class PaymentSchedule extends SeedObject
{
    /**
     * Draft status
     */
    const STATUS_DRAFT = 0;
    /**
     * Validated status
     */
    const STATUS_VALIDATED = 1;
    /**
     * Closed status
     */
    const STATUS_CLOSED = 2;

	/** @var array $TStatus Array of translate key for each const */
	public static $TStatus = array(
		self::STATUS_DRAFT => 'PaymentScheduleStatusDraftShort'
		,self::STATUS_VALIDATED => 'PaymentScheduleStatusValidatedShort'
		,self::STATUS_CLOSED => 'PaymentScheduleStatusClosedShort'
	);


	/** @var string $table_element Table name in SQL */
	public $table_element = 'paymentschedule';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'paymentschedule';

	/** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 1;

    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
    public $ismultientitymanaged = 1;

    // periodicity unit
    const PERIODICITY_VALUE_DAY = 'day';
    const PERIODICITY_VALUE_MONTH = 'month';
    const PERIODICITY_VALUE_YEAR = 'year';

    public static $TPeriodicityString = array(
        self::PERIODICITY_VALUE_DAY => 'day'
        ,self::PERIODICITY_VALUE_MONTH => 'month'
        ,self::PERIODICITY_VALUE_YEAR => 'year'
    );

    public $fields = array(
        'status' => array('type' => 'integer', 'visible' => 0, 'notnull' => 1, 'enabled' => 1, 'default' => 0, 'index' => 1, 'position' => 30)
        ,'fk_facture' => array('type' => 'integer', 'visible' => 0, 'notnull' => 1, 'enabled' => 1, 'position' => 50, 'index' => 1)
        ,'date_start' => array('type' => 'date', 'label' => 'DateStart', 'visible' => 1, 'notnull' => 1)
//        ,'date_end' => array('type' => 'date', 'label' => 'DateEnd', 'visible' => 1, 'notnull' => 1)
        ,'periodicity_unit' => array('type' => 'list', 'label' => 'PeriodicityUnit', 'visible' => 1, 'notnull' => 1, 'default' => self::PERIODICITY_VALUE_MONTH) // day, month, year (value for strtotime)
        ,'periodicity_value' => array('type' => 'integer', 'label' => 'PeriodicityValue', 'visible' => 1, 'notnull' => 1)
        ,'nb_term' => array('type' => 'integer', 'label' => 'NbTerm', 'visible' => 1, 'notnull' => 1)
        //,'fk_user_valid' => array('type'=>'integer', 'label'=>'UserValidation', 'enabled'=>1, 'visible'=>-1, 'position'=>512)
		,'model_pdf'=>array('type' => 'varchar(255)', 'length' => 255, 'label' => 'model_pdf', 'visible' => 0)
		,'last_main_doc'=>array('type' => 'varchar(255)', 'length' => 255, 'label' => 'last_main_doc', 'visible' => 0)
        ,'import_key' => array('type' => 'varchar(14)', 'length' => 14, 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'index' => 0, 'position' => 1000)
    );

	/** @var int $status Object status */
	public $status;

    /** @var int $fk_facture Object link to invoice */
    public $fk_facture;

    /** @var integer $date_start timestamp */
    public $date_start;

//    /** @var integer $date_start timestamp */
//    public $date_end;

    /** @var integer $nb_term */
    public $nb_term;

    /** @var integer $periodicity_unit */
    public $periodicity_unit;

    /** @var integer $periodicity_value */
    public $periodicity_value;


    public $childtables = array('paymentscheduledet'=>'PaymentScheduleDet');

    public $fk_element = 'fk_payment_schedule';

    /** @var PaymentScheduleDet[] $TPaymentScheduleDet */
    public $TPaymentScheduleDet = array();

    /** @var Facture $facture Object */
    public $facture;

    /**
     * PaymentSchedule constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
        global $langs;

		$this->db = $db;

		$this->init();

		$this->status = self::STATUS_DRAFT;

		$this->fields['periodicity_unit']['arrayofkeyval'] = array(
		    self::PERIODICITY_VALUE_DAY => $langs->trans('paymentschedule_periodicityDay')
            , self::PERIODICITY_VALUE_MONTH => $langs->trans('paymentschedule_periodicityMonth')
            , self::PERIODICITY_VALUE_YEAR => $langs->trans('paymentschedule_periodicityYear')
        );
    }

    public function fetch($id=0, $loadChild = true, $ref='')
	{
		$ret = parent::fetch($id, $loadChild, $ref);
		if ($ret > 0)
		{
			$this->facture = new facture($this->db);
			$this->facture->fetch($this->fk_facture);
			$this->ref = $this->facture->ref."_ps";
			$this->socid = $this->facture->socid;
		}

		return $ret;
	}

    /**
     *	Get object and children from database on custom field
     *
     *	@param      string		$key       		key of object to load
     *	@param      string		$field       	field of object used to load
     * 	@param		bool		$loadChild		used to load children from database
     *	@return     int         				>0 if OK, <0 if KO, 0 if not found
     */
    public function fetchBy($key, $field, $loadChild = true)
    {

        if (empty($this->fields[$field])) return false;

        $resql = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE ".$field."=".$this->quote($key, $this->fields[$field])." LIMIT 1 ");
        if ($resql)
        {
            if (($obj = $this->db->fetch_object($resql)))
            {
                return $this->fetch($obj->rowid, $loadChild);
            }
        }
        else
        {
            $this->error = $this->db->lastqueryerror();
            $this->errors[] = $this->error;
            return -1;
        }

        return 0;
    }

        /**
     * @param string  $ref        facture ref
     * @param bool    $loadChild
     * @return int
     */
    public function fetchByFactureRef($ref, $loadChild = true)
    {
        $field = (float) DOL_VERSION < 10.0 ? 'facnumber' : 'ref';
        $sql = 'SELECT p.rowid FROM '.MAIN_DB_PREFIX.'facture f INNER JOIN '.MAIN_DB_PREFIX.$this->table_element.' p ON (p.fk_facture = f.rowid) WHERE f.'.$field.' = \''.$this->db->escape($ref).'\'';
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if (($obj = $this->db->fetch_object($resql)))
            {
                return $this->fetch($obj->rowid, $loadChild);
            }
        }
        else
        {
            $this->error = $this->db->lastqueryerror();
            $this->errors[] = $this->error;
            return -1;
        }

        return 0;
    }

    /**
     * @param	User	$user		User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return	int
     */
    public function save($user, $notrigger = false)
    {
        if (!empty($this->is_clone)) {}

        return $this->create($user, $notrigger);
    }


    /**
     * @see cloneObject
     * @return void
     */
    public function clearUniqueFields()
    {

    }


    /**
     * @param	User	$user		User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return	int
     */
    public function delete(User &$user, $notrigger = false)
    {
    	global $conf;

        $this->deleteObjectLinked();

		require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
		$fac = new Facture($this->db);
		$fac->fetch($this->fk_facture);

		$ref = dol_sanitizeFileName($fac->ref)."_ps";
		if ($conf->paymentschedule->dir_output)
		{
			require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";

			$dir = $conf->paymentschedule->dir_output . "/" . $ref;
			$file = $conf->paymentschedule->dir_output . "/" . $ref . "/" . $ref . ".pdf";
			if (file_exists($file))	// We must delete all files before deleting directory
			{

				$ret=dol_delete_preview($this);
				dol_delete_file($file,0,0,0,$this); // For triggers
			}

			if (file_exists($dir))
			{
				dol_delete_dir_recursive($dir); // For remove dir and meta
			}
		}

        unset($this->fk_element); // avoid conflict with standard Dolibarr comportment
        return parent::delete($user, $notrigger);
    }


    /**
     * @param	User	$user		User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return	int
     */
    public function setDraft($user, $notrigger = false)
    {
        if ($this->status === self::STATUS_VALIDATED)
        {
            $this->status = self::STATUS_DRAFT;
            $this->withChild = false;

            return $this->update($user, $notrigger);
        }

        return 0;
    }

    /**
     * @param	User	$user		User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return	int
     */
    public function setValid($user, $notrigger = false)
    {
        if ($this->status === self::STATUS_DRAFT)
        {
//            $this->fk_user_valid = $user->id;
            $this->status = self::STATUS_VALIDATED;
            $this->withChild = false;

            return $this->update($user, $notrigger);
        }

        return 0;
    }

	/**
	 * function for massaction compatibility
	 *
	 * @param	User	$user
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
	 * @return	int
	 */
    public function validate($user, $notrigger = false)
	{
		return $this->setValid($user, $notrigger);
	}

    /**
     * @param	User	$user		User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return	int
     */
    public function setClose($user, $notrigger = false)
    {
        if ($this->status === self::STATUS_VALIDATED)
        {
//            $this->fk_user_valid = $user->id;
            $this->status = self::STATUS_CLOSED;
            $this->withChild = false;

            return $this->update($user, $notrigger);
        }

        return 0;
    }


    /**
     * @param	User	$user		User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return	int
     */
    public function setReopen($user, $notrigger = false)
    {
        if ($this->status === self::STATUS_VALIDATED || $this->status === self::STATUS_CLOSED)
        {
            $this->status = self::STATUS_VALIDATED;
            $this->withChild = false;

            return $this->update($user, $notrigger);
        }

        return 0;
    }

    /**
     * @param int $mode     0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
     * @return string
     */
    public function getLibStatut($mode = 0)
    {
        return self::LibStatut($this->status, $mode);
    }

    /**
     * @param int       $status   Status
     * @param int       $mode     0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
     * @return string
     */
    public static function LibStatut($status, $mode)
    {
		global $langs;

		$langs->load('paymentschedule@paymentschedule');
        $res = '';

        if ($status==self::STATUS_DRAFT) { $statusType='status6'; $statusLabel=$langs->trans('PaymentScheduleStatusDraft'); $statusLabelShort=$langs->trans('PaymentScheduleStatusDraftShort'); }
        elseif ($status==self::STATUS_VALIDATED) { $statusType='status4'; $statusLabel=$langs->trans('PaymentScheduleStatusValidated'); $statusLabelShort=$langs->trans('PaymentScheduleStatusValidateShort'); }
        elseif ($status==self::STATUS_CLOSED) { $statusType='status6'; $statusLabel=$langs->trans('PaymentScheduleStatusClosed'); $statusLabelShort=$langs->trans('PaymentScheduleStatusClosedShort'); }

        if (function_exists('dolGetStatus'))
        {
            $res = dolGetStatus($statusLabel, $statusLabelShort, '', $statusType, $mode);
        }
        else
        {
            $statusType = str_replace('status', 'statut', $statusType);
            if ($mode == 0) $res = $statusLabel;
            elseif ($mode == 1) $res = $statusLabelShort;
            elseif ($mode == 2) $res = img_picto($statusLabel, $statusType).$statusLabelShort;
            elseif ($mode == 3) $res = img_picto($statusLabel, $statusType);
            elseif ($mode == 4) $res = img_picto($statusLabel, $statusType).$statusLabel;
            elseif ($mode == 5) $res = $statusLabelShort.img_picto($statusLabel, $statusType);
            elseif ($mode == 6) $res = $statusLabel.img_picto($statusLabel, $statusType);
        }

        return $res;
    }

	/**
     * Méthode permettant de savoir si une facture correspond aux critères permettant de créer un échéancier
	 * @param Facture $facture
	 *
	 * @return array array(bool, array(msgs))
	 */
    public static function checkFactureCondition($facture)
	{
		global $conf, $langs, $user;

		$langs->load('paymentschedule@paymentschedule');

        $TRestrictMessage = array();

        if (empty($user->rights->paymentschedule->write)) $TRestrictMessage[] = $langs->trans('CheckErrorInvoiceInsufficientPermission');

        if ($facture->statut == Facture::STATUS_DRAFT) $TRestrictMessage[] = $langs->trans('CheckErrorInvoiceIsDraft');

        $TPaymentId = array();
        if (!empty($conf->global->PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE)) $TPaymentId[] = $conf->global->PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE;
        if (!empty($conf->global->PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE_SECOND)) $TPaymentId = array_merge($TPaymentId, explode(',', $conf->global->PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE_SECOND));

        if (!in_array($facture->mode_reglement_id, $TPaymentId))
		{
			$TRestrictMessage[] = $langs->trans('CheckErrorModeRgltNotMatch');
		}

        $echeancier = new PaymentSchedule($facture->db);
        $echeancier->fetchBy($facture->id, 'fk_facture');
        if (!empty($echeancier->id)) $TRestrictMessage[] = $langs->trans('CheckErrorTimetableAlreadyExists');

		if (empty($facture->array_options)) $facture->fetch_optionals();

		if (empty($facture->linkedObjects)) $facture->fetchObjectLinked();

		// vérifier qu'on a bien l'extrafield isecheancier à true
		if (empty($facture->array_options['options_isecheancier']))
		{
            $TRestrictMessage[] = $langs->trans('CheckErrorIsNotPaymentSchedule');
		}

		// TODO à vérifier mais peut être que le test sur link contrat pas utile
		// vérifier qu'on a bien un contrat lié à cette facture avec au moins une ligne active
		if (array_key_exists('contrat', $facture->linkedObjects))
		{
			// si y a un contrat, on valide qu'il y a une ligne active sur ce contrat
			// je prend le premier qui vient pour test
			$keys = array_keys($facture->linkedObjects['contrat']);
			$contrat = &$facture->linkedObjects['contrat'][$keys[0]];

			if ($contrat->nbofservicesopened == 0)
			{
                $TRestrictMessage[] = $langs->trans('CheckErrorNoActiveLineOnContract');
			}
		}
		else
		{
            $TRestrictMessage[] = $langs->trans('CheckErrorIsNotLinkedToContract');
		}

		// TODO vérifier que le tiers de la facture a bien un compte bancaire avec les infos nécessaires au prélèvement
		// IBAN valide + BIC + RUM + MODE (FRST ou RECUR)...
        if (empty($conf->global->PAYMENTSCHEDULE_DISABLE_RESTRICTION_ON_IBAN))
		{
			require_once DOL_DOCUMENT_ROOT.'/societe/class/companypaymentmode.class.php';
			$companypaymentmode = new CompanyPaymentMode($facture->db);
			if ($companypaymentmode->fetch(null, null, $facture->socid) <= 0)
			{
				$TRestrictMessage[] = $langs->trans('CheckErrorCustomerHasNoIBAN');
			}
		}

		return $TRestrictMessage;
	}

	/**
	 * Crée l'objet échéancier depuis la facture en récupérant les infos du contrat lié
	 *
	 * @param Facture $facture
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
	 * @return int <0 if KO, > 0 if OK
	 */
	public function createFromFacture($facture, $date_start, $periodicity_unit, $periodicity_value, $nb_term, $notrigger = false)
	{
		global $user;

        // check la facture
        $TRestrictMessage = self::checkFactureCondition($facture);
        if (!empty($TRestrictMessage))
        {
            $this->errors = $TRestrictMessage;
            return -1;
        }
        else
        {
            $this->fk_facture = $facture->id;

            $this->date_start = $date_start;
            // TODO control values
            $this->periodicity_unit = $periodicity_unit;
            $this->periodicity_value = $periodicity_value;
            $this->nb_term = $nb_term;

            $this->db->begin();

            $res = $this->save($user, $notrigger);
            if ($res > 0)
            {
                $res = $this->initDetailEcheancier();
                if ($res >= 0)
                {
                    $this->db->commit();
                    return $this->id;
                }
            }

            $this->db->rollback();
            return $res;
        }
	}

	public function initDetailEcheancier($reset = false, $fill_amount = 'onlast')
	{
		global $user, $conf, $langs;

		$langs->load('paymentschedule@paymentschedule');

		$start = $this->date_start;

		if ((empty($this->date_start) && $this->date_start !== 0) || empty($this->fk_facture) || $this->fk_facture < 0 || empty($this->nb_term))
        {
			$this->error = "initDetail : missing parameters";
			$this->errors[] = $this->error;
			return -1;
		}

		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

		$facture = new Facture($this->db);
		$ret = $facture->fetch($this->fk_facture);
		if ($ret <= 0)
		{
			$this->errors[] = $langs->trans('CanNotRetrieveInvoice');
			return -2;
		}

		if (empty($facture->thirdparty)) $facture->fetch_thirdparty();

        $nb_term = $this->nb_term;
		if ($nb_term < 0) $nb_term = 0;

		$TDatesEcheance = array();
		while ($nb_term--)
		{
			$TDatesEcheance[] = $start;
			$start = strtotime('+'.$this->periodicity_value.' '.$this->periodicity_unit, $start);
			$facture->date_lim_reglement = $start;
		}
		$facture->update($user);

		$TDefaultAmountToPay = array(
			'HT' 	=> round($facture->total_ht / $this->nb_term, 2)
			,'VAT'	=> round($facture->total_tva / $this->nb_term, 2)
			,'TTC'	=> round($facture->total_ttc / $this->nb_term, 2)
		);

		$TLeftAmountToPay = array(
			'HT' 	=> $facture->total_ht - (($this->nb_term-1) * $TDefaultAmountToPay['HT'])
			,'VAT' 	=> $facture->total_tva - (($this->nb_term-1) * $TDefaultAmountToPay['VAT'])
			,'TTC' 	=> $facture->total_ttc - (($this->nb_term-1) * $TDefaultAmountToPay['TTC'])
		);

		// si reset à true, alors on supprime toutes les lignes avant de les recréer (uniquement celles qui sont en attente de traitement)
		if ($reset)
		{
            foreach ($this->TPaymentScheduleDet as $det)
            {
                if ($det->status == PaymentScheduleDet::STATUS_WAITING) $det->delete($user);
            }
        }

		// on crée les lignes d'échéance SEPA (base des demandes de prélévement généré en auto)
		foreach ($TDatesEcheance as $i => $time)
		{
		    $k = $this->addChild('PaymentScheduleDet');
			$det = $this->TPaymentScheduleDet[$k];
			$det->fk_payment_schedule = $this->id;

			$facnumber = ((float) DOL_VERSION < 9.0 ) ? $facture->facnumber : $facture->ref;
			// {INDICE} {SOCNAME} - {FACNUMBER} {REFCLIENT}
            if (!empty($conf->global->PAYMENTSCHEDULE_LABEL_PATTERN))
            {
                $det->label = $conf->global->PAYMENTSCHEDULE_LABEL_PATTERN;
                $det->label = strtr($det->label, array(
                    '{INDICE}' => $i+1
                    , '{SOCNAME}' => $facture->thirdparty->name
                    , '{FACNUMBER}' => $facnumber
                    , '{REFCLIENT}' => $facture->ref_client
                ));
            }
            else
            {
                $det->label = 'Prélèvement '.$facture->thirdparty->name.' - '.$facnumber;
            }

			$det->date_demande = $time;
			$det->fk_mode_reglement = $facture->mode_reglement_id;

            if ($fill_amount === 'onfirst' && $i == 0)
            {
                $det->amount_ht  = $TLeftAmountToPay['HT'];
                $det->amount_tva = $TLeftAmountToPay['VAT'];
                $det->amount_ttc = $TLeftAmountToPay['TTC'];
            }
            else if ($fill_amount === 'onlast' && $i == ($this->nb_term - 1))
            {
                $det->amount_ht  = $TLeftAmountToPay['HT'];
                $det->amount_tva = $TLeftAmountToPay['VAT'];
                $det->amount_ttc = $TLeftAmountToPay['TTC'];
            }
            else
            {
                $det->amount_ht  = $TDefaultAmountToPay['HT'];
                $det->amount_tva = $TDefaultAmountToPay['VAT'];
                $det->amount_ttc = $TDefaultAmountToPay['TTC'];
            }

			$ret = $det->create($user);
			if ($ret < 0)
            {
                $this->error = $det->error;
                $this->errors[] = $this->error;
                return -4;
            }
		}

		return count($this->TPaymentScheduleDet);
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *	@param	string		$modele			Generator to use. Caller must set it to obj->modelpdf or GETPOST('modelpdf') for example.
	 *	@param	Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param  int			$hidedetails    Hide details of lines
	 *  @param  int			$hidedesc       Hide description
	 *  @param  int			$hideref        Hide ref
	 *  @param   null|array  $moreparams     Array to provide more information
	 *	@return int        					<0 if KO, >0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0, $moreparams=null)
	{
		global $conf,$langs;

		$langs->loadLangs(array("bills", "paymentschedule@paymentschedule"));

		if (! dol_strlen($modele))
		{
			$modele = 'surimi';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (! empty($conf->global->PAYMENTSCHEDULE_ADDON_PDF)) {
				$modele = $conf->global->PAYMENTSCHEDULE_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/paymentschedule/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	/**
	 *  Return clicable link of object (with eventually picto)
	 *
	 *  @param	int		$withpicto       			Add picto into link
	 *  @param  string	$option          			Where point the link
	 *  @param  int		$max             			Maxlength of ref
	 *  @param  int		$short           			1=Return just URL
	 *  @param  string  $moretitle       			Add more text to title tooltip
	 *  @param	int  	$notooltip		 			1=Disable tooltip
	 *  @param  int     $addlinktonotes  			1=Add link to notes
	 *  @param  int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return string 			         			String with URL
	 */
	function getNomUrl($withpicto=0, $option='', $max=0, $short=0, $moretitle='', $notooltip=0, $addlinktonotes=0, $save_lastsearch_value=-1)
	{
		global $langs, $conf, $user, $form;

		if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

		$result='';

		$url = dol_buildpath("/paymentschedule/card.php?facid=".$this->fk_facture, 2);//DOL_URL_ROOT.'/compta/facture/card.php?facid='.$this->id;

		if (!$user->rights->facture->lire)
			$option = 'nolink';

		if ($option !== 'nolink')
		{
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
			if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
		}

		if ($short) return $url;

		$picto='bill';

		$label='';

		if ($user->rights->paymentschedule->read) {
			$label = '<u>' . $langs->trans("ShowInvoice") . '</u>';
			if (! empty($this->ref))
				$label .= '<br><b>'.$langs->trans('Ref') . ':</b> ' . $this->ref;
			if (! empty($this->ref_client))
				$label .= '<br><b>' . $langs->trans('RefCustomer') . ':</b> ' . $this->ref_client;
			if (! empty($this->total_ht))
				$label.= '<br><b>' . $langs->trans('AmountHT') . ':</b> ' . price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
			if (! empty($this->total_tva))
				$label.= '<br><b>' . $langs->trans('VAT') . ':</b> ' . price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
			if (! empty($this->total_localtax1) && $this->total_localtax1 != 0)		// We keep test != 0 because $this->total_localtax1 can be '0.00000000'
				$label.= '<br><b>' . $langs->trans('LT1') . ':</b> ' . price($this->total_localtax1, 0, $langs, 0, -1, -1, $conf->currency);
			if (! empty($this->total_localtax2) && $this->total_localtax2 != 0)
				$label.= '<br><b>' . $langs->trans('LT2') . ':</b> ' . price($this->total_localtax2, 0, $langs, 0, -1, -1, $conf->currency);
			if (! empty($this->total_ttc))
				$label.= '<br><b>' . $langs->trans('AmountTTC') . ':</b> ' . price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
			if ($moretitle) $label.=' - '.$moretitle;
		}

		$linkclose='';
		if (empty($notooltip) && $user->rights->facture->lire)
		{
			if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label=$langs->trans("ShowInvoice");
				$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose.=' class="classfortooltip"';
		}

		$linkstart='<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		if ($option == 'nolink') {
			$linkstart = '';
			$linkend = '';
		}

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), $picto, ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= ($max?dol_trunc($this->ref,$max):$this->ref);
		$result .= $linkend;

		if ($addlinktonotes)
		{
			$txttoshow=($user->socid > 0 ? $this->note_public : $this->note_private);
			if ($txttoshow)
			{
				$notetoshow=$langs->trans("ViewPrivateNote").':<br>'.dol_string_nohtmltag($txttoshow,1);
				$result.=' <span class="note inline-block">';
				$result.='<a href="'.DOL_URL_ROOT.'/compta/facture/note.php?id='.$this->id.'" class="classfortooltip" title="'.dol_escape_htmltag($notetoshow).'">';
				$result.=img_picto('','note');
				$result.='</a>';
				//$result.=img_picto($langs->trans("ViewNote"),'object_generic');
				//$result.='</a>';
				$result.='</span>';
			}
		}

		return $result;
	}

    /**
     * @param User  $user   User object
     * @param int   $fk_payment_schedule_det   id
     * @param bool  $create_payment   create payment object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return int
     */
    public function setLineAccepted($user, $fk_payment_schedule_det, $create_payment = false, $notrigger = false)
    {
        $res = 0;
        foreach ($this->TPaymentScheduleDet as $paymentScheduleDet)
        {
            if ($paymentScheduleDet->id == $fk_payment_schedule_det)
            {
                $res = $paymentScheduleDet->setAccepted($user, $notrigger);
                if ($res && $create_payment)
                {
                    include_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
                    include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

                    $thirdparty = new Societe($this->db);
                    if ($this->socid > 0) $thirdparty->fetch($this->socid);

                    $paiement = new Paiement($this->db);
                    $paiement->datepaye     = dol_now();
                    $paiement->amounts      = array($this->fk_facture => $paymentScheduleDet->amount_ttc);   // Array with all payments dispatching with invoice id
                    $paiement->multicurrency_amounts = array();   // Array with all payments dispatching
                    $paiement->paiementid   = $paymentScheduleDet->fk_mode_reglement;
                    $paiement->num_paiement = '';
                    $paiement->note         = '';

                    $fk_paiement = $paiement->create($user, 1, $thirdparty);    // This include closing invoices and regenerating documents
                    if ($fk_paiement > 0)
                    {
                        $sql = 'SELECT rowid AS fk_paiement_facture FROM ' . MAIN_DB_PREFIX . 'paiement_facture WHERE fk_paiement = '.$fk_paiement;
                        $resql = $this->db->query($sql);
                        if ($resql)
                        {
                            $obj = $this->db->fetch_object($resql);
                            if ($obj)
                            {
                                $paymentScheduleDet->add_object_linked('paymentdet', $obj->fk_paiement_facture);
                            }
                        }

                        $label='(CustomerInvoicePayment)';
                        if ($this->facture->type == Facture::TYPE_CREDIT_NOTE) $label='(CustomerInvoicePaymentBack)';  // Refund of a credit note
                        $paiement->addPaymentToBank($user, 'payment', $label, $this->facture->fk_account, '', '', $notrigger);
                    }
                }

                break;
            }
        }

        return $res;
    }

    /**
     * @param User  $user   User object
     * @param int   $fk_payment_schedule_det   id
     * @param bool  $create_reject   create reject object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return int
     */
    public function setLineRefused($user, $fk_payment_schedule_det, $create_reject = false, $notrigger = false)
    {
        $res = 0;

        foreach ($this->TPaymentScheduleDet as $paymentScheduleDet)
        {
            if ($paymentScheduleDet->id == $fk_payment_schedule_det)
            {
                $res = $paymentScheduleDet->setRefused($user, $notrigger);
                if ($res && $create_reject)
                {
                    $paymentScheduleDet->fetchObjectLinked();
                    if (!empty($paymentScheduleDet->linkedObjectsIds['widthdraw_line']))
                    {
                        $daterej = dol_now();

                        reset($paymentScheduleDet->linkedObjectsIds['widthdraw_line']);
                        $k = key($paymentScheduleDet->linkedObjectsIds['widthdraw_line']);
                        $fk_widthdraw_line = $paymentScheduleDet->linkedObjectsIds['widthdraw_line'][$k];

                        include_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/ligneprelevement.class.php';
                        include_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/rejetprelevement.class.php';

                        $lipre = new LignePrelevement($this->db, $user);
                        $lipre->fetch($fk_widthdraw_line);
                        if ($lipre->id > 0)
                        {
                            $rej = new RejetPrelevement($this->db, $user);
                            /** @see RejetPrelevement::motifs 1 = Provision insuffisante; 2 = Prélèvement contesté; ...*/
                            $rej->create($user, $lipre->id, 2, $daterej, $lipre->bon_rowid, 0);

                            $sql = 'SELECT MAX(rowid) as fk_paiement_facture FROM '.MAIN_DB_PREFIX.'paiement_facture';
                            $resql = $this->db->query($sql);
                            if ($resql)
                            {
                                $obj = $this->db->fetch_object($resql);
                                if ($obj)
                                {
                                    $paymentScheduleDet->add_object_linked('paymentdet', $obj->fk_paiement_facture);
                                }
                            }
                        }
                    }
                }

                break;
            }
        }

        return $res;
    }
}


class PaymentScheduleDet extends SeedObject
{
    /**
     * Waiting status
     */
    const STATUS_WAITING = 0;
    /**
     * Demande de prelevement faite
     */
    const STATUS_IN_PROCESS = 1;
    /**
     * La demande fait partie d'un bon de prelevement
     */
    const STATUS_REQUESTED = 2;
    /**
     * Accepted status
     */
    const STATUS_ACCEPTED = 3;
    /**
     * Refused status
     */
    const STATUS_REFUSED = -1;

    public static $TStatusTransKey = array(
        self::STATUS_WAITING => 'PaymentScheduleDetStatusWaiting'
        , self::STATUS_IN_PROCESS => 'PaymentScheduleDetStatusInProcess'
        , self::STATUS_REQUESTED => 'PaymentScheduleDetStatusRequested'
        , self::STATUS_ACCEPTED => 'PaymentScheduleDetStatusAccepted'
        , self::STATUS_REFUSED => 'PaymentScheduleDetStatusRefused'
    );

	/** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
	public $isextrafieldmanaged = 1;

    public $table_element = 'paymentscheduledet';

    public $element = 'paymentscheduledet';

	public $fields = array(
		'fk_payment_schedule'	=>	array('type'=>'integer', 'index'=>1)
		,'status'	    =>	array('type'=>'integer', 'notnull' => 1, 'default' => 0)
		,'label'		=>  array('type'=>'varchar(255)', 'length'=>255)
		,'date_demande'	=> 	array('type'=>'date')
		,'fk_mode_reglement'	=> 	array('type'=>'integer')
		,'tva_tx'	    => 	array('type'=>'double')
//		,'amount_ht'	=> 	array('type'=>'double')
//		,'amount_tva'	=> 	array('type'=>'double')
		,'amount_ttc'	=> 	array('type'=>'double')
	);

	public $fk_payment_schedule;
	public $status;
	public $label;
	public $date_demande;
	public $fk_mode_reglement;
//	public $amount_ht;
//	public $amount_tva;
	public $amount_ttc;

    /**
     * PaymentScheduleDet constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->init();
    }

    public function fetchBySourceElement($fk_source, $sourcetype)
    {
        $sql = 'SELECT fk_target FROM '.MAIN_DB_PREFIX.'element_element';
        $sql.= ' WHERE fk_source = '.$fk_source;
        $sql.= ' AND sourcetype = \''.$sourcetype.'\'';
        $sql.= ' AND targettype = \''.$this->element.'\'';

        $resql = $this->db->query($sql);
        if ($resql)
        {
            if (($obj = $this->db->fetch_object($resql)))
            {
                return $this->fetch($obj->fk_target);
            }
        }
        else
        {
            $this->error = $this->db->lastqueryerror();
            $this->errors[] = $this->error;

            return -1;
        }

        return 0;
    }

    public function fetchByPrelevementFactureDemandeId($fk_prelevement_facture_demande)
    {
        return $this->fetchBySourceElement($fk_prelevement_facture_demande, 'prelevement_facture_demande');
    }
	/**
	 * @param	User	$user		User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
	 * @return	int
	 */
	public function delete(User &$user, $notrigger = false)
	{
		$this->deleteObjectLinked();

//		unset($this->fk_element);
		return parent::delete($user, $notrigger);
	}

    /**
     * @param	User	$user		User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return	int
     */
    public function setWaiting($user, $notrigger = false)
    {
        $this->status = self::STATUS_WAITING;
        $this->withChild = false;

        return $this->update($user, $notrigger);
    }

    /**
     * @param	User	$user		User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return	int
     */
    public function setInProcess($user, $fk_prelevement_facture_demande=null, $notrigger = false)
    {
        $this->status = self::STATUS_IN_PROCESS;
        $this->withChild = false;

        if ($fk_prelevement_facture_demande > 0)
        {
            $this->add_object_linked('prelevement_facture_demande', $fk_prelevement_facture_demande);
        }

        return $this->update($user, $notrigger);
    }

    /**
     * @param	User	$user		User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return	int
     */
    public function setRequested($user, $fk_prelevement_bons = null, $notrigger = false)
    {
        $this->status = self::STATUS_REQUESTED;
        $this->withChild = false;

        if ($fk_prelevement_bons > 0)
        {
            $this->add_object_linked('widthdraw', $fk_prelevement_bons);
        }

        return $this->update($user, $notrigger);
    }

    /**
     * @param	User	$user		User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return	int
     */
    public function setAccepted($user, $notrigger = false)
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->withChild = false;

        return $this->update($user, $notrigger);
    }

    /**
     * @param	User	$user		User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return	int
     */
    public function setRefused($user, $notrigger = false)
    {
        $this->status = self::STATUS_REFUSED;
        $this->withChild = false;

        return $this->update($user, $notrigger);
    }

    /**
     * @param int $mode     0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
     * @return string
     */
    public function getLibStatut($mode = 0)
    {
        return self::LibStatut($this->status, $mode);
    }

    /**
     * @param int       $status   Status
     * @param int       $mode     0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
     * @return string
     */
    public static function LibStatut($status, $mode)
    {
        global $langs;

        $langs->load('paymentschedule@paymentschedule');
        $res = '';

        $statusLabel=$langs->trans(self::$TStatusTransKey[$status]);
        $statusLabelShort=$langs->trans(self::$TStatusTransKey[$status]);

        if ($status==self::STATUS_WAITING) $statusType='status6';
        elseif ($status==self::STATUS_IN_PROCESS) $statusType='status3';
        elseif ($status==self::STATUS_REQUESTED) $statusType='status1';
        elseif ($status==self::STATUS_ACCEPTED) $statusType='status4';
        elseif ($status==self::STATUS_REFUSED) $statusType='status8';

        if (function_exists('dolGetStatus'))
        {
            $res = dolGetStatus($statusLabel, $statusLabelShort, '', $statusType, $mode);
        }
        else
        {
            $statusType = str_replace('status', 'statut', $statusType);
            if ($mode == 0) $res = $statusLabel;
            elseif ($mode == 1) $res = $statusLabelShort;
            elseif ($mode == 2) $res = img_picto($statusLabel, $statusType).$statusLabelShort;
            elseif ($mode == 3) $res = img_picto($statusLabel, $statusType);
            elseif ($mode == 4) $res = img_picto($statusLabel, $statusType).$statusLabel;
            elseif ($mode == 5) $res = $statusLabelShort.img_picto($statusLabel, $statusType);
            elseif ($mode == 6) $res = $statusLabel.img_picto($statusLabel, $statusType);
        }

        return $res;
    }

    /**
     * @param BonPrelevement $object
     * @return PaymentScheduleDet[] array
     */
    public static function getAllFromBonPrelevement($object)
    {
        $TRes = array();

        $sql = 'SELECT fk_target FROM '.MAIN_DB_PREFIX.'element_element
                WHERE fk_source = '.$object->id.'
                AND sourcetype = \''.$object->element.'\'
                AND targettype = \'paymentscheduledet\'';

        $resql = $object->db->query($sql);
        if ($resql)
        {
            while ($obj = $object->db->fetch_object($resql))
            {
                $det = new PaymentScheduleDet($object->db);
                $det->fetch($obj->fk_target);
                $TRes[] = $det;
            }
        }

        return $TRes;
    }

    function fetchObjectLinked($sourceid = null, $sourcetype = '', $targetid = null, $targettype = '', $clause = 'OR', $alsosametype = 1, $orderby = 'sourcetype', $loadalsoobjects = 1)
    {
        global $conf;

        foreach (array('paymentdet', 'prelevement_facture_demande', 'widthdraw', 'widthdraw_line') as $element)
        {
            if (empty($conf->{$element}))
            {
                $conf->{$element} = new stdClass();
                $conf->{$element}->enabled = 1;
            }
        }

        return parent::fetchObjectLinked($sourceid, $sourcetype, $targetid, $targettype, $clause, $alsosametype, $orderby, $loadalsoobjects); // TODO: Change the autogenerated stub
    }

    /**
     * @param $fk_paymentdet
     * @return Paiement|false
     */
    public static function getPaymentObjectFromDetId($fk_paymentdet)
    {
        global $db, $TPaymentCache;

        if (empty($TPaymentCache)) $TPaymentCache = array();

        if (!isset($TPaymentCache[$fk_paymentdet]))
        {
            $sql = 'SELECT fk_paiement FROM '.MAIN_DB_PREFIX.'paiement_facture WHERE rowid = '.$fk_paymentdet;
            $resql = $db->query($sql);
            if ($resql)
            {
                if (($obj = $db->fetch_object($resql)))
                {
                    require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
                    $paiement = new Paiement($db);
                    $paiement->fetch($obj->fk_paiement);
                    $TPaymentCache[$fk_paymentdet] = $paiement;
                }
            }
            else
            {
                setEventMessage($db->lastqueryerror(), 'errors');
                return false;
            }
        }

        return $TPaymentCache[$fk_paymentdet];
    }
}


class PaymentScheduleUpdateStatus extends SeedObject
{
    public $output = '';

    public function run()
    {
        global $user, $langs, $conf;

        $this->output = $langs->trans('PaymentScheduleUpdateStatus_start', date('Y-m-d H:i:s'));

        if (empty($user->rights->paymentschedule->write))
        {
            $this->output.= '<br />'.$langs->trans('PaymentScheduleUpdateStatus_ErrorNotEnougthPermission');
        }
        elseif (empty($conf->global->PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE))
        {
            $this->output.= '<br />'.$langs->trans('PaymentScheduleUpdateStatus_ErrorMainConfigurationMissing');
        }
        else
        {
            $today = date('Y-m-d 12:00:00');
            $today_timestamp = strtotime($today);

            $sql = 'SELECT DISTINCT pb.rowid AS fk_prelevement_bons';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'prelevement_bons pb';
            $sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'element_element ee ON (ee.fk_source = pb.rowid AND ee.sourcetype = \'widthdraw\')';
            $sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'paymentscheduledet pd ON (ee.fk_target = pd.rowid AND ee.targettype = \'paymentscheduledet\')';
            $sql.= ' WHERE statut = 1'; // 1 = En attente du passage en crédité
            $sql.= ' AND pd.date_demande <= \''.$this->db->idate($today).'\'';

            $resql = $this->db->query($sql);
            if ($resql)
            {
                $num = $this->db->num_rows($resql);
                $this->output.= '<br />'.$langs->trans('PaymentScheduleUpdateStatus_QueryFoundNum', $num);

                while ($obj = $this->db->fetch_object($resql))
                {
                    $bonprelevement = new BonPrelevement($this->db);
                    if ($bonprelevement->fetch($obj->fk_prelevement_bons) > 0)
                    {
                        $bonprelevement->set_infocredit($user, $today_timestamp);
                    }
                }
            }
            else
            {
                $this->output.= '<br />'.$langs->trans('PaymentScheduleUpdateStatus_ErrorQuery', $this->db->lastqueryerror());
            }
        }

        $this->output.= '<br />'.$langs->trans('PaymentScheduleUpdateStatus_end', date('Y-m-d H:i:s'));

        return 0;
    }
}

require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
class PaymentScheduleBonPrelevement extends BonPrelevement
{
    /**
     *	Get invoice list
     *
     *  @param 	int		$amounts 	If you want to get the amount of the order for each invoice
     *	@return	array 				Id of invoices
     */
    public function getListInvoices($amounts=0)
    {
        global $conf;

        $arr = array();

        /*
         * Returns all invoices presented
         * within a withdrawal receipt
         */
        $sql = "SELECT fk_facture";
        if ($amounts)
        {
            $sql .= ", SUM(pl.amount)";
            $sql .= ", pf.fk_prelevement_lignes";
        }
        $sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
        $sql.= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
        $sql.= " , ".MAIN_DB_PREFIX."prelevement_facture as pf";
        $sql.= " WHERE pf.fk_prelevement_lignes = pl.rowid";
        $sql.= " AND pl.fk_prelevement_bons = p.rowid";
        $sql.= " AND p.rowid = ".$this->id;
        $sql.= " AND p.entity = ".$conf->entity;
        if ($amounts) $sql.= " GROUP BY fk_facture";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);

            if ($num)
            {
                $i = 0;
                while ($i < $num)
                {
                    $row = $this->db->fetch_row($resql);
                    if (!$amounts) $arr[$i] = $row[0];
                    else
                    {
                        $arr[$i] = array(
                            $row[0],
                            $row[1],
                            $row[2]
                        );
                    }
                    $i++;
                }
            }
            $this->db->free($resql);
        }
        else
        {
            dol_syslog(get_class($this)."::getListInvoices Erreur");
        }

        return $arr;
    }
}
