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
 * \file       accountancytoolbox/revision/cycle.php
 * \ingroup    accountancytoolbox
 * \brief      Fiche d'un cycle (grand domaine) d'un modèle de guide de révision
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
dol_include_once('/accountancytoolbox/class/revisioncycle.class.php');
dol_include_once('/accountancytoolbox/class/revisiondiligence.class.php');

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
$guideId = GETPOST('guide', 'int');

/*
 * Actions
 */
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST' && $canWrite && $guideId > 0) {
	$token = GETPOST('token', 'alphanohtml');
	if (empty($token) || (!hash_equals(newToken(), $token) && !hash_equals(currentToken(), $token))) {
		accessforbidden('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry');
	}

	$cycle = new RevisionCycle($db);
	$cycle->fk_guide = $guideId;
	$cycle->code = strtoupper(substr(trim(GETPOST('code', 'alphanohtml')), 0, 8));
	$cycle->label = trim(GETPOST('label', 'restricthtml'));
	$cycle->accounts_scope = trim(GETPOST('accounts_scope', 'restricthtml'));
	$cycle->standard_controls = GETPOST('standard_controls', 'restricthtml');
	$cycle->position = GETPOST('position', 'int');
	$cycle->active = GETPOST('active', 'int') ? 1 : 0;

	if ($cycle->code === '' || $cycle->label === '') {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->trans('ATBRevisionCycleCode').' / '.$langs->trans('Label')), null, 'errors');
		$action = 'create';
	} else {
		if (empty($cycle->position)) {
			$sql = "SELECT COALESCE(MAX(position), 0) + 1 as nextpos FROM ".MAIN_DB_PREFIX."accountancytoolbox_revision_cycle WHERE fk_guide = ".((int) $guideId);
			$resql = $db->query($sql);
			if ($resql && ($obj = $db->fetch_object($resql))) {
				$cycle->position = (int) $obj->nextpos;
			}
		}
		$res = $cycle->create($user);
		if ($res > 0) {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: revision.php?guide='.((int) $guideId).'#cycle'.((int) $cycle->id));
			exit;
		}
		setEventMessages($cycle->error ?: $db->lasterror(), null, 'errors');
		$action = 'create';
	}
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $canWrite && $id > 0) {
	$token = GETPOST('token', 'alphanohtml');
	if (empty($token) || (!hash_equals(newToken(), $token) && !hash_equals(currentToken(), $token))) {
		accessforbidden('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry');
	}

	$cycle = new RevisionCycle($db);
	if ($cycle->fetch($id) > 0) {
		$guideId = (int) $cycle->fk_guide;
		$cycle->code = strtoupper(substr(trim(GETPOST('code', 'alphanohtml')), 0, 8));
		$cycle->label = trim(GETPOST('label', 'restricthtml'));
		$cycle->accounts_scope = trim(GETPOST('accounts_scope', 'restricthtml'));
		$cycle->standard_controls = GETPOST('standard_controls', 'restricthtml');
		$cycle->position = GETPOST('position', 'int');
		$cycle->active = GETPOST('active', 'int') ? 1 : 0;
		if ($cycle->update($user) > 0) {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: revision.php?guide='.((int) $guideId).'#cycle'.((int) $cycle->id));
			exit;
		}
		setEventMessages($cycle->error ?: $db->lasterror(), null, 'errors');
	}
	$action = 'edit';
}

if ($action === 'delete' && $canWrite && $id > 0) {
	$token = GETPOST('token', 'alphanohtml');
	if (empty($token) || (!hash_equals(newToken(), $token) && !hash_equals(currentToken(), $token))) {
		accessforbidden('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry');
	}
	$cycle = new RevisionCycle($db);
	if ($cycle->fetch($id) > 0) {
		$guideId = (int) $cycle->fk_guide;
		if ($cycle->delete($user) > 0) {
			setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
		}
	}
	header('Location: revision.php?guide='.((int) $guideId));
	exit;
}

/*
 * View
 */
$form = new Form($db);
$title = $langs->trans('ATBRevisionTitle');

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-accountancytoolbox page-revision');

if (function_exists('accountancytoolboxPrepareHead')) {
	$head = accountancytoolboxPrepareHead();
	print dol_get_fiche_head($head, 'revision', $title, -1, 'fa-tasks');
} else {
	print load_fiche_titre($title, '', 'fa-tasks');
}

if ($action === 'confirm_delete' && $id > 0) {
	$cycle = new RevisionCycle($db);
	$cycle->fetch($id);
	$guideId = (int) $cycle->fk_guide;
	print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.((int) $id).'&guide='.((int) $guideId), $langs->trans('ATBRevisionDeleteCycleTitle'), $langs->trans('ATBRevisionConfirmDeleteCycle'), 'delete', '', '', 1);
	print '<br>';
}

