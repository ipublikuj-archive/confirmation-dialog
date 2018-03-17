<?php
/**
 * Test: IPub\ConfirmationDialog\Extension
 * @testCase
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Tests
 * @since          1.0.0
 *
 * @date           01.02.15
 */

declare(strict_types = 1);

namespace IPubTests\ConfirmationDialog;

use Nette;

use Tester;
use Tester\Assert;

use IPub;
use IPub\ConfirmationDialog;

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';
require __DIR__ . DS . 'libs' . DS . 'RouterFactory.php';

/**
 * Extension registration tests
 *
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Tests
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class ExtensionTest extends Tester\TestCase
{
	public function testCompilersServices() : void
	{
		$dic = $this->createContainer();

		// Get component factory
		$factory = $dic->getService('confirmationDialog.dialog');

		Assert::true($factory instanceof IPub\ConfirmationDialog\Components\IControl);
		Assert::true($factory->create() instanceof IPub\ConfirmationDialog\Components\Control);

		// Get confirmer factory
		$factory = $dic->getService('confirmationDialog.confirmer');

		Assert::true($factory instanceof IPub\ConfirmationDialog\Components\IConfirmer);
		Assert::true($factory->create() instanceof IPub\ConfirmationDialog\Components\Confirmer);
	}

	/**
	 * @return Nette\DI\Container
	 */
	private function createContainer() : Nette\DI\Container
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);

		ConfirmationDialog\DI\ConfirmationDialogExtension::register($config);

		return $config->createContainer();
	}
}

\run(new ExtensionTest());
