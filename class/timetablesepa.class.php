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


class timetableSEPA extends SeedObject
{
    /**
     * Canceled status
     */
    const STATUS_CANCELED = -1;
    /**
     * Draft status
     */
    const STATUS_DRAFT = 0;
	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;
	/**
	 * Refused status
	 */
	const STATUS_REFUSED = 3;
	/**
	 * Accepted status
	 */
	const STATUS_ACCEPTED = 4;

	/** @var array $TStatus Array of translate key for each const */
	public static $TStatus = array(
		self::STATUS_CANCELED => 'timetableSEPAStatusCanceledShort'
		,self::STATUS_DRAFT => 'timetableSEPAStatusDraftShort'
		,self::STATUS_VALIDATED => 'timetableSEPAStatusValidatedShort'
//		,self::STATUS_REFUSED => 'timetableSEPAStatusRefusedShort'
//		,self::STATUS_ACCEPTED => 'timetableSEPAStatusAcceptedShort'
	);

	const PERIODE_DAYS = 1;
	const PERIODE_MONTH = 2;
	const PERIODE_YEAR = 3;

	public $TPeriodicite = array(
		self::PERIODE_DAYS => 'days'
		,self::PERIODE_MONTH=>'month'
		,self::PERIODE_YEAR=>'year'
	);

	/** @var string $table_element Table name in SQL */
	public $table_element = 'timetablesepa';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'timetablesepa';

	/** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 1;

    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
    public $ismultientitymanaged = 1;


    public $fields = array(
		'ref'           =>array('type'=>'varchar(50)',  'length'=>50, 'label'=>'Ref','enabled'=>1, 'visible'=>1,  'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1, 'comment'=>'Reference of object'),
	    'entity'        =>array('type'=>'integer',      'label'=>'Entity',           'enabled'=>1, 'visible'=>0,  'default'=>1, 'notnull'=>1,  'index'=>1, 'position'=>20),
	    'status'        =>array('type'=>'integer',      'label'=>'Status',           'enabled'=>1, 'visible'=>0,  'notnull'=>1, 'default'=>0, 'index'=>1,  'position'=>30, 'arrayofkeyval'=>array(0=>'Draft', 1=>'Active', -1=>'Canceled')),
	    'label'         =>array('type'=>'varchar(255)', 'label'=>'Label',            'enabled'=>1, 'visible'=>1,  'position'=>40,  'searchall'=>1, 'css'=>'minwidth200', 'help'=>'Help text', 'showoncombobox'=>1),
		'fk_facture' 	=>array('type'=>'integer:Facture:compta/facture/class/facture.class.php', 'label'=>'Invoice', 'visible'=>1, 'enabled'=>1, 'position'=>50, 'index'=>1, 'help'=>'LinkToInvoice'),
		'date_start' 	=>array('type'=>'date'),
		'date_end' 		=>array('type'=>'date'),
		'fk_periodicite'=>array('type'=>'integer', 		'label'=>'Periodicite'),
		'nb_periodes'	=>array('type'=>'integer'),
		'description'   =>array('type'=>'text',			'label'=>'Description',		 'enabled'=>1, 'visible'=>0,  'position'=>60),
		//'fk_user_valid' =>array('type'=>'integer',      'label'=>'UserValidation',        'enabled'=>1, 'visible'=>-1, 'position'=>512),
		'import_key'    =>array('type'=>'varchar(14)',  'label'=>'ImportId',         'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'index'=>0,  'position'=>1000),
    );

    /** @var string $ref Object reference */
	public $ref;

    /** @var int $entity Object entity */
	public $entity;

	/** @var int $status Object status */
	public $status;

    /** @var string $label Object label */
    public $label;

    /** @var int $fk_facture Object link to invoice */
    public $fk_facture;

    /** @var integer timetable periodicite */
    public $fk_periodicite;

    public $nb_periodes;

    /** @var integer nombre d'échéances (calculé) */
    public $nb_echeances;



    /**
     * timetableSEPA constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
		global $conf;

		$this->db = $db;

		$this->init();

		$this->status = self::STATUS_DRAFT;
		$this->entity = $conf->entity;
    }

    /**
     * @param User $user User object
     * @return int
     */
    public function save($user)
    {
        if (!empty($this->is_clone))
        {
            // TODO determinate if auto generate
            $this->ref = '(PROV'.$this->id.')';
        }
		$this->ref = '(PROV'.$this->id.')';
        return $this->create($user);
    }


