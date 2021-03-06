<?php

/**
* WizyTówka 5
* Admin page — website settings.
*/
namespace WizyTowka\AdminPages;
use WizyTowka as __;

class WebsiteSettings extends __\AdminPanelPage
{
	protected $_pageTitle = 'Ustawienia witryny';
	protected $_userRequiredPermissions = __\User::PERM_WEBSITE_SETTINGS;

	private $_dateTimeFormatCurrent  = '';
	private $_dateTimeFormatDisable  = false;

	private const DATE_TIME_FORMATS = [
		// Order: date format, separator, time format.
		['%Y-%m-%d'    , ' ' , '%H:%M:%S'],
		['%Y-%m-%d'    , ' ' , '%H:%M'   ],
		['%Y-%m-%d'    , ' ' , '%k:%M'   ],
		['%d.%m.%Y'    , ' ' , '%H:%M'   ],
		['%e.%m.%Y'    , ' ' , '%H:%M'   ],
		['%e.%m.%Y'    , ' ' , '%k:%M'   ],
		['%e %B %Y'    , ', ', '%k:%M'   ],
		['%A, %e %B %Y', ', ', '%k:%M'   ],
		['%e %B %Y, %A', ', ', '%k:%M'   ],
		['%m/%d/%y'    , ' ' , '%H:%M'   ],
	];

	private $_settings;

	protected function _prepare() : void
	{
		$this->_settings = __\WT()->settings;

		// Disallow modifying of date time format if settings was changed outside GUI.
		$this->_dateTimeFormatCurrent = [$this->_settings->dateDateFormat, $this->_settings->dateSeparator,
		                                 $this->_settings->dateTimeFormat];
		$this->_dateTimeFormatDisable = !in_array($this->_dateTimeFormatCurrent, self::DATE_TIME_FORMATS);
	}

	public function POSTQuery() : void
	{
		if (!$_POST['websiteTitle'] or !$_POST['websiteTitlePattern'] or !$_POST['websiteAddress']
			or !$_POST['websiteEmailAddress'] or !$_POST['websiteHomepageId']) {
			$this->_HTMLMessage->error('Nie wypełniono wymaganych pól.');
			return;
		}

		// Try to update ".htaccess" file, when pretty links setting was changed.
		// Tell user about problem, when he enabled pretty links and server is other than Apache.
		if ($this->_settings->websitePrettyLinks != isset($_POST['websitePrettyLinks'])
			and !$this->_updateHtaccess(isset($_POST['websitePrettyLinks']))
			and isset($_POST['websitePrettyLinks'])) {
			$this->_HTMLMessage->error('Zmiany zostały zapisane. Przyjazne odnośniki wymagają ręcznej konfiguracji serwera.');
		}

		// Date/time format can be changed in configuration file. In this case form field will be disabled.
		if (!$this->_dateTimeFormatDisable and isset($_POST['dateTimeFormat'])
			and isset(self::DATE_TIME_FORMATS[$_POST['dateTimeFormat']])) {
			$this->_dateTimeFormatCurrent    = self::DATE_TIME_FORMATS[$_POST['dateTimeFormat']];
			$this->_settings->dateDateFormat = $this->_dateTimeFormatCurrent[0];
			$this->_settings->dateSeparator  = $this->_dateTimeFormatCurrent[1];
			$this->_settings->dateTimeFormat = $this->_dateTimeFormatCurrent[2];
		}

		$this->_settings->websiteTitle        = $_POST['websiteTitle'];
		$this->_settings->websiteAuthor       = $_POST['websiteAuthor'];
		$this->_settings->websiteTitlePattern = $_POST['websiteTitlePattern'];
		$this->_settings->websiteAddress      = $_POST['websiteAddress'];
		$this->_settings->websiteHomepageId   = $_POST['websiteHomepageId'];

		$this->_settings->websiteEmailAddress = $_POST['websiteEmailAddress'];
		$this->_settings->websitePrettyLinks  = isset($_POST['websitePrettyLinks']);

		// Website address should not have "/" at the end.
		if (substr($this->_settings->websiteAddress, -1) == '/') {
			$this->_settings->websiteAddress = substr($this->_settings->websiteAddress, 0, -1);
		}

		// Title pattern must have place for page title "%s".
		if (strpos($this->_settings->websiteTitlePattern, '%s') === false) {
			$this->_settings->websiteTitlePattern = '%s — ' . $this->_settings->websiteTitlePattern;
		}

		$this->_settings->typographyQuotes  = isset($_POST['typographyQuotes']);
		$this->_settings->typographyDashes  = isset($_POST['typographyDashes']);
		$this->_settings->typographyOrphans = isset($_POST['typographyOrphans']);
		$this->_settings->typographyOther   = isset($_POST['typographyOther']);

		$this->_HTMLMessage->default('Zmiany zostały zapisane.');
	}

