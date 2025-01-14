<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2015 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2012 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Florian Henry         <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Jean-François Ferry   <jfefe@aternatik.fr>
 * Copyright (C) 2015-2016 Ferran Marcet         <fmarcet@2byte.es>
 * Copyright (C) 2017      Josep Lluís Amador    <joseplluis@lliuretic.cat>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/compta/facture/list.php
 *	\ingroup    facture
 *	\brief      List of customer invoices
 */

require __DIR__ . '/config.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
if (isModEnabled('commande')) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
dol_include_once('/paymentschedule/class/paymentschedule.class.php');

// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies', 'products', 'categories', 'paymentschedule@paymentschedule'));

$search_all = trim(GETPOSTISSET('search_all') ? GETPOST('search_all', 'alphanohtml') :  GETPOST('sall', 'alphanohtml') );

$projectid=(GETPOST('projectid', 'int')?GETPOST('projectid','int'):0);

$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alphanohtml');
$socid=GETPOST('socid','int');

$action=GETPOST('action','alphanohtml');
$massaction=GETPOST('massaction','alphanohtml');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alphanohtml');
$toselect = GETPOST('toselect', 'array');
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'invoicelist';

$lineid=GETPOST('lineid','int');
$userid=GETPOST('userid','int');
$search_product_category=GETPOST('search_product_category','int');
$search_ref=GETPOST('sf_ref', 'alphanohtml')?GETPOST('sf_ref','alphanohtml'):GETPOST('search_ref','alphanohtml');
$search_refcustomer=GETPOST('search_refcustomer','alphanohtml');
$search_type=GETPOST('search_type','int');
$search_project=GETPOST('search_project','alphanohtml');
$search_societe=GETPOST('search_societe','alphanohtml');
$search_montant_ht=GETPOST('search_montant_ht','alphanohtml');
$search_montant_vat=GETPOST('search_montant_vat','alphanohtml');
$search_montant_localtax1=GETPOST('search_montant_localtax1','alphanohtml');
$search_montant_localtax2=GETPOST('search_montant_localtax2','alphanohtml');
$search_montant_ttc=GETPOST('search_montant_ttc','alphanohtml');
$search_status=GETPOST('search_status','intcomma');
$search_paymentmode=GETPOST('search_paymentmode','int');
$search_town=GETPOST('search_town','alphanohtml');
$search_zip=GETPOST('search_zip','alphanohtml');
$search_state=trim(GETPOST("search_state", 'alphanohtml'));
$search_country=GETPOST("search_country",'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty",'int');
$search_user = GETPOST('search_user','int');
$search_sale = GETPOST('search_sale','int');
$search_day		= GETPOST('search_day','int');
$search_month	= GETPOST('search_month','int');
$search_year	= GETPOST('search_year','int');
$search_day_lim		= GETPOST('search_day_lim','int');
$search_month_lim	= GETPOST('search_month_lim','int');
$search_year_lim	= GETPOST('search_year_lim','int');
$search_categ_cus=trim(GETPOST("search_categ_cus",'int'));
$search_btn=GETPOST('button_search','alphanohtml');
$search_remove_btn=GETPOST('button_removefilter','alphanohtml');
$search_status_schedule=GETPOST('search_status_schedule', 'int');

$option = GETPOST('search_option', 'alphanohtml');
if ($option == 'late') {
    $search_status = '1';
}
$filtre	= GETPOST('filtre','alphanohtml');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alphanohtml');
$sortorder = GETPOST("sortorder",'alphanohtml');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
if (! $sortorder && getDolGlobalString('INVOICE_DEFAULT_UNPAYED_SORT_ORDER') && $search_status == '1') $sortorder=getDolGlobalString('INVOICE_DEFAULT_UNPAYED_SORT_ORDER');
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='f.datef';
$pageprev = $page - 1;
$pagenext = $page + 1;

// Security check
$fieldRefFacture = 'ref';
$fieldid = (! empty($ref)?$fieldRefFacture:'rowid');
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $id,'','','fk_soc',$fieldid);

$diroutputmassaction=$conf->paymentschedule->dir_output . '/temp/massgeneration/'.$user->id;


