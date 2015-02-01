<?php
/**
 * Test: IPub\ConfirmationDialog\Extension
 * @testCase
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:ConfirmationDialog!
 * @subpackage	Tests
 * @since		5.0
 *
 * @date		01.02.15
 */

namespace IPubTests\ConfirmationDialog;

use Nette;

use Tester;
use Tester\Assert;

use IPub;
use IPub\ConfirmationDialog;

require __DIR__ . '/../bootstrap.php';

class ExtensionTest extends Tester\TestCase
{
	/**
	 * @return \SystemContainer|\Nette\DI\Container
	 */
	protected function createContainer()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);

		ConfirmationDialog\DI\ConfirmationDialogExtension::register($config);

		return $config->createContainer();
	}

	public function testCompilersServices()
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
}

\run(new ExtensionTest());