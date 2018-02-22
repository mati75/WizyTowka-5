<?php

/**
* WizyTówka 5
* HTML navigation menu generator with unordered list <ul>.
*/
namespace WizyTowka;

class HTMLMenu extends HTMLTag
{
	private $_elements     = [];
	private $_autoPosition = 1.9;

	private $_renderingInProgress = false;

	public function __debugInfo()
	{
		return $this->_elements;
	}

	public function add($label, $content, $CSSClass = null, $position = null, array $HTMLAttributes = [], $visible = true)
	{
		if (!is_string($content) and (!is_object($content) or !($content instanceof $this))) {
			throw HTMLMenuException::invalidContentValue();
		}

		$position = is_numeric($position) ? $position : $this->_autoPosition++;
		// If position number is not specified, autogenerated number from $_autoPosition is used.
		// $_autoPosition is not an integer (1.9, 2.9, 3.9, 4.9 etc.) to move elements with specifed position
		// before elements without specified position (for example 1 is lower than 1.9).

		$visible = (bool)$visible;

		// $_elements array will be sorted by numeric value of first element of nested arrays.
		$this->_elements[] = compact('position', 'label', 'content', 'CSSClass', 'HTMLAttributes', 'visible');
		return $this;
	}

	public function removeByContent($content)
	{
		foreach ($this->_elements as $key => $element) {
			if ($element['content'] == $content) {
				unset($this->_elements[$key]);
			}
		}

		return $this;
	}

	public function removeByLabel($label)
	{
		foreach ($this->_elements as $key => $element) {
			if ($element['label'] == $label) {
				unset($this->_elements[$key]);
			}
		}

		return $this;
	}

	public function output()
	{
		if ($this->_renderingInProgress) {
			throw HTMLMenuException::renderingInProgress();
		}
		$this->_renderingInProgress = true;

		sort($this->_elements); // Sort elements by position number.

		if ($this->_elements) {
			echo '<ul', $this->_CSSClass ? ' class="'.$this->_CSSClass.'">' : '>';

			foreach ($this->_elements as $element) {
				if (!$element['visible']) {
					continue;
				}

				echo '<li', $element['CSSClass'] ? ' class="' . $element['CSSClass'] . '">' : '>';

				if (is_object($element['content'])) {
					echo $element['label'], (string)$element['content'];
				}
				else {
					$element['HTMLAttributes']['href'] = $element['content'];

					$this->_renderHTMLOpenTag('a', $element['HTMLAttributes']);
					echo $element['label'], '</a>';
				}

				echo '</li>';
			}

			echo '</ul>';
		}

		$this->_renderingInProgress = false;
	}
}

class HTMLMenuException extends Exception
{
	static public function invalidContentValue()
	{
		return new self('You must pass string with URL address or other instance of menu class as content of menu element.', 1);
	}
	static public function renderingInProgress()
	{
		return new self('Menu contains itself as element, infinite recursion.', 2);
	}
}