// COMPATIBILITY VERSION < 14
$invoiceTotalHtField = 'total_ht';
$invoiceTotalVatField = 'total_tva';
$now=dol_now();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Facture($db);
$hookmanager->initHooks(array('paymentschedulelist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('paymentschedule');
$search_array_options=$extrafields->getOptionalsFromPost('paymentschedule','','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'f.'.$fieldRefFacture=>'Ref',
    'f.ref_client'=>'RefCustomer',
    'pd.description'=>'Description',
    's.nom'=>"ThirdParty",
    'f.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["f.note_private"]="NotePrivate";

$checkedtypetiers=0;
$arrayfields=array(
    'f.'.$fieldRefFacture=>array('label'=>"Ref", 'checked'=>1),
    'f.ref_client'=>array('label'=>"RefCustomer", 'checked'=>1),
    'f.type'=>array('label'=>"Type", 'checked'=>0),
    'f.date'=>array('label'=>"DateInvoice", 'checked'=>1),
    'f.date_lim_reglement'=>array('label'=>"DateDue", 'checked'=>1),
    'p.ref'=>array('label'=>"ProjectRef", 'checked'=>0, 'enabled'=>(!isModEnabled('projet')?0:1)),
    's.nom'=>array('label'=>"ThirdParty", 'checked'=>1),
    's.town'=>array('label'=>"Town", 'checked'=>1),
    's.zip'=>array('label'=>"Zip", 'checked'=>1),
    'state.nom'=>array('label'=>"StateShort", 'checked'=>0),
    'country.code_iso'=>array('label'=>"Country", 'checked'=>0),
    'typent.code'=>array('label'=>"ThirdPartyType", 'checked'=>$checkedtypetiers),
    'f.fk_mode_reglement'=>array('label'=>"InvoicePaymentMode", 'checked'=>1),
    'f.total_ht'=>array('label'=>"AmountHT", 'checked'=>1),
    'f.total_vat'=>array('label'=>"AmountVAT", 'checked'=>0),
    'f.total_localtax1'=>array('label'=>$langs->transcountry("AmountLT1", $mysoc->country_code), 'checked'=>0, 'enabled'=>($mysoc->localtax1_assuj=="1")),
    'f.total_localtax2'=>array('label'=>$langs->transcountry("AmountLT2", $mysoc->country_code), 'checked'=>0, 'enabled'=>($mysoc->localtax2_assuj=="1")),
    'f.total_ttc'=>array('label'=>"AmountTTC", 'checked'=>0),
    'dynamount_payed'=>array('label'=>"Received", 'checked'=>0),
    'rtp'=>array('label'=>"Rest", 'checked'=>0),
    'ps.status'=>array('label'=>'PaymentScheduleStatus', 'checked' => 1),
    'ps.date_start'=>array('label'=>'DateStart', 'checked'=> 1),
    'ps.periodicity_value'=>array('label'=>'PeriodicityValue', 'checked' => 1),
    'ps.periodicity_unit'=>array('label' =>'PeriodicityUnit', 'checked' => 1),
    'ps.nb_term'=>array('label'=>'NbTerm', 'checked' => 1),
    'f.datec'=>array('label'=>"DateCreation", 'checked'=>0, 'position'=>500),
    'f.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>500),
    'f.fk_statut'=>array('label'=>"InvoiceStatus", 'checked'=>1, 'position'=>1000),
);

if(floatval(DOL_VERSION) >= 17) {
    $extrafields->attribute_type= $extrafields->attributes['paymentschedule']['type'] ?? array();
    $extrafields->attribute_size= $extrafields->attributes['paymentschedule']['size'] ?? array();
    $extrafields->attribute_unique= $extrafields->attributes['paymentschedule']['unique'] ?? array();
    $extrafields->attribute_required= $extrafields->attributes['paymentschedule']['required'] ?? array();
    $extrafields->attribute_label= $extrafields->attributes['paymentschedule']['label'] ?? array();
}
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
        if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
    }
}


/*
 * Actions
 */

if (GETPOST('cancel','alphanohtml')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alphanohtml') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x','alphanohtml') || GETPOST('button_removefilter','alphanohtml') || GETPOST('button_removefilter.x','alphanohtml')) // All tests are required to be compatible with all browsers
{
    $search_user='';
    $search_sale='';
    $search_product_category='';
    $search_ref='';
    $search_refcustomer='';
    $search_type='';
    $search_project='';
    $search_societe='';
    $search_montant_ht='';
    $search_montant_vat='';
    $search_montant_localtax1='';
    $search_montant_localtax2='';
    $search_montant_ttc='';
    $search_status='';
    $search_paymentmode='';
    $search_town='';
    $search_zip="";
    $search_state="";
    $search_type='';
    $search_country='';
    $search_type_thirdparty='';
    $search_day='';
    $search_year='';
    $search_month='';
    $option='';
    $filter='';
    $search_day_lim='';
    $search_year_lim='';
    $search_month_lim='';
    $toselect='';
    $search_array_options=array();
    $search_categ_cus=0;
    $search_status_schedule='';
}

