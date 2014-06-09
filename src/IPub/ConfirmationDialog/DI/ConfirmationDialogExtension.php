<?php
/**
 * ConfirmationDialogExtension.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:ConfirmationDialog!
 * @subpackage	DI
 * @since		5.0
 *
 * @date		08.06.14
 */

namespace IPub\ConfirmationDialog\DI;

use Nette;
use Nette\DI\Compiler;
use Nette\DI\Configurator;
use Nette\PhpGenerator as Code;

use Kdyby\Translation\DI\ITranslationProvider;

if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

if (isset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']) || !class_exists('Nette\Configurator')) {
	unset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']);
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
}

class ConfirmationDialogExtension extends Nette\DI\CompilerExtension implements ITranslationProvider
{
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		// Session storage
		$builder->addDefinition($this->prefix('session'))
			->setClass('IPub\ConfirmationDialog\SessionStorage');

		// Define components
		$builder->addDefinition($this->prefix('dialog'))
			->setClass('IPub\ConfirmationDialog\Components\Control')
			->setImplement('IPub\ConfirmationDialog\Components\IControl')
			->addTag('cms.components');
	}

	/**
	 * @param \Nette\Configurator $config
	 * @param string $extensionName
	 */
	public static function register(Nette\Configurator $config, $extensionName = 'confirmationDialog')
	{
		$config->onCompile[] = function (Configurator $config, Compiler $compiler) use ($extensionName) {
			$compiler->addExtension($extensionName, new CommentsExtension());
		};
	}

	/**
	 * Return array of directories, that contain resources for translator.
	 *
	 * @return string[]
	 */
	function getTranslationResources()
	{
		return array(
			__DIR__ . '/../Translations'
		);
	}
}