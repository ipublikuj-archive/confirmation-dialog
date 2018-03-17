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
final class ConfirmationDialogExtension extends DI\CompilerExtension
{
	/**
	 * @var array
	 */
	private $defaults = [
		'layoutFile'   => NULL,
		'templateFile' => NULL
	];

	/**
	 * @return void
	 */
	public function loadConfiguration() : void
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		// Session storage
		$builder->addDefinition($this->prefix('storage'))
			->setType(Storage\Session::class);

		$confirmerFactory = $builder->addDefinition($this->prefix('confirmer'))
			->setType(Components\Confirmer::class)
			->setImplement(Components\IConfirmer::class)
			->setArguments([new Code\PhpLiteral('$templateFile')])
			->setAutowired(FALSE)
			->setInject(TRUE);

		// Define components factories
		$dialogFactory = $builder->addDefinition($this->prefix('dialog'))
			->setType(Components\Control::class)
			->setImplement(Components\IControl::class)
			->setArguments([
				new Code\PhpLiteral('$layoutFile'),
				new Code\PhpLiteral('$templateFile'),
				$confirmerFactory,
			])
			->setInject(TRUE);

		if ($config['layoutFile']) {
			$dialogFactory->addSetup('$service->setLayoutFile(?)', [$config['layoutFile']]);
		}

		if ($config['templateFile']) {
			$dialogFactory->addSetup('$service->setTemplateFile(?)', [$config['templateFile']]);
		}
	}

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 */
	public static function register(Nette\Configurator $config, $extensionName = 'confirmationDialog') : void
	{
		$config->onCompile[] = function (Nette\Configurator $config, DI\Compiler $compiler) use ($extensionName) : void {
			$compiler->addExtension($extensionName, new ConfirmationDialogExtension());
		};
	}

	/**
	 * Return array of directories, that contain resources for translator.
	 *
	 * @return string[]
	 */
	public function getTranslationResources() : array
	{
		return [
			__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Translations'
		];
	}
}
