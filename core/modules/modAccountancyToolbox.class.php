<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2023-2026  Alexandre Spangaro      <alexandre@inovea-conseil.com>
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
 * 	\defgroup   accountancytoolbox     Module AccountancyToolbox
 *  \brief      AccountancyToolbox module descriptor.
 *
 *  \file       htdocs/accountancytoolbox/core/modules/modAccountancyToolbox.class.php
 *  \ingroup    accountancytoolbox
 *  \brief      Description and activation file for module AccountancyToolbox
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module AccountancyToolbox
 */
class modAccountancyToolbox extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf, $user;
		$this->db = $db;

		$langs->loadLangs(array("accountancytoolbox@accountancytoolbox", "accountancy"));

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 432623; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'accountancytoolbox';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
		// It is used to group modules by family in module setup page
        $this->family = "Inovea Conseil";

		// Module position in the family

		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        //$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
        // Module label (no space allowed), used if translation string 'ModuleQuickUXName' not found (QuickUX is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description      = "AccountancyToolBoxDescription";
		$this->descriptionlong  = "AccountancyToolBoxDescription";

        // Author
        $this->editor_name      = '<b>Inovea Conseil</b>';
        $this->editor_url       = 'https://www.inovea-conseil.com';		// Must be an external online web site
        $this->editor_squarred_logo = 'inoveaconseil@accountancytoolbox';		// Must be image filename into the module/img directory followed with @modulename. Example: 'myimage.png@quickux'

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = file_get_contents(__DIR__.'/../../VERSION');
		// Url to the file with your last numberversion of this module
        //$this->url_last_version = 'https://www.dolibiz.com/wp-content/uploads/lastversion/last_version-accountancytoolbox-1.txt';

        // Key used in llx_const table to save module status enabled/disabled (where ACCOUNTANCYTOOLBOX is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'inoveaconseil@accountancytoolbox';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			'hooks' => array(
				'data' => array('formDolBanner', 'accountancyindex', 'accountancyadminaccount', 'balancelist', 'balancesubaccountlist',),
				'entity' => '0',
			),
			'api' => array(
				'accountancytoolboxjournals' => array(
					'class' => 'AccountancyToolboxJournals',
					'file' => '/accountancytoolbox/class/api_accountancytoolboxjournals.class.php',
				),
			),
			'css' => array(
				'/accountancytoolbox/css/dashboard.css.php',
				'/accountancytoolbox/css/report.css.php'
			),
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/accountancytoolbox/temp","/accountancytoolbox/subdir");
		$this->dirs = array("/accountancytoolbox/temp");

		// Config pages. Put here list of php page, stored into accountancytoolbox/admin directory, to use to setup module.
		//$this->config_page_url = array("setup.php@accountancytoolbox");
        $this->config_page_url = array("setup.php@accountancytoolbox");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array();
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("accountancytoolbox@accountancytoolbox");

		// Prerequisites
		// $this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(19, 0); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'AccountancyToolboxWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('ACCOUNTANCYTOOLBOX_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('ACCOUNTANCYTOOLBOX_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->accountancytoolbox) || !isset($conf->accountancytoolbox->enabled)) {
			$conf->accountancytoolbox = new stdClass();
			$conf->accountancytoolbox->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@accountancytoolbox:$user->rights->accountancytoolbox->read:/accountancytoolbox/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@accountancytoolbox:$user->rights->othermodule->read:/accountancytoolbox/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view

		// Dictionaries
		$this->dictionaries = array();
		/* Example:
		$this->dictionaries=array(
			'langs'=>'accountancytoolbox@accountancytoolbox',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array(MAIN_DB_PREFIX."table1", MAIN_DB_PREFIX."table2", MAIN_DB_PREFIX."table3"),
			// Label of tables
			'tablib'=>array("Table1", "Table2", "Table3"),
			// Request to select fields
			'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),
			// Sort order
			'tabsqlsort'=>array("label ASC", "label ASC", "label ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array("code,label", "code,label", "code,label"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array("code,label", "code,label", "code,label"),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array("code,label", "code,label", "code,label"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array("rowid", "rowid", "rowid"),
			// Condition to show each dictionary
			'tabcond'=>array($conf->accountancytoolbox->enabled, $conf->accountancytoolbox->enabled, $conf->accountancytoolbox->enabled),
			// Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
			'tabhelp'=>array(array('field1' => 'field1tooltip', 'field2' => 'field2tooltip'), array('field1' => 'field1tooltip', 'field2' => 'field2tooltip'), ...),

		);
		*/

		// Boxes/Widgets
		// Add here list of php file(s) stored in accountancytoolbox/core/boxes that contains a class to show a widget.
        $this->boxes = array(
	        0 => array('file' => 'box_accountancytoolbox_unbalanced_entries.php@accountancytoolbox', 'note' => $langs->trans('BoxUnbalancedEntries'), 'enabledbydefaulton' => 'AccountancyHome'),
	        1 => array('file' => 'box_accountancytoolbox_tax_deadlines.php@accountancytoolbox', 'note' => $langs->trans('BoxTitleTaxDeadlines'), 'enabledbydefaulton' => 'AccountancyHome'),
	        2 => array('file' => 'box_accountancytoolbox_duplicates.php@accountancytoolbox', 'note' => $langs->trans('AccountancyToolboxDuplicateBoxTitle'), 'enabledbydefaulton' => 'AccountancyHome'),
	        3 => array('file' => 'box_accountancytoolbox_overdue.php@accountancytoolbox', 'note' => $langs->trans('BoxOverdueInvoices'), 'enabledbydefaulton' => 'AccountancyHome'),
	        4 => array('file' => 'box_accountancytoolbox_callback.php@accountancytoolbox', 'note' => $langs->trans('BoxCallbackClients'), 'enabledbydefaulton' => 'AccountancyHome'),
	        5 => array('file' => 'box_accountancytoolbox_agedchart.php@accountancytoolbox', 'note' => $langs->trans('BoxAgedChart'), 'enabledbydefaulton' => 'AccountancyHome'),
	        6 => array('file' => 'box_accountancytoolbox_ca.php@accountancytoolbox', 'note' => $langs->trans('BoxCaTitle'), 'enabledbydefaulton' => 'AccountancyHome'),
        );

        // Cronjobs
        // Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'test'=>true),
        //                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'test'=>true)
        // );
        $datestart = dol_now();
        $this->cronjobs = array(
            0 => array(
				'label'         => 'Auto accounting binding',
	            'jobtype'       => 'method',
                'class'         => 'accountancytoolbox/class/accountingautotransfer_cron.class.php',
	            'objectname'    => 'accountingautotransferCron',
                'method'        => 'run',
	            'parameters'    => '',
	            'comment'       => 'Automatically validate accounting bindings for invoices, supplier invoices, and expense reports',
	            'frequency'     => 1,
                'unitfrequency' => 86400,
	            'test'          => isModEnabled('accountancytoolbox'),
	            'status'        => 1,
	            'datestart'     => $datestart
            ),
	        1 => array(
		        'label'         => 'Auto write bookkeeping',
		        'jobtype'       => 'method',
		        'class'         => 'accountancytoolbox/class/accountingautotransfer_transfer_cron.class.php',
		        'objectname'    => 'accountingautotransferTransferCron',
		        'method'        => 'run',
		        'parameters'    => '',
		        'comment'       => 'Automatically write transfer entries in accountancy.',
		        'frequency'     => 1,
		        'unitfrequency' => 86400,
		        'test'          => isModEnabled('accountancytoolbox'),
		        'status'        => 1,
		        'datestart'     => $datestart
	        ),
        );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Access to the tools'; // Permission label
		$this->rights[$r][4] = 'access';
		$this->rights[$r][5] = '';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = 'Access to the setup of the module (if user are not an administrator'; // Permission label
		$this->rights[$r][4] = 'setup';
		$this->rights[$r][5] = '';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Anonymiser / pseudonymiser un FEC';
		$this->rights[$r][4] = 'fecanonymize';
		$this->rights[$r][5] = 'run';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
        $this->rights[$r][1] = 'Lire les entrées PCA/CCA';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'deferred';
        $this->rights[$r][5] = 'read';
        $r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = 'Créer les entrées PCA/CCA';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'deferred';
		$this->rights[$r][5] = 'create';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = 'Echéances fiscales - Marquer comme réalisé';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'deadline';
		$this->rights[$r][5] = 'write';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = 'Read shareholders and capital repartition';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'capital';
		$this->rights[$r][5] = 'read';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = 'Create/modify shareholders and capital repartition';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'capital';
		$this->rights[$r][5] = 'write';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Lire la balance âgée clients';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'agedbalance';
		$this->rights[$r][5] = 'read';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Lire les rapports financiers (Bilan, Résultat, SIG)';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'report';
		$this->rights[$r][5] = 'read';
		$r++;

		// TVA
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Lire les déclarations de TVA CA3';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'tva';
		$this->rights[$r][5] = 'read';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Créer / modifier les déclarations de TVA CA3';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'tva';
		$this->rights[$r][5] = 'write';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Valider les déclarations de TVA CA3';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'tva';
		$this->rights[$r][5] = 'validate';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Clôturer / déclarer les déclarations de TVA CA3';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'tva';
		$this->rights[$r][5] = 'file';
		$r++;
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU
		$this->menu[$r++] = array(
			'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'top', // This is a Top menu entry
			'titre'=>'ModuleAccountancyToolboxName',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'accountancytoolbox',
			'leftmenu'=>'',
			'url'=>'/accountancytoolbox/accountancytoolboxindex.php',
			'langs'=>'accountancytoolbox@accountancytoolbox', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>isModEnabled('accountancytoolbox'), // Define condition to show or hide menu entry. Use isModEnabled('accountancytoolbox') if entry must be visible if module is enabled.
			'perms'=>'1', // Use 'perms'=>'$user->rights->accountancytoolbox->myobject->read' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU */
		$this->menu[$r]	= array(
			'fk_menu'	=> 'fk_mainmenu=accountancy',
            'type'		=> 'left',
            'prefix'    => img_picto('', 'wrench', 'class="paddingright pictofixedwidth valignmiddle"'),
            'titre'		=> 'AccountancyToolboxMenu',
            'leftmenu'	=> 'accountancytoolbox',
            'url'		=> '/accountancytoolbox/index.php?mainmenu=accountancy&leftmenu=accountancytoolbox',
            'langs'		=> 'accountancytoolbox@accountancytoolbox',
            'position'	=> 120,
            'enabled'	=> isModEnabled('accountancytoolbox'),
            'perms'		=> '$user->rights->accountancytoolbox->access',
            'target'	=> '',
            'user'		=> 0
		);
        $r++;
		$this->menu[$r]	= array(
			'fk_menu'   => 'fk_mainmenu=accountancy,fk_leftmenu=accountancytoolbox',
			'type'		=> 'left',
			'titre'		=> 'AccountancyToolboxMenuConfiguration',
			'leftmenu'	=> '',
			'url'		=> '/accountancytoolbox/admin/taxfr.php?leftmenu=accountancytoolbox',
			'langs'		=> 'accountancytoolbox@accountancytoolbox',
			'position'	=> 121,
			'enabled'	=> isModEnabled('accountancytoolbox'),
			'perms'		=> '$user->rights->accountancytoolbox->access',
			'target'	=> '',
			'user'		=> 0
		);
		$r++;
        $this->menu[$r]	= array(
			'fk_menu'   => 'fk_mainmenu=accountancy,fk_leftmenu=accountancytoolbox',
            'type'		=> 'left',
            'titre'		=> 'AccountancyToolboxMenuTaxDeadline',
            'leftmenu'	=> '',
            'url'		=> '/accountancytoolbox/deadlines.php?leftmenu=accountancytoolbox',
            'langs'		=> 'accountancytoolbox@accountancytoolbox',
            'position'	=> 130,
            'enabled'	=> isModEnabled('accountancytoolbox'),
            'perms'		=> '$user->rights->accountancytoolbox->access',
            'target'	=> '',
            'user'		=> 0
        );
        $r++;
        $this->menu[$r]	= array(
			'fk_menu'   => 'fk_mainmenu=accountancy,fk_leftmenu=accountancytoolbox',
            'type'		=> 'left',
            'titre'		=> 'AccountancyToolboxMenuDeferred',
            'leftmenu'	=> '',
            'url'		=> '/accountancytoolbox/deferred.php?leftmenu=accountancytoolbox',
            'langs'		=> 'accountancytoolbox@accountancytoolbox',
            'position'	=> 140,
            //'enabled'	=> isModEnabled("accountancytoolbox") && getDolGlobalString("ACCOUNTANCYTOOLBOX_ENABLE_DEFERRED"),
            'enabled'	=> isModEnabled("accountancytoolbox"),
            'perms'		=> '$user->rights->accountancytoolbox->deferred->read',
            'target'	=> '',
            'user'		=> 0
        );
        $r++;
        $this->menu[$r]	= array(
			'fk_menu' => 'fk_mainmenu=accountancy,fk_leftmenu=accountancytoolbox',
            'type'		=> 'left',
            'titre'		=> 'AccountancyToolboxMenuRenumbering',
            'leftmenu'	=> '',
            'url'		=> '/accountancytoolbox/renumbering.php?leftmenu=accountancytoolbox',
            'langs'		=> 'accountancytoolbox@accountancytoolbox',
            'position'	=> 150,
            'enabled'	=> isModEnabled('accountancytoolbox'),
            'perms'		=> '$user->rights->accountancytoolbox->access',
            'target'	=> '',
            'user'		=> 0
        );
        $r++;
		$this->menu[$r] = array(
			'fk_menu'  => 'fk_mainmenu=accountancy,fk_leftmenu=accountancytoolbox',
			'type'     => 'left',
			'titre'    => 'AccountancyToolboxMenuFecAnonymize',
			'prefix'   => img_picto('', 'fa-user-secret', 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu' => 'accountancy',
			'leftmenu' => 'accountancytoolbox_fecanon',
			'url'      => '/accountancytoolbox/fecanonymize.php?mainmenu=accountancy&leftmenu=accountancytoolbox',
			'langs'    => 'accountancytoolbox@accountancytoolbox',
			'position' => 155,
			'enabled'  => 'isModEnabled("accountancytoolbox")',
			'perms'    => '$user->hasRight("accountancytoolbox", "fecanonymize", "run")',
			'target'   => '',
			'user'     => 0
		);
		$r++;
		$this->menu[$r] = array(
			'fk_menu'  => 'fk_mainmenu=accountancy,fk_leftmenu=accountancytoolbox',
			'type'     => 'left',
			'titre'    => 'ATB_BankStatementsCleanerMenu',
			'mainmenu' => 'accountancy',
			'leftmenu' => 'accountancytoolbox_bank_statements',
			'url'      => '/accountancytoolbox/bank_statements_cleaner.php',
			'langs'    => 'accountancytoolbox@accountancytoolbox',
			'position' => 160,
			'enabled'  => 'isModEnabled("accountancytoolbox") && isModEnabled("bank")',
			'perms'    => '$user->hasRight("banque", "consolidate")',
			'target'   => '',
			'user'     => 0
		);
		$r++;
		$this->menu[$r] = array(
			'fk_menu'   => 'fk_mainmenu=accountancy,fk_leftmenu=accountancytoolbox',
			'type'      => 'left',
			'titre'     => 'AccountancyToolboxMenuCapital',
			'prefix' => img_picto('', 'contact', 'class="paddingright pictofixedwidth valignmiddle"'),
			'leftmenu'	=> 'accountancytoolboxcapital',
			'url'       => '/accountancytoolbox/capital/index.php',
			'langs'     => 'accountancytoolbox@accountancytoolbox',
			'position'  => 180,
			'enabled'   => isModEnabled('accountancytoolbox'),
			'perms'     => '$user->rights->accountancytoolbox->capital->read',
			'target'    => '',
			'user'      => 0
		);
		$r++;
		$this->menu[$r] = array(
			'fk_menu'   => 'fk_mainmenu=accountancy,fk_leftmenu=accountancytoolboxcapital',
			'type'      => 'left',
			'titre'     => 'List',
			'url'       => '/accountancytoolbox/capital/list.php',
			'langs'     => 'accountancytoolbox@accountancytoolbox',
			'position'  => 180,
			'enabled'   => isModEnabled('accountancytoolbox'),
			'perms'     => '$user->rights->accountancytoolbox->capital->read',
			'target'    => '',
			'user'      => 0
		);
		$r++;

		// Aged balance
		$this->menu[$r] = array(
			'fk_menu'   => 'fk_mainmenu=billing,fk_leftmenu=customers_bills',
			'type'      => 'left',
			'titre'     => 'AgedBalance',
			'leftmenu'  => '',
			'url'       => '/accountancytoolbox/agedbalance.php?leftmenu=accountancytoolbox',
			'langs'     => 'accountancytoolbox@accountancytoolbox',
			'position'  => 400,
			'enabled'   => 'isModEnabled("accountancytoolbox") && isModEnabled("invoice")',
			'perms'     => '$user->hasRight("accountancytoolbox", "agedbalance", "read")',
			'target'    => '',
			'user'      => 0
		);
		$r++;
		$this->menu[$r] = array(
			'fk_menu'  => 'fk_mainmenu=billing,fk_leftmenu=customers_bills',
			'type'     => 'left',
			'titre'    => 'ATB_AuditMenuCustomer',
			'leftmenu' => '',
			'url'      => '/accountancytoolbox/audit/audit_customer.php?leftmenu=accountancytoolbox',
			'langs'    => 'audit@accountancytoolbox',
			'position' => 410,
			'enabled'  => 'isModEnabled("accountancytoolbox") && isModEnabled("invoice")',
			'perms'    => '$user->hasRight("accountancytoolbox", "agedbalance", "read")',
			'target'   => '',
			'user'     => 0
		);
		$r++;
		$this->menu[$r] = array(
			'fk_menu'  => 'fk_mainmenu=billing,fk_leftmenu=suppliers_bills',
			'type'     => 'left',
			'titre'    => 'SupplierAgedBalance',
			'leftmenu' => '',
			'url'      => '/accountancytoolbox/agedbalance_supplier.php?leftmenu=accountancytoolbox',
			'langs'    => 'accountancytoolbox@accountancytoolbox',
			'position' => 450,
			'enabled'  => 'isModEnabled("accountancytoolbox") && isModEnabled("supplier_invoice")',
			'perms'    => '$user->hasRight("accountancytoolbox", "agedbalance", "read")',
			'target'   => '',
			'user'     => 0
		);
		$r++;
		$this->menu[$r] = array(
			'fk_menu'  => 'fk_mainmenu=billing,fk_leftmenu=suppliers_bills',
			'type'     => 'left',
			'titre'    => 'ATB_SupplierAuditMenu',
			'leftmenu' => '',
			'url'      => '/accountancytoolbox/audit/audit_supplier.php?leftmenu=accountancytoolbox',
			'langs'    => 'audit@accountancytoolbox',
			'position' => 460,
			'enabled'  => 'isModEnabled("accountancytoolbox") && isModEnabled("supplier_invoice")',
			'perms'    => '$user->hasRight("accountancytoolbox", "agedbalance", "read")',
			'target'   => '',
			'user'     => 0
		);
		$r++;

		// Saisie au kilomètre (Écritures diverses)
		$this->menu[$r] = array(
			'fk_menu'   => 'fk_mainmenu=accountancy,fk_leftmenu=accountancy',
			'type'      => 'left',
			'titre'     => 'AccountancyToolboxMenuOdKilometer',
			'leftmenu'  => 'accountancy_accountancy_odkm',
			'url'       => '/accountancytoolbox/od_kilometer.php?leftmenu=accountancy_accountancy',
			'langs'     => 'accountancytoolbox@accountancytoolbox',
			'position'  => 195,
			'enabled'   => 'isModEnabled("accountancytoolbox") && isModEnabled("accounting")',
			'perms'     => '$user->hasRight("accounting", "mouvements", "creer")',
			'target'    => '',
			'user'      => 0
		);
		$r++;

		// Reports
		$this->menu[$r] = array(
			'fk_menu'   => 'fk_mainmenu=accountancy,fk_leftmenu=accountancy',
			'type'      => 'left',
			'titre'     => 'AccountancyToolboxMenuBilan',
			'leftmenu'  => 'accountancy_accountancy_bilan',
			'url'       => '/accountancytoolbox/report/index.php?mode=bilan&leftmenu=accountancy_accountancy',
			'langs'     => 'accountancytoolbox@accountancytoolbox',
			'position'  => 200,
			'enabled'   => 'isModEnabled("accountancytoolbox")',
			'perms'     => '$user->hasRight("accountancytoolbox", "report", "read")',
			'target'    => '',
			'user'      => 0
		);
		$r++;

		$this->menu[$r] = array(
			'fk_menu'   => 'fk_mainmenu=accountancy,fk_leftmenu=accountancy',
			'type'      => 'left',
			'titre'     => 'AccountancyToolboxMenuResultat',
			'leftmenu'  => 'accountancy_accountancy_resultat',
			'url'       => '/accountancytoolbox/report/index.php?mode=resultat&leftmenu=accountancy_accountancy',
			'langs'     => 'accountancytoolbox@accountancytoolbox',
			'position'  => 210,
			'enabled'   => 'isModEnabled("accountancytoolbox")',
			'perms'     => '$user->hasRight("accountancytoolbox", "report", "read")',
			'target'    => '',
			'user'      => 0
		);
		$r++;

		$this->menu[$r] = array(
			'fk_menu'   => 'fk_mainmenu=accountancy,fk_leftmenu=accountancy',
			'type'      => 'left',
			'titre'     => 'AccountancyToolboxMenuSIG',
			'leftmenu'  => 'accountancy_accountancy_sig',
			'url'       => '/accountancytoolbox/report/index.php?mode=sig&leftmenu=accountancy_accountancy',
			'langs'     => 'accountancytoolbox@accountancytoolbox',
			'position'  => 220,
			'enabled'   => 'isModEnabled("accountancytoolbox")',
			'perms'     => '$user->hasRight("accountancytoolbox", "report", "read")',
			'target'    => '',
			'user'      => 0
		);
		$r++;

		/* END MODULEBUILDER LEFTMENU MYOBJECT */
		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */
		/*
		$langs->load("accountancytoolbox@accountancytoolbox");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='myobject@accountancytoolbox';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'MyObject'; $keyforclassfile='/accountancytoolbox/class/myobject.class.php'; $keyforelement='myobject@accountancytoolbox';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'MyObjectLine'; $keyforclassfile='/accountancytoolbox/class/myobject.class.php'; $keyforelement='myobjectline@accountancytoolbox'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject@accountancytoolbox';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='myobjectline'; $keyforaliasextra='extraline'; $keyforelement='myobjectline@accountancytoolbox';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('myobjectline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'myobject as t';
		//$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'myobject_line as tl ON tl.fk_myobject = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('myobject').')';
		$r++; */
		/* END MODULEBUILDER EXPORT MYOBJECT */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT MYOBJECT */
		/*
		 $langs->load("accountancytoolbox@accountancytoolbox");
		 $this->export_code[$r]=$this->rights_class.'_'.$r;
		 $this->export_label[$r]='MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		 $this->export_icon[$r]='myobject@accountancytoolbox';
		 $keyforclass = 'MyObject'; $keyforclassfile='/accountancytoolbox/class/myobject.class.php'; $keyforelement='myobject@accountancytoolbox';
		 include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		 $keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject@accountancytoolbox';
		 include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		 //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		 $this->export_sql_start[$r]='SELECT DISTINCT ';
		 $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'myobject as t';
		 $this->export_sql_end[$r] .=' WHERE 1 = 1';
		 $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('myobject').')';
		 $r++; */
		/* END MODULEBUILDER IMPORT MYOBJECT */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$result = $this->_load_tables('/accountancytoolbox/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
        global $db, $conf, $user;

		$sql = array();

        require_once DOL_DOCUMENT_ROOT . '/cron/class/cronjob.class.php';

        // List of cron labels you want to delete
        $cron_labels = array(
            'aatAutoAccountingBinding',
            'aatAutoWriteBookkeeping'
        );

        foreach ($cron_labels as $label) {
            $cronjob = new Cronjob($db);
            $found = 0;
            $found = $cronjob->fetchAll('DESC', 't.rowid', 0, 0, 1, array('label' => $label, 'entity' => $conf->entity));
            if (!empty($cronjob->lines)) {
                foreach ($cronjob->lines as $line) {
                    $tmp = new Cronjob($db);
                    if ($tmp->fetch($line->id) > 0) {
                        $tmp->delete($user);
                    }
                }
            }
        }

		return $this->_remove($sql, $options);
	}
}
