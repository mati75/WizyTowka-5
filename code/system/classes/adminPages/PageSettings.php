<?php

/**
* WizyTówka 5
* Admin page — page settings.
*/
namespace WizyTowka\AdminPages;
use WizyTowka as WT;

class PageSettings extends WT\AdminPanel
{
	protected $_pageTitle = 'Ustawienia strony';

	private $_page;

	public function _prepare()
	{
		if (empty($_GET['id']) or !$this->_page = WT\Page::getById($_GET['id'])) {
			$this->_redirect('error', ['type' => 'parameters']);
		}
	}

	public function POSTQuery()
	{
		$_POST['title'] = trim($_POST['title']);
		$_POST['slug']  = trim($_POST['slug']);

		if (empty($_POST['title'])) {
			$this->_apMessage->error('Nie określono tytułu strony.');
		}
		else {
			$this->_page->title = $_POST['title'];
		}

		if ($_POST['slug'] != $this->_page->slug) {
			$newSlug = !empty($_POST['slug']) ? $_POST['slug'] : (new WT\Text($_POST['title']))->makeSlug()->get();

			if (WT\Page::getBySlug($newSlug)) {
				$this->_apMessage->error('Identyfikator „' . $newSlug . '” jest już wykorzystany w innej stronie.');
			}
			else {
				$this->_page->slug = $newSlug;
			}
		}

		$this->_page->isDraft     = (bool)$_POST['isDraft'];
		$this->_page->description = $_POST['description'];
		$this->_page->keywords    = $_POST['keywords'];

		$this->_page->save();
		$this->_apMessage->default('Zmiany zostały zapisane.');
	}

	protected function _output()
	{
		$this->_apTemplate->page = $this->_page;
	}
}