if ($action === 'create' || $action === 'edit') {
	$cycle = new RevisionCycle($db);
	if ($action === 'edit' && $id > 0) {
		$cycle->fetch($id);
		$guideId = (int) $cycle->fk_guide;
	} else {
		$cycle->fk_guide = $guideId;
		$cycle->code = '';
		$cycle->label = '';
		$cycle->accounts_scope = '';
		$cycle->standard_controls = '';
		$cycle->position = 0;
		$cycle->active = 1;
	}

	$formTitle = $action === 'edit' ? $langs->trans('ATBRevisionCycleEditTitle') : $langs->trans('ATBRevisionCycleNewTitle');

	print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="'.($action === 'edit' ? 'update' : 'add').'">';
	print '<input type="hidden" name="id" value="'.((int) $cycle->id).'">';
	print '<input type="hidden" name="guide" value="'.((int) $guideId).'">';

	print load_fiche_titre($formTitle, '', '');

	print '<table class="border centpercent tableforfield">';

	print '<tr><td class="titlefieldcreate fieldrequired" style="width:220px">'.$langs->trans('ATBRevisionCycleCode').'</td><td>';
	print '<input type="text" name="code" class="width100" maxlength="8" value="'.dol_escape_htmltag($cycle->code).'" placeholder="A" required>';
	print '</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans('ATBRevisionCycleLabel').'</td><td>';
	print '<input type="text" name="label" class="minwidth300" maxlength="255" value="'.dol_escape_htmltag($cycle->label).'" required>';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('ATBRevisionCycleAccountsScope').'</td><td>';
	print '<input type="text" name="accounts_scope" class="minwidth300" maxlength="255" value="'.dol_escape_htmltag($cycle->accounts_scope).'" placeholder="'.dol_escape_htmltag('Classes 5 (512, 530)').'">';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('ATBRevisionCycleControls').'</td><td>';
	print '<textarea name="standard_controls" class="quatrevingtpercent" rows="3">'.dol_escape_htmltag($cycle->standard_controls).'</textarea>';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Position').'</td><td>';
	print '<input type="number" name="position" class="width100" min="0" value="'.((int) $cycle->position).'">';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Active').'</td><td>';
	print '<input type="checkbox" name="active" value="1"'.(!empty($cycle->active) ? ' checked' : '').'>';
	print '</td></tr>';

	print '</table>';

	print '<div class="center">';
	print '<button type="submit" class="button button-save">'.$langs->trans('Save').'</button>';
	print '&nbsp;&nbsp;<a class="button button-cancel" href="revision.php?guide='.((int) $guideId).'">'.$langs->trans('Cancel').'</a>';
	print '</div>';

	print '</form>';

	if ($action === 'edit' && $cycle->id > 0) {
		$dilObj = new RevisionDiligence($db);
		$dils = $dilObj->fetchAll('t.position', 'ASC', 0, 0, array('t.fk_cycle' => $cycle->id, 't.active' => 1));
		if (!is_array($dils)) {
			$dils = array();
		}
		$controlTypes = RevisionGuide::getControlTypes();

		print '<h3 style="margin-top:24px">'.$langs->trans('ATBRevisionCycleDiligences').'</h3>';
		print '<div class="tabsAction">';
		if ($canWrite) {
			print '<a class="butAction" href="diligence.php?action=create&amp;guide='.((int) $guideId).'&amp;cycle='.((int) $cycle->id).'">'.$langs->trans('ATBRevisionAddDiligence').'</a>';
		}
		print '</div>';

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans('ATBRevisionColRef').'</th>';
		print '<th>'.$langs->trans('ATBRevisionColDiligence').'</th>';
		print '<th>'.$langs->trans('ATBRevisionColType').'</th>';
		print '<th class="center">'.$langs->trans('Actions').'</th>';
		print '</tr>';
		if (empty($dils)) {
			print '<tr class="oddeven"><td colspan="4" class="opacitymedium" style="text-align:center">'.$langs->trans('ATBRevisionNoDiligence').'</td></tr>';
		}
		foreach ($dils as $d) {
			print '<tr class="oddeven">';
			print '<td class="bold" style="white-space:nowrap">'.dol_escape_htmltag($d->ref).'</td>';
			print '<td>'.dol_escape_htmltag($d->label).'</td>';
			print '<td>'.dol_escape_htmltag($langs->trans(isset($controlTypes[$d->type_control]) ? $controlTypes[$d->type_control] : $d->type_control)).'</td>';
			print '<td class="center">';
			if ($canWrite) {
				print '<a href="diligence.php?action=edit&amp;id='.((int) $d->id).'&amp;guide='.((int) $guideId).'" title="'.$langs->trans('Edit').'">'.img_edit($langs->trans('Edit')).'</a>';
				print '&nbsp;<a href="diligence.php?action=confirm_delete&amp;id='.((int) $d->id).'&amp;guide='.((int) $guideId).'" title="'.$langs->trans('Delete').'">'.img_delete($langs->trans('Delete')).'</a>';
			}
			print '</td>';
			print '</tr>';
		}
		print '</table>';
	}
}

llxFooter();
