<?php
/**
 * Control.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:ConfirmationDialog!
 * @subpackage	Components
 * @since		5.0
 *
 * @date		12.03.14
 */

namespace IPub\ConfirmationDialog\Components;

use Nette;
use Nette\Application;
use Nette\Localization;
use Nette\Utils;

use IPub;
use IPub\ConfirmationDialog\Exceptions;

/**
 * Confirmation dialog control
 *
 * @package		iPublikuj:ConfirmationDialog!
 * @subpackage	Components
 */
class Dialog extends Control
{
	/**
	 * @var null|string
	 */
	protected $layoutPath = NULL;

	/**
	 * @var IConfirmer
	 */
	protected $confirmerFactory;

	/**
	 * @var Confirmer
	 */
	protected $confirmer;

	/**
	 * @var bool
	 */
	protected $useAjax = TRUE;

	/**
	 * @param IConfirmer $confirmerFactory
	 */
	public function injectFactories(IConfirmer $confirmerFactory)
	{
		// Get confirmer component factory
		$this->confirmerFactory = $confirmerFactory;
	}

	/**
	 * @param Nette\ComponentModel\IContainer $parent
	 * @param null $name
	 */
	public function __construct(
		Nette\ComponentModel\IContainer $parent = NULL, $name = NULL
	) {
		// TODO: remove, only for tests
		parent::__construct(NULL, NULL);
	}

	/**
	 * Change default dialog layout path
	 *
	 * @param string $layoutPath
	 *
	 * @return $this
	 */
	public function setLayoutFile($layoutPath)
	{
		// Check if template file exists...
		if (!is_file($layoutPath)) {
			// ...check if extension template is used
			if (is_file(__DIR__ . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR . $layoutPath)) {
				$layoutPath = __DIR__ . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR . $layoutPath;

			} else {
				// ...if not throw exception
				throw new Exceptions\FileNotFoundException('Layout file "'. $layoutPath .'" was not found.');
			}
		}

		$this->layoutPath = $layoutPath;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateFile()
	{
		// ...try to get default component layout file
		return !empty($this->templatePath) ? $this->templatePath : __DIR__ . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR .'default.latte';
	}

	/**
	 * Overrides signal method formatter
	 * This provide "dynamically named signals"
	 *
	 * @param string $signal
	 *
	 * @return string
	 */
	public static function formatSignalMethod($signal)
	{
		if (Utils\Strings::startsWith($signal, 'confirm')) {
			return 'handleShowConfirmer';
		}

		parent::formatSignalMethod($signal);
	}

	/**
	 * Add confirmation handler to "dynamicaly named signals"
	 *
	 * @param string $name Confirmation/signal name
	 * @param callback|Nette\Callback $handler Callback called when confirmation succeed
	 * @param callback|string $question Callback ($confirmer, $params) or string containing question text
	 * @param callback|string $heading Callback ($confirmer, $params) or string containing heading text
	 *
	 * @return $this
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function addConfirmer($name, $handler, $question, $heading)
	{
		// Confirmer name could be only A-z
		if (!preg_match('/[A-Za-z_]+/', $name)) {
			throw new Exceptions\InvalidArgumentException("Confirmation control name contain invalid characters.");

		// Check confirmer
		} else if ((!$confirmer = $this->getComponent('confirmer-'. $name)) || !$confirmer instanceof Confirmer) {
			throw new Exceptions\InvalidArgumentException("Confirmation control '$name' could not be created.");

		// Check confirmer
		} else if ($confirmer->isConfigured()) {
			throw new Exceptions\InvalidArgumentException("Confirmation control '$name' already exists.");

		} else {
			$confirmer
				// Set confirmer handler
				->setHandler($handler)
				// Set confirmer heading
				->setHeading($heading)
				// Set confirmer question
				->setQuestion($question);
		}

		return $this;
	}

	/**
	 * @return Application\UI\Multiplier
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	protected function createComponentConfirmer()
	{
		$that = $this;

		return new Application\UI\Multiplier((function() use ($that) {
			// Check if confirmer factory is available
			if ($that->confirmerFactory) {
				$confirmer = $that->confirmerFactory->create($that->templatePath);

			} else {
				throw new Exceptions\InvalidStateException("Confirmation control factory does not exist.");
			}

			if ($that->useAjax) {
				$confirmer->enableAjax();

			} else {
				$confirmer->disableAjax();
			}

			return $confirmer;
		}));
	}

	/**
	 * Show dialog for confirmation
	 *
	 * @param string $name
	 * @param array $params
	 *
	 * @throws Exceptions\InvalidArgumentException
	 * @throws Exceptions\InvalidStateException
	 */
	public function showConfirm($name, $params = [])
	{
		if (!is_string($name)) {
			throw new Exceptions\InvalidArgumentException('$name must be string.');
		}

		if ((!$this->confirmer = $this['confirmer-'. $name]) || !$this->confirmer->isConfigured()) {
			throw new Exceptions\InvalidStateException("confirmation '$name' do not exist.");
		}

		if (!is_array($params)) {
			throw new Exceptions\InvalidArgumentException('$params must be array.');
		}

		// Prepare confirmer for displaying
		$this->confirmer->showConfirm($params);

		// Invalidate confirm dialog snippets
		$this->redrawControl();
	}

	/**
	 * Dynamically named signal receiver
	 *
	 * @throws Exceptions\InvalidArgumentException
	 * @throws Exceptions\InvalidStateException
	 */
	public function handleShowConfirmer()
	{
		if ($this->getPresenter() instanceof Application\UI\Presenter) {
			list(, $signal) = $this->getPresenter()->getSignal();

		} else {
			throw new Exceptions\InvalidArgumentException('Confirmer is not attached to presenter.');
		}

		$name = Utils\Strings::substring($signal, 7);
		$name{0} = strtolower($name{0});

		if (!$this['confirmer-'. $name]->isConfigured()) {
			throw new Exceptions\InvalidArgumentException('Invalid confirmation control.');
		}

		$params = $this->getParameters();

		$this->showConfirm($name, $params);
	}

	/**
	 * @return $this
	 */
	public function enableAjax()
	{
		$this->useAjax = TRUE;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function disableAjax()
	{
		$this->useAjax = FALSE;

		return $this;
	}

	/**
	 * Render control
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function render()
	{
		// Create template
		$template = parent::render();

		// Check if control has template
		if ($template instanceof Nette\Bridges\ApplicationLatte\Template) {
			// Assign vars to template
			$template->confirmer = $this->confirmer;

			// If layout was not defined before...
			if ($template->getFile() === NULL) {
				// ...try to get default component layout file
				$layoutPath = !empty($this->layoutPath) ? $this->layoutPath : __DIR__ . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'layout.latte';
				$template->setFile($layoutPath);
			}

			// Render component template
			$template->render();

		} else {
			throw new Exceptions\InvalidStateException('Dialog control is without template.');
		}
	}
}