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
 * \file        accountancytoolbox/class/revisiondiligence.class.php
 * \ingroup     accountancytoolbox
 * \brief       Classe des diligences (questions de contrôle) d'un cycle de guide de révision
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Diligence (question) attachée à un cycle d'un modèle de guide de révision
 */
class RevisionDiligence extends CommonObject
{
	/**
	 * @var string Name of element
	 */
	public $element = 'accountancytoolbox_revision_diligence';

	/**
	 * @var string Name of table without prefix
	 */
	public $table_element = 'accountancytoolbox_revision_diligence';

	/**
	 * @var string Picto
	 */
	public $picto = 'fa-question-circle';

	/**
	 * @var int Multientity managed
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var int Id of guide
	 */
	public $fk_guide;

	/**
	 * @var int Id of cycle
	 */
	public $fk_cycle;

	/**
	 * @var string Ref (ex. A.1)
	 */
	public $ref;

	/**
	 * @var string Label (question)
	 */
	public $label;

	/**
	 * @var string Type de contrôle (analytique|rapprochement|ratios|justificatifs)
	 */
	public $type_control = 'justificatifs';

	/**
	 * @var string JSON array of account prefixes
	 */
	public $accounts_filter;

	/**
	 * @var string JSON array of account prefixes (numerator, type ratios)
	 */
	public $numerator_filter;

	/**
	 * @var string JSON array of account prefixes (denominator, type ratios)
	 */
	public $denominator_filter;

	/**
	 * @var int Applicable par défaut
	 */
	public $applicable_default = 1;

	/**
	 * @var int Prioritaire
	 */
	public $priority = 0;

	/**
	 * @var string Note / modèle de justificatif
	 */
	public $note;

	/**
	 * @var int Position
	 */
	public $position = 0;

	/**
	 * @var int Actif
	 */
	public $active = 1;

	/**
	 * @var int User create
	 */
	public $fk_user_cre;

	/**
	 * @var int User modif
	 */
	public $fk_user_modif;

	/**
	 * @var string Creation date (format sql)
	 */
	public $datec;

	/**
	 * @var string Timestamp
	 */
	public $tms;

	/**
	 * @var RevisionDiligence[] Lines
	 */
	public $lines = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Create into database
	 *
	 * @param User $user      User that creates
	 * @param int  $notrigger 1 = disable triggers
	 * @return int            <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf;

		$this->entity = (int) $conf->entity;
		if (empty($this->datec)) {
			$this->datec = $this->db->idate(dol_now());
		}
		if (!isset($this->active) || $this->active === '' || $this->active === null) {
			$this->active = 1;
		}
		if (empty($this->type_control)) {
			$this->type_control = 'justificatifs';
		}
		$this->fk_user_cre = (int) $user->id;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
		$sql .= "entity, fk_guide, fk_cycle, ref, label, type_control, accounts_filter, numerator_filter, denominator_filter, applicable_default, priority, note, position, active, fk_user_cre, fk_user_modif, datec";
		$sql .= ") VALUES (";
		$sql .= " ".((int) $this->entity).",";
		$sql .= " ".((int) $this->fk_guide).",";
		$sql .= " ".((int) $this->fk_cycle).",";
		$sql .= " '".$this->db->escape((string) $this->ref)."',";
		$sql .= " ".($this->label === null || $this->label === '' ? "NULL" : "'".$this->db->escape((string) $this->label)."'").",";
		$sql .= " '".$this->db->escape((string) $this->type_control)."',";
		$sql .= " ".($this->accounts_filter === null || $this->accounts_filter === '' ? "NULL" : "'".$this->db->escape((string) $this->accounts_filter)."'").",";
		$sql .= " ".($this->numerator_filter === null || $this->numerator_filter === '' ? "NULL" : "'".$this->db->escape((string) $this->numerator_filter)."'").",";
		$sql .= " ".($this->denominator_filter === null || $this->denominator_filter === '' ? "NULL" : "'".$this->db->escape((string) $this->denominator_filter)."'").",";
		$sql .= " ".((int) $this->applicable_default).",";
		$sql .= " ".((int) $this->priority).",";
		$sql .= " ".($this->note === null || $this->note === '' ? "NULL" : "'".$this->db->escape((string) $this->note)."'").",";
		$sql .= " ".((int) $this->position).",";
		$sql .= " ".((int) $this->active).",";
		$sql .= " ".((int) $this->fk_user_cre).",";
		$sql .= " ".((int) $this->fk_user_cre).",";
		$sql .= " '".$this->datec."'";
		$sql .= ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		$this->id = (int) $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

