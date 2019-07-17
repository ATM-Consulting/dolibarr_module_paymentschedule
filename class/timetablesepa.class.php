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


class TimetableSEPA extends SeedObject
{
    /**
     * Draft status
     */
    const STATUS_DRAFT = 0;
	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;

	/** @var array $TStatus Array of translate key for each const */
	public static $TStatus = array(
		self::STATUS_DRAFT => 'timetableSEPAStatusDraftShort'
		,self::STATUS_VALIDATED => 'timetableSEPAStatusValidatedShort'
	);


	/** @var string $table_element Table name in SQL */
	public $table_element = 'timetablesepa';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'timetablesepa';

	/** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 0;

    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
    public $ismultientitymanaged = 1;

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


    public $childtables = array('timetablesepadet'=>'TimetableSEPADet');

    public $fk_element = 'fk_timetable';

    /** @var TimetableSEPADet[] $TTimetableSEPADet */
    public $TTimetableSEPADet = array();

    /**
     * timetableSEPA constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
        global $langs;

		$this->db = $db;

		$this->init();

		$this->status = self::STATUS_DRAFT;

		$this->fields['periodicity_unit']['arrayofkeyval'] = array(
		    self::PERIODICITY_VALUE_DAY => $langs->trans('timetablesepa_periodicityDay')
            , self::PERIODICITY_VALUE_MONTH => $langs->trans('timetablesepa_periodicityMonth')
            , self::PERIODICITY_VALUE_YEAR => $langs->trans('timetablesepa_periodicityYear')
        );
    }

    /**
     * @param User $user User object
     * @return int
     */
    public function save($user)
    {
        if (!empty($this->is_clone)) {}

        return $this->create($user);
    }


    /**
     * @see cloneObject
     * @return void
     */
    public function clearUniqueFields()
    {

    }


    /**
     * @param User $user User object
     * @return int
     */
    public function delete(User &$user)
    {
        $this->deleteObjectLinked();

        unset($this->fk_element); // avoid conflict with standard Dolibarr comportment
        return parent::delete($user);
    }


    /**
     * @param User  $user   User object
     * @return int
     */
    public function setDraft($user)
    {
        if ($this->status === self::STATUS_VALIDATED)
        {
            $this->status = self::STATUS_DRAFT;
            $this->withChild = false;

            return $this->update($user);
        }

        return 0;
    }

    /**
     * @param User  $user   User object
     * @return int
     */
    public function setValid($user)
    {
        if ($this->status === self::STATUS_DRAFT)
        {
//            $this->fk_user_valid = $user->id;
            $this->status = self::STATUS_VALIDATED;
            $this->withChild = false;

            return $this->update($user);
        }

        return 0;
    }


    /**
     * @param User  $user   User object
     * @return int
     */
    public function setReopen($user)
    {
        if ($this->status === self::STATUS_ACCEPTED || $this->status === self::STATUS_REFUSED)
        {
            $this->status = self::STATUS_VALIDATED;
            $this->withChild = false;

            return $this->update($user);
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

		$langs->load('timetablesepa@timetablesepa');
        $res = '';

        if ($status==self::STATUS_DRAFT) { $statusType='status6'; $statusLabel=$langs->trans('timetableSEPAStatusDraft'); $statusLabelShort=$langs->trans('timetableSEPAStatusDraftShort'); }
        elseif ($status==self::STATUS_VALIDATED) { $statusType='status4'; $statusLabel=$langs->trans('timetableSEPAStatusValidated'); $statusLabelShort=$langs->trans('timetableSEPAStatusValidateShort'); }

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

		$langs->load('timetablesepa@timetablesepa');

        $TRestrictMessage = array();

        if (empty($user->rights->timetablesepa->write)) $TRestrictMessage[] = $langs->trans('CheckErrorInvoiceInsufficientPermission');

        if ($facture->statut == Facture::STATUS_DRAFT) $TRestrictMessage[] = $langs->trans('CheckErrorInvoiceIsDraft');

        if (empty($conf->global->TIMETABLESEPA_MODE_REGLEMENT_TO_USE) || $conf->global->TIMETABLESEPA_MODE_REGLEMENT_TO_USE != $facture->mode_reglement_id) $TRestrictMessage[] = $langs->trans('CheckErrorModeRgltNotMatch');

        $echeancier = new TimetableSEPA($facture->db);
        $echeancier->fetchBy($facture->id, 'fk_facture');
        if (!empty($echeancier->id)) $TRestrictMessage[] = $langs->trans('CheckErrorTimetableAlreadyExists');

		if (empty($facture->array_options)) $facture->fetch_optionals();

		if (empty($facture->linkedObjects)) $facture->fetchObjectLinked();

		// vérifier qu'on a bien l'extrafield isecheancier à true
		if (empty($facture->array_options['options_isecheancier']))
		{
            $TRestrictMessage[] = $langs->trans('CheckErrorIsNotTimetible');
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
        require_once DOL_DOCUMENT_ROOT.'/societe/class/companypaymentmode.class.php';
        $companypaymentmode = new CompanyPaymentMode($facture->db);
        if ($companypaymentmode->fetch(null, null, $facture->socid) <= 0)
        {
            $TRestrictMessage[] = $langs->trans('CheckErrorCustomerHasNoIBAN');
        }

		return $TRestrictMessage;
	}

	/**
	 * Crée l'objet échéancier depuis la facture en récupérant les infos du contrat lié
	 *
	 * @param Facture $facture
	 *
	 * @return int <0 if KO, > 0 if OK
	 */
	public function createFromFacture($facture, $date_start, $periodicity_unit, $periodicity_value, $nb_term)
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

            $res = $this->save($user);
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

		$langs->load('timetablesepa@timetablesepa');

		$start = $this->date_start;

		if ((empty($this->date_start) && $this->date_start !== 0) || empty($this->fk_facture) || $this->fk_facture < 0)
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
		}

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
            foreach ($this->TTimetableSEPADet as $det)
            {
                if ($det->status == TimetableSEPADet::STATUS_WAITING) $det->delete($user);
            }
        }

