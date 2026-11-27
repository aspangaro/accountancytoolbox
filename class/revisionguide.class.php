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
 * \file        accountancytoolbox/class/revisionguide.class.php
 * \ingroup     accountancytoolbox
 * \brief       Classe des modèles de guide de révision des comptes (cycles A à I et diligences)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Modèle de guide de révision : regroupe les grands domaines (cycles) et leurs diligences
 */
class RevisionGuide extends CommonObject
{
	/**
	 * @var string Name of element
	 */
	public $element = 'accountancytoolbox_revision_guide';

	/**
	 * @var string Name of table without prefix
	 */
	public $table_element = 'accountancytoolbox_revision_guide';

	/**
	 * @var string Picto
	 */
	public $picto = 'fa-tasks';

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
	 * @var string Ref
	 */
	public $ref;

	/**
	 * @var string Label
	 */
	public $label;

	/**
	 * @var string Description
	 */
	public $description;

	/**
	 * @var int Modèle par défaut
	 */
	public $is_default = 0;

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
	 * @var RevisionGuide[] Lines
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
		if (empty($this->ref)) {
			$this->ref = $this->getNextRef();
		}
		if (empty($this->datec)) {
			$this->datec = $this->db->idate(dol_now());
		}
		if (!isset($this->active) || $this->active === '' || $this->active === null) {
			$this->active = 1;
		}
		$this->fk_user_cre = (int) $user->id;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
		$sql .= "entity, ref, label, description, is_default, active, fk_user_cre, fk_user_modif, datec";
		$sql .= ") VALUES (";
		$sql .= " ".((int) $this->entity).",";
		$sql .= " '".$this->db->escape((string) $this->ref)."',";
		$sql .= " '".$this->db->escape((string) $this->label)."',";
		$sql .= " ".($this->description === null || $this->description === '' ? "NULL" : "'".$this->db->escape((string) $this->description)."'").",";
		$sql .= " ".((int) $this->is_default).",";
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
	 * @param int    $id  Id of object
	 * @param string $ref Ref of object
	 * @return int        <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = '')
	{
		global $conf;

		$sql = "SELECT t.rowid, t.entity, t.ref, t.label, t.description, t.is_default, t.active, t.fk_user_cre, t.fk_user_modif, t.datec, t.tms";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.entity = ".((int) $conf->entity);
		if ($id > 0) {
			$sql .= " AND t.rowid = ".((int) $id);
		}
		if ($ref !== '') {
			$sql .= " AND t.ref = '".$this->db->escape($ref)."'";
		}

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
	public function fetchAll($sortfield = 't.ref', $sortorder = 'ASC', $limit = 0, $offset = 0, $filter = array())
	{
		global $conf;

		$this->lines = array();

		$sql = "SELECT t.rowid, t.entity, t.ref, t.label, t.description, t.is_default, t.active, t.fk_user_cre, t.fk_user_modif, t.datec, t.tms";
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
		$sql .= " ref = '".$this->db->escape((string) $this->ref)."',";
		$sql .= " label = '".$this->db->escape((string) $this->label)."',";
		$sql .= " description = ".($this->description === null || $this->description === '' ? "NULL" : "'".$this->db->escape((string) $this->description)."'").",";
		$sql .= " is_default = ".((int) $this->is_default).",";
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
	 * Delete from database (with cascade on cycles and diligences)
	 *
	 * @param User $user      User that deletes
	 * @param int  $notrigger 1 = disable triggers
	 * @return int            <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$this->db->query("DELETE FROM ".MAIN_DB_PREFIX."accountancytoolbox_revision_diligence WHERE fk_guide = ".((int) $this->id));
		$this->db->query("DELETE FROM ".MAIN_DB_PREFIX."accountancytoolbox_revision_cycle WHERE fk_guide = ".((int) $this->id));

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
		$this->ref = $obj->ref;
		$this->label = $obj->label;
		$this->description = $obj->description;
		$this->is_default = (int) $obj->is_default;
		$this->active = (int) $obj->active;
		$this->fk_user_cre = (int) $obj->fk_user_cre;
		$this->fk_user_modif = (int) $obj->fk_user_modif;
		$this->datec = $this->db->jdate($obj->datec);
		$this->tms = $this->db->jdate($obj->tms);
	}

	/**
	 * Generate next ref of guide (G0001, G0002, ...)
	 *
	 * @return string Next ref
	 */
	public function getNextRef()
	{
		global $conf;

		$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE entity = ".((int) $conf->entity);
		$resql = $this->db->query($sql);
		$nb = 0;
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$nb = (int) $obj->nb;
			}
		}
		return 'G'.str_pad((string) ($nb + 1), 4, '0', STR_PAD_LEFT);
	}

	/**
	 * Types de contrôle possibles pour une diligence
	 *
	 * @return array<string,string> code => translation key
	 */
	public static function getControlTypes()
	{
		return array(
			'analytique' => 'ATBRevisionTypeAnalytique',
			'rapprochement' => 'ATBRevisionTypeRapprochement',
			'ratios' => 'ATBRevisionTypeRatios',
			'justificatifs' => 'ATBRevisionTypeJustificatifs',
		);
	}

	/**
	 * Grands domaines (cycles A à I) proposés à la création d'un modèle de guide,
	 * avec pour chacun les comptes concernés et les diligences/contrôles types.
	 *
	 * @return array
	 */
	public static function getDefaultCycles()
	{
		return array(
			array(
				'code' => 'A',
				'label' => 'Régularité / Général',
				'accounts_scope' => 'Tous comptes',
				'standard_controls' => 'Contrôle du FEC, validation des à-nouveaux, solde des comptes d\'attente (471)',
				'diligences' => array(
					array('ref' => 'A.1', 'label' => 'Note de présentation et immeuble(s)', 'type_control' => 'justificatifs'),
					array('ref' => 'A.2', 'label' => 'Note de tenue', 'type_control' => 'justificatifs'),
					array('ref' => 'A.3', 'label' => 'S\'assurer que des réponses ont été apportées aux questions sur le maintien de la mission', 'type_control' => 'justificatifs'),
					array('ref' => 'A.4', 'label' => 'S\'assurer que le formulaire de lutte anti-blanchiment a été complété', 'type_control' => 'justificatifs'),
					array('ref' => 'A.5', 'label' => 'Qualité - satisfaction client - compléter la feuille de travail', 'type_control' => 'justificatifs'),
					array('ref' => 'A.6', 'label' => 'Besoins et préconisations', 'type_control' => 'justificatifs'),
					array('ref' => 'A.7', 'label' => 'Analyse du FEC', 'type_control' => 'analytique'),
					array('ref' => 'A.8', 'label' => 'Absence de libellés alertant l\'administration', 'type_control' => 'analytique'),
				),
			),
			array(
				'code' => 'B',
				'label' => 'Trésorerie',
				'accounts_scope' => 'Classes 5 (512, 530)',
				'standard_controls' => 'Rapprochement bancaire de clôture, contrôle des soldes de caisse, virements internes (580)',
			),
			array(
				'code' => 'C',
				'label' => 'Achats / Fournisseurs',
				'accounts_scope' => 'Classes 40 et 60/61/62',
				'standard_controls' => 'Lettrage des comptes auxiliaires, justification des soldes débiteurs, calcul des FNP et CCA',
				'diligences' => array(
					array('ref' => 'C.1', 'label' => 'Effectuer la revue analytique', 'type_control' => 'analytique', 'accounts' => array('60', '61', '62', '65', '67')),
				),
			),
			array(
				'code' => 'D',
				'label' => 'Ventes / Clients',
				'accounts_scope' => 'Classes 41 et 70',
				'standard_controls' => 'Lettrage client, revue de la balance âgée, dépréciation des créances douteuses, calcul des FAE et PCA',
			),
			array(
				'code' => 'E',
				'label' => 'Stocks et en-cours',
				'accounts_scope' => 'Classes 3 et 603/713',
				'standard_controls' => 'Cadrage avec l\'inventaire physique valorisé, constatation de la variation de stock',
			),
			array(
				'code' => 'F',
				'label' => 'Immobilisations',
				'accounts_scope' => 'Classes 2 et 68/775',
				'standard_controls' => 'Tableau des acquisitions/cessions, calcul des dotations aux amortissements',
			),
			array(
				'code' => 'G',
				'label' => 'Personnel / Social',
				'accounts_scope' => 'Classes 42, 43 et 64',
				'standard_controls' => 'Cadrage des salaires bruts avec la DSN annuelle, provision pour congés payés et charges sociales',
				'diligences' => array(
					array('ref' => 'G.1', 'label' => 'Analyser le taux de charges sociales', 'type_control' => 'ratios', 'numerator' => array('641'), 'denominator' => array('645')),
				),
			),
			array(
				'code' => 'H',
				'label' => 'État / Fiscalité',
				'accounts_scope' => 'Classe 44 et 63/69',
				'standard_controls' => 'Cadrage de la TVA (CA3/CA12 vs comptabilité), calcul de l\'IS, vérification de la CFE/CVAE',
			),
			array(
				'code' => 'I',
				'label' => 'Capitaux propres',
				'accounts_scope' => 'Classe 1',
				'standard_controls' => 'Affectation du résultat N-1 (AG), contrôle des emprunts et des intérêts courus non échus (ICNE)',
			),
		);
	}

	/**
	 * Initialise le modèle avec les cycles par défaut (A à I) et leurs diligences d'exemple
	 *
	 * @param User $user User that creates
	 * @return int       1 if OK, <0 if KO
	 */
	public function seedDefaultCycles($user)
	{
		dol_include_once('/accountancytoolbox/class/revisioncycle.class.php');
		dol_include_once('/accountancytoolbox/class/revisiondiligence.class.php');

		$position = 1;
		foreach (self::getDefaultCycles() as $c) {
			$cycle = new RevisionCycle($this->db);
			$cycle->fk_guide = $this->id;
			$cycle->code = $c['code'];
			$cycle->label = $c['label'];
			$cycle->accounts_scope = $c['accounts_scope'];
			$cycle->standard_controls = $c['standard_controls'];
			$cycle->position = $position++;
			$cycle->active = 1;
			$res = $cycle->create($user);
			if ($res < 0) {
				return -1;
			}
			if (!empty($c['diligences'])) {
				$dpos = 1;
				foreach ($c['diligences'] as $d) {
					$dil = new RevisionDiligence($this->db);
					$dil->fk_guide = $this->id;
					$dil->fk_cycle = $cycle->id;
					$dil->ref = $d['ref'];
					$dil->label = $d['label'];
					$dil->type_control = $d['type_control'];
					$dil->accounts_filter = json_encode(isset($d['accounts']) ? $d['accounts'] : array());
					$dil->numerator_filter = json_encode(isset($d['numerator']) ? $d['numerator'] : array());
					$dil->denominator_filter = json_encode(isset($d['denominator']) ? $d['denominator'] : array());
					$dil->applicable_default = 1;
					$dil->priority = 0;
					$dil->position = $dpos++;
					$dil->active = 1;
					$resd = $dil->create($user);
					if ($resd < 0) {
						return -1;
					}
				}
			}
		}
		return 1;
	}
}
