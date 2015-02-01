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
use Nette\DI;
use Nette\PhpGenerator as Code;

class ConfirmationDialogExtension extends DI\CompilerExtension
{
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		// Session storage
		$builder->addDefinition($this->prefix('session'))
			->setClass('IPub\ConfirmationDialog\SessionStorage');

		// Define components factories
		$builder->addDefinition($this->prefix('dialog'))
			->setClass('IPub\ConfirmationDialog\Components\Control')
			->setImplement('IPub\ConfirmationDialog\Components\IControl')
			->setInject(TRUE)
			->addTag('cms.components');

		$builder->addDefinition($this->prefix('confirmer'))
			->setClass('IPub\ConfirmationDialog\Components\Confirmer')
			->setImplement('IPub\ConfirmationDialog\Components\IConfirmer')
			->setInject(TRUE)
			->addTag('cms.components');
	}

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 */
	public static function register(Nette\Configurator $config, $extensionName = 'confirmationDialog')
	{
		$config->onCompile[] = function (Nette\Configurator $config, Nette\DI\Compiler $compiler) use ($extensionName) {
			$compiler->addExtension($extensionName, new ConfirmationDialogExtension());
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