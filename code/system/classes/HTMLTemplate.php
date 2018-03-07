<?php

/**
* WizyTówka 5
* HTML template class.
*/
namespace WizyTowka;

class HTMLTemplate implements \IteratorAggregate, \Countable
{
	private $_templatesPath;
	private $_templateName;

	private $_variables = [];

	public function __construct($templateName = null, $templatePath = null)
	{
		$this->_templateName  = (string)$templateName;
		$this->_templatesPath = (string)$templatePath;
	}

	public function __get($variable)
	{
		return $this->_variables[$variable];
	}

	public function __set($variable, $value)
	{
		try {
			$this->_variables[$variable] = $this->_escapeValue($value);
		} catch (\UnexpectedValueException $e) {
			throw HTMLTemplateException::valueCantBeEscaped($variable);
		}
	}

	public function setRaw($variable, $value)
	{
		$this->_variables[$variable] = $value;  // Don't escape value.
	}

	public function __isset($variable)
	{
		return isset($this->_variables[$variable]);
	}

	public function __unset($variable)
	{
		unset($this->_variables[$variable]);
	}

	public function __debugInfo()
	{
		return $this->_variables;
	}

	public function __toString()
	{
		ob_start();
		$this->render();
		return ob_get_clean();
	}

	public function getIterator() // For IteratorAggregate interface.
	{
		foreach ($this->_variables as $key => $value) {
			yield $key => $value;
		}
	}

	public function count() // For Countable interface.
	{
		return count($this->_variables);
	}

	public function getTemplate()
	{
		return $this->_templateName;
	}

	public function setTemplate($templateName)
	{
		$this->_templateName = $templateName;
	}

	public function getTemplatePath()
	{
		return $this->_templatesPath;
	}

	public function setTemplatePath($templatePath)
	{
		$this->_templatesPath = $templatePath;
	}

	public function render($templateName = null)
	{
		static $autoloaderAdded = false;
		if (!$autoloaderAdded) {
			spl_autoload_register([$this, '_systemNamespaceAlias']);
			$autoloaderAdded = true;
		}

		if (empty($templateName)) {
			if (empty($this->_templateName)) {
				throw HTMLTemplateException::templateNotSpecified();
			}
			$templateName = $this->_templateName;
		}

		if (!headers_sent()) {
			header('Content-type: text/html; charset=UTF-8');
		}

		$include = function(&$___variables___, $___template___)
		{
			try {
				ob_start();
				extract($___variables___, EXTR_SKIP | EXTR_REFS);
				include $___template___;
				ob_end_flush();
			}
			catch (\Throwable $e) {
				ob_end_clean();
				ErrorHandler::addToLog($e);
				echo '<br><b>Template rendering error.</b><br>', get_class($e), ': ', $e->getMessage(),
					 '<br>', basename($e->getFile()), ':', $e->getLine(), '<br>';
			}
			catch (\Exception $e) { // PHP 5.6 backwards compatibility.
				ob_end_clean();
				ErrorHandler::addToLog($e);
				echo '<br><b>Template rendering error.</b><br>', get_class($e), ': ', $e->getMessage(),
					 '<br>', basename($e->getFile()), ':', $e->getLine(), '<br>';
			}
		};
		$include = $include->bindTo(null);
		// Anonymous function is used here to isolate $this and local variables.

		$include(
			$this->_variables,
			(empty($this->_templatesPath) ? null : $this->_templatesPath.'/') . $templateName . '.php'
		);
	}

	public function _escapeValue($value)
	{
		switch (gettype($value)) {
			case 'integer':
			case 'double':
			case 'boolean':
			case 'NULL':
				return $value;

			case 'string':
				return HTML::escape($value);

			case 'array':
				return array_map(__METHOD__, $value);

			case 'object':
				if ($value instanceof $this or $value instanceof HTMLTag) {
					return $value;
				}
				elseif ($value instanceof \Traversable) {
					return (object)$this->{__FUNCTION__}(iterator_to_array($value));
				}
				elseif ($value instanceof \stdClass) {
					return (object)$this->{__FUNCTION__}((array)$value);
				}

			default:
				throw new \UnexpectedValueException;
		}
	}

	// This autoloader is used to make creating new classes easier in templates code.
	// Instead of `new WizyTowka\HTMLFormFields()` it's possible to use shorter `new HTMLFormFields()` syntax.
	private function _systemNamespaceAlias($classNamePart)
	{
		static $inProgress;  // Avoid endless loop while calling class_exists().

		if ($inProgress) {
			return false;
		}

		$potentialClass = '\\' . __NAMESPACE__ . '\\' . $classNamePart;

		$inProgress  = true;
		$classExists = (class_exists($potentialClass) or trait_exists($potentialClass));
		$inProgress  = false;

		if ($classExists) {
			class_alias($potentialClass, $classNamePart);
		}

		return $classExists;
	}
}

class HTMLTemplateException extends Exception
{
	static public function templateNotSpecified()
	{
		return new self('Template name was not specified.', 1);
	}
	static public function valueCantBeEscaped($variable)
	{
		return new self('Value of "' . $variable . '" variable cannot be escaped. Allowed types: integer, float, boolean, array, template instance, iterator, stdClass. Convert variable value or escape it and use setRaw() instead.', 2);
	}
}