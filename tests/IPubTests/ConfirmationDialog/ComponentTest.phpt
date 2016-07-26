<?php
/**
 * Test: IPub\ConfirmationDialog\Compiler
 * @testCase
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Tests
 * @since          1.0.0
 *
 * @date           30.01.15
 */

declare(strict_types = 1);

namespace IPubTests\ConfirmationDialog;

use Nette;
use Nette\Application;
use Nette\Application\Routers;
use Nette\Application\UI;
use Nette\Utils;

use Tester;
use Tester\Assert;

use IPub;
use IPub\ConfirmationDialog;
use IPub\ConfirmationDialog\Exceptions;

require __DIR__ . '/../bootstrap.php';

class ComponentTest extends Tester\TestCase
{
	/**
	 * @var Application\IPresenterFactory
	 */
	private $presenterFactory;

	/**
	 * @var Nette\DI\Container
	 */
	private $container;

	/**
	 * @var string
	 */
	private $doVar = '_do';

	/**
	 * Set up
	 */
	public function setUp()
	{
		parent::setUp();

		$this->container = $this->createContainer();

		// Get presenter factory from container
		$this->presenterFactory = $this->container->getByType(Application\IPresenterFactory::class);

		$version = getenv('NETTE');

		if ($version !== 'default') {
			$this->doVar = 'do';
		}
	}

	public function testSetValidTemplate()
	{
		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'GET', ['action' => 'validTemplate']);
		// & fire presenter & catch response
		$response = $presenter->run($request);

		$dq = Tester\DomQuery::fromHtml((string) $response->getSource());

		Assert::true($dq->has('div[id*="dialog-area"]'));
	}

	/**
	 * @throws IPub\ConfirmationDialog\Exceptions\FileNotFoundException
	 */
	public function testSetInvalidTemplate()
	{
		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'GET', ['action' => 'invalidTemplate']);
		// & fire presenter & catch response
		$presenter->run($request);
	}

	public function testOpenDialogTemplate()
	{
		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'GET', ['action' => 'openDialog', $this->doVar => 'confirmationDialog-confirmDelete']);
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

	public function testClickYes()
	{
		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'GET', ['action' => 'openDialog', $this->doVar => 'confirmationDialog-confirmDelete']);
		// & fire presenter & catch response
		$response = $presenter->run($request);

		$dq = Tester\DomQuery::fromHtml((string) $response->getSource());

		Assert::true($dq->has('form[class*="confirmation-dialog"]'));

		$secureToken = $dq->find('input[name="secureToken"]');
		$secureToken = (string) $secureToken[0]->attributes()->{'value'};

		$token = $dq->find('input[name="_token_"]');
		$token = (string) $token[0]->attributes()->{'value'};

		$do = $dq->find('input[name="' . $this->doVar . '"]');
		$do = (string) $do[0]->attributes()->{'value'};

		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'POST', ['action' => 'openDialog'], [
			$this->doVar  => $do,
			'secureToken' => $secureToken,
			'_token_'     => $token,
			'yes'         => 'confirmationDialog.buttons.bNo'
		]);
		// & fire presenter & catch response
		$response = $presenter->run($request);

		Assert::equal('deleting', (string) $response->getSource());
	}

	public function testClickNo()
	{
		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'GET', ['action' => 'openDialog', $this->doVar => 'confirmationDialog-confirmDelete']);
		// & fire presenter & catch response
		$response = $presenter->run($request);

		$dq = Tester\DomQuery::fromHtml((string) $response->getSource());

		Assert::true($dq->has('form[class*="confirmation-dialog"]'));

		$secureToken = $dq->find('input[name="secureToken"]');
		$secureToken = (string) $secureToken[0]->attributes()->{'value'};

		$token = $dq->find('input[name="_token_"]');
		$token = (string) $token[0]->attributes()->{'value'};

		$do = $dq->find('input[name="'. $this->doVar .'"]');
		$do = (string) $do[0]->attributes()->{'value'};

		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'POST', ['action' => 'openDialog', 'headers' => ['X-Requested-With' => 'XMLHttpRequest']], [
			$this->doVar          => $do,
			'secureToken' => $secureToken,
			'_token_'     => $token,
			'no'          => 'confirmationDialog.buttons.bNo'
		]);
		// & fire presenter
		$response = $presenter->run($request);

		Assert::true($response instanceof Application\Responses\RedirectResponse);
		Assert::same(302, $response->getCode());
	}

	/**
	 * @return Application\IPresenter
	 */
	private function createPresenter()
	{
		// Create test presenter
		$presenter = $this->presenterFactory->createPresenter('Test');
		// Disable auto canonicalize to prevent redirection
		$presenter->autoCanonicalize = FALSE;

		return $presenter;
	}

	/**
	 * @return Nette\DI\Container
	 */
	private function createContainer()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);

		ConfirmationDialog\DI\ConfirmationDialogExtension::register($config);

		$version = getenv('NETTE');

		if (!$version || $version == 'default') {
			$config->addConfig(__DIR__ . '/files/presenters.neon');

		} else {
			$config->addConfig(__DIR__ . '/files/presenters_2.3.neon');
		}

		return $config->createContainer();
	}
}

class TestPresenter extends UI\Presenter
{
	/**
	 * @var ConfirmationDialog\Components\IControl
	 */
	private $factory;

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
		$this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'validTemplate.latte');
	}

	public function renderOpenDialog()
	{
		// Set template for component testing
		$this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'openDialog.latte');
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

		// Add first confirmer
		$control->addConfirmer(
			'delete',
			[$this, 'handleDeleteItem'],
			'Really delete this item?',
			'Delete item'
		);

		// Add second confirmer
		$control->addConfirmer(
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
	public function questionEnable(ConfirmationDialog\Components\Confirmer $confirmer, $params) : string
	{
		return 'Are your sure to enable this item?';
	}

	/**
	 * @param ConfirmationDialog\Components\Confirmer $confirmer
	 * @param $params
	 *
	 * @return string
	 */
	public function headingEnable(ConfirmationDialog\Components\Confirmer $confirmer, $params) : string
	{
		return 'Enable item';
	}

	public function handleDeleteItem()
	{
		$this->sendResponse(new Application\Responses\TextResponse('deleting'));
	}
}

class RouterFactory
{
	/**
	 * @return \Nette\Application\IRouter
	 */
	public static function createRouter()
	{
		$router = new Routers\  RouteList();
		$router[] = new Routers\Route('<presenter>/<action>[/<id>]', 'Test:default');

		return $router;
	}
}

\run(new ComponentTest());
