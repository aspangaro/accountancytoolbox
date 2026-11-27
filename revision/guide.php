<?php
/* Copyright (C) 2026       Alexandre Spangaro      <alexandre@inovea-conseil.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       accountancytoolbox/revision/guide.php
 * \ingroup    accountancytoolbox
 * \brief      Fiche et liste des modèles de guide de révision des comptes
 */

$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

/**
 * @var Conf      $conf
 * @var DoliDB    $db
 * @var Translate $langs
 * @var User      $user
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/accountancytoolbox/lib/accountancytoolbox.lib.php');
dol_include_once('/accountancytoolbox/class/revisionguide.class.php');

$langs->loadLangs(array('accountancy', 'compta', 'main', 'accountancytoolbox@accountancytoolbox'));

if (!isModEnabled('accounting') || $user->socid > 0 || !$user->hasRight('accounting', 'mouvements', 'lire')) {
	accessforbidden();
}

$canWrite = ($user->hasRight('accounting', 'mouvements', 'creer') || $user->admin);
if (!$canWrite && in_array(GETPOST('action', 'aZ09'), array('create', 'add', 'edit', 'update', 'delete', 'confirm_delete'), true)) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');

/*
 * Actions
 */
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST' && $canWrite) {
	$token = GETPOST('token', 'alphanohtml');
	if (empty($token) || (!hash_equals(newToken(), $token) && !hash_equals(currentToken(), $token))) {
		accessforbidden('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry');
	}

	$guide = new RevisionGuide($db);
	$guide->ref = strtoupper(trim(GETPOST('ref', 'alphanohtml')));
	$guide->label = trim(GETPOST('label', 'restricthtml'));
	$guide->description = GETPOST('description', 'restricthtml');
	$guide->is_default = GETPOST('is_default', 'int') ? 1 : 0;
	$guide->active = GETPOST('active', 'int') ? 1 : 0;

	if ($guide->label === '') {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->trans('Label')), null, 'errors');
	} else {
		if (!empty($guide->is_default)) {
			$db->query("UPDATE ".MAIN_DB_PREFIX."accountancytoolbox_revision_guide SET is_default = 0 WHERE entity = ".((int) $conf->entity));
		}
		$res = $guide->create($user);
		if ($res > 0) {
			$guide->seedDefaultCycles($user);
			setEventMessages($langs->trans('ATBRevisionGuideCreated'), null, 'mesgs');
			header('Location: revision.php?guide='.((int) $guide->id));
			exit;
		}
		setEventMessages($guide->error ?: $db->lasterror(), null, 'errors');
		$action = 'create';
	}
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $canWrite && $id > 0) {
	$token = GETPOST('token', 'alphanohtml');
	if (empty($token) || (!hash_equals(newToken(), $token) && !hash_equals(currentToken(), $token))) {
		accessforbidden('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry');
	}

	$guide = new RevisionGuide($db);
	if ($guide->fetch($id) > 0) {
		$guide->ref = strtoupper(trim(GETPOST('ref', 'alphanohtml')));
		$guide->label = trim(GETPOST('label', 'restricthtml'));
		$guide->description = GETPOST('description', 'restricthtml');
		$guide->is_default = GETPOST('is_default', 'int') ? 1 : 0;
		$guide->active = GETPOST('active', 'int') ? 1 : 0;
		if (!empty($guide->is_default)) {
			$db->query("UPDATE ".MAIN_DB_PREFIX."accountancytoolbox_revision_guide SET is_default = 0 WHERE entity = ".((int) $conf->entity)." AND rowid != ".((int) $guide->id));
		}
		if ($guide->update($user) > 0) {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: revision.php?guide='.((int) $guide->id));
			exit;
		}
		setEventMessages($guide->error ?: $db->lasterror(), null, 'errors');
	}
	$action = 'edit';
}

if ($action === 'delete' && $canWrite && $id > 0) {
	$token = GETPOST('token', 'alphanohtml');
	if (empty($token) || (!hash_equals(newToken(), $token) && !hash_equals(currentToken(), $token))) {
		accessforbidden('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry');
	}
	$guide = new RevisionGuide($db);
	if ($guide->fetch($id) > 0 && $guide->delete($user) > 0) {
		setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
	}
	header('Location: '.$_SERVER['PHP_SELF']);
	exit;
}

/*
 * View
 */
$form = new Form($db);
$title = $langs->trans('ATBRevisionGuidesList');

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-accountancytoolbox page-revision');

if (function_exists('accountancytoolboxPrepareHead')) {
	$head = accountancytoolboxPrepareHead();
	print dol_get_fiche_head($head, 'revision', $title, -1, 'fa-tasks');
} else {
	print load_fiche_titre($title, '', 'fa-tasks');
}

