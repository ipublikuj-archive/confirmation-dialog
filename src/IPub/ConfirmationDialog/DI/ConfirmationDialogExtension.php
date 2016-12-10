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

use IPub;
use IPub\ConfirmationDialog;
use IPub\ConfirmationDialog\Components;
use IPub\ConfirmationDialog\Storage;

/**
 * Confirmation dialog extension container
 *
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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
			->setClass(Storage\Session::class);

		// Define components factories
		$dialog = $builder->addDefinition($this->prefix('dialog'))
			->setClass(Components\Control::class)
			->setImplement(Components\IControl::class)
			->setArguments([new Code\PhpLiteral('$layoutFile'), new Code\PhpLiteral('$templateFile')])
			->setInject(TRUE);

		$builder->addDefinition($this->prefix('confirmer'))
			->setClass(Components\Confirmer::class)
			->setImplement(Components\IConfirmer::class)
			->setArguments([new Code\PhpLiteral('$templateFile')])
			->setInject(TRUE);

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
		$config->onCompile[] = function (Nette\Configurator $config, DI\Compiler $compiler) use ($extensionName) {
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
			__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Translations'
		];
	}
}
