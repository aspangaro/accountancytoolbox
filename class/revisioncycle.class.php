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
 * \file        accountancytoolbox/class/revisioncycle.class.php
 * \ingroup     accountancytoolbox
 * \brief       Classe des cycles (grands domaines A à I) d'un guide de révision
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Cycle (grand domaine) d'un modèle de guide de révision
 */
class RevisionCycle extends CommonObject
{
	/**
	 * @var string Name of element
	 */
	public $element = 'accountancytoolbox_revision_cycle';

	/**
	 * @var string Name of table without prefix
	 */
	public $table_element = 'accountancytoolbox_revision_cycle';

	/**
	 * @var string Picto
	 */
	public $picto = 'fa-folder-open';

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
	 * @var string Code (A, B, C, ...)
	 */
	public $code;

	/**
	 * @var string Label
	 */
	public $label;

	/**
	 * @var string Comptes concernés (ex. "Classes 5 (512, 530)")
	 */
	public $accounts_scope;

	/**
	 * @var string Diligences et contrôles types
	 */
	public $standard_controls;

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
	 * @var RevisionCycle[] Lines
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
		$this->fk_user_cre = (int) $user->id;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
		$sql .= "entity, fk_guide, code, label, accounts_scope, standard_controls, position, active, fk_user_cre, fk_user_modif, datec";
		$sql .= ") VALUES (";
		$sql .= " ".((int) $this->entity).",";
		$sql .= " ".((int) $this->fk_guide).",";
		$sql .= " '".$this->db->escape((string) $this->code)."',";
		$sql .= " '".$this->db->escape((string) $this->label)."',";
		$sql .= " ".($this->accounts_scope === null || $this->accounts_scope === '' ? "NULL" : "'".$this->db->escape((string) $this->accounts_scope)."'").",";
		$sql .= " ".($this->standard_controls === null || $this->standard_controls === '' ? "NULL" : "'".$this->db->escape((string) $this->standard_controls)."'").",";
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

		$sql = "SELECT t.rowid, t.entity, t.fk_guide, t.code, t.label, t.accounts_scope, t.standard_controls, t.position, t.active, t.fk_user_cre, t.fk_user_modif, t.datec, t.tms";
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

		$sql = "SELECT t.rowid, t.entity, t.fk_guide, t.code, t.label, t.accounts_scope, t.standard_controls, t.position, t.active, t.fk_user_cre, t.fk_user_modif, t.datec, t.tms";
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
		$sql .= " code = '".$this->db->escape((string) $this->code)."',";
		$sql .= " label = '".$this->db->escape((string) $this->label)."',";
		$sql .= " accounts_scope = ".($this->accounts_scope === null || $this->accounts_scope === '' ? "NULL" : "'".$this->db->escape((string) $this->accounts_scope)."'").",";
		$sql .= " standard_controls = ".($this->standard_controls === null || $this->standard_controls === '' ? "NULL" : "'".$this->db->escape((string) $this->standard_controls)."'").",";
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
	 * Delete from database (with cascade on diligences of the cycle)
	 *
	 * @param User $user      User that deletes
	 * @param int  $notrigger 1 = disable triggers
	 * @return int            <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$this->db->query("DELETE FROM ".MAIN_DB_PREFIX."accountancytoolbox_revision_diligence WHERE fk_cycle = ".((int) $this->id));

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
		$this->code = $obj->code;
		$this->label = $obj->label;
		$this->accounts_scope = $obj->accounts_scope;
		$this->standard_controls = $obj->standard_controls;
		$this->position = (int) $obj->position;
		$this->active = (int) $obj->active;
		$this->fk_user_cre = (int) $obj->fk_user_cre;
		$this->fk_user_modif = (int) $obj->fk_user_modif;
		$this->datec = $this->db->jdate($obj->datec);
		$this->tms = $this->db->jdate($obj->tms);
	}
}