if ($action === 'confirm_delete' && $id > 0) {
	$guide = new RevisionGuide($db);
	$guide->fetch($id);
	print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.((int) $id), $langs->trans('ATBRevisionDeleteGuideTitle'), $langs->trans('ATBRevisionConfirmDeleteGuide'), 'delete', '', '', 1);
	print '<br>';
}

if ($action === 'create' || $action === 'edit') {
	$guide = new RevisionGuide($db);
	if ($action === 'edit' && $id > 0) {
		$guide->fetch($id);
	} else {
		$guide->ref = '';
		$guide->label = '';
		$guide->description = '';
		$guide->is_default = 0;
		$guide->active = 1;
	}

	$formTitle = $action === 'edit' ? $langs->trans('ATBRevisionEditGuideTitle') : $langs->trans('ATBRevisionNewGuideTitle');

	print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="'.($action === 'edit' ? 'update' : 'add').'">';
	print '<input type="hidden" name="id" value="'.((int) $guide->id).'">';

	print load_fiche_titre($formTitle, '', '');

	print '<table class="border centpercent tableforfield">';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('ATBRevisionGuideLabel').'</td><td>';
	print '<input type="text" name="label" class="minwidth300" maxlength="255" value="'.dol_escape_htmltag($guide->label).'" required>';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('ATBRevisionGuideRef').'</td><td>';
	print '<input type="text" name="ref" class="width100" maxlength="64" value="'.dol_escape_htmltag($guide->ref).'" placeholder="'.dol_escape_htmltag($langs->trans('Generated').' : G0001').'">';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Description').'</td><td>';
	print '<textarea name="description" class="quatrevingtpercent" rows="3">'.dol_escape_htmltag($guide->description).'</textarea>';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('ATBRevisionGuideDefault').'</td><td>';
	print '<input type="checkbox" name="is_default" value="1"'.(!empty($guide->is_default) ? ' checked' : '').'>';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Active').'</td><td>';
	print '<input type="checkbox" name="active" value="1"'.(!empty($guide->active) ? ' checked' : '').'>';
	print '</td></tr>';

	print '</table>';

	print '<div class="center">';
	print '<button type="submit" class="button button-save">'.$langs->trans('Save').'</button>';
	print '&nbsp;&nbsp;<a class="button button-cancel" href="'.$_SERVER['PHP_SELF'].'">'.$langs->trans('Cancel').'</a>';
	print '</div>';

	print '</form>';
} else {
	$guideTmp = new RevisionGuide($db);
	$guides = $guideTmp->fetchAll('t.ref', 'ASC');
	if (!is_array($guides)) {
		$guides = array();
	}

	print '<div class="tabsAction">';
	if ($canWrite) {
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=create">'.$langs->trans('ATBRevisionNewGuide').'</a>';
	}
	print '<a class="butAction" href="revision.php">'.$langs->trans('ATBRevisionBackToRevision').'</a>';
	print '</div>';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans('ATBRevisionGuideRef').'</th>';
	print '<th>'.$langs->trans('ATBRevisionGuideLabel').'</th>';
	print '<th>'.$langs->trans('Description').'</th>';
	print '<th class="center">'.$langs->trans('ATBRevisionGuideCycles').'</th>';
	print '<th class="center">'.$langs->trans('ATBRevisionGuideDefault').'</th>';
	print '<th class="center">'.$langs->trans('Active').'</th>';
	print '<th class="center">'.$langs->trans('Actions').'</th>';
	print '</tr>';

	if (empty($guides)) {
		print '<tr class="oddeven"><td colspan="7" class="opacitymedium" style="text-align:center">'.$langs->trans('ATBRevisionNoGuide').'</td></tr>';
	}

	foreach ($guides as $g) {
		$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."accountancytoolbox_revision_cycle WHERE fk_guide = ".((int) $g->id)." AND active = 1";
		$nbCycles = 0;
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$nbCycles = (int) $obj->nb;
			}
		}

		print '<tr class="oddeven">';
		print '<td><a href="revision.php?guide='.((int) $g->id).'">'.dol_escape_htmltag($g->ref).'</a></td>';
		print '<td>'.dol_escape_htmltag($g->label).'</td>';
		print '<td class="opacitymedium">'.dol_escape_htmltag($g->description).'</td>';
		print '<td class="center">'.$nbCycles.'</td>';
		print '<td class="center">'.($g->is_default ? $langs->trans('Yes') : $langs->trans('No')).'</td>';
		print '<td class="center">'.($g->active ? $langs->trans('Yes') : $langs->trans('No')).'</td>';
		print '<td class="center">';
		if ($canWrite) {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=edit&amp;id='.((int) $g->id).'" title="'.$langs->trans('Edit').'">'.img_edit($langs->trans('Edit')).'</a>';
			print '&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?action=confirm_delete&amp;id='.((int) $g->id).'" title="'.$langs->trans('Delete').'">'.img_delete($langs->trans('Delete')).'</a>';
		}
		print '</td>';
		print '</tr>';
	}
	print '</table>';
}

llxFooter();
