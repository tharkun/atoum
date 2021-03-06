<?php

namespace mageekguy\atoum\script;

use
	mageekguy\atoum,
	mageekguy\atoum\system,
	mageekguy\atoum\exceptions
;

abstract class configurable extends atoum\script
{
	const defaultConfigFile = '.config.php';

	protected $includer = null;

	public function __construct($name, atoum\adapter $adapter = null)
	{
		parent::__construct($name, $adapter);

		$this->setIncluder();
	}

	public function setIncluder(atoum\includer $includer = null)
	{
		$this->includer = $includer ?: new atoum\includer();

		return $this;
	}

	public function getIncluder()
	{
		return $this->includer;
	}

	public function run(array $arguments = array())
	{
		$this->useDefaultConfigFiles();

		return parent::run($arguments);
	}

	public function useConfigFile($path)
	{
		return $this->includeConfigFile($path);
	}

	public function useDefaultConfigFiles($startDirectory = null)
	{
		foreach (self::getSubDirectoryPath($startDirectory ?: $this->getDirectory()) as $directory)
		{
			try
			{
				$this->useConfigFile($directory . static::defaultConfigFile);
			}
			catch (atoum\includer\exception $exception) {}
		}

		return $this;
	}

	public static function getSubDirectoryPath($directory, $directorySeparator = null)
	{
		$directorySeparator = $directorySeparator ?: DIRECTORY_SEPARATOR;

		$paths = array();

		if ($directory != '')
		{
			if ($directory == $directorySeparator)
			{
				$paths[] = $directory;
			}
			else
			{
				$directory = rtrim($directory, $directorySeparator);

				$path = '';

				foreach (explode($directorySeparator, $directory) as $subDirectory)
				{
					$path .= $subDirectory . $directorySeparator;

					$paths[] = $path;
				}
			}
		}

		return $paths;
	}

	protected function setArgumentHandlers()
	{
		parent::setArgumentHandlers()
			->addArgumentHandler(
					function($script, $argument, $values) {
						if (sizeof($values) !== 0)
						{
							throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
						}

						$script->help();
					},
					array('-h', '--help'),
					null,
					$this->locale->_('Display this help')
				)
			->addArgumentHandler(
					function($script, $argument, $files) {
						if (sizeof($files) <= 0)
						{
							throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
						}

						foreach ($files as $path)
						{
							try
							{
								$script->useConfigFile($path);
							}
							catch (includer\exception $exception)
							{
								throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Configuration file \'%s\' does not exist'), $path));
							}
						}
					},
					array('-c', '--configurations'),
					'<file>...',
					$this->locale->_('Use all configuration files <file>'),
					1
				)
		;

		return $this;
	}

	protected function includeConfigFile($path, \closure $callback = null)
	{
		if ($callback === null)
		{
			$script = $this;

			$callback = function($path) use ($script) { include_once($path); };
		}

		try
		{
			$this->includer->includePath($path, $callback);
		}
		catch (atoum\includer\exception $exception)
		{
			throw new atoum\includer\exception(sprintf($this->getLocale()->_('Unable to find configuration file \'%s\''), $path));
		}

		return $this;
	}
}