		// on crée les lignes d'échéance SEPA (base des demandes de prélévement généré en auto)
		foreach ($TDatesEcheance as $i => $time)
		{
		    $k = $this->addChild('TimetableSEPADet');
			$det = $this->TTimetableSEPADet[$k];
			$det->fk_timetable = $this->id;

			$facnumber = ((float) DOL_VERSION < 9.0 ) ? $facture->facnumber : $facture->ref;
			// {INDICE} {SOCNAME} - {FACNUMBER} {REFCLIENT}
            if (!empty($conf->global->TIMETABLESEPA_LABEL_PATTERN))
            {
                $det->label = $conf->global->TIMETABLESEPA_LABEL_PATTERN;
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

            if ($fill_amount === 'onfirst' && $i == 0)
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

		return count($this->TTimetableSEPADet);
	}
}


class TimetableSEPADet extends SeedObject
{
    /**
     * Waiting status
     */
    const STATUS_WAITING = 0;
    /**
     * Validated status
     */
    const STATUS_IN_PROCESS = 1;
    /**
     * Accepted status
     */
    const STATUS_ACCEPTED = 2;
    /**
     * Refused status
     */
    const STATUS_REFUSED = -1;

    public static $TStatusTransKey = array(
        self::STATUS_WAITING => 'TimetableSEPADetStatusWaiting'
        , self::STATUS_IN_PROCESS => 'TimetableSEPADetStatusInProcess'
        , self::STATUS_ACCEPTED => 'TimetableSEPADetStatusAccepted'
        , self::STATUS_REFUSED => 'TimetableSEPADetStatusRefused'
    );

    public $table_element = 'timetablesepadet';

    public $element = 'timetablesepadet';

	public $fields = array(
		'fk_timetable'	=>	array('type'=>'integer', 'index'=>1)
		,'status'	    =>	array('type'=>'integer', 'notnull' => 1, 'default' => 0)
		,'label'		=>  array('type'=>'varchar(50)', 'length'=>50)
		,'date_demande'	=> 	array('type'=>'date')
		,'amount_ht'	=> 	array('type'=>'double')
		,'amount_tva'	=> 	array('type'=>'double')
		,'amount_ttc'	=> 	array('type'=>'double')
	);

	public $fk_timetable;
	public $status;
	public $label;
	public $date_demande;
	public $amount_ht;
	public $amount_tva;
	public $amount_ttc;

    /**
     * timetableSEPADet constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->init();
    }

	/**
	 * @param User $user User object
	 * @return int
	 */
	public function delete(User &$user)
	{
		$this->deleteObjectLinked();

//		unset($this->fk_element);
		return parent::delete($user);
	}

    /**
     * @param User  $user   User object
     * @return int
     */
    public function setWaiting($user)
    {
        $this->status = self::STATUS_WAITING;
        $this->withChild = false;

        return $this->update($user);
    }

    /**
     * @param User  $user   User object
     * @return int
     */
    public function setInProcess($user)
    {
        $this->status = self::STATUS_IN_PROCESS;
        $this->withChild = false;

        return $this->update($user);
    }

    /**
     * @param User  $user   User object
     * @return int
     */
    public function setAccepted($user)
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->withChild = false;

        return $this->update($user);
    }

    /**
     * @param User  $user   User object
     * @return int
     */
    public function setRefused($user)
    {
        $this->status = self::STATUS_REFUSED;
        $this->withChild = false;

        return $this->update($user);
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

        $langs->load('timetablesepa@timetablesepa');
        $res = '';

        $statusLabel=$langs->trans(self::$TStatusTransKey[$status]);
        $statusLabelShort=$langs->trans(self::$TStatusTransKey[$status]);

        if ($status==self::STATUS_WAITING) $statusType='status6';
        elseif ($status==self::STATUS_IN_PROCESS) $statusType='status1';
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
}
