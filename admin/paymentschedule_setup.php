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
dol_include_once('/paymentschedule/class/paymentschedule.class.php');

// Translations
$langs->loadLangs(array('paymentschedule@paymentschedule', 'admin', 'other'));

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$value = GETPOST('value','alphanohtml');
$label = GETPOST('label','alphanohtml');
$scandir = GETPOST('scan_dir','alphanohtml');
$type='paymentschedule';

/*
 * Actions
 */
if ($action == 'specimen')
{
	$modele=GETPOST('module','alphanohtml');

	$paymentschedule = new PaymentSchedule($db);
	$paymentschedule->initAsSpecimen();

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
		$file=dol_buildpath($reldir."core/modules/facture/doc/pdf_".$modele.".modules.php",0);
		if (file_exists($file))
		{
			$filefound=1;
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($filefound)
	{
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($paymentschedule,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=facture&file=SPECIMEN.pdf");
			return;
		}
		else
		{
			setEventMessages($module->error, $module->errors, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	}
	else
	{
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

// Activate a model
else if ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

else if ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		if (getDolGlobalString('PAYMENTSCHEDULE_ADDON_PDF') == "$value") dolibarr_del_const($db, 'PAYMENTSCHEDULE_ADDON_PDF',$conf->entity);
	}
}

// Set default model
else if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "PAYMENTSCHEDULE_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->PAYMENTSCHEDULE_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

if (preg_match('/set_(.*)/', $action, $reg))
{
	$code=$reg[1];
	$val = GETPOST($code, 'none');
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
$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);

$page_name = "PaymentScheduleSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'tools');

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

/*
 *  Document templates generators
 */
print '<br>';
print load_fiche_titre($langs->trans("PaymentschedulesPDFModules"),'','');

// Load array def with activated templates
$type='paymentschedule';
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".$db->prefix()."document_model";
$sql.= " WHERE type = '".$type."'";
$sql.= " AND entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql)
{
	$i = 0;
	$num_rows=$db->num_rows($resql);
	while ($i < $num_rows)
	{
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
}
else
{
	dol_print_error($db);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="32">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="32">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

$activatedModels = array();

foreach ($dirmodels as $reldir)
{
	foreach (array('','/doc') as $valdir)
	{
		$dir = dol_buildpath($reldir."core/modules/paymentschedule".$valdir);

		if (is_dir($dir))
		{
			$handle=opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle))!==false)
				{
					$filelist[]=$file;
				}
				closedir($handle);
				arsort($filelist);

				foreach($filelist as $file)
				{
					if (preg_match('/\.modules\.php$/i',$file) && preg_match('/^(pdf_|doc_)/',$file))
					{
						if (file_exists($dir.'/'.$file))
						{
							$name = substr($file, 4, dol_strlen($file) -16);
							$classname = substr($file, 0, dol_strlen($file) -12);

							require_once $dir.'/'.$file;
							$module = new $classname($db);

							$modulequalified=1;
							if ($module->version == 'development'  && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) $modulequalified=0;
							if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) $modulequalified=0;

							if ($modulequalified)
							{
								print '<tr class="oddeven"><td width="100">';
								print (empty($module->name)?$name:$module->name);
								print "</td><td>\n";
								if (method_exists($module,'info')) print $module->info($langs);
								else print $module->description;
								print '</td>';

								// Active
								if (in_array($name, $def))
								{
									print '<td align="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'&token='.newToken().'">';
									print img_picto($langs->trans("Enabled"),'switch_on');
									print '</a>';
									print '</td>';
								}
								else
								{
									print "<td align=\"center\">\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'&token='.newToken().'">'.img_picto($langs->trans("SetAsDefault"),'switch_off').'</a>';
									print "</td>";
								}

								// Defaut
								print "<td align=\"center\">";
								if (getDolGlobalString('PAYMENTSCHEDULE_ADDON_PDF') == "$name")
								{
									print img_picto($langs->trans("Default"),'on');
								}
								else
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'&token='.newToken().'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("SetAsDefault"),'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
								$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
								if ($module->type == 'pdf')
								{
									$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
								$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
								$htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg,1,1);
								$htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg,1,1);
								$htmltooltip.='<br>'.$langs->trans("Discounts").': '.yn($module->option_escompte,1,1);
								$htmltooltip.='<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
								$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);
								$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraft").': '.yn($module->option_draft_watermark,1,1);


								print '<td align="center">';
								print $form->textwithpicto('',$htmltooltip,1,0);
								print '</td>';

								// Preview
								print '<td align="center">';
								if ($module->type == 'pdf')
								{
									// TODO créer la methode Paymentschedule::InitAsSpecimen
									//print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
								}
								else
								{
									print img_object($langs->trans("PreviewNotAvailable"),'generic');
								}
								print '</td>';

								print "</tr>\n";
							}
						}
					}
				}
			}
		}
	}
}
print '</table>';

print "<br>";
print load_fiche_titre($langs->trans("OtherOptions"),'','');
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="set_PAYMENTSCHEDULE_FREE_TEXT" />';
print '<tr class="oddeven"><td colspan="2">';
print $langs->trans("FreeLegalTextOnPaymentShedule").'<br>';
$variablename='PAYMENTSCHEDULE_FREE_TEXT';
if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT'))
{
	print '<textarea name="'.$variablename.'" class="flat" cols="120">' . getDolGlobalString($variablename).'</textarea>';
}
else
{
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor($variablename, getDolGlobalString($variablename),'',80,'dolibarr_notes');
	print $doleditor->Create();
}
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';
print '</table>';


print '<br>';
print load_fiche_titre($langs->trans("OtherOptions"),'','');
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
print $form->select_types_paiements(getDolGlobalString('PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE'), 'PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE');
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
print Form::multiselectarray('PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE_SECOND', $TPaiementId, explode(',', getDolGlobalString('PAYMENTSCHEDULE_MODE_REGLEMENT_TO_USE_SECOND')), 0, 0, 'minwidth200');
print '<input type="submit" class="butAction" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

setup_print_input_form_part('PAYMENTSCHEDULE_LABEL_PATTERN', $langs->trans('PAYMENTSCHEDULE_LABEL_PATTERN'), $langs->transnoentitiesnoconv('PAYMENTSCHEDULE_LABEL_PATTERN_HELP'), array('placeholder' => 'Prélèvement {SOCNAME} - {FACNUMBER}', 'size' => '40'));

// Example with placeholder
//setup_print_input_form_part('CONSTNAME',$langs->trans('ParamLabel'),'ParamDesc',array('placeholder'=>'http://'),'input','ParamHelp');

// Example with textarea
//setup_print_input_form_part('CONSTNAME',$langs->trans('ParamLabel'),'ParamDesc',array(),'textarea');

setup_print_on_off('PAYMENTSCHEDULE_DISABLE_RESTRICTION_ON_IBAN');

setup_print_on_off('PAYMENTSCHEDULE_AUTO_CREATE_WITHDRAW');

print '</table>';

dol_fiche_end(-1);

llxFooter();

$db->close();
