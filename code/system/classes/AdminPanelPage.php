<?php

/**
* WizyTówka 5
* Admin panel page abstract controller.
*/
namespace WizyTowka;

abstract class AdminPanelPage extends Controller
{
	protected $_pageTitle = 'Panel administracyjny';
	protected $_userRequiredPermissions;
	protected $_userMustBeLoggedIn = true;

	protected $_currentUser;

	private $_HTMLLayout;
	private $_HTMLTopMenu;
	private $_HTMLMainMenu;
	protected $_HTMLHead;
	protected $_HTMLTemplate;
	protected $_HTMLContextMenu;
	protected $_HTMLMessage;

	final public function __construct()
	{
		if (SessionManager::isUserLoggedIn()) {
			$this->_currentUser = User::getById(SessionManager::getUserId());

			// Prevent access to this page of admin panel if user have not required permissions.
			if ($this->_userRequiredPermissions and !($this->_currentUser->permissions & $this->_userRequiredPermissions)) {
				$this->_redirect('error', ['type' => 'permissions']);
			}
		}
		elseif ($this->_userMustBeLoggedIn) {
			$this->_redirect(
				'login',
				!empty($_SERVER['QUERY_STRING']) ? ['redirect'=>$_SERVER['QUERY_STRING']] : []
			);
		}

		// Prepare HTML parts here for child class.
		$this->_setupHTMLParts();

		// Run _prepare() method from child class.
		$this->_prepare();
	}

	final public function output()
	{
		// Run _output() method. Child class can specify additional template variables and context menu here.
		$this->_output();

		// Main HTML layout.
		$this->_HTMLLayout = new HTMLTemplate(
			$this->_userMustBeLoggedIn ? 'AdminPanelLayout' : 'AdminPanelAlternative',
			SYSTEM_DIR . '/templates'
		);
		$this->_HTMLLayout->head         = $this->_HTMLHead;
		$this->_HTMLLayout->topMenu      = $this->_HTMLTopMenu;
		$this->_HTMLLayout->mainMenu     = $this->_HTMLMainMenu;
		$this->_HTMLLayout->contextMenu  = $this->_HTMLContextMenu;
		$this->_HTMLLayout->message      = $this->_HTMLMessage;
		$this->_HTMLLayout->pageTitle    = $this->_pageTitle;
		$this->_HTMLLayout->pageTemplate = $this->_HTMLTemplate;
		$this->_HTMLLayout->id           = empty($_GET['c']) ? '' : strtolower($_GET['c']);

		// Recursively render all HTML elements and whole layout.
		$this->_HTMLLayout->render();
	}

	// This method sets up HTML layout parts needed by child class.
	private function _setupHTMLParts()
	{
		// HTML <head>.
		$this->_HTMLHead = new HTMLHead;
		$this->_HTMLHead->setAssetsPath(SYSTEM_URL . '/assets');
		$this->_HTMLHead->title($this->_pageTitle . ' — WizyTówka');
		$this->_HTMLHead->meta('viewport', 'width=device-width');
		$this->_HTMLHead->stylesheet('AdminMain.css');
		$this->_HTMLHead->stylesheet('AdminMobile.css');

		// Main template of page.
		$className = substr(strrchr(static::class, '\\'), 1);  // "WizyTowka\AdminPages\Pages" --> "Pages".
		$this->_HTMLTemplate = new HTMLTemplate($className, SYSTEM_DIR.'/templates/AdminPages');

		// Context menu.
		$this->_HTMLContextMenu = new HTMLMenu;

		// HTML message box.
		$this->_HTMLMessage = new HTMLMessage;

		// Top navigation menu and main navigation menu. Only for logged in users.
		if ($this->_userMustBeLoggedIn) {
			$this->_setupMenus();
		}
	}

	// This method sets up admin panel main menu elements according to current user permissions.
	private function _setupMenus()
	{
		// Top navigation menu.
		$this->_HTMLTopMenu = new HTMLMenu;
		$this->_HTMLTopMenu->add($this->_currentUser->name, self::URL('userSettings'),       'iconUser');
		$this->_HTMLTopMenu->add('Zaktualizuj',             self::URL('systemUpdate'),       'iconUpdates');
		$this->_HTMLTopMenu->add('Zobacz witrynę',          Settings::get('websiteAddress'), 'iconWebsite', null, ['target' => '_blank']);
		$this->_HTMLTopMenu->add('Wyloguj się',             self::URL('logout'),             'iconLogout');

		// Main navigation menu.
		$this->_HTMLMainMenu = new HTMLMenu;
		$add = function($label, $url, $CSSClass, $permission = null)
		{
			$this->_HTMLMainMenu->add(
				$label, $url, $CSSClass, null, [],
				$permission ? ($this->_currentUser->permissions & $permission) : true
			);
		};
		$add('Strony',             self::URL('pages'),             'iconPages');
		$add('Utwórz stronę',      self::URL('pageCreate'),        'iconAdd',           User::PERM_CREATING_PAGES);
		$add('Szkice',             self::URL('drafts'),            'iconDrafts');
		$add('Utwórz szkic',       self::URL('pageCreate', ['draft'=>true]), 'iconAdd', User::PERM_CREATING_PAGES);
		$add('Pliki',              self::URL('files'),             'iconFiles');
		$add('Wyślij pliki',       self::URL('filesSend'),         'iconAdd',           User::PERM_SENDING_FILES);
		$add('Menu',               self::URL('menus'),             'iconMenus' ,        User::PERM_EDITING_SITE_ELEMENTS);
		$add('Obszary',            self::URL('areas'),             'iconAreas' ,        User::PERM_EDITING_SITE_ELEMENTS);
		$add('Personalizacja',     self::URL('customization'),     'iconCustomization', User::PERM_EDITING_SITE_CONFIG);
		$add('Ustawienia',         self::URL('websiteSettings'),   'iconSettings',      User::PERM_EDITING_SITE_CONFIG);
		$add('Użytkownicy',        self::URL('users'),             'iconUsers',         User::PERM_SUPER_USER);
		$add('Utwórz użytkownika', self::URL('userCreate'),        'iconAdd',           User::PERM_SUPER_USER);
		$add('Edytor plików',      self::URL('dataEditor_List'),   'iconDataEditor',    User::PERM_SUPER_USER);
		$add('Utwórz plik',        self::URL('dataEditor_Editor'), 'iconAdd',           User::PERM_SUPER_USER);
		$add('Kopia zapasowa',     self::URL('backup'),            'iconBackup',        User::PERM_SUPER_USER);
		$add('Informacje',         self::URL('about'),             'iconInformation');
	}

	// Equivalent of Controller::__construct() method for AdminPanel child classes.
	protected function _prepare() {}

	// Equivalent of Controller::output() method for AdminPanel child classes.
	protected function _output() {}

	static public function URL($target, array $arguments = [])
	{
		return AdminPanel::URL(...func_get_args());
	}
}