		return $this->id;
	}

	/**
	 * Load object from database
	 *
	 * @param int $id Id of object
	 * @return int   <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id)
	{
		global $conf;

		$sql = "SELECT t.rowid, t.entity, t.fk_guide, t.fk_cycle, t.ref, t.label, t.type_control, t.accounts_filter, t.numerator_filter, t.denominator_filter, t.applicable_default, t.priority, t.note, t.position, t.active, t.fk_user_cre, t.fk_user_modif, t.datec, t.tms";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.entity = ".((int) $conf->entity)." AND t.rowid = ".((int) $id);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->initFromObj($obj);
				return $this->id;
			}
			return 0;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Load all objects from database
	 *
	 * @param string $sortfield Sort field
	 * @param string $sortorder Sort order
	 * @param int    $limit     Limit
	 * @param int    $offset    Offset
	 * @param array  $filter    Filter array (sql conditions on t.xxx fields)
	 * @return array|int        Array of objects if OK, <0 if KO
	 */
	public function fetchAll($sortfield = 't.position', $sortorder = 'ASC', $limit = 0, $offset = 0, $filter = array())
	{
		global $conf;

		$this->lines = array();

		$sql = "SELECT t.rowid, t.entity, t.fk_guide, t.fk_cycle, t.ref, t.label, t.type_control, t.accounts_filter, t.numerator_filter, t.denominator_filter, t.applicable_default, t.priority, t.note, t.position, t.active, t.fk_user_cre, t.fk_user_modif, t.datec, t.tms";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.entity = ".((int) $conf->entity);
		foreach ($filter as $key => $value) {
			if ($key === 't.entity' || $value === '' || $value === null) {
				continue;
			}
			if (is_array($value)) {
				$list = array();
				foreach ($value as $v) {
					$list[] = "'".$this->db->escape((string) $v)."'";
				}
				if (!empty($list)) {
					$sql .= " AND ".$key." IN (".implode(',', $list).")";
				}
			} else {
				$sql .= " AND ".$key." = '".$this->db->escape((string) $value)."'";
			}
		}
		if (!empty($sortfield)) {
			$sql .= " ORDER BY ".preg_replace('/[^a-zA-Z0-9_.\-]/', '', $sortfield)." ".(strtoupper($sortorder) === 'DESC' ? 'DESC' : 'ASC');
		}
		if ($limit > 0) {
			$sql .= " LIMIT ".((int) $limit)." OFFSET ".((int) $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$line = new self($this->db);
				$line->initFromObj($obj);
				$this->lines[] = $line;
			}
			return $this->lines;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Update into database
	 *
	 * @param User $user      User that modifies
	 * @param int  $notrigger 1 = disable triggers
	 * @return int            <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql .= " fk_cycle = ".((int) $this->fk_cycle).",";
		$sql .= " ref = '".$this->db->escape((string) $this->ref)."',";
		$sql .= " label = ".($this->label === null || $this->label === '' ? "NULL" : "'".$this->db->escape((string) $this->label)."'").",";
		$sql .= " type_control = '".$this->db->escape((string) $this->type_control)."',";
		$sql .= " accounts_filter = ".($this->accounts_filter === null || $this->accounts_filter === '' ? "NULL" : "'".$this->db->escape((string) $this->accounts_filter)."'").",";
		$sql .= " numerator_filter = ".($this->numerator_filter === null || $this->numerator_filter === '' ? "NULL" : "'".$this->db->escape((string) $this->numerator_filter)."'").",";
		$sql .= " denominator_filter = ".($this->denominator_filter === null || $this->denominator_filter === '' ? "NULL" : "'".$this->db->escape((string) $this->denominator_filter)."'").",";
		$sql .= " applicable_default = ".((int) $this->applicable_default).",";
		$sql .= " priority = ".((int) $this->priority).",";
		$sql .= " note = ".($this->note === null || $this->note === '' ? "NULL" : "'".$this->db->escape((string) $this->note)."'").",";
		$sql .= " position = ".((int) $this->position).",";
		$sql .= " active = ".((int) $this->active).",";
		$sql .= " fk_user_modif = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		return 1;
	}

	/**
	 * Delete from database
	 *
	 * @param User $user      User that deletes
	 * @param int  $notrigger 1 = disable triggers
	 * @return int            <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".((int) $this->id);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		return 1;
	}

	/**
	 * Fill object properties from a database object
	 *
	 * @param object $obj Row
	 * @return void
	 */
	protected function initFromObj($obj)
	{
		$this->id = (int) $obj->rowid;
		$this->entity = (int) $obj->entity;
		$this->fk_guide = (int) $obj->fk_guide;
		$this->fk_cycle = (int) $obj->fk_cycle;
		$this->ref = $obj->ref;
		$this->label = $obj->label;
		$this->type_control = $obj->type_control;
		$this->accounts_filter = $obj->accounts_filter;
		$this->numerator_filter = $obj->numerator_filter;
		$this->denominator_filter = $obj->denominator_filter;
		$this->applicable_default = (int) $obj->applicable_default;
		$this->priority = (int) $obj->priority;
		$this->note = $obj->note;
		$this->position = (int) $obj->position;
		$this->active = (int) $obj->active;
		$this->fk_user_cre = (int) $obj->fk_user_cre;
		$this->fk_user_modif = (int) $obj->fk_user_modif;
		$this->datec = $this->db->jdate($obj->datec);
		$this->tms = $this->db->jdate($obj->tms);
	}

	/**
	 * Comptes à inclure (préfixes) sous forme de tableau
	 *
	 * @return array
	 */
	public function getAccountsFilter()
	{
		$arr = json_decode((string) $this->accounts_filter, true);
		return is_array($arr) ? $arr : array();
	}

	/**
	 * Préfixes du numérateur (type ratios)
	 *
	 * @return array
	 */
	public function getNumeratorFilter()
	{
		$arr = json_decode((string) $this->numerator_filter, true);
		return is_array($arr) ? $arr : array();
	}

	/**
	 * Préfixes du dénominateur (type ratios)
	 *
	 * @return array
	 */
	public function getDenominatorFilter()
	{
		$arr = json_decode((string) $this->denominator_filter, true);
		return is_array($arr) ? $arr : array();
	}

	/**
	 * Propose la prochaine référence libre pour un cycle (ex. A.9)
	 *
	 * @param int    $fkCycle Id of cycle
	 * @param string $cycleCode Code of cycle (ex. A)
	 * @return string Next ref
	 */
	public function getNextRefForCycle($fkCycle, $cycleCode)
	{
		$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE fk_cycle = ".((int) $fkCycle);
		$resql = $this->db->query($sql);
		$nb = 0;
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$nb = (int) $obj->nb;
			}
		}
		return strtoupper((string) $cycleCode).'.'.($nb + 1);
	}
}
