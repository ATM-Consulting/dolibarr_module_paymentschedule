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

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('timetablesepa/class/timetablesepa.class.php');
dol_include_once('timetablesepa/lib/timetablesepa.lib.php');
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';

if(empty($user->rights->timetablesepa->read)) accessforbidden();

$langs->loadLangs(array('timetablesepa@timetablesepa', 'bills', 'main'));

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$facid = GETPOST('facid', 'int');
$lineid = GETPOST('lineid', 'int');

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'timetablesepacard';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');

$object = new TimetableSEPA($db);

if (!empty($id) || !empty($ref)) $object->fetch($id, true, $ref);
elseif (!empty($facid)) $object->fetchBy($facid, 'fk_facture');

$facture = new Facture($db);

if (!empty($object->id))
{
    $facture->fetch($object->fk_facture);
    $facture->fetch_thirdparty();
//    accessforbidden($langs->trans('timetablesepaNotCreatedYet'));
}

$hookmanager->initHooks(array('timetablesepacard', 'globalcard'));


if ($object->isextrafieldmanaged)
{
    $extrafields = new ExtraFields($db);

    $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
    $search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
}

// Initialize array of search criterias
//$search_all=trim(GETPOST("search_all",'alpha'));
//$search=array();
//foreach($object->fields as $key => $val)
//{
//    if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
//}

/*
 * Actions
 */

$parameters = array('id' => $id, 'ref' => $ref);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{
	$error = 0;
	switch ($action) {
		case 'addtimetablesepa':
		case 'updatetimetablesepa':
			$object->setValues($_REQUEST); // Set standard attributes

            if ($object->isextrafieldmanaged)
            {
                $ret = $extrafields->setOptionalsFromPost($extralabels, $object);
                if ($ret < 0) $error++;
            }

//			$object->date_other = dol_mktime(GETPOST('starthour'), GETPOST('startmin'), 0, GETPOST('startmonth'), GETPOST('startday'), GETPOST('startyear'));

			// Check parameters
//			if (empty($object->date_other))
//			{
//				$error++;
//				setEventMessages($langs->trans('warning_date_must_be_fill'), array(), 'warnings');
//			}
			
			// ...

			if ($error > 0)
			{
				$action = 'edittimetablesepa';
				break;
			}
			
			$res = $object->save($user);
            if ($res < 0)
            {
                setEventMessage($object->errors, 'errors');
                if (empty($object->id)) $action = 'createtimetablesepa';
                else $action = 'edittimetablesepa';
            }
            else
            {
                header('Location: '.dol_buildpath('/timetablesepa/card.php', 1).'?id='.$object->id);
                exit;
            }
        case 'update_extras':

            $object->oldcopy = dol_clone($object);

            // Fill array 'array_options' with data from update form
            $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute', 'none'));
            if ($ret < 0) $error++;

            if (! $error)
            {
                $result = $object->insertExtraFields('MYMODULE_MODIFY');
                if ($result < 0)
                {
                    setEventMessages($object->error, $object->errors, 'errors');
                    $error++;
                }
            }

            if ($error) $action = 'edit_extras';
            else
            {
                header('Location: '.dol_buildpath('/timetablesepa/card.php', 1).'?id='.$object->id);
                exit;
            }
            break;

		case 'modiftimetablesepa':
		case 'reopentimetablesepa':
			if (!empty($user->rights->timetablesepa->write)) $object->setDraft($user);
				
			break;
		case 'confirm_validatetimetablesepa':
			if (!empty($user->rights->timetablesepa->write)) $object->setValid($user);
			
			header('Location: '.dol_buildpath('/timetablesepa/card.php', 1).'?id='.$object->id);
			exit;

		case 'confirm_deletetimetablesepa':
			if (!empty($user->rights->timetablesepa->delete)) $res = $object->delete($user);

			header('Location: '.dol_buildpath('/compta/facture/card.php', 1).'?facid='.$facture->id);
			exit;

        case 'confirm_resettimetablesepa':
            if (!empty($user->rights->timetablesepa->write))
            {
                $object->initDetailEcheancier(GETPOST('full_reset'));
            }
            header('Location: '.dol_buildpath('/timetablesepa/card.php', 1).'?id='.$object->id);
            exit;

        case 'updatelinetimetablesepa':
//            var_dump(GETPOST('save'), $lineid);
            if (GETPOST('save') && $lineid > 0)
            {
                $k = $object->addChild('TimetableSEPADet', $lineid);
                $child = &$object->TTimetableSEPADet[$k];
                if (!empty($child->id))
                {
                    $child->label = GETPOST('label');
                    $child->date_demande = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
                    $child->fk_mode_reglement = GETPOST('fk_mode_reglement', 'int');

                    $old_amount_ttc = $child->amount_ttc;
                    $child->amount_ttc = price2num(GETPOST('amount_ttc', 'intcomma'));

                    $res = $child->update($user);

                    // TODO
                    if ($child->amount_ttc != $child->amount_ttc)
                    {

                    }
                }
//            var_dump($k, $child->id, $res);
            }
//            exit;
            header('Location: '.$_SERVER['PHP_SELF'].'?facid='.$facture->id);
            exit;

	}
}