	protected function _output() : void
	{
		$this->_HTMLContextMenu->append('Informacje wyszukiwarek', self::URL('searchSettings'), 'iconSearch');

		$this->_HTMLTemplate->settings = $this->_settings;

		// "Website homepage" field — titles of public pages.
		$pagesIds = array_column(__\Page::getAll(), 'title', 'id');
		array_walk($pagesIds, function(&$title){ $title = __\HTML::correctTypography($title); });
		$this->_HTMLTemplate->pagesIds = $pagesIds;

		// "Date/time format" field — current format and list with formats and examples.
		$dateTimeFormatList = [];
		if ($this->_dateTimeFormatDisable) {
			$dateTimeFormatList[''] = '(format niestandardowy)';
			$dateTimeFormatSelected = '';
		}
		else {
			foreach (self::DATE_TIME_FORMATS as $key => $format) {
				$dateTimeFormatList[$key] = (new __\Text(1472705330))->formatAsDateTime(
					join($this->_settings->dateSwapTime ? array_reverse($format) : $format)
				)->get();
			}
			$dateTimeFormatSelected = array_search($this->_dateTimeFormatCurrent, self::DATE_TIME_FORMATS);
		}
		$this->_HTMLTemplate->dateTimeFormatList     = $dateTimeFormatList;
		$this->_HTMLTemplate->dateTimeFormatSelected = $dateTimeFormatSelected;
		$this->_HTMLTemplate->dateTimeFormatDisable  = $this->_dateTimeFormatDisable;
	}

	// This method tries to prepare ".htaccess" file for "pretty links" feature.
	// Returns false on failure or when web server is other than Apache, otherwise true.
	private function _updateHtaccess(bool $enablePrettyLinks) : bool
	{
		if (empty($_SERVER['SERVER_SOFTWARE']) or stripos($_SERVER['SERVER_SOFTWARE'], 'Apache') === false) {
			return false;
		}

		try {
			$htaccessPath    = PUBLIC_DIR . '/.htaccess';
			$htaccessContent = file_exists($htaccessPath) ? file_get_contents($htaccessPath) : '';

			if ($enablePrettyLinks) {
				$websiteAddressPath = ($p = parse_url($this->_settings->websiteAddress, PHP_URL_PATH)) ? $p : '/';
				$htaccessRule = <<< HTACCESS
# WizyTowka
RewriteEngine on
RewriteBase $websiteAddressPath
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule /?([A-Za-z0-9-._]+)/?$ index.php?id=$1 [QSA,L]
# WizyTowka
HTACCESS;
				$htaccessContent .= "\n\n\n" . $htaccessRule;
			}
			else {
				$htaccessContent = preg_replace('/# WizyTowka.*# WizyTowka/s', null, $htaccessContent);
			}

			$htaccessContent = trim($htaccessContent);
			$htaccessContent ? file_put_contents($htaccessPath, $htaccessContent) : @unlink($htaccessPath);
		}
		catch (\ErrorException $e) {
			__\WT()->errors->addToLog($e);
			return false;
		}

		return true;
	}
}