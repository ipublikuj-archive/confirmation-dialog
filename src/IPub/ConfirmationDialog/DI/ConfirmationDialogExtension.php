<?php
/**
 * ConfirmationDialogExtension.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           08.06.14
 */

declare(strict_types = 1);

namespace IPub\ConfirmationDialog\DI;

use Nette;
use Nette\DI;
use Nette\PhpGenerator as Code;

use IPub\ConfirmationDialog;

/**
 * Confirmation dialog extension container
 *
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConfirmationDialogExtension extends DI\CompilerExtension
{
	/**
	 * @var array
	 */
	protected $defaults = [
		'layoutFile'   => NULL,
		'templateFile' => NULL
	];

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		// Session storage
		$builder->addDefinition($this->prefix('storage'))
			->setClass(ConfirmationDialog\Storage\Session::CLASS_NAME)
			->setImplement(ConfirmationDialog\Storage\IStorage::INTERFACE_NAME);

		// Define components factories
		$dialog = $builder->addDefinition($this->prefix('dialog'))
			->setClass(ConfirmationDialog\Components\Control::CLASS_NAME)
			->setImplement(ConfirmationDialog\Components\IControl::INTERFACE_NAME)
			->setArguments([new Nette\PhpGenerator\PhpLiteral('$layoutFile'), new Nette\PhpGenerator\PhpLiteral('$templateFile')])
			->setInject(TRUE)
			->addTag('cms.components');

		$builder->addDefinition($this->prefix('confirmer'))
			->setClass(ConfirmationDialog\Components\Confirmer::CLASS_NAME)
			->setImplement(ConfirmationDialog\Components\IConfirmer::INTERFACE_NAME)
			->setArguments([new Nette\PhpGenerator\PhpLiteral('$templateFile')])
			->setInject(TRUE)
			->addTag('cms.components');

		if ($config['layoutFile']) {
			$dialog->addSetup('$service->setLayoutFile(?)', [$config['layoutFile']]);
		}

		if ($config['templateFile']) {
			$dialog->addSetup('$service->setTemplateFile(?)', [$config['templateFile']]);
		}
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
	public function getTranslationResources()
	{
		return [
			__DIR__ . '/../Translations'
		];
	}
}