if (empty($reshook))
{
    $objectclass='PaymentSchedule';
    $objectlabel='PaymentSchedule';
    $permtoread = $user->hasRight('paymentschedule','read');
    $permtocreate = $user->hasRight('paymentschedule','write');
    $permtodelete = $user->hasRight('paymentschedule','delete');
    $uploaddir = $conf->paymentschedule->dir_output;
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$bankaccountstatic=new Account($db);
$facturestatic=new Facture($db);
$formcompany=new FormCompany($db);
$thirdpartystatic=new Societe($db);
$scheduleStatic=new PaymentSchedule($db);

$sql = 'SELECT';
if ($search_all || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' f.rowid as id, f.'.$fieldRefFacture.' as ref, f.ref_client, f.type, f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.'.$invoiceTotalHtField.' as total_ht, f.'.$invoiceTotalVatField.' as total_vat, f.total_ttc,';
$sql.= ' f.localtax1 as total_localtax1, f.localtax2 as total_localtax2,';
$sql.= ' f.datef as df, f.date_lim_reglement as datelimite,';
$sql.= ' f.paye as paye, f.fk_statut,';
$sql.= ' f.datec as date_creation, f.tms as date_modification,';
$sql.= ' s.rowid as socid, s.nom as name, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur,';
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name,";
$sql.= " country.code as country_code,";
$sql.= " p.rowid as project_id, p.ref as project_ref, p.title as project_label";
$sql.= " , ps.status as ps_status, ps.date_start, ps.periodicity_value, ps.periodicity_unit, ps.nb_term";
// We need dynamount_payed to be able to sort on status (value is surely wrong because we can count several lines several times due to other left join or link with contacts. But what we need is just 0 or > 0)
// TODO Better solution to be able to sort on already payed or remain to pay is to store amount_payed in a denormalized field.
if (! $search_all) $sql.= ', SUM(pf.amount) as dynamount_payed';
if ($search_categ_cus) $sql .= ", cc.fk_categorie, cc.fk_soc";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
if (! empty($search_categ_cus)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cc ON s.rowid = cc.fk_soc"; // We'll need this table joined to the select in order to filter by categ

$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture as f ON (f.fk_soc = s.rowid AND f.entity IN ('.getEntity('facture').'))';
$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'paymentschedule as ps ON (f.rowid = ps.fk_facture)';
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paymentschedule_extrafields as ef on (ps.rowid = ef.fk_object)";
if (! $search_all) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
if ($search_all || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as pd ON f.rowid=pd.fk_facture';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = f.fk_projet";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->hasRight('societe', 'client', 'voir') && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE 1=1';

if (! $user->hasRight('societe', 'client', 'voir') && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$db->escape($search_product_category);
if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
if ($userid)
{
    if ($userid == -1) $sql.=' AND f.fk_user_author IS NULL';
    else $sql.=' AND f.fk_user_author = '.$userid;
}
if ($filtre)
{
    $aFilter = explode(',', $filtre);
    foreach ($aFilter as $filter)
    {
        $filt = explode(':', $filter);
        $sql .= ' AND ' . $db->escape(trim($filt[0])) . ' = ' . $db->escape(trim($filt[1]));
    }
}
if ($search_ref) $sql .= natural_search('f.'.$fieldRefFacture, $search_ref);
if ($search_refcustomer) $sql .= natural_search('f.ref_client', $search_refcustomer);
if ($search_type != '' && $search_type != '-1') $sql.=" AND f.type IN (".$db->escape($search_type).")";
if ($search_project) $sql .= natural_search('p.ref', $search_project);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_town)  $sql.= natural_search('s.town', $search_town);
if ($search_zip)   $sql.= natural_search("s.zip",$search_zip);
if ($search_state) $sql.= natural_search("state.nom",$search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$db->escape($search_country).')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$db->escape($search_type_thirdparty).')';
if ($search_montant_ht != '') $sql.= natural_search('f.'.$invoiceTotalHtField, $search_montant_ht, 1);
if ($search_montant_vat != '') $sql.= natural_search('f.'.$invoiceTotalVatField, $search_montant_vat, 1);
if ($search_montant_localtax1 != '') $sql.= natural_search('f.localtax1', $search_montant_localtax1, 1);
if ($search_montant_localtax2 != '') $sql.= natural_search('f.localtax2', $search_montant_localtax2, 1);
if ($search_montant_ttc != '') $sql.= natural_search('f.total_ttc', $search_montant_ttc, 1);
if ($search_categ_cus > 0) $sql.= " AND cc.fk_categorie = ".$db->escape($search_categ_cus);
if ($search_categ_cus == -2)   $sql.= " AND cc.fk_categorie IS NULL";
if ($search_status != '-1' && $search_status != '')
{
    if (is_numeric($search_status) && $search_status >= 0)
    {
        if ($search_status == '0') $sql.=" AND f.fk_statut = 0";  // draft
        if ($search_status == '1') $sql.=" AND f.fk_statut = 1";  // unpayed
        if ($search_status == '2') $sql.=" AND f.fk_statut = 2";  // payed     Not that some corrupted data may contains f.fk_statut = 1 AND f.paye = 1 (it means payed too but should not happend. If yes, reopen and reclassify billed)
        if ($search_status == '3') $sql.=" AND f.fk_statut = 3";  // abandonned
    }
    else
    {
        $sql.= " AND f.fk_statut IN (".$search_status.")";	// When search_status is '1,2' for example
    }
}
if ($search_status_schedule != '-1' && $search_status_schedule != '')
{
    if (is_numeric($search_status_schedule) && $search_status_schedule >= 0)
    {
        if ($search_status_schedule == '0') $sql.=" AND ps.status = 0";  // draft
        if ($search_status_schedule == '1') $sql.=" AND ps.status = 1";  // validated
        if ($search_status_schedule == '2') $sql.=" AND ps.status = 2";  // closed     Not that some corrupted data may contains f.fk_statut = 1 AND f.paye = 1 (it means payed too but should not happend. If yes, reopen and reclassify billed)
    }
    else
    {
        $sql.= " AND ps.status IN (".$search_status.")";	// When search_status is '1,2' for example
    }
}

if ($search_paymentmode > 0) $sql .= " AND f.fk_mode_reglement = ".$db->escape($search_paymentmode);
if ($search_month > 0)
{
    if ($search_year > 0 && empty($search_day))
        $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($search_year,$search_month,false))."' AND '".$db->idate(dol_get_last_day($search_year,$search_month,false))."'";
    else if ($search_year > 0 && ! empty($search_day))
        $sql.= " AND f.datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_month, $search_day, $search_year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_month, $search_day, $search_year))."'";
    else
        $sql.= " AND date_format(f.datef, '%m') = '".$search_month."'";
}
else if ($search_year > 0)
{
    $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($search_year,1,false))."' AND '".$db->idate(dol_get_last_day($search_year,12,false))."'";
}
if ($search_month_lim > 0)
{
    if ($search_year_lim > 0 && empty($search_day_lim))
        $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($search_year_lim,$search_month_lim,false))."' AND '".$db->idate(dol_get_last_day($search_year_lim,$search_month_lim,false))."'";
    else if ($search_year_lim > 0 && ! empty($search_day_lim))
        $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_month_lim, $search_day_lim, $search_year_lim))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_month_lim, $search_day_lim, $search_year_lim))."'";
    else
        $sql.= " AND date_format(f.date_lim_reglement, '%m') = '".$db->escape($search_month_lim)."'";
}
else if ($search_year_lim > 0)
{
    $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($search_year_lim,1,false))."' AND '".$db->idate(dol_get_last_day($search_year_lim,12,false))."'";
}
if ($option == 'late') $sql.=" AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->client->warning_delay)."'";
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
    $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
}
// Add where from extra fields
$extrafieldsobjectkey='paymentschedule';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

if (! $search_all)
{



    $sql.= ' GROUP BY f.rowid, f.'.$fieldRefFacture.', ref_client, f.type, f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.'.$invoiceTotalHtField.', f.'.$invoiceTotalVatField.', f.total_ttc,';
    $sql.= ' f.localtax1, f.localtax2,';
    $sql.= ' f.datef, f.date_lim_reglement,';
    $sql.= ' f.paye, f.fk_statut,';
    $sql.= ' f.datec, f.tms,';
    $sql.= ' s.rowid, s.nom, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur,';
    $sql.= ' typent.code,';
    $sql.= ' state.code_departement, state.nom,';
    $sql.= ' country.code,';
    $sql.= " p.rowid, p.ref, p.title,";
    $sql.= " ps.status, ps.date_start, ps.periodicity_value, ps.periodicity_unit, ps.nb_term";
    if ($search_categ_cus) $sql .= ", cc.fk_categorie, cc.fk_soc";
    // Add fields from extrafields
    foreach ($extrafields->attribute_label as $key => $val) //prevent error with sql_mode=only_full_group_by
    {
        $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key : '');
    }
}
else
{
    $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}

$sql.= ' ORDER BY ';
$listfield=explode(',',$sortfield);
$listorder=explode(',',$sortorder);
foreach ($listfield as $key => $value) $sql.= $listfield[$key].' '.($listorder[$key]?$listorder[$key]:'DESC').',';
$sql.= ' f.rowid DESC ';

$nbtotalofrecords = '';
if (!getDolGlobalString('MAIN_DISABLE_FULL_SCANLIST'))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
        $page = 0;
        $offset = 0;
    }
}

$sql.= $db->plimit($limit+1,$offset);
//print $sql;
$resql = $db->query($sql);