/**
 * View
 */
$form = new Form($db);

$title=$langs->trans('TimetableSEPA');
llxHeader('', $title);

if ($action == 'create')
{
    print load_fiche_titre($langs->trans('NewtimetableSEPA'), '', 'timetablesepa@timetablesepa');

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="addtimetablesepa">';
    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

    dol_fiche_head(array(), '');

    print '<table class="border centpercent">'."\n";

    // Common attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    print '</table>'."\n";

    dol_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans('Create')).'">';
    print '&nbsp; ';
    print '<input type="'.($backtopage?"submit":"button").'" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans('Cancel')).'"'.($backtopage?'':' onclick="javascript:history.go(-1)"').'>';	// Cancel for create does not post form if we don't know the backtopage
    print '</div>';

    print '</form>';
}
else
{
    if (empty($object->id))
    {
        $langs->load('errors');
        print $langs->trans('ErrorRecordNotFound');
    }
    else
    {
        if (!empty($object->id) && $action === 'edittimetablesepa')
        {
            print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="updatetimetablesepa">';
            print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
            print '<input type="hidden" name="id" value="'.$object->id.'">';

//            $head = timetablesepa_prepare_head($object);
            $head = facture_prepare_head($facture);
            $picto = 'timetablesepa@timetablesepa';
            dol_fiche_head($head, 'timetablesepacard', $langs->trans('TimetableSEPA'), -1, $picto);

            print '<table class="border centpercent">'."\n";

            // Common attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

            // Other attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

            print '</table>';

            dol_fiche_end();

            print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
            print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
            print '</div>';

            print '</form>';
        }
        elseif ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
        {
//            $head = timetablesepa_prepare_head($object);
            $head = facture_prepare_head($facture);
            $picto = 'timetablesepa@timetablesepa';
            dol_fiche_head($head, 'timetablesepacard', $langs->trans('TimetableSEPA'), -1, $picto);

            $formconfirm = getFormConfirmtimetableSEPA($form, $object, $facture, $action);
            if (!empty($formconfirm)) print $formconfirm;


            $linkback = '<a href="' .dol_buildpath('/timetablesepa/list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

            $morehtmlref='<div class="refidno">';
            // Ref customer
            $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $facture->ref_client, $facture, 0, 'string', '', 0, 1);
            $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $facture->ref_client, $facture, 0, 'string', '', null, null, '', 1);
            // Thirdparty
            $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $facture->thirdparty->getNomUrl(1,'customer');
            if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $facture->thirdparty->id > 0) $morehtmlref.=' (<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$facture->thirdparty->id.'&search_societe='.urlencode($facture->thirdparty->name).'">'.$langs->trans("OtherBills").'</a>)';

            $morehtmlref.='</div>';

            $morehtmlstatus = $object->getLibStatut(6).'<br />';

            $fieldid = 'facnumber';
            if ((float) DOL_VERSION >= 10.0) $fieldid = 'ref';
            dol_banner_tab($facture, 'ref', '', 1, $fieldid, 'ref', $morehtmlref, '', 0, '', $morehtmlstatus);


            print '<div class="fichecenter">';

            print '<div class="fichehalfleft">'; // Auto close by commonfields_view.tpl.php
            print '<div class="underbanner clearboth"></div>';
            print '<table class="border tableforfield" width="100%">'."\n";

            // Common attributes
            //$keyforbreak='fieldkeytoswithonsecondcolumn';
            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

            // Other attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

            print '</table>';

            print '</div></div>'; // Fin fichehalfright & ficheaddleft
            print '</div>'; // Fin fichecenter

            print '<div class="clearboth"></div><br />';


            print '<br />';
            print '<div class="div-table-responsive-no-min">';

            if ($action == 'editline')
            {
                print '<form id="" name="" action="'.$_SERVER['PHP_SELF'].'?facid='.$facture->id.'#row-'.$lineid.'" method="POST">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
                print '<input type="hidden" name="action" value="updatelinetimetablesepa" />';
                print '<input type="hidden" name="facid" value="'.$facture->id.'" />';
                print '<input type="hidden" name="lineid" value="'.$lineid.'" />';
            }

            print '<table id="tablelines" class="noborder noshadow" width="100%">';

            $dateSelector = 1;
            $selected = null;

            $parameters = array('num'=>$num,'i'=>$i,'dateSelector'=>$dateSelector,'selected'=>$selected);
            $reshook = $hookmanager->executeHooks('printObjectLineTitle', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
            if (empty($reshook))
            {
                // Title line
                print "<thead>\n";

                print '<tr class="liste_titre nodrag nodrop">';

                // Adds a line numbering column
                if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<td class="linecolnum" align="center" width="5">&nbsp;</td>';

                // Label
                print '<td class="linecollabel">'.$langs->trans('Label').'</td>';

                // Date demande
                print '<td class="linecoldatedemande nowrap" align="right" width="80">'.$langs->trans('timetablesepaDateDemande').'</td>';

                // Mode de règlement
                print '<td class="linecoldatedemande nowrap" align="right" width="80">'.$langs->trans('timetablesepaModeRglt').'</td>';

//                // Amount HT
//                print '<td class="linecolamountht" align="right" width="80">'.$langs->trans('TotalHT').'</td>';
//
//                // Amount TVA
//                print '<td class="linecolamountvat" align="right" width="80">'.$langs->trans('TotalVAT').'</td>';

                // Amount TTC ( TotalTTCShort )
                print '<td class="linecolamountttc" align="right" width="80">'.$langs->trans('TotalTTC').'</td>';

                print '<td class="linecolstatus">'.$langs->trans('timetablesepaStatusBankLevy').'</td>';

                print '<td class="linecoledit"></td>';  // No width to allow autodim

                print '<td class="linecoldelete" width="10"></td>';

                print '<td class="linecolmove" width="10"></td>';

                print "</tr>\n";
                print "</thead>\n";
            }

            $var = true;
            $i	 = 0;
            $sum = 0;

            $form->load_cache_types_paiements();

            print "<tbody>\n";
            foreach ($object->TTimetableSEPADet as $line)
            {
                if (is_object($hookmanager))   // Old code is commented on preceding line.
                {
                    if (empty($line->fk_parent_line))
                    {
                        $parameters = array('line'=>$line,'var'=>$var,'num'=>$num,'i'=>$i,'dateSelector'=>$dateSelector,'selected'=>$selected);
                        $reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
                    }
                    else
                    {
                        $parameters = array('line'=>$line,'var'=>$var,'num'=>$num,'i'=>$i,'dateSelector'=>$dateSelector,'selected'=>$selected);
                        $reshook = $hookmanager->executeHooks('printObjectSubLine', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
                    }
                }
                if (empty($reshook))
                {
                    $domData  = ' data-element="'.$line->element.'"';
                    $domData .= ' data-id="'.$line->id.'"';
                    $domData .= ' data-qty="'.$line->qty.'"';
                    $domData .= ' data-product_type="'.$line->product_type.'"';

                    print '<tr  id="row-'.$line->id.'" class="drag drop oddeven" '.$domData.' >';
                    if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
                        print '<td class="linecolnum" align="center">'.($i+1).'</td>';
                        $coldisplay++;
                    }

                    // Label
                    print '<td class="linecollabel minwidth300imp"><div id="line_'.$line->id.'">';
                    if ($action == 'editline' && $line->id == $lineid) print '<textarea class="flat" name="label" rows="3" cols="60">'.$line->label.'</textarea>';
                    else print $line->label;
                    print '</div>';
                    $coldisplay++;

                    // Date demande
                    print '<td class="linecoldatedemande  nowrap"  width="80">';
                    if ($action == 'editline' && $line->id == $lineid) print $form->selectDate($line->date_demande, 're');
                    else print dol_print_date($line->date_demande, 'day');
                    print '</td>';
                    $coldisplay++;

                    // Mode de règlement
                    print '<td class="linecoldatedemande  nowrap"  width="80">';
                    if ($action == 'editline' && $line->id == $lineid) $form->select_types_paiements($object->mode_reglement_id, 'fk_mode_reglement', 'CRDT', 0, 1, 0, 0, 1);
                    else print $form->cache_types_paiements[$object->mode_reglement_id]['label'];
                    print '</td>';
                    $coldisplay++;

//                    // Amount HT
//                    print '<td class="linecolamountht right nowrap" align="right" width="80">';
//                    if ($action == 'editline' && $line->id == $lineid) print '<input class="flat right" type="text" value="'.$line->amount_ht.'" name="amount_ht" size="6">';
//                    else print price($line->amount_ht);
//                    print '</td>';
//                    $coldisplay++;
//
//                    // Amount TVA
//                    print '<td class="linecolamountvat right nowrap" align="right" width="80">'.price($line->amount_tva).'</td>';
//                    $coldisplay++;

                    // Amount TTC ( TotalTTCShort )
                    print '<td class="linecolamountttc right nowrap" align="right" width="80">';
                    if ($action == 'editline' && $line->id == $lineid) print '<input class="flat right" type="text" value="'.$line->amount_ttc.'" name="amount_ttc" size="6">';
                    else print price($line->amount_ttc);
                    print '</td>';
                    $coldisplay++;

                    print '<td class="linecolstatus">'.$line->getLibStatut(3).'</td>';
                    $coldisplay++;

                    if ($action == 'editline' && $line->id == $lineid)
                    {
                        print '<td class="linecolsave center" colspan="2">
                                    <input id="savelinebutton" type="submit" class="button" name="save" value="'.$langs->trans('Save').'" />
                                    <input id="cancellinebutton" type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'" />
                                </td>';
                    }
                    else
                    {
                        print '<td class="linecoledit" width="10"><a href="'.$_SERVER['PHP_SELF'].'?facid='.$facture->id.'&action=editline&lineid='.$line->id.'#row-'.$line->id.'">'.img_edit().'</a></td>';  // No width to allow autodim
                        $coldisplay++;

                        print '<td class="linecoldelete" width="10">'.img_delete().'</td>';
                        $coldisplay++;
                    }

                    print '<td class="linecolmove" width="10">&nbsp;</td>';
                    $coldisplay++;

                    print "</tr>\n";

                }

                $sum+= $line->amount_ttc;
                $i++;
            }
            print "</tbody>\n";

            print "</table>\n";

            if ($action == 'editline')
            {
                print '</form>';
            }
            else
            {
                if ($sum != $facture->total_ttc) print '<div class="warning">'.$langs->trans('TimetableSEPA_warningSumIsDifferent', price($sum-$facture->total_ttc)).'</div>';
            }

            print "</div>";

            dol_fiche_end();



            print '<div class="tabsAction">'."\n";
            $parameters=array();
            $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
            if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

            if (empty($reshook))
            {
                // Send
                //        print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a>'."\n";

                // Modify
                if (!empty($user->rights->timetablesepa->write))
                {
                    if ($object->status === TimetableSEPA::STATUS_DRAFT)
                    {
                        // Reset échéancier
                        print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=resettimetablesepa">'.$langs->trans("timetableSEPAReset").'</a></div>'."\n";
                        // Modify
                        print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edittimetablesepa">'.$langs->trans("timetableSEPAModify").'</a></div>'."\n";
                        // Valid
                        print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validtimetablesepa">'.$langs->trans('timetableSEPAValid').'</a></div>'."\n";
                    }


                    // Reopen
                    if ($object->status === TimetableSEPA::STATUS_VALIDATED) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopentimetablesepa">'.$langs->trans('timetableSEPAReopen').'</a></div>'."\n";
                }
                else
                {
                    if ($object->status === TimetableSEPA::STATUS_DRAFT)
                    {
                        // Reset échéancier
                        print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("timetableSEPAReset").'</a></div>'."\n";
                        // Modify
                        print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("timetableSEPAModify").'</a></div>'."\n";
                        // Valid
                        print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('timetableSEPAValid').'</a></div>'."\n";
                    }

                    // Reopen
                    if ($object->status === TimetableSEPA::STATUS_VALIDATED) print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('timetableSEPAReopen').'</a></div>'."\n";
                }

                if (!empty($user->rights->timetablesepa->delete))
                {
                    print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=deletetimetablesepa">'.$langs->trans("timetableSEPADelete").'</a></div>'."\n";
                }
                else
                {
                    print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("timetableSEPADelete").'</a></div>'."\n";
                }
            }
            print '</div>'."\n";

        }
    }
}


llxFooter();
$db->close();
