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
 * \file       accountancytoolbox/od_kilometer.php
 * \ingroup    accountancytoolbox
 * \brief      Saisie au kilomètre d'écritures diverses équilibrées (lot comptable)
 */

$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
dol_include_once('/accountancytoolbox/lib/accountancytoolbox.lib.php');

$langs->loadLangs(array('accountancy', 'compta', 'main', 'accountancytoolbox@accountancytoolbox'));

if (!$user->hasRight('accounting', 'mouvements', 'creer') && !$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

// Récupération des journaux comptables actifs
$journals = array();
$sqlJ = "SELECT rowid, code, label FROM ".MAIN_DB_PREFIX."accounting_journal WHERE entity IN (".getEntity('accounting_journal').") AND active = 1 ORDER BY code ASC";
$resJ = $db->query($sqlJ);
if ($resJ) {
	while ($objJ = $db->fetch_object($resJ)) {
		$journals[] = array(
			'code'  => $objJ->code,
			'label' => $objJ->code.' - '.$objJ->label,
			'jlabel' => $objJ->label,
		);
	}
}
if (empty($journals)) {
	$journals[] = array('code' => 'OD', 'label' => 'OD - Opérations diverses', 'jlabel' => 'Opérations diverses');
}

/*
 * Actions
 */
if ($action === 'save_batch' && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = GETPOST('token', 'alphanohtml');
	if (empty($token) || (!hash_equals(newToken(), $token) && !hash_equals(currentToken(), $token))) {
		accessforbidden('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry');
	}

	$journalCode = GETPOST('journal_code', 'alphanohtml') ?: $journals[0]['code'];
	$pieceDateStr = GETPOST('piece_date', 'alphanohtml');
	$pieceRef = trim(GETPOST('piece_ref', 'alphanohtml'));

	$accounts = GETPOST('account', 'array');
	$subaccounts = GETPOST('subaccount', 'array');
	$labels = GETPOST('label', 'array');
	$debits = GETPOST('debit', 'array');
	$credits = GETPOST('credit', 'array');

	$pieceTs = $pieceDateStr ? dol_stringtotime($pieceDateStr) : dol_now();
	$lines = array();
	$accountLabels = array();
	$totalDebit = 0.0;
	$totalCredit = 0.0;

	$nbLines = max(count($accounts), count($debits), count($credits));
	for ($idx = 0; $idx < $nbLines; $idx++) {
		$rawAcc = trim((string) ($accounts[$idx] ?? ''));
		$rawSub = trim((string) ($subaccounts[$idx] ?? ''));
		if ($rawAcc === '-1') {
			$rawAcc = '';
		}
		if ($rawSub === '-1') {
			$rawSub = '';
		}
		$acc = preg_replace('/[^0-9A-Za-z]/', '', $rawAcc);
		$sub = preg_replace('/[^0-9A-Za-z]/', '', $rawSub);
		$lbl = trim((string) ($labels[$idx] ?? ''));
		$deb = (float) price2num((string) ($debits[$idx] ?? '0'));
		$crd = (float) price2num((string) ($credits[$idx] ?? '0'));

		if ($acc === '' && abs($deb) < 0.005 && abs($crd) < 0.005) {
			continue;
		}
		if (!isset($accountLabels[$acc])) {
			$tmpAccount = new AccountingAccount($db);
			$resAccount = ($acc !== '') ? $tmpAccount->fetch(0, $acc, 1) : -1;
			if ($resAccount > 0) {
				$accountLabels[$acc] = !empty($tmpAccount->labelshort) ? $tmpAccount->labelshort : ($tmpAccount->label ?: '');
			} else {
				$accountLabels[$acc] = null;
			}
		}
		if ($acc === '') {
			setEventMessages('Ligne '.($idx + 1).' : le numéro de compte est obligatoire.', null, 'errors');
			$lines = array();
			break;
		}
		if ($accountLabels[$acc] === null) {
			setEventMessages('Ligne '.($idx + 1).' : le compte \''.$acc."'\' est introuvable dans le plan comptable actif.", null, 'errors');
			$lines = array();
			break;
		}
		if (abs($deb) < 0.005 && abs($crd) < 0.005) {
			setEventMessages('Ligne '.($idx + 1).' : saisissez un montant au débit ou au crédit.', null, 'errors');
			$lines = array();
			break;
		}

		$totalDebit += $deb;
		$totalCredit += $crd;
		$lines[] = array(
			'account'      => $acc,
			'subaccount'   => $sub,
			'label'        => $lbl !== '' ? $lbl : ($pieceRef !== '' ? $pieceRef : 'Saisie OD au kilomètre'),
			'label_compte' => $accountLabels[$acc],
			'debit'        => $deb,
			'credit'       => $crd,
		);
	}

	$diff = round($totalDebit - $totalCredit, 2);
	if (!empty($lines)) {
		if (abs($diff) >= 0.01) {
			setEventMessages('Validation impossible : le lot n\'est pas équilibré (écart : '.price($diff).').', null, 'errors');
		} else {
			$bookkeepingStatic = new BookKeeping($db);
			$pieceNum = (int) $bookkeepingStatic->getNextNumMvt();
			if ($pieceNum < 1) {
				setEventMessages($langs->trans('Error').' : '.$bookkeepingStatic->error, null, 'errors');
			} else {
				$db->begin();
				$error = 0;
				$docRef = $pieceRef !== '' ? $pieceRef : 'OD'.dol_print_date($pieceTs, '%Y%m%d');
				$journalLabel = '';
				foreach ($journals as $j) {
					if ($j['code'] === $journalCode) {
						$journalLabel = $j['jlabel'];
						break;
					}
				}
				$dateSql = $db->idate($pieceTs);
				$nowSql = $db->idate(dol_now());

				foreach ($lines as $ln) {
					$sens = ($ln['debit'] > 0) ? 'D' : 'C';
					$montant = ($ln['debit'] > 0) ? $ln['debit'] : $ln['credit'];

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."accounting_bookkeeping (";
					$sql .= "doc_date, doc_type, doc_ref, fk_doc, fk_docdet, code_journal, journal_label, piece_num, numero_compte, label_compte, subledger_account, subledger_label, label_operation, debit, credit, montant, sens, fk_user_author, date_creation, entity";
					$sql .= ") VALUES (";
					$sql .= "'".$dateSql."',";
					$sql .= "'od_km',";
					$sql .= "'".$db->escape($docRef)."',";
					$sql .= "0,";
					$sql .= "0,";
					$sql .= "'".$db->escape($journalCode)."',";
					$sql .= ($journalLabel !== '' ? "'".$db->escape($journalLabel)."'," : "NULL,");
					$sql .= ((int) $pieceNum).",";
					$sql .= "'".$db->escape($ln['account'])."',";
					$sql .= "'".$db->escape($ln['label_compte'])."',";
					$sql .= ($ln['subaccount'] !== '' ? "'".$db->escape($ln['subaccount'])."'," : "NULL,");
					$sql .= ($ln['subaccount'] !== '' ? "'".$db->escape($ln['label'])."'," : "NULL,");
					$sql .= "'".$db->escape($ln['label'])."',";
					$sql .= ((float) $ln['debit']).",";
					$sql .= ((float) $ln['credit']).",";
					$sql .= ((float) $montant).",";
					$sql .= "'".$sens."',";
					$sql .= ((int) $user->id).",";
					$sql .= "'".$nowSql."',";
					$sql .= ((int) $conf->entity);
					$sql .= ")";

					if (!$db->query($sql)) {
						$error++;
						dol_syslog(__METHOD__.' '.$db->lasterror(), LOG_ERR);
						break;
					}
				}

				if (!$error) {
					$db->commit();
					setEventMessages('Lot d\'écritures diverses enregistré ('.count($lines).' ligne(s), '.price($totalDebit).' '.$langs->getCurrencySymbol($conf->currency).').', null, 'mesgs');
					header('Location: '.$_SERVER['PHP_SELF']);
					exit;
				} else {
					$db->rollback();
					setEventMessages($langs->trans('Error').' : '.$db->lasterror(), null, 'errors');
				}
			}
		}
	}
}

/*
 * View
 */
$form = new Form($db);
$formaccounting = new FormAccounting($db);
$title = 'Écritures diverses — Saisie au kilomètre';

$accountSelectHtml = '';
$tmpAccountSelect = $formaccounting->select_account('', 'kmTplAccount', 1, array(), 1, 1, 'km-cell km-select');
if (is_string($tmpAccountSelect)) {
	$accountSelectHtml = preg_replace('/\s*<script\b[^>]*>[\s\S]*?<\/script>/i', '', $tmpAccountSelect);
}

$savedAuxMode = getDolGlobalInt('ACCOUNTANCY_AUXACCOUNT_USE_SEARCH_TO_SELECT');
setDolGlobalString('ACCOUNTANCY_AUXACCOUNT_USE_SEARCH_TO_SELECT', 1);
$tmpAuxSelect = $formaccounting->select_auxaccount('', 'kmTplAux', 1, 'km-cell km-select', 'odkm');
setDolGlobalString('ACCOUNTANCY_AUXACCOUNT_USE_SEARCH_TO_SELECT', $savedAuxMode);
$auxSelectHtml = '';
if (is_string($tmpAuxSelect)) {
	$auxSelectHtml = preg_replace('/\s*<script\b[^>]*>[\s\S]*?<\/script>/i', '', $tmpAuxSelect);
}

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-accountancytoolbox page-od-kilometer');

if (function_exists('accountancytoolboxPrepareHead')) {
	$head = accountancytoolboxPrepareHead();
	print dol_get_fiche_head($head, 'od_kilometer', $title, -1, 'fa-keyboard');
} else {
	print load_fiche_titre($title, '', 'fa-keyboard');
}
?>
	<style>
        #kmWrapper { background: var(--colorbacktitle1, #fff); border: 1px solid var(--colortopborder, #d1d5db); border-radius: 8px; overflow: hidden; }
        #kmWrapper:fullscreen, #kmWrapper:-webkit-full-screen { width: 100%; height: 100%; overflow: auto; border-radius: 0; padding: 14px; box-sizing: border-box; }
        .km-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--colortopborder, #e5e7eb); background: var(--colorbacktitle2, #f9fafb); flex-wrap: wrap; }
        .km-header h2 { margin: 0; font-size: 1.15em; font-weight: 700; }
        .km-header p { margin: 3px 0 0; font-size: .88em; color: var(--colortext, #6b7280); opacity: .8; }
        .km-toolbar-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .km-meta { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; padding: 12px 18px; border-bottom: 1px solid var(--colortopborder, #e5e7eb); font-size: .92em; }
        .km-meta label { font-weight: 600; margin-right: 4px; }
        .km-meta select, .km-meta input { padding: 5px 9px; border: 1px solid var(--colortopborder, #cbd5e1); border-radius: 5px; background: var(--colorbacktitle1, #fff); color: var(--colortext, #111827); }
        .km-grid-wrap { min-height: 360px; max-height: 55vh; overflow: auto; display: flex; flex-direction: column; justify-content: space-between; }
        .km-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .km-table th { position: sticky; top: 0; z-index: 2; background: var(--colorbacktitle2, #f3f4f6); border-bottom: 1px solid var(--colortopborder, #d1d5db); border-right: 1px solid var(--colortopborder, #e5e7eb); padding: 8px 10px; text-align: left; font-size: .88em; font-weight: 700; }
        .km-table th.num, .km-table td.num { text-align: right; }
        .km-table td { border-bottom: 1px solid var(--colortopborder, #e5e7eb); border-right: 1px solid var(--colortopborder, #f1f5f9); padding: 0; height: 34px; }
        .km-cell { width: 100%; height: 100%; min-height: 34px; border: 0; background: transparent; padding: 6px 9px; font-size: .92em; color: var(--colortext, #111827); box-sizing: border-box; outline: none; }
        .km-cell.num { text-align: right; font-variant-numeric: tabular-nums; }
        .km-cell:focus { box-shadow: inset 0 0 0 2px #2563eb; background: rgba(37, 99, 235, .04); }
        .km-summary { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 9px 18px; border-top: 1px solid var(--colortopborder, #e5e7eb); background: var(--colorbacktitle2, #f9fafb); font-size: .9em; flex-wrap: wrap; }
        .km-shortcuts { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; padding: 10px 18px; border-top: 1px solid var(--colortopborder, #f1f5f9); font-size: .82em; color: var(--colortext, #6b7280); }
        .km-kbd { display: inline-block; padding: 1px 6px; border: 1px solid var(--colortopborder, #cbd5e1); border-radius: 4px; background: var(--colorbacktitle1, #fff); font-family: monospace; font-size: .92em; color: var(--colortext, #374151); }
        .btn-validate-lot { padding: 6px 14px; border-radius: 6px; border: 1px solid #16a34a; background: #16a34a; color: #fff; font-weight: 600; cursor: pointer; }
        .btn-validate-lot:disabled { opacity: .45; cursor: not-allowed; }
        .btn-clear-lot { padding: 6px 12px; border-radius: 6px; border: 1px solid var(--colortopborder, #cbd5e1); background: var(--colorbacktitle1, #fff); color: var(--colortext, #374151); font-weight: 600; cursor: pointer; }
	</style>

	<div id="kmWrapper">
		<form id="kmForm" method="POST" action="<?php echo dol_escape_htmltag($_SERVER['PHP_SELF']); ?>">
			<input type="hidden" name="token" value="<?php echo newToken(); ?>">
			<input type="hidden" name="action" value="save_batch">

			<div class="km-header">
				<div>
					<h2>Écritures diverses</h2>
					<p>Saisie au kilomètre : un lot équilibré, un journal, une date de pièce</p>
				</div>
				<div class="km-toolbar-actions">
					<button type="submit" id="btnValidateLot" class="btn-validate-lot" disabled>Valider le lot</button>
					<button type="button" id="btnClearDraft" class="btn-clear-lot">Vider le brouillon</button>
					<button type="button" id="btnFullscreen" class="btn-clear-lot">Plein écran</button>
				</div>
			</div>

			<div class="km-meta">
				<div>
					<label for="journal_code">Journal</label>
					<select name="journal_code" id="journal_code">
						<?php foreach ($journals as $j) { ?>
							<option value="<?php echo dol_escape_htmltag($j['code']); ?>"><?php echo dol_escape_htmltag($j['label']); ?></option>
						<?php } ?>
					</select>
				</div>
				<div>
					<label for="piece_date">Date de pièce</label>
					<input type="date" name="piece_date" id="piece_date" value="<?php echo dol_print_date(dol_now(), '%Y-%m-%d'); ?>">
				</div>
				<div>
					<label for="piece_ref">Référence</label>
					<input type="text" name="piece_ref" id="piece_ref" placeholder="Ex. OD-2026-11">
				</div>
				<span class="opacitymedium small">Journal et date sont repris sur toutes les lignes du lot</span>
			</div>

			<div class="km-grid-wrap">
				<table class="km-table" id="kmTable">
					<thead>
					<tr>
						<th style="width:15%">Compte</th>
						<th style="width:15%">Compte auxiliaire</th>
						<th style="width:38%">Libellé</th>
						<th class="num" style="width:16%">Débit</th>
						<th class="num" style="width:16%">Crédit</th>
					</tr>
					</thead>
					<tbody id="kmTbody"></tbody>
				</table>

				<template id="kmTplAccount"><?php echo $accountSelectHtml; ?></template>
				<template id="kmTplAux"><?php echo $auxSelectHtml; ?></template>

				<div class="km-summary">
					<div>
						Lignes <strong id="kmLineCount">0</strong>
						&nbsp;·&nbsp; Total débit <strong id="kmSumDebit">0,00</strong>
						&nbsp;·&nbsp; Total crédit <strong id="kmSumCredit">0,00</strong>
						&nbsp;·&nbsp; Écart <strong id="kmDiff" style="color:#dc2628">0,00</strong>
						&nbsp;·&nbsp; <span id="kmStatusText" style="color:#dc2628">Validation impossible : le lot n'a aucun montant</span>
					</div>
					<div>
						D <span id="kmFootD">0.00</span> &nbsp; C <span id="kmFootC">0.00</span> &nbsp; <strong id="kmFootDiff" style="color:#dc2628">0.00</strong>
					</div>
				</div>
			</div>

			<div class="km-shortcuts">
				<span>Clavier</span>
				<span><span class="km-kbd">F2</span> journal suivant</span>
				<span><span class="km-kbd">F7</span> ajouter une ligne</span>
				<span><span class="km-kbd">Ctrl+D</span> dupliquer la ligne précédente</span>
				<span><span class="km-kbd">Ctrl+Suppr</span> supprimer la ligne</span>
				<span><span class="km-kbd">Entrée</span> valider et passer à la ligne suivante</span>
				<span><span class="km-kbd">Ctrl+S</span> valider le lot</span>
				<span><span class="km-kbd">Échap</span> quitter la grille</span>
			</div>
		</form>
	</div>

	<script>
        (function() {
            var tbody = document.getElementById('kmTbody');
            var btnValidate = document.getElementById('btnValidateLot');
            var btnClear = document.getElementById('btnClearDraft');
            var btnFullscreen = document.getElementById('btnFullscreen');
            var wrapper = document.getElementById('kmWrapper');
            var journalSelect = document.getElementById('journal_code');
            var tplAccount = document.getElementById('kmTplAccount');
            var tplAux = document.getElementById('kmTplAux');

            function buildField(colIdx, c) {
                var tpl = colIdx === 0 ? tplAccount : (colIdx === 1 ? tplAux : null);
                var field = null;
                if (tpl && tpl.content && tpl.content.firstElementChild) {
                    field = tpl.content.firstElementChild.cloneNode(true);
                    field.removeAttribute('id');
                }
                if (!field) {
                    field = document.createElement('input');
                    field.type = 'text';
                    field.placeholder = c.ph;
                }
                field.name = c.name;
                field.className = c.cls;
                field.dataset.col = colIdx;
                if (field.tagName === 'SELECT') {
                    field.value = (c.val !== undefined && c.val !== '' && c.val !== null) ? c.val : '-1';
                    if (field.selectedIndex < 0) field.selectedIndex = 0;
                } else {
                    field.value = c.val || '';
                }
                field.addEventListener('input', recompute);
                field.addEventListener('change', recompute);
                field.addEventListener('keydown', onCellKeydown);
                return field;
            }

            function getLastLabel(rows) {
                if (!rows.length) return '';
                var fields = rows[rows.length - 1].querySelectorAll('input, select');
                return fields[2] ? fields[2].value : '';
            }

            function parseAmt(val) {
                if (!val) return 0;
                var n = parseFloat(String(val).replace(/\s+/g, '').replace(',', '.'));
                return isNaN(n) ? 0 : n;
            }

            function fmtFr(n) {
                return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function createRow(values) {
                values = values || { account: '', subaccount: '', label: '', debit: '', credit: '' };
                var tr = document.createElement('tr');
                var cols = [
                    { name: 'account[]', cls: 'km-cell', val: values.account, ph: '401 / 606...' },
                    { name: 'subaccount[]', cls: 'km-cell', val: values.subaccount, ph: '' },
                    { name: 'label[]', cls: 'km-cell', val: values.label, ph: 'Libellé de l\'écriture' },
                    { name: 'debit[]', cls: 'km-cell num', val: values.debit, ph: '0,00' },
                    { name: 'credit[]', cls: 'km-cell num', val: values.credit, ph: '0,00' }
                ];
                cols.forEach(function(c, colIdx) {
                    var td = document.createElement('td');
                    if (c.cls.indexOf('num') !== -1) td.className = 'num';
                    td.appendChild(buildField(colIdx, c));
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
                recompute();
                return tr;
            }

            function getRows() {
                return Array.prototype.slice.call(tbody.querySelectorAll('tr'));
            }

            function recompute() {
                var rows = getRows();
                var sumD = 0, sumC = 0, activeLines = 0;

                rows.forEach(function(tr) {
                    var inputs = tr.querySelectorAll('input, select');
                    var acc = (inputs[0].value === '-1') ? '' : inputs[0].value.trim();
                    var d = parseAmt(inputs[3].value);
                    var c = parseAmt(inputs[4].value);
                    sumD += d;
                    sumC += c;
                    if (acc !== '' || d !== 0 || c !== 0) activeLines++;
                });

                var diff = Math.round((sumD - sumC) * 100) / 100;
                var hasAmount = (Math.abs(sumD) > 0.001 || Math.abs(sumC) > 0.001);
                var isBalanced = hasAmount && Math.abs(diff) < 0.01;

                document.getElementById('kmLineCount').textContent = activeLines;
                document.getElementById('kmSumDebit').textContent = fmtFr(sumD);
                document.getElementById('kmSumCredit').textContent = fmtFr(sumC);
                document.getElementById('kmDiff').textContent = fmtFr(diff);
                document.getElementById('kmFootD').textContent = sumD.toFixed(2);
                document.getElementById('kmFootC').textContent = sumC.toFixed(2);
                document.getElementById('kmFootDiff').textContent = diff.toFixed(2);

                var diffColor = Math.abs(diff) < 0.01 ? '#16a34a' : '#dc2628';
                document.getElementById('kmDiff').style.color = diffColor;
                document.getElementById('kmFootDiff').style.color = diffColor;

                var statusEl = document.getElementById('kmStatusText');
                if (!hasAmount) {
                    statusEl.textContent = "Validation impossible : le lot n'a aucun montant";
                    statusEl.style.color = '#dc2628';
                } else if (!isBalanced) {
                    statusEl.textContent = "Validation impossible : le lot n'est pas équilibré";
                    statusEl.style.color = '#dc2628';
                } else {
                    statusEl.textContent = "Lot équilibré, prêt à être validé";
                    statusEl.style.color = '#16a34a';
                }
                btnValidate.disabled = !isBalanced;
            }

            function focusCell(rowIdx, colIdx) {
                var rows = getRows();
                if (!rows[rowIdx]) return;
                var inputs = rows[rowIdx].querySelectorAll('input, select');
                var target = inputs[Math.max(0, Math.min(inputs.length - 1, colIdx))];
                if (target) target.focus();
            }

            function onCellKeydown(e) {
                var inp = e.target;
                var tr = inp.closest('tr');
                var rows = getRows();
                var rowIdx = rows.indexOf(tr);
                var colIdx = parseInt(inp.dataset.col, 10);

                if (e.key === 'F7') {
                    e.preventDefault();
                    createRow({ label: getLastLabel(rows) });
                    focusCell(rows.length, 0);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (rowIdx === rows.length - 1) {
                        createRow({ label: getLastLabel(rows) });
                    }
                    focusCell(rowIdx + 1, 0);
                } else if (e.ctrlKey && (e.key === 'd' || e.key === 'D')) {
                    e.preventDefault();
                    var srcIdx = rowIdx > 0 ? rowIdx - 1 : rowIdx;
                    var srcInputs = rows[srcIdx].querySelectorAll('input, select');
                    var vals = {
                        account: srcInputs[0].value,
                        subaccount: srcInputs[1].value,
                        label: srcInputs[2].value,
                        debit: srcInputs[3].value,
                        credit: srcInputs[4].value
                    };
                    createRow(vals);
                    focusCell(getRows().length - 1, colIdx);
                } else if (e.ctrlKey && (e.key === 'Delete' || e.key === 'Backspace')) {
                    e.preventDefault();
                    if (rows.length > 1) {
                        tr.remove();
                        recompute();
                        focusCell(Math.max(0, rowIdx - 1), colIdx);
                    } else {
                        tr.querySelectorAll('input, select').forEach(function(i) { i.value = (i.tagName === 'SELECT' ? '-1' : ''); });
                        recompute();
                    }
                } else if (e.key === 'Escape') {
                    inp.blur();
                }
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'F2') {
                    e.preventDefault();
                    if (journalSelect && journalSelect.options.length > 0) {
                        journalSelect.selectedIndex = (journalSelect.selectedIndex + 1) % journalSelect.options.length;
                    }
                } else if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
                    e.preventDefault();
                    if (!btnValidate.disabled) {
                        document.getElementById('kmForm').submit();
                    }
                }
            });

            btnClear.addEventListener('click', function() {
                tbody.innerHTML = '';
                createRow();
                createRow();
                focusCell(0, 0);
            });

            btnFullscreen.addEventListener('click', function() {
                if (!document.fullscreenElement) {
                    if (wrapper.requestFullscreen) wrapper.requestFullscreen();
                } else {
                    if (document.exitFullscreen) document.exitFullscreen();
                }
            });

            // 2 lignes initiales comme sur la saisie au kilomètre
            createRow();
            createRow();
            focusCell(0, 0);
        })();
	</script>
<?php
if (function_exists('accountancytoolboxPrepareHead')) {
	print dol_get_fiche_end();
}
llxFooter();
$db->close();