if ($resql)
{
    $num = $db->num_rows($resql);

    $arrayofselected=is_array($toselect)?$toselect:array();

    if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all)
    {
        $obj = $db->fetch_object($resql);
        $id = $obj->id;

        header("Location: ".DOL_URL_ROOT.'/compta/facture/card.php?facid='.$id);
        exit;
    }

    llxHeader('',$langs->trans('CustomersInvoices'),'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');

    if ($socid)
    {
        $soc = new Societe($db);
        $soc->fetch($socid);
        if (empty($search_societe)) $search_societe = $soc->name;
    }

    $param='&socid='.$socid;
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
    if ($search_all && DOL_VERSION >= 21)		$param.='&search_all='.urlencode($search_all);
	else  										$param.='&sall='.urlencode($sall);
    if ($search_day)         $param.='&search_day='.urlencode($search_day);
    if ($search_month)       $param.='&search_month='.urlencode($search_month);
    if ($search_year)        $param.='&search_year=' .urlencode($search_year);
    if ($search_day_lim)     $param.='&search_day_lim='.urlencode($search_day_lim);
    if ($search_month_lim)   $param.='&search_month_lim='.urlencode($search_month_lim);
    if ($search_year_lim)    $param.='&search_year_lim=' .urlencode($search_year_lim);
    if ($search_ref)         $param.='&search_ref=' .urlencode($search_ref);
    if ($search_refcustomer) $param.='&search_refcustomer=' .urlencode($search_refcustomer);
    if ($search_type != '')  $param.='&search_type='.urlencode($search_type);
    if ($search_societe)     $param.='&search_societe=' .urlencode($search_societe);
    if ($search_town)        $param.='&search_town='.urlencode($search_town);
    if ($search_zip)         $param.='&search_zip='.urlencode($search_zip);
    if ($search_sale > 0)    $param.='&search_sale=' .urlencode($search_sale);
    if ($search_user > 0)    $param.='&search_user=' .urlencode($search_user);
    if ($search_product_category > 0)   $param.='&search_product_category=' .urlencode($search_product_category);
    if ($search_montant_ht != '')  $param.='&search_montant_ht='.urlencode($search_montant_ht);
    if ($search_montant_vat != '')  $param.='&search_montant_vat='.urlencode($search_montant_vat);
    if ($search_montant_localtax1 != '')  $param.='&search_montant_localtax1='.urlencode($search_montant_localtax1);
    if ($search_montant_localtax2 != '')  $param.='&search_montant_localtax2='.urlencode($search_montant_localtax2);
    if ($search_montant_ttc != '') $param.='&search_montant_ttc='.urlencode($search_montant_ttc);
    if ($search_status != '') $param.='&search_status='.urlencode($search_status);
    if ($search_status_schedule != '') $param.='&search_status_schedule='.urlencode($search_status_schedule);
    if ($search_paymentmode > 0) $param.='&search_paymentmode='.urlencode($search_paymentmode);
    if ($show_files)         $param.='&show_files='.urlencode($show_files);
    if ($option)             $param.="&search_option=".urlencode($option);
    if ($search_categ_cus > 0) $param.='&search_categ_cus='.urlencode($search_categ_cus);

    // Add $param from extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

    $arrayofmassactions=array(
        'validate'=>$langs->trans("Validate"),
        'presend'=>$langs->trans("SendByMail"),
        'builddoc'=>$langs->trans("PDFMerge"),
        'generate_doc'=>$langs->trans('Generate')
    );

    if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
    $massactionbutton=$form->selectMassAction('', $arrayofmassactions);

    $newcardbutton='';

    $i = 0;
    print '<form method="POST" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
    print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

    print_barre_liste($langs->trans('PaymentScheduleList').' '.($socid?' '.$soc->name:''), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_accountancy.png', 0, $newcardbutton, '', $limit);

    $topicmail="SendBillRef";
    $modelmail="facture_send";
    $objecttmp=new PaymentSchedule($db);
    $trackid='inv'.$object->id;
    include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

    if ($search_all)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all) . join(', ',$fieldstosearchall).'</div>';
    }

    // If the user can view prospects other than his'
    $moreforfilter='';
    if ($user->hasRight('societe', 'client', 'voir') || $socid)
    {
        $langs->load("commercial");
        $moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
        $moreforfilter.=$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth200');
        $moreforfilter.='</div>';
    }
    // If the user can view prospects other than his'
    if ($user->hasRight('societe', 'client', 'voir') || $socid)
    {
        $moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.=$langs->trans('LinkedToSpecificUsers'). ': ';
        $moreforfilter.=$form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
        $moreforfilter.='</div>';
    }
    // If the user can view prospects other than his'
    if (isModEnabled('categorie') && ($user->hasRight('produit', 'lire') || $user->hasRight('service', 'lire')))
    {
        include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
        $moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.=$langs->trans('IncludingProductWithTag'). ': ';
        $cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
        $moreforfilter.=$form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, 'maxwidth300', 1);
        $moreforfilter.='</div>';
    }
    if (isModEnabled('categorie'))
    {
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
        $moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.=$langs->trans('CustomersProspectsCategoriesShort').': ';
        $moreforfilter.=$formother->select_categories('customer',$search_categ_cus,'search_categ_cus',1);
        $moreforfilter.='</div>';
    }
    $parameters=array();
    $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
    else $moreforfilter = $hookmanager->resPrint;

    if ($moreforfilter)
    {
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        print '</div>';
    }

    $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
    $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
    if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

    // Filters lines
    print '<tr class="liste_titre_filter">';
    // Ref
    if (! empty($arrayfields['f.'.$fieldRefFacture]['checked']))
    {
        print '<td class="liste_titre" align="left">';
        print '<input class="flat" size="6" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
        print '</td>';
    }
    // Ref customer
    if (! empty($arrayfields['f.ref_client']['checked']))
    {
        print '<td class="liste_titre">';
        print '<input class="flat" size="6" type="text" name="search_refcustomer" value="'.dol_escape_htmltag($search_refcustomer).'">';
        print '</td>';
    }
    // Type
    if (! empty($arrayfields['f.type']['checked']))
    {
        print '<td class="liste_titre maxwidthonsmartphone">';
        $listtype=array(
            Facture::TYPE_STANDARD=>$langs->trans("InvoiceStandard"),
            Facture::TYPE_REPLACEMENT=>$langs->trans("InvoiceReplacement"),
            Facture::TYPE_CREDIT_NOTE=>$langs->trans("InvoiceAvoir"),
            Facture::TYPE_DEPOSIT=>$langs->trans("InvoiceDeposit"),
        );
        if (getDolGlobalString('INVOICE_USE_SITUATION'))
        {
            $listtype[Facture::TYPE_SITUATION] = $langs->trans("InvoiceSituation");
        }
        //$listtype[Facture::TYPE_PROFORMA]=$langs->trans("InvoiceProForma");     // A proformat invoice is not an invoice but must be an order.
        print $form->selectarray('search_type', $listtype, $search_type, 1, 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth100');
        print '</td>';
    }
    // Date invoice
    if (! empty($arrayfields['f.date']['checked']))
    {
        print '<td class="liste_titre nowraponall" align="center">';
        if (getDolGlobalString('MAIN_LIST_FILTER_ON_DAY')) print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day" value="'.dol_escape_htmltag($search_day).'">';
        print '<input class="flat valignmiddle width25" type="text" size="1" maxlength="2" name="search_month" value="'.dol_escape_htmltag($search_month).'">';
        $formother->select_year($search_year?$search_year:-1,'search_year',1, 20, 5, 0, 0, '', 'widthauto valignmiddle');
        print '</td>';
    }
    // Date due
    if (! empty($arrayfields['f.date_lim_reglement']['checked']))
    {
        print '<td class="liste_titre nowraponall" align="center">';
        if (getDolGlobalString('MAIN_LIST_FILTER_ON_DAY')) print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day_lim" value="'.dol_escape_htmltag($search_day_lim).'">';
        print '<input class="flat valignmiddle width25" type="text" size="1" maxlength="2" name="search_month_lim" value="'.dol_escape_htmltag($search_month_lim).'">';
        $formother->select_year($search_year_lim?$search_year_lim:-1,'search_year_lim',1, 20, 5, 0, 0, '', 'widthauto valignmiddle');
        print '<br><input type="checkbox" name="search_option" value="late"'.($option == 'late'?' checked':'').'> '.$langs->trans("Alert");
        print '</td>';
    }
    // Project
    if (! empty($arrayfields['p.ref']['checked']))
    {
        print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_project" value="'.$search_project.'"></td>';
    }
    // Thirpdarty
    if (! empty($arrayfields['s.nom']['checked']))
    {
        print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_societe" value="'.$search_societe.'"></td>';
    }
    // Town
    if (! empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.dol_escape_htmltag($search_town).'"></td>';
    // Zip
    if (! empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_zip" value="'.dol_escape_htmltag($search_zip).'"></td>';
    // State
    if (! empty($arrayfields['state.nom']['checked']))
    {
        print '<td class="liste_titre">';
        print '<input class="flat" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
        print '</td>';
    }
    // Country
    if (! empty($arrayfields['country.code_iso']['checked']))
    {
        print '<td class="liste_titre" align="center">';
        print $form->select_country($search_country,'search_country','',0,'maxwidth100');
        print '</td>';
    }
    // Company type
    if (! empty($arrayfields['typent.code']['checked']))
    {
        print '<td class="liste_titre maxwidthonsmartphone" align="center">';
        print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (getDolGlobalString('SOCIETE_SORT_ON_TYPEENT','ASC')), 'maxwidth100');
        print '</td>';
    }
    // Payment mode
    if (! empty($arrayfields['f.fk_mode_reglement']['checked']))
    {
        print '<td class="liste_titre" align="left">';
        $form->select_types_paiements($search_paymentmode, 'search_paymentmode', '', 0, 1, 1, 10);
        print '</td>';
    }
    if (! empty($arrayfields['f.total_ht']['checked']))
    {
        // Amount
        print '<td class="liste_titre" align="right">';
        print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
        print '</td>';
    }
    if (! empty($arrayfields['f.total_vat']['checked']))
    {
        // Amount
        print '<td class="liste_titre" align="right">';
        print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
        print '</td>';
    }
    if (! empty($arrayfields['f.total_localtax1']['checked']))
    {
        // Localtax1
        print '<td class="liste_titre" align="right">';
        print '<input class="flat" type="text" size="5" name="search_montant_localtax1" value="'.$search_montant_localtax1.'">';
        print '</td>';
    }
    if (! empty($arrayfields['f.total_localtax2']['checked']))
    {
        // Localtax2
        print '<td class="liste_titre" align="right">';
        print '<input class="flat" type="text" size="5" name="search_montant_localtax2" value="'.$search_montant_localtax2.'">';
        print '</td>';
    }
    if (! empty($arrayfields['f.total_ttc']['checked']))
    {
        // Amount
        print '<td class="liste_titre" align="right">';
        print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
        print '</td>';
    }
    if (! empty($arrayfields['dynamount_payed']['checked']))
    {
        print '<td class="liste_titre" align="right">';
        print '</td>';
    }
    if (! empty($arrayfields['rtp']['checked']))
    {
        print '<td class="liste_titre" align="right">';
        print '</td>';
    }
    if (! empty($arrayfields['ps.status']['checked']))
    {
        print '<td class="liste_titre" align="right">';
        foreach ($scheduleStatic::$TStatus as $key => $val) $liststatus[$key] = $langs->trans($val);
        print $form->selectarray('search_status_schedule', $liststatus, $search_status_schedule, 1);
        print '</td>';
    }
    if (! empty($arrayfields['ps.date_start']['checked']))
    {
        print '<td class="liste_titre" align="right">';
        print '</td>';
    }
    if (! empty($arrayfields['ps.periodicity_value']['checked']))
    {
        print '<td class="liste_titre" align="right">';
        print '</td>';
    }
    if (! empty($arrayfields['ps.periodicity_unit']['checked']))
    {
        print '<td class="liste_titre" align="right">';
        print '</td>';
    }
    if (! empty($arrayfields['ps.nb_term']['checked']))
    {
        print '<td class="liste_titre" align="right">';
        print '</td>';
    }
    // Extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

    // Fields from hook
    $parameters=array('arrayfields'=>$arrayfields);
    $reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    // Date creation
    if (! empty($arrayfields['f.datec']['checked']))
    {
        print '<td class="liste_titre">';
        print '</td>';
    }
    // Date modification
    if (! empty($arrayfields['f.tms']['checked']))
    {
        print '<td class="liste_titre">';
        print '</td>';
    }
    // Status
    if (! empty($arrayfields['f.fk_statut']['checked']))
    {
        print '<td class="liste_titre maxwidthonsmartphone" align="right">';
        $liststatus=array('0'=>$langs->trans("BillShortStatusDraft"), '1'=>$langs->trans("BillShortStatusNotPaid"), '2'=>$langs->trans("BillShortStatusPaid"), '1,2'=>$langs->trans("BillShortStatusNotPaid").'+'.$langs->trans("BillShortStatusPaid"), '3'=>$langs->trans("BillShortStatusCanceled"));
        print $form->selectarray('search_status', $liststatus, $search_status, 1);
        print '</td>';
    }
    // Action column
    print '<td class="liste_titre" align="middle">';
    $searchpicto=$form->showFilterButtons();
    print $searchpicto;
    print '</td>';
    print "</tr>\n";

    print '<tr class="liste_titre">';
    if (! empty($arrayfields['f.'.$fieldRefFacture]['checked']))          print_liste_field_titre($arrayfields['f.'.$fieldRefFacture]['label'],$_SERVER['PHP_SELF'],'f.'.$fieldRefFacture,'',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['f.ref_client']['checked']))         print_liste_field_titre($arrayfields['f.ref_client']['label'],$_SERVER["PHP_SELF"],'f.ref_client','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['f.type']['checked']))               print_liste_field_titre($arrayfields['f.type']['label'],$_SERVER["PHP_SELF"],'f.type','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['f.date']['checked']))               print_liste_field_titre($arrayfields['f.date']['label'],$_SERVER['PHP_SELF'],'f.datef','',$param,'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.date_lim_reglement']['checked'])) print_liste_field_titre($arrayfields['f.date_lim_reglement']['label'],$_SERVER['PHP_SELF'],"f.date_lim_reglement",'',$param,'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['p.ref']['checked']))                print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER['PHP_SELF'],"p.ref",'',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['s.nom']['checked']))                print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER['PHP_SELF'],'s.nom','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['s.town']['checked']))               print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['s.zip']['checked']))                print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['state.nom']['checked']))            print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['country.code_iso']['checked']))     print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['typent.code']['checked']))          print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.fk_mode_reglement']['checked']))  print_liste_field_titre($arrayfields['f.fk_mode_reglement']['label'],$_SERVER["PHP_SELF"],"f.fk_mode_reglement","",$param,"",$sortfield,$sortorder);
    if (! empty($arrayfields['f.total_ht']['checked']))           print_liste_field_titre($arrayfields['f.total_ht']['label'],$_SERVER['PHP_SELF'],'f.total','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.total_vat']['checked']))          print_liste_field_titre($arrayfields['f.total_vat']['label'],$_SERVER['PHP_SELF'],'f.'.$invoiceTotalVatField,'',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.total_localtax1']['checked']))    print_liste_field_titre($arrayfields['f.total_localtax1']['label'],$_SERVER['PHP_SELF'],'f.localtax1','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.total_localtax2']['checked']))    print_liste_field_titre($arrayfields['f.total_localtax2']['label'],$_SERVER['PHP_SELF'],'f.localtax2','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.total_ttc']['checked']))          print_liste_field_titre($arrayfields['f.total_ttc']['label'],$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['dynamount_payed']['checked']))      print_liste_field_titre($arrayfields['dynamount_payed']['label'],$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['rtp']['checked']))                  print_liste_field_titre($arrayfields['rtp']['label'],$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['ps.status']['checked']))            print_liste_field_titre($arrayfields['ps.status']['label'],$_SERVER['PHP_SELF'],'ps.status','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['ps.date_start']['checked']))        print_liste_field_titre($arrayfields['ps.date_start']['label'],$_SERVER['PHP_SELF'],'ps.date_start','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['ps.periodicity_value']['checked'])) print_liste_field_titre($arrayfields['ps.periodicity_value']['label'],$_SERVER['PHP_SELF'],'ps.periodicity_value','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['ps.periodicity_unit']['checked']))  print_liste_field_titre($arrayfields['ps.periodicity_unit']['label'],$_SERVER['PHP_SELF'],'ps.periodicity_unit','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['ps.nb_term']['checked']))           print_liste_field_titre($arrayfields['ps.nb_term']['label'],$_SERVER['PHP_SELF'],'ps.nb_term','',$param,'align="right"',$sortfield,$sortorder);
    // Extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
    // Hook fields
    $parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (! empty($arrayfields['f.datec']['checked']))     print_liste_field_titre($arrayfields['f.datec']['label'],$_SERVER["PHP_SELF"],"f.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.tms']['checked']))       print_liste_field_titre($arrayfields['f.tms']['label'],$_SERVER["PHP_SELF"],"f.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.fk_statut']['checked'])) print_liste_field_titre($arrayfields['f.fk_statut']['label'],$_SERVER["PHP_SELF"],"f.fk_statut,f.paye,f.type,dynamount_payed","",$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
    print "</tr>\n";

    $projectstatic=new Project($db);
    $discount = new DiscountAbsolute($db);

    if ($num > 0)
    {
        $i=0;
        $totalarray=array('nbfield' => 0);
        while ($i < min($num,$limit))
        {
            $obj = $db->fetch_object($resql);

            $datelimit=$db->jdate($obj->datelimite);

            $facturestatic->id=$obj->id;
            $facturestatic->ref=$obj->ref;
            $facturestatic->type=$obj->type;
            $facturestatic->total_ht=$obj->total_ht;
            $facturestatic->total_tva=$obj->total_vat;
            $facturestatic->total_ttc=$obj->total_ttc;
            $facturestatic->statut=$obj->fk_statut;
            $facturestatic->total_ttc=$obj->total_ttc;
            $facturestatic->paye=$obj->paye;
            if(property_exists($facturestatic,'fk_soc') && !empty($obj->fk_soc)) $facturestatic->fk_soc=$obj->fk_soc;
            if(property_exists($facturestatic,'socid') && !empty($obj->fk_soc)) $facturestatic->socid=$obj->fk_soc;
            $facturestatic->date_lim_reglement=$db->jdate($obj->datelimite);
            $facturestatic->note_public=$obj->note_public;
            $facturestatic->note_private=$obj->note_private;

            $thirdpartystatic->id=$obj->socid;
            $thirdpartystatic->name=$obj->name;
            $thirdpartystatic->client=$obj->client;
            $thirdpartystatic->fournisseur=$obj->fournisseur;
            $thirdpartystatic->code_client=$obj->code_client;
            $thirdpartystatic->code_compta_client=$obj->code_compta_client;
            $thirdpartystatic->code_fournisseur=$obj->code_fournisseur;
            $thirdpartystatic->code_compta_fournisseur=$obj->code_compta_fournisseur;
            $thirdpartystatic->email=$obj->email;
            $thirdpartystatic->country_code=$obj->country_code;

            $paiement = $facturestatic->getSommePaiement();
            $totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
            $totaldeposits = $facturestatic->getSumDepositsUsed();
            $totalpay = $paiement + $totalcreditnotes + $totaldeposits;
            $remaintopay = $facturestatic->total_ttc - $totalpay;
            if ($facturestatic->type == Facture::TYPE_CREDIT_NOTE && $obj->paye == 1) {
                $remaincreditnote = $discount->getAvailableDiscounts($obj->fk_soc, '', 'rc.fk_facture_source='.$facturestatic->id);
                $remaintopay = -$remaincreditnote;
                $totalpay = $facturestatic->total_ttc - $remaintopay;
            }

            $scheduleStatic->fetchBy($facturestatic->id, 'fk_facture');

            print '<tr class="oddeven">';
            if (! empty($arrayfields['f.'.$fieldRefFacture]['checked']))
            {
                print '<td class="nowrap">';

                print '<table class="nobordernopadding"><tr class="nocellnopadd">';

                print '<td class="nobordernopadding nowraponall">';
                $scheduleStatic->ref = $facturestatic->ref;
                print $scheduleStatic->getNomUrl(1,'',200,0,'',0,1);
                print empty($obj->increment)?'':' ('.$obj->increment.')';

                $filename=dol_sanitizeFileName($obj->ref)."_ps";
                $filedir=$conf->paymentschedule->dir_output . '/' . dol_sanitizeFileName($obj->ref)."_ps";
                $urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->id;
                print $formfile->getDocumentsLink($scheduleStatic->element, $filename, $filedir);
                print '</td>';
                print '</tr>';
                print '</table>';

                print "</td>\n";
                if (! $i) $totalarray['nbfield']++;
            }

            // Customer ref
            if (! empty($arrayfields['f.ref_client']['checked']))
            {
                print '<td class="nowrap">';
                print $obj->ref_client;
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }

            // Type
            if (! empty($arrayfields['f.type']['checked']))
            {
                print '<td class="nowrap">';
                print $facturestatic->getLibType();
                print "</td>";
                if (! $i) $totalarray['nbfield']++;
            }

            // Date
            if (! empty($arrayfields['f.date']['checked']))
            {
                print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($obj->df),'day');
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }

            // Date limit
            if (! empty($arrayfields['f.date_lim_reglement']['checked']))
            {
                print '<td align="center" class="nowrap">'.dol_print_date($datelimit,'day');
                if ($facturestatic->hasDelay())
                {
                    print img_warning($langs->trans('Alert').' - '.$langs->trans('Late'));
                }
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }

            // Project
            if (! empty($arrayfields['p.ref']['checked']))
            {
                print '<td class="nocellnopadd nowrap">';
                if ($obj->project_id > 0)
                {
                    $projectstatic->id=$obj->project_id;
                    $projectstatic->ref=$obj->project_ref;
                    $projectstatic->title=$obj->project_label;
                    print $projectstatic->getNomUrl(1);
                }
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }

            // Third party
            if (! empty($arrayfields['s.nom']['checked']))
            {
                print '<td class="tdoverflowmax200">';
                print $thirdpartystatic->getNomUrl(1,'customer');
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Town
            if (! empty($arrayfields['s.town']['checked']))
            {
                print '<td class="nocellnopadd">';
                print $obj->town;
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Zip
            if (! empty($arrayfields['s.zip']['checked']))
            {
                print '<td class="nocellnopadd">';
                print $obj->zip;
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // State
            if (! empty($arrayfields['state.nom']['checked']))
            {
                print "<td>".$obj->state_name."</td>\n";
                if (! $i) $totalarray['nbfield']++;
            }
            // Country
            if (! empty($arrayfields['country.code_iso']['checked']))
            {
                print '<td align="center">';
                $tmparray=getCountry($obj->fk_pays,'all');
                print $tmparray['label'];
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Type ent
            if (! empty($arrayfields['typent.code']['checked']))
            {
                print '<td align="center">';
                if (! is_array($typenArray) || count($typenArray)==0) $typenArray = $formcompany->typent_array(1);
                print $typenArray[$obj->typent_code];
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Staff
            if (! empty($arrayfields['staff.code']['checked']))
            {
                print '<td align="center">';
                if (! is_array($staffArray) || count($staffArray)==0) $staffArray = $formcompany->effectif_array(1);
                print $staffArray[$obj->staff_code];
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }

            // Payment mode
            if (! empty($arrayfields['f.fk_mode_reglement']['checked']))
            {
                print '<td>';
                $form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1);
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }

            // Amount HT
            if (! empty($arrayfields['f.total_ht']['checked']))
            {
                print '<td align="right">'.price($obj->total_ht)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
                if (! $i) $totalarray['totalhtfield']=$totalarray['nbfield'];
                if(!isset( $totalarray['totalht']))  $totalarray['totalht'] = 0;
                $totalarray['totalht'] += $obj->total_ht;
            }
            // Amount VAT
            if (! empty($arrayfields['f.total_vat']['checked']))
            {
                print '<td align="right">'.price($obj->total_vat)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
                if (! $i) $totalarray['totalvatfield']=$totalarray['nbfield'];
                if(!isset( $totalarray['totalvat']))  $totalarray['totalvat'] = 0;
                $totalarray['totalvat'] += $obj->total_vat;
            }
            // Amount LocalTax1
            if (! empty($arrayfields['f.total_localtax1']['checked']))
            {
                print '<td align="right">'.price($obj->total_localtax1)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
                if (! $i) $totalarray['totallocaltax1field']=$totalarray['nbfield'];
                if(!isset( $totalarray['totallocaltax1']))  $totalarray['totallocaltax1'] = 0;
                $totalarray['totallocaltax1'] += $obj->total_localtax1;
            }
            // Amount LocalTax2
            if (! empty($arrayfields['f.total_localtax2']['checked']))
            {
                print '<td align="right">'.price($obj->total_localtax2)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
                if (! $i) $totalarray['totallocaltax2field']=$totalarray['nbfield'];
                if(!isset( $totalarray['totallocaltax2']))  $totalarray['totallocaltax2'] = 0;

                $totalarray['totallocaltax2'] += $obj->total_localtax2;
            }
            // Amount TTC
            if (! empty($arrayfields['f.total_ttc']['checked']))
            {
                print '<td align="right">'.price($obj->total_ttc)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
                if (! $i) $totalarray['totalttcfield']=$totalarray['nbfield'];
                if(!isset( $totalarray['totalttc']))  $totalarray['totalttc'] = 0;

                $totalarray['totalttc'] += $obj->total_ttc;
            }

            if (! empty($arrayfields['dynamount_payed']['checked']))
            {
                print '<td align="right">'.(! empty($totalpay)?price($totalpay,0,$langs):'&nbsp;').'</td>'; // TODO Use a denormalized field
                if (! $i) $totalarray['nbfield']++;
                if (! $i) $totalarray['totalamfield']=$totalarray['nbfield'];
                if(!isset( $totalarray['totalam']))  $totalarray['totalam'] = 0;

                $totalarray['totalam'] += $totalpay;
            }

            if (! empty($arrayfields['rtp']['checked']))
            {
                print '<td align="right">'.(! empty($remaintopay)?price($remaintopay,0,$langs):'&nbsp;').'</td>'; // TODO Use a denormalized field
                if (! $i) $totalarray['nbfield']++;
                if (! $i) $totalarray['totalrtpfield']=$totalarray['nbfield'];
                if(!isset( $totalarray['totalrtp']))  $totalarray['totalrtp'] = 0;

                $totalarray['totalrtp'] += $remaintopay;
            }

            if (! empty($arrayfields['ps.status']['checked']))
            {
                print '<td align="right">';
                print $scheduleStatic::LibStatut($obj->ps_status, 4);
                print '</td>';
            }

            if (! empty($arrayfields['ps.date_start']['checked']))
            {
                print '<td align="right">';
                print dol_print_date($db->jdate($obj->date_start),'day');
                print '</td>';
            }

            if (! empty($arrayfields['ps.periodicity_value']['checked']))
            {
                print '<td align="right">';
                print $obj->periodicity_value;
                print '</td>';
            }

            if (! empty($arrayfields['ps.periodicity_unit']['checked']))
            {
                print '<td align="right">';
                print $langs->trans(ucfirst($obj->periodicity_unit));
                print '</td>';
            }

            if (! empty($arrayfields['ps.nb_term']['checked']))
            {
                print '<td align="right">';
                print $obj->nb_term;
                print '</td>';
            }

            // Extra fields
            include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
            // Fields from hook
            $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
            $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            // Date creation
            if (! empty($arrayfields['f.datec']['checked']))
            {
                print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Date modification
            if (! empty($arrayfields['f.tms']['checked']))
            {
                print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Status
            if (! empty($arrayfields['f.fk_statut']['checked']))
            {
                print '<td align="right" class="nowrap">';
                print $facturestatic->LibStatut($obj->paye,$obj->fk_statut,5,$paiement,$obj->type);
                print "</td>";
                if (! $i) $totalarray['nbfield']++;
            }

            // Action column
            print '<td class="nowrap" align="center">';
            if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
            {
                $selected=0;
                if (in_array($obj->id, $arrayofselected)) $selected=1;
                print '<input id="cb'.$scheduleStatic->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$scheduleStatic->id.'"'.($selected?' checked="checked"':'').'>';
            }
            print '</td>' ;
            if (! $i) $totalarray['nbfield']++;

            print "</tr>\n";

            $i++;
        }

        // Show total line
        if (isset($totalarray['totalhtfield'])
            || isset($totalarray['totalvatfield'])
            || isset($totalarray['totallocaltax1field'])
            || isset($totalarray['totallocaltax2field'])
            || isset($totalarray['totalttcfield'])
            || isset($totalarray['totalamfield'])
            || isset($totalarray['totalrtpfield'])
        )
        {
            print '<tr class="liste_total">';
            $i=0;
            while ($i < $totalarray['nbfield'])
            {
                $i++;
                if ($i == 1)
                {
                    if ($num < $limit && empty($offset)) print '<td align="left">'.$langs->trans("Total").'</td>';
                    else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
                }
                elseif (!empty($totalarray['totalhtfield']) && $totalarray['totalhtfield'] == $i)  print '<td align="right">'.price($totalarray['totalht']).'</td>';
                elseif (!empty($totalarray['totalvatfield']) && $totalarray['totalvatfield'] == $i) print '<td align="right">'.price($totalarray['totalvat']).'</td>';
                elseif (!empty($totalarray['totallocaltax1field']) && $totalarray['totallocaltax1field'] == $i) print '<td align="right">'.price($totalarray['totallocaltax1']).'</td>';
                elseif (!empty($totalarray['totallocaltax2field']) && $totalarray['totallocaltax2field'] == $i) print '<td align="right">'.price($totalarray['totallocaltax2']).'</td>';
                elseif (!empty($totalarray['totalttcfield']) && $totalarray['totalttcfield'] == $i) print '<td align="right">'.price($totalarray['totalttc']).'</td>';
                elseif (!empty($totalarray['totalamfield']) && $totalarray['totalamfield'] == $i)  print '<td align="right">'.price($totalarray['totalam']).'</td>';
                elseif (!empty($totalarray['totalrtpfield']) && $totalarray['totalrtpfield'] == $i)  print '<td align="right">'.price($totalarray['totalrtp']).'</td>';
                else print '<td></td>';
            }
            print '</tr>';
        }
    }

    $db->free($resql);

    $parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
    $reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    print "</table>\n";
    print '</div>';

    print "</form>\n";

    $hidegeneratedfilelistifempty=1;
    if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty=0;

    // Show list of available documents
    $urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
    $urlsource.=str_replace('&amp;','&',$param);

    $filedir=$diroutputmassaction;
    $genallowed=$user->hasRight('paymentschedule','read');
    $delallowed=$user->hasRight('paymentschedule','write');

    print $formfile->showdocuments('paymentschedule','/temp/massgeneration/'.$conf->entity,$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,'','','','',null,$hidegeneratedfilelistifempty);

    ?>
    <script type="text/javascript">
        $('.fa-trash').each(function (i, el) {
            var href = $(el).parent().attr('href');
            var toreplace = "<?php echo urlencode('/temp/massgeneration/'.$conf->entity."/"); ?>"
            var len = toreplace.length;
            var index = href.indexOf(toreplace);
            var newhref = href.substring(0, index) + href.substring(href.indexOf(toreplace)+len, href.length);
            $(el).parent().attr('href', newhref)
        })
    </script>
    <?php
}
else
{
    dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
