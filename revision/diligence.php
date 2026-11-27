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
 * \file       accountancytoolbox/revision/diligence.php
 * \ingroup    accountancytoolbox
 * \brief      Fiche d'une diligence (question de contrôle) d'un cycle de guide de révision
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
$cycleId = GETPOST('cycle', 'int');

/**
 * Normalize a list of account prefixes entered as comma separated text
 *
 * @param string $raw Raw input
 * @return array Cleaned unique prefixes
 */
function revisionCleanPrefixes($raw)
{
	$out = array();
	foreach (preg_split('/[\s,;]+/', (string) $raw) as $p) {
		$p = preg_replace('/[^0-9A-Za-z]/', '', $p);
		if ($p !== '') {
			$out[$p] = $p;
		}
	}
	return array_values($out);
}

/*
 * Actions
 */
if (($action === 'add' || $action === 'update') && $_SERVER['REQUEST_METHOD'] === 'POST' && $canWrite) {
	$token = GETPOST('token', 'alphanohtml');
	if (empty($token) || (!hash_equals(newToken(), $token) && !hash_equals(currentToken(), $token))) {
		accessforbidden('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry');
	}

	$dil = new RevisionDiligence($db);
	if ($action === 'update' && $id > 0) {
		$dil->fetch($id);
		$guideId = (int) $dil->fk_guide;
	} else {
		$dil->fk_guide = $guideId;
	}

	$typeControl = GETPOST('type_control', 'aZ09');
	$controlTypes = RevisionGuide::getControlTypes();
	if (!isset($controlTypes[$typeControl])) {
		$typeControl = 'justificatifs';
	}

	$accPrefixes = GETPOST('acc_prefix', 'array');
	if (!is_array($accPrefixes)) {
		$accPrefixes = array();
	}
	$accPrefixes = array_merge($accPrefixes, revisionCleanPrefixes(GETPOST('acc_custom', 'restricthtml')));
	$accPrefixes = array_values(array_unique(array_map(function ($p) {
		return preg_replace('/[^0-9A-Za-z]/', '', (string) $p);
	}, $accPrefixes)));
	$accPrefixes = array_values(array_filter($accPrefixes));

	$dil->fk_cycle = GETPOST('fk_cycle', 'int') ?: $cycleId;
	$dil->ref = strtoupper(preg_replace('/[^A-Za-z0-9.\-]/', '', trim(GETPOST('ref', 'restricthtml'))));
	$dil->label = trim(GETPOST('label', 'restricthtml'));
	$dil->type_control = $typeControl;
	$dil->accounts_filter = json_encode($accPrefixes);
	$dil->numerator_filter = json_encode(revisionCleanPrefixes(GETPOST('num_prefixes', 'restricthtml')));
	$dil->denominator_filter = json_encode(revisionCleanPrefixes(GETPOST('den_prefixes', 'restricthtml')));
	$dil->applicable_default = GETPOST('applicable_default', 'int') ? 1 : 0;
	$dil->priority = GETPOST('priority', 'int') ? 1 : 0;
	$dil->note = GETPOST('note', 'restricthtml');
	$dil->active = GETPOST('active', 'int') ? 1 : 0;

	if ($dil->fk_cycle <= 0 || $dil->label === '') {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->trans('ATBRevisionDiligenceCycle').' / '.$langs->trans('ATBRevisionDiligenceName')), null, 'errors');
		$action = ($action === 'update') ? 'edit' : 'create';
	} else {
		if ($dil->ref === '') {
			$cycleTmp = new RevisionCycle($db);
			$cycleCode = 'X';
			if ($cycleTmp->fetch($dil->fk_cycle) > 0) {
				$cycleCode = $cycleTmp->code;
			}
			$dil->ref = $dil->getNextRefForCycle($dil->fk_cycle, $cycleCode);
		}
		if (empty($dil->position)) {
			$sql = "SELECT COALESCE(MAX(position), 0) + 1 as nextpos FROM ".MAIN_DB_PREFIX."accountancytoolbox_revision_diligence WHERE fk_cycle = ".((int) $dil->fk_cycle);
			$resql = $db->query($sql);
			if ($resql && ($obj = $db->fetch_object($resql))) {
				$dil->position = (int) $obj->nextpos;
			}
		}

		if ($action === 'update') {
			$res = $dil->update($user);
		} else {
			$res = $dil->create($user);
		}
		if ($res > 0) {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: revision.php?guide='.((int) $dil->fk_guide).'#cycle'.((int) $dil->fk_cycle));
			exit;
		}
		setEventMessages($dil->error ?: $db->lasterror(), null, 'errors');
		$action = ($action === 'update') ? 'edit' : 'create';
	}
}

if ($action === 'delete' && $canWrite && $id > 0) {
	$token = GETPOST('token', 'alphanohtml');
	if (empty($token) || (!hash_equals(newToken(), $token) && !hash_equals(currentToken(), $token))) {
		accessforbidden('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry');
	}
	$dil = new RevisionDiligence($db);
	if ($dil->fetch($id) > 0) {
		$guideId = (int) $dil->fk_guide;
		$cycleId = (int) $dil->fk_cycle;
		if ($dil->delete($user) > 0) {
			setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
		}
	}
	header('Location: revision.php?guide='.((int) $guideId).'#cycle'.((int) $cycleId));
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
	$dil = new RevisionDiligence($db);
	$dil->fetch($id);
	$guideId = (int) $dil->fk_guide;
	print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.((int) $id).'&guide='.((int) $guideId), $langs->trans('ATBRevisionDeleteDiligenceTitle'), $langs->trans('ATBRevisionConfirmDeleteDiligence'), 'delete', '', '', 1);
	print '<br>';
}

if ($action === 'create' || $action === 'edit' || $action === 'add' || $action === 'update') {
	$dil = new RevisionDiligence($db);
	if (($action === 'edit' || $action === 'update') && $id > 0) {
		$dil->fetch($id);
		$guideId = (int) $dil->fk_guide;
		$cycleId = (int) $dil->fk_cycle;
	} else {
		$dil->fk_guide = $guideId;
		$dil->fk_cycle = $cycleId;
		$dil->ref = '';
		$dil->label = '';
		$dil->type_control = 'justificatifs';
		$dil->accounts_filter = '[]';
		$dil->numerator_filter = '[]';
		$dil->denominator_filter = '[]';
		$dil->applicable_default = 1;
		$dil->priority = 0;
		$dil->note = '';
		$dil->active = 1;
	}

	$controlTypes = RevisionGuide::getControlTypes();

	$cycleObj = new RevisionCycle($db);
	$cycles = $cycleObj->fetchAll('t.position', 'ASC', 0, 0, array('t.fk_guide' => $guideId, 't.active' => 1));
	if (!is_array($cycles)) {
		$cycles = array();
	}

	$selectedAccs = $dil->getAccountsFilter();
	$selectedNums = $dil->getNumeratorFilter();
	$selectedDens = $dil->getDenominatorFilter();

	// Prefixes de comptes proposés (2 premiers chiffres du plan de comptes actif)
	$prefixes = array();
	$sql = "SELECT DISTINCT numero_compte FROM ".MAIN_DB_PREFIX."accounting_account";
	$sql .= " WHERE entity IN (".getEntity('accounting_account').") AND active = 1";
	$sql .= " ORDER BY numero_compte ASC";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$num = (string) $obj->numero_compte;
			if (strlen($num) >= 2) {
				$p = substr($num, 0, 2);
				$prefixes[$p] = $p;
			}
		}
	}
	sort($prefixes);
	$customAccs = array_diff($selectedAccs, $prefixes);

	$formTitle = ($action === 'edit' || $action === 'update') ? $langs->trans('ATBRevisionEditDiligenceTitle') : $langs->trans('ATBRevisionNewDiligenceTitle');
	$saveAction = ($action === 'edit' || $action === 'update') ? 'update' : 'add';

	print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" id="diligenceForm">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="'.$saveAction.'">';
	print '<input type="hidden" name="id" value="'.((int) $dil->id).'">';
	print '<input type="hidden" name="guide" value="'.((int) $guideId).'">';
	print '<input type="hidden" name="cycle" value="'.((int) $cycleId).'">';

	print load_fiche_titre($formTitle, '', '');

	// Informations générales
	print '<div class="fiche">';
	print '<div class="fichecenter">';
	print '<table class="border centpercent tableforfield">';

	print '<tr><td class="titlefieldcreate" style="width:260px" colspan="2"><strong>'.$langs->trans('ATBRevisionDiligenceGeneral').'</strong></td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans('ATBRevisionDiligenceName').'</td><td>';
	print '<input type="text" name="label" class="minwidth400" value="'.dol_escape_htmltag($dil->label).'" placeholder="'.dol_escape_htmltag($langs->trans('ATBRevisionDiligenceNamePlaceholder')).'" required>';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('ATBRevisionColRef').'</td><td>';
	print '<input type="text" name="ref" class="width100" maxlength="16" value="'.dol_escape_htmltag($dil->ref).'" placeholder="A.1">';
	print '</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans('ATBRevisionDiligenceCycle').'</td><td>';
	print '<select name="fk_cycle" class="minwidth300">';
	if (empty($cycles)) {
		print '<option value="">'.$langs->trans('ATBRevisionChoose').'</option>';
	}
	foreach ($cycles as $c) {
		print '<option value="'.((int) $c->id).'"'.((int) $c->id === (int) $dil->fk_cycle ? ' selected' : '').'>'.dol_escape_htmltag($c->code.' - '.$c->label).'</option>';
	}
	print '</select>';
	print '</td></tr>';

	print '</table>';
	print '</div>';

	// Type de contrôle
	print '<table class="border centpercent tableforfield" style="margin-top:14px">';
	print '<tr><td class="titlefieldcreate" colspan="2"><strong>'.$langs->trans('ATBRevisionControlQuestion').'</strong><br><span class="opacitymedium" style="font-weight:400">'.$langs->trans('ATBRevisionControlHelp').'</span></td></tr>';
	print '<tr><td colspan="2">';
	foreach ($controlTypes as $tcode => $tkey) {
		print '<label class="rev-type-option">';
		print '<input type="radio" name="type_control" value="'.$tcode.'"'.($dil->type_control === $tcode ? ' checked' : '').'> ';
		print '<strong>'.$langs->trans($tkey).'</strong> — <span class="opacitymedium">'.$langs->trans($tkey.'Desc').'</span>';
		print '</label><br>';
	}
	print '</td></tr>';
	print '</table>';

	// Comptes à inclure
	print '<table class="border centpercent tableforfield" style="margin-top:14px">';
	print '<tr><td class="titlefieldcreate" colspan="2"><strong>'.$langs->trans('ATBRevisionDiligenceAccounts').'</strong><br><span class="opacitymedium" style="font-weight:400">'.$langs->trans('ATBRevisionDiligenceAccountsHelp').'</span></td></tr>';
	print '<tr><td colspan="2">';
	print '<div class="rev-prefix-list">';
	foreach ($prefixes as $p) {
		print '<label class="rev-prefix-item"><input type="checkbox" name="acc_prefix[]" value="'.$p.'"'.(in_array($p, $selectedAccs, true) ? ' checked' : '').'> '.dol_escape_htmltag($langs->trans('ATBRevisionAccountsPrefix', $p).'…').'</label>';
	}
	print '</div>';
	print '<div style="margin-top:8px">';
	print '<label>'.$langs->trans('ATBRevisionCustomPrefixes').' : </label>';
	print '<input type="text" name="acc_custom" class="minwidth300" value="'.dol_escape_htmltag(implode(', ', $customAccs)).'" placeholder="ex. 471, 7402">';
	print '</div>';
	print '</td></tr>';
	print '</table>';

	// Ratios : numérateur / dénominateur
	print '<table class="border centpercent tableforfield rev-ratios-block" id="revRatiosBlock" style="margin-top:14px">';
	print '<tr><td class="titlefieldcreate" style="width:260px">'.$langs->trans('ATBRevisionNumerator').'</td><td>';
	print '<input type="text" name="num_prefixes" class="minwidth300" value="'.dol_escape_htmltag(implode(', ', $selectedNums)).'" placeholder="ex. 641">';
	print '</td></tr>';
	print '<tr><td class="titlefieldcreate">'.$langs->trans('ATBRevisionDenominator').'</td><td>';
	print '<input type="text" name="den_prefixes" class="minwidth300" value="'.dol_escape_htmltag(implode(', ', $selectedDens)).'" placeholder="ex. 645">';
	print '</td></tr>';
	print '</table>';

	// Options avancées
	print '<table class="border centpercent tableforfield" style="margin-top:14px">';
	print '<tr><td class="titlefieldcreate" colspan="2"><strong>'.$langs->trans('ATBRevisionAdvanced').'</strong></td></tr>';
	print '<tr><td style="width:260px">'.$langs->trans('ATBRevisionApplicableDefault').'</td><td>';
	print '<input type="checkbox" name="applicable_default" value="1"'.(!empty($dil->applicable_default) ? ' checked' : '').'>';
	print ' <span class="opacitymedium">'.$langs->trans('ATBRevisionApplicableDefaultHelp').'</span>';
	print '</td></tr>';
	print '<tr><td>'.$langs->trans('ATBRevisionPriority').'</td><td>';
	print '<input type="checkbox" name="priority" value="1"'.(!empty($dil->priority) ? ' checked' : '').'>';
	print ' <span class="opacitymedium">'.$langs->trans('ATBRevisionPriorityHelp').'</span>';
	print '</td></tr>';
	print '<tr><td>'.$langs->trans('ATBRevisionDiligenceNote').'</td><td>';
	print '<textarea name="note" class="quatrevingtpercent" rows="4">'.dol_escape_htmltag($dil->note).'</textarea>';
	print '</td></tr>';
	print '<tr><td>'.$langs->trans('Active').'</td><td>';
	print '<input type="checkbox" name="active" value="1"'.(!empty($dil->active) ? ' checked' : '').'>';
	print '</td></tr>';
	print '</table>';

	print '</div>';

	print '<div class="center" style="margin-top:14px">';
	print '<button type="submit" class="button button-save">'.$langs->trans('Save').'</button>';
	print '&nbsp;&nbsp;<a class="button button-cancel" href="revision.php?guide='.((int) $guideId).'">'.$langs->trans('Cancel').'</a>';
	print '</div>';

	print '</form>';
?>
	<style>
		.rev-type-option { display: inline-block; padding: 4px 0; }
		.rev-prefix-list { display: flex; flex-wrap: wrap; gap: 4px 14px; max-height: 180px; overflow: auto; padding: 6px; border: 1px solid var(--colortopborder, #e2e8f0); border-radius: 6px; background: var(--colorbacktitle2, #f8fafb); }
		.rev-prefix-item { white-space: nowrap; font-size: .9em; }
	</style>
	<script>
		function revToggleRatios() {
			var checked = document.querySelector('input[name="type_control"]:checked');
			var v = checked ? checked.value : '';
			document.getElementById('revRatiosBlock').style.display = (v === 'ratios') ? 'table' : 'none';
		}
		document.querySelectorAll('input[name="type_control"]').forEach(function (r) {
			r.addEventListener('change', revToggleRatios);
		});
		revToggleRatios();
	</script>
<?php
}

llxFooter();
