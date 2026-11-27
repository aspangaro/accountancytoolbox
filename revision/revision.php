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
 * \file       accountancytoolbox/revision/revision.php
 * \ingroup    accountancytoolbox
 * \brief      Plan de révision : modèles de guide, cycles (A à I) et diligences (questions) par cycle
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

$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */
if ($action === 'set_default' && $canWrite) {
	$token = GETPOST('token', 'alphanohtml');
	if (empty($token) || (!hash_equals(newToken(), $token) && !hash_equals(currentToken(), $token))) {
		accessforbidden('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry');
	}
	$guideId = GETPOST('guide', 'int');
	$guide = new RevisionGuide($db);
	if ($guideId > 0 && $guide->fetch($guideId) > 0) {
		$db->query("UPDATE ".MAIN_DB_PREFIX."accountancytoolbox_revision_guide SET is_default = 0 WHERE entity = ".((int) $conf->entity));
		$guide->is_default = 1;
		$guide->update($user);
		setEventMessages($langs->trans('ATBRevisionGuideSetAsDefault'), null, 'mesgs');
	}
	header('Location: '.$_SERVER['PHP_SELF'].'?guide='.((int) $guideId));
	exit;
}

/*
 * Load data
 */
$guideId = GETPOST('guide', 'int');

$guideTmp = new RevisionGuide($db);
$guides = $guideTmp->fetchAll('t.ref', 'ASC', 0, 0, array('t.active' => 1));
if (!is_array($guides)) {
	$guides = array();
}

if ($guideId <= 0 && !empty($guides)) {
	foreach ($guides as $g) {
		if (!empty($g->is_default)) {
			$guideId = (int) $g->id;
			break;
		}
	}
	if ($guideId <= 0) {
		$guideId = (int) $guides[0]->id;
	}
}

$currentGuide = null;
if ($guideId > 0) {
	$currentGuide = new RevisionGuide($db);
	if ($currentGuide->fetch($guideId) <= 0) {
		$currentGuide = null;
		$guideId = 0;
	}
}

$cycles = array();
$diligencesByCycle = array();
if ($currentGuide && $currentGuide->id > 0) {
	$cycleObj = new RevisionCycle($db);
	$cycles = $cycleObj->fetchAll('t.position', 'ASC', 0, 0, array('t.fk_guide' => $currentGuide->id, 't.active' => 1));
	if (!is_array($cycles)) {
		$cycles = array();
	}
	$dilObj = new RevisionDiligence($db);
	$dils = $dilObj->fetchAll('t.position', 'ASC', 0, 0, array('t.fk_guide' => $currentGuide->id, 't.active' => 1));
	if (is_array($dils)) {
		foreach ($dils as $d) {
			$diligencesByCycle[(int) $d->fk_cycle][] = $d;
		}
	}
}

$search = trim(GETPOST('search', 'alphanohtml'));
$cycleFilter = GETPOST('cycle', 'int');
$typeFilter = GETPOST('type', 'aZ09');

