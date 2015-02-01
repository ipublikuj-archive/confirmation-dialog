<?php
/**
 * Test: IPub\ConfirmationDialog\Compiler
 * @testCase
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:ConfirmationDialog!
 * @subpackage	Tests
 * @since		5.0
 *
 * @date		30.01.15
 */

namespace IPubTests\ConfirmationDialog;

use Nette;
use Nette\Application;
use Nette\Application\UI;
use Nette\Utils;

use Tester;
use Tester\Assert;

use IPub;
use IPub\ConfirmationDialog;

require __DIR__ . '/../bootstrap.php';

class ComponentTest extends Tester\TestCase
{
	/**
	 * @var Nette\Application\IPresenterFactory
	 */
	private $presenterFactory;

	/**
	 * @var \SystemContainer|\Nette\DI\Container
	 */
	private $container;

	/**
	 * Set up
	 */
	public function setUp()
	{
		parent::setUp();

		$this->container = $this->createContainer();

		// Get presenter factory from container
		$this->presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
	}

	public function testSetValidTemplate()
	{
		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'GET', array('action' => 'validTemplate'));
		// & fire presenter & catch response
		$response = $presenter->run($request);

		$dq = Tester\DomQuery::fromHtml((string) $response->getSource());

		Assert::true($dq->has('div[id*="dialog-area"]'));
	}

	/**
	 * @throws \IPub\ConfirmationDialog\Exceptions\FileNotFoundException
	 */
	public function testSetInvalidTemplate()
	{
		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'GET', array('action' => 'invalidTemplate'));
		// & fire presenter & catch response
		$presenter->run($request);
	}

	public function testOpenDialogTemplate()
	{
		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'GET', array('action' => 'openDialog', 'do' => 'confirmationDialog-confirmDelete'));
		// & fire presenter & catch response
		$response = $presenter->run($request);

		$dq = Tester\DomQuery::fromHtml((string) $response->getSource());

		Assert::true($dq->has('form[class*="confirmation-dialog"]'));

		// Get all styles element
		$heading = $dq->find('h3');

		Assert::match('Delete item', trim((string) $heading[0]));

		// Get all styles element
		$question = $dq->find('div[class="modal-body"]');

		Assert::match('Really delete this item?', trim((string) $question[0]));
	}

	/**
	 * @return Application\IPresenter
	 */
	protected function createPresenter()
	{
		// Create test presenter
		$presenter = $this->presenterFactory->createPresenter('Test');
		// Disable auto canonicalize to prevent redirection
		$presenter->autoCanonicalize = FALSE;

		return $presenter;
	}

	/**
	 * @return \SystemContainer|\Nette\DI\Container
	 */
	protected function createContainer()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);

		ConfirmationDialog\DI\ConfirmationDialogExtension::register($config);

		$config->addConfig(__DIR__ . '/files/presenters.neon', $config::NONE);

		return $config->createContainer();
	}
}

class TestPresenter extends UI\Presenter
{
	/**
	 * @var ConfirmationDialog\Components\IControl
	 */
	protected $factory;

	public function actionValidTemplate()
	{
		// Set valid template name
		$this['confirmationDialog']->setTemplateFile('bootstrap.latte');
	}

	public function actionInvalidTemplate()
	{
		// Set invalid template name
		$this['confirmationDialog']->setTemplateFile('invalid.latte');
	}

	public function renderValidTemplate()
	{
		// Set template for component testing
		$this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR .'templates'. DIRECTORY_SEPARATOR .'validTemplate.latte');
	}

	public function renderOpenDialog()
	{
		// Set template for component testing
		$this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR .'templates'. DIRECTORY_SEPARATOR .'openDialog.latte');
	}

	/**
	 * @param ConfirmationDialog\Components\IControl $factory
	 */
	public function injectDialogFactory(ConfirmationDialog\Components\IControl $factory)
	{
		$this->factory = $factory;
	}

	/**
	 * Create confirmation dialog
	 *
	 * @return ConfirmationDialog\Components\Control
	 */
	protected function createComponentConfirmationDialog()
	{
		// Init confirmation dialog
		$control = $this->factory->create();

		$control
			// Add first confirmer
			->addConfirmer(
				'delete',
				[$this, 'deleteItem'],
				'Really delete this item?',
				'Delete item'
			)
			// Add second confirmer
			->addConfirmer(
				'enable',
				[$this, 'enableItem'],
				[$this, 'questionEnable'],
				[$this, 'headingEnable']
			);

		return $control;
	}

	/**
	 * @param ConfirmationDialog\Components\Confirmer $confirmer
	 * @param $params
	 *
	 * @return string
	 */
	public function questionEnable(ConfirmationDialog\Components\Confirmer $confirmer, $params)
	{
		return 'Are your sure to enable this item?';
	}

	/**
	 * @param ConfirmationDialog\Components\Confirmer $confirmer
	 * @param $params
	 *
	 * @return string
	 */
	public function headingEnable(ConfirmationDialog\Components\Confirmer $confirmer, $params)
	{
		return 'Enable item';
	}
}

\run(new ComponentTest());