    /**
     * @see cloneObject
     * @return void
     */
    public function clearUniqueFields()
    {
        $this->ref = 'Copy of '.$this->ref;
    }


    /**
     * @param User $user User object
     * @return int
     */
    public function delete(User &$user)
    {
        $this->deleteObjectLinked();

        return parent::delete($user);
    }

    /**
     * @return string
     */
    public function getRef()
    {
		if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))
		{
			return $this->getNextRef();
		}

		return $this->ref;
    }

    /**
     * @return string
     */
    private function getNextRef()
    {
		global $db,$conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$mask = !empty($conf->global->MYMODULE_REF_MASK) ? $conf->global->MYMODULE_REF_MASK : 'MM{yy}{mm}-{0000}';
		$ref = get_next_value($db, $mask, 'timetablesepa', 'ref');

		return $ref;
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
            // TODO determinate if auto generate
//            $this->ref = $this->getRef();
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
    public function setAccepted($user)
    {
        if ($this->status === self::STATUS_VALIDATED)
        {
            $this->status = self::STATUS_ACCEPTED;
            $this->withChild = false;

            return $this->update($user);
        }

        return 0;
    }

    /**
     * @param User  $user   User object
     * @return int
     */
    public function setRefused($user)
    {
        if ($this->status === self::STATUS_VALIDATED)
        {
            $this->status = self::STATUS_REFUSED;
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
     * @param int    $withpicto     Add picto into link
     * @param string $moreparams    Add more parameters in the URL
     * @return string
     */
    public function getNomUrl($withpicto = 0, $moreparams = '')
    {
		global $langs;

        $result='';
        $label = '<u>' . $langs->trans("ShowtimetableSEPA") . '</u>';
        if (! empty($this->ref)) $label.= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

        $linkclose = '" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $link = '<a href="'.dol_buildpath('/timetablesepa/card.php', 1).'?id='.$this->id.urlencode($moreparams).$linkclose;

        $linkend='</a>';

        $picto='generic';
//        $picto='timetablesepa@timetablesepa';

        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';

        $result.=$link.$this->ref.$linkend;

        return $result;
    }

    /**
     * @param int       $id             Identifiant
     * @param null      $ref            Ref
     * @param int       $withpicto      Add picto into link
     * @param string    $moreparams     Add more parameters in the URL
     * @return string
     */
    public static function getStaticNomUrl($id, $ref = null, $withpicto = 0, $moreparams = '')
    {
		global $db;

		$object = new timetableSEPA($db);
		$object->fetch($id, false, $ref);

		return $object->getNomUrl($withpicto, $moreparams);
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

        if ($status==self::STATUS_CANCELED) { $statusType='status9'; $statusLabel=$langs->trans('timetableSEPAStatusCancel'); $statusLabelShort=$langs->trans('timetableSEPAStatusShortCancel'); }
        elseif ($status==self::STATUS_DRAFT) { $statusType='status0'; $statusLabel=$langs->trans('timetableSEPAStatusDraft'); $statusLabelShort=$langs->trans('timetableSEPAStatusShortDraft'); }
        elseif ($status==self::STATUS_VALIDATED) { $statusType='status1'; $statusLabel=$langs->trans('timetableSEPAStatusValidated'); $statusLabelShort=$langs->trans('timetableSEPAStatusShortValidate'); }
        elseif ($status==self::STATUS_REFUSED) { $statusType='status5'; $statusLabel=$langs->trans('timetableSEPAStatusRefused'); $statusLabelShort=$langs->trans('timetableSEPAStatusShortRefused'); }
        elseif ($status==self::STATUS_ACCEPTED) { $statusType='status6'; $statusLabel=$langs->trans('timetableSEPAStatusAccepted'); $statusLabelShort=$langs->trans('timetableSEPAStatusShortAccepted'); }

        if (function_exists('dolGetStatus'))
        {
            $res = dolGetStatus($statusLabel, $statusLabelShort, '', $statusType, $mode);
        }
        else
        {
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
	 * @param Facture $facture
	 *
	 * @return array array(bool, array(msgs))
	 */
    public static function checkFacture(Facture &$facture)
	{
		global $langs;
		$langs->load('timetablesepa@timetablesepa');

		$ret = true;
		$msgs = array();

		if (empty($facture->array_options))
		{
			$facture->fetch_optionals();
		}

		if (empty($facture->linkedObjects))
		{
			$facture->fetchObjectLinked();
		}

		// vérifier qu'on a bien l'extrafield isecheancier à true
		if (empty($facture->array_options['options_isecheancier']))
		{
			$ret = false;
			$msgs[] = $langs->trans('CheckErrorIsNotTimetible');
		}

		// vérifier qu'on a bien un contrat lié à cette facture avec au moins une ligne active
		if (array_key_exists('contrat', $facture->linkedObjects))
		{
			// si y a un contrat, on valide qu'il y a une ligne active sur ce contrat
			// je prend le premier qui vient pour test
			$keys = array_keys($facture->linkedObjects['contrat']);
			$contrat = &$facture->linkedObjects['contrat'][$keys[0]];

			if ($contrat->nbofservicesopened == 0)
			{
				$ret = false;
				$msgs[] = $langs->trans('CheckErrorNoActiveLineOnContract');
			}
		}
		else
		{
			$ret = false;
			$msgs[] = $langs->trans('CheckErrorIsNotLinkedToContract');
		}

		return array($ret, $msgs);
	}

	/**
	 * Crée l'objet échéancier depuis la facture en récupérant les infos du contrat lié
	 *
	 * @param Facture $facture
	 *
	 * @return int <0 if KO, > 0 if OK
	 */
	public function createFromFacture(Facture &$facture)
	{
		global $user;

		// check la facture
		list($isOK, $errors) = $this->checkFacture($facture);
		if (!$isOK)
		{
			setEventMessages('Impossible de créer l\'échéancier',$errors, 'errors');
			return -1;
		}
		else
		{
			$this->fk_facture = $facture->id;

			// récupérer le contrat lié à la facture
			if (empty($facture->linkedObjects))
			{
				$facture->fetchObjectLinked();
			}

			$keys = array_keys($facture->linkedObjects['contrat']);
			$contrat = &$facture->linkedObjects['contrat'][$keys[0]];

			// TODO on cherche une ligne active pour le moment, je ne connais pas la structure finale de cette partie
			foreach($contrat->lines as $line)
			{
				// récupérer la périodicité du contrat + date début + date fin
				if ($line->statut == 4){
					$this->date_start = $line->date_start;
					$this->date_end = $line->date_end;
					$this->fk_periodicite = $line->array_options['options_periodicite'];
					$this->nb_periodes = $line->array_options['options_nb_periodes'];

					// TODO remove ajouté en dure pour les tests
					$this->date_start = 1561932000;
					$this->date_end = 1593511200;
					$this->fk_periodicite = 2;
					$this->nb_periodes = 3;

					break;
				}
			}

			// calculer le nombre d'échéances
			$start = $this->date_start;
			$end = $this->date_end;

			$this->nb_echeances = 1;
			$cpt = 0;
			while ($start < $end && $cpt < 50)
			{
				$start = strtotime('+ '.$this->nb_periodes.' '.$this->TPeriodicite[$this->fk_periodicite], $start);

				if ($start < $end) $this->nb_echeances++;

				$cpt++;
			}

			$ret = $this->save($user);
			var_dump($ret, $cpt, $this->nb_echeances, $start, $end, $this->TPeriodicite); exit;

		}



		// si la facture est en brouillon et qu'aucune ligne n'est liée à un prélèvement SEPA, initDetailEcheancier($rest = false)
	}

	public function initDetailEcheancier($reset = false)
	{
		// si reset à true on supprime toutes les lignes avant de les recréer

		// on crée les ligne d'échéance SEPA
	}
}


//class timetableSEPADet extends SeedObject
//{
//    public $table_element = 'timetablesepadet';
//
//    public $element = 'timetablesepadet';
//
//
//    /**
//     * timetableSEPADet constructor.
//     * @param DoliDB    $db    Database connector
//     */
//    public function __construct($db)
//    {
//        $this->db = $db;
//
//        $this->init();
//    }
//}