$controlTypes = RevisionGuide::getControlTypes();

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
?>
	<style>
		.rev-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin: 10px 0 14px; }
		.rev-guide-form { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
		.rev-guide-form label { font-weight: 600; }
		.rev-guide-form select { padding: 6px 10px; border: 1px solid var(--colortopborder, #cbd5e1); border-radius: 6px; background: var(--colorbacktitle1, #fff); }
		.rev-filterbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 11px 14px; margin-bottom: 14px; border: 1px solid var(--colortopborder, #d8dce3); border-radius: 6px; background: var(--colorbacktitle2, #f8fafb); }
		.rev-search-box { position: relative; display: flex; align-items: center; }
		.rev-search-box .fas { position: absolute; left: 10px; color: #94a3b8; font-size: .85em; pointer-events: none; }
		.rev-search-box input { padding: 6px 11px 6px 30px; border: 1px solid var(--colortopborder, #cbd5e1); border-radius: 6px; background: var(--colorbacktitle1, #fff); min-width: 240px; }
		.rev-filterbar select { padding: 6px 10px; border: 1px solid var(--colortopborder, #cbd5e1); border-radius: 6px; background: var(--colorbacktitle1, #fff); font-weight: 500; }
		.rev-filterbar label { font-weight: 600; font-size: .9em; }
		.rev-table { width: 100%; border-collapse: collapse; border: 1px solid var(--colortopborder, #d8dce3); font-size: .92em; background: var(--colorbacktitle1, #fff); }
		.rev-table th { background: var(--colorbacktitle2, #f8fafb); border-bottom: 1px solid var(--colortopborder, #d8dce3); padding: 9px 12px; text-align: left; font-size: .78em; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #475569; }
		.rev-table td { border-bottom: 1px solid var(--colortopborder, #eef1f5); padding: 8px 12px; vertical-align: middle; }
		.rev-cycle-row td { background: var(--colorbacktitle2, #eef2f7); font-weight: 700; padding: 10px 12px; }
		.rev-cycle-row .cycle-meta { font-weight: 400; font-size: .85em; color: #64748b; margin-left: 10px; }
		.rev-cycle-row .cycle-actions { float: right; font-weight: 400; font-size: .85em; }
		.rev-ref { font-weight: 700; white-space: nowrap; }
		.rev-badge { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: .78em; font-weight: 700; white-space: nowrap; }
		.rev-badge.type-analytique { background: #e0f2fe; color: #075985; }
		.rev-badge.type-rapprochement { background: #fef3c7; color: #92400e; }
		.rev-badge.type-ratios { background: #ede9fe; color: #5b21b6; }
		.rev-badge.type-justificatifs { background: #dcfce7; color: #166534; }
		.rev-tag { display: inline-block; padding: 1px 8px; margin: 1px 2px 1px 0; border-radius: 4px; font-size: .78em; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; white-space: nowrap; }
		.rev-tag.prio { background: #fee2e2; border-color: #fecaca; color: #991b1b; }
		.rev-actions { white-space: nowrap; text-align: right; }
		.rev-empty { padding: 26px; text-align: center; color: #64748b; border: 1px dashed var(--colortopborder, #cbd5e1); border-radius: 6px; margin: 12px 0; }
	</style>

	<div class="rev-toolbar">
		<form method="GET" action="<?php echo dol_escape_htmltag($_SERVER['PHP_SELF']); ?>" class="rev-guide-form">
			<label for="guide"><?php echo $langs->trans('ATBRevisionSelectGuide'); ?></label>
			<select name="guide" id="guide" onchange="this.form.submit()">
				<?php foreach ($guides as $g) { ?>
					<option value="<?php echo (int) $g->id; ?>"<?php echo ((int) $g->id === $guideId ? ' selected' : ''); ?>><?php echo dol_escape_htmltag($g->ref.' - '.$g->label.(!empty($g->is_default) ? ' ('.$langs->trans('ATBRevisionGuideDefault').')' : '')); ?></option>
				<?php } ?>
			</select>
			<button type="submit" class="button"><?php echo $langs->trans('Filter'); ?></button>
		</form>
		<div>
			<?php if ($canWrite) { ?>
				<a class="button butAction" href="guide.php?action=create"><?php echo $langs->trans('ATBRevisionNewGuide'); ?></a>
				<?php if ($currentGuide) { ?>
					<a class="button butAction" href="guide.php?action=edit&amp;id=<?php echo (int) $currentGuide->id; ?>"><?php echo $langs->trans('Edit'); ?></a>
					<?php if (empty($currentGuide->is_default)) { ?>
						<a class="button butAction" href="<?php echo dol_escape_htmltag($_SERVER['PHP_SELF']); ?>?action=set_default&amp;guide=<?php echo (int) $currentGuide->id; ?>&amp;token=<?php echo newToken(); ?>"><?php echo $langs->trans('ATBRevisionSetDefault'); ?></a>
					<?php } ?>
					<a class="button butAction" href="cycle.php?action=create&amp;guide=<?php echo (int) $currentGuide->id; ?>"><?php echo $langs->trans('ATBRevisionNewCycle'); ?></a>
				<?php } ?>
			<?php } ?>
			<a class="button butActionNew" href="guide.php"><?php echo $langs->trans('ATBRevisionGuidesList'); ?></a>
		</div>
	</div>

	<?php if (empty($guides)) { ?>
		<div class="rev-empty">
			<p><?php echo $langs->trans('ATBRevisionNoGuide'); ?></p>
			<p class="opacitymedium"><?php echo $langs->trans('ATBRevisionCreateFirstGuide'); ?></p>
			<?php if ($canWrite) { ?>
				<p><a class="button butAction" href="guide.php?action=create"><?php echo $langs->trans('ATBRevisionNewGuide'); ?></a></p>
			<?php } ?>
		</div>
	<?php } elseif ($currentGuide) { ?>

		<form method="GET" action="<?php echo dol_escape_htmltag($_SERVER['PHP_SELF']); ?>" class="rev-filterbar">
			<input type="hidden" name="guide" value="<?php echo (int) $currentGuide->id; ?>">
			<div class="rev-search-box">
				<span class="fas fa-search"></span>
				<input type="text" name="search" value="<?php echo dol_escape_htmltag($search); ?>" placeholder="<?php echo dol_escape_htmltag($langs->trans('ATBRevisionSearchDiligence')); ?>">
			</div>
			<div>
				<label for="cycle"><?php echo $langs->trans('ATBRevisionCycle'); ?></label>
				<select name="cycle" id="cycle">
					<option value="0"><?php echo $langs->trans('ATBRevisionFilterAllCycles'); ?></option>
					<?php foreach ($cycles as $c) { ?>
						<option value="<?php echo (int) $c->id; ?>"<?php echo ($cycleFilter == (int) $c->id ? ' selected' : ''); ?>><?php echo dol_escape_htmltag($c->code.' - '.$c->label); ?></option>
					<?php } ?>
				</select>
			</div>
			<div>
				<label for="type"><?php echo $langs->trans('ATBRevisionFilterType'); ?></label>
				<select name="type" id="type">
					<option value=""><?php echo $langs->trans('ATBRevisionFilterAllTypes'); ?></option>
					<?php foreach ($controlTypes as $tcode => $tkey) { ?>
						<option value="<?php echo dol_escape_htmltag($tcode); ?>"<?php echo ($typeFilter === $tcode ? ' selected' : ''); ?>><?php echo $langs->trans($tkey); ?></option>
					<?php } ?>
				</select>
			</div>
			<button type="submit" class="button"><?php echo $langs->trans('Filter'); ?></button>
			<a class="button button-cancel" href="<?php echo dol_escape_htmltag($_SERVER['PHP_SELF']); ?>?guide=<?php echo (int) $currentGuide->id; ?>"><?php echo $langs->trans('Reset'); ?></a>
		</form>

		<table class="rev-table">
			<thead>
				<tr>
					<th style="width:9%"><?php echo $langs->trans('ATBRevisionColRef'); ?></th>
					<th style="width:44%"><?php echo $langs->trans('ATBRevisionColDiligence'); ?></th>
					<th style="width:13%"><?php echo $langs->trans('ATBRevisionColType'); ?></th>
					<th style="width:18%"><?php echo $langs->trans('ATBRevisionColAccounts'); ?></th>
					<th style="width:8%"><?php echo $langs->trans('ATBRevisionColOptions'); ?></th>
					<th style="width:8%" class="rev-actions"><?php echo $langs->trans('Actions'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			$hasRows = false;
			foreach ($cycles as $c) {
				if ($cycleFilter > 0 && $cycleFilter !== (int) $c->id) {
					continue;
				}
				$cycleDils = isset($diligencesByCycle[(int) $c->id]) ? $diligencesByCycle[(int) $c->id] : array();
				$visibleDils = array();
				foreach ($cycleDils as $d) {
					if ($typeFilter !== '' && $d->type_control !== $typeFilter) {
						continue;
					}
					if ($search !== '' && mb_stripos($d->ref.' '.$d->label, $search) === false) {
						continue;
					}
					$visibleDils[] = $d;
				}
				if (empty($visibleDils) && ($search !== '' || $typeFilter !== '')) {
					continue;
				}
				$hasRows = true;
				?>
				<tr class="rev-cycle-row" id="cycle<?php echo (int) $c->id; ?>">
					<td colspan="6">
						<?php if ($canWrite) { ?>
							<span class="cycle-actions">
								<a href="diligence.php?action=create&amp;guide=<?php echo (int) $currentGuide->id; ?>&amp;cycle=<?php echo (int) $c->id; ?>"><?php echo $langs->trans('ATBRevisionAddDiligence'); ?></a>
								&nbsp;|&nbsp;
								<a href="cycle.php?action=edit&amp;id=<?php echo (int) $c->id; ?>&amp;guide=<?php echo (int) $currentGuide->id; ?>"><?php echo $langs->trans('ATBRevisionEditCycle'); ?></a>
							</span>
						<?php } ?>
						<?php echo dol_escape_htmltag($langs->trans('ATBRevisionCycle').' '.$c->code.' — '.$c->label); ?>
						<span class="cycle-meta">
							<?php echo dol_escape_htmltag($langs->trans('ATBRevisionCycleAccounts').' '.$c->accounts_scope); ?>
							<?php if (!empty($c->standard_controls)) { ?>
								&nbsp;·&nbsp;<?php echo dol_escape_htmltag($langs->trans('ATBRevisionCycleControls').' '.$c->standard_controls); ?>
							<?php } ?>
						</span>
					</td>
				</tr>
				<?php foreach ($visibleDils as $d) { ?>
					<tr>
						<td class="rev-ref"><?php echo dol_escape_htmltag($d->ref); ?></td>
						<td><?php echo dol_escape_htmltag($d->label); ?></td>
						<td><span class="rev-badge type-<?php echo dol_escape_htmltag($d->type_control); ?>"><?php echo $langs->trans(isset($controlTypes[$d->type_control]) ? $controlTypes[$d->type_control] : $d->type_control); ?></span></td>
						<td>
							<?php
							$accs = $d->getAccountsFilter();
							if (empty($accs)) {
								echo dol_escape_htmltag($langs->trans('ATBRevisionAllAccounts'));
							} else {
								echo dol_escape_htmltag($langs->trans('ATBRevisionAccountsList').' '.implode(', ', $accs));
							}
							if ($d->type_control === 'ratios') {
								echo '<br><span class="opacitymedium" style="font-size:.85em">'.$langs->trans('ATBRevisionNumerator').' : '.dol_escape_htmltag(implode(', ', $d->getNumeratorFilter())).' / '.$langs->trans('ATBRevisionDenominator').' : '.dol_escape_htmltag(implode(', ', $d->getDenominatorFilter())).'</span>';
							}
							?>
						</td>
						<td>
							<?php if (!empty($d->applicable_default)) { ?>
								<span class="rev-tag"><?php echo $langs->trans('ATBRevisionApplicableDefault'); ?></span>
							<?php } ?>
							<?php if (!empty($d->priority)) { ?>
								<span class="rev-tag prio"><?php echo $langs->trans('ATBRevisionPriorityTag'); ?></span>
							<?php } ?>
						</td>
						<td class="rev-actions">
							<?php if ($canWrite) { ?>
								<a href="diligence.php?action=edit&amp;id=<?php echo (int) $d->id; ?>&amp;guide=<?php echo (int) $currentGuide->id; ?>" title="<?php echo dol_escape_htmltag($langs->trans('Edit')); ?>"><?php echo img_edit($langs->trans('Edit')); ?></a>
								<a href="diligence.php?action=confirm_delete&amp;id=<?php echo (int) $d->id; ?>&amp;guide=<?php echo (int) $currentGuide->id; ?>" title="<?php echo dol_escape_htmltag($langs->trans('Delete')); ?>"><?php echo img_delete($langs->trans('Delete')); ?></a>
							<?php } ?>
						</td>
					</tr>
				<?php } ?>
				<?php if (empty($visibleDils)) { ?>
					<tr><td colspan="6" class="opacitymedium" style="padding-left:24px"><?php echo $langs->trans('ATBRevisionNoDiligence'); ?></td></tr>
				<?php } ?>
			<?php } ?>
			<?php if (!$hasRows) { ?>
				<tr><td colspan="6" class="opacitymedium" style="text-align:center"><?php echo $langs->trans('ATBRevisionNoDiligence'); ?></td></tr>
			<?php } ?>
			</tbody>
		</table>

	<?php } ?>

<?php
llxFooter();
