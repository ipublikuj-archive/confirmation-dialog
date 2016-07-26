<?php
/**
 * Control.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Components
 * @since          1.0.0
 *
 * @date           12.03.14
 */

declare(strict_types = 1);

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
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Components
 */
final class Control extends BaseControl
{
	/**
	 * Define class name
	 */
	const CLASS_NAME = __CLASS__;

	/**
	 * @var IConfirmer
	 */
	private $confirmerFactory;

	/**
	 * @var Confirmer
	 */
	private $confirmer;

	/**
	 * @var bool
	 */
	private $useAjax = TRUE;

	/**
	 * @param IConfirmer $confirmerFactory
	 */
	public function injectFactories(IConfirmer $confirmerFactory)
	{
		// Get confirmer component factory
		$this->confirmerFactory = $confirmerFactory;
	}

	/**
	 * @param string|NULL $layoutFile
	 * @param string|NULL $templateFile
	 */
	public function __construct(
		string $layoutFile = NULL,
		string $templateFile = NULL
	) {
		list(, , $parent, $name) = func_get_args() + [NULL, NULL, NULL, NULL];

		parent::__construct($parent, $name);

		if ($layoutFile !== NULL) {
			$this->setLayoutFile($layoutFile);
		}

		if ($templateFile !== NULL) {
			$this->setTemplateFile($templateFile);
		}
	}

	/**
	 * Change default dialog layout path
	 *
	 * @param string $layoutFile
	 *
	 * @return void
	 */
	public function setLayoutFile(string $layoutFile)
	{
		$this->setTemplateFilePath($layoutFile, self::TEMPLATE_LAYOUT);
	}

	/**
	 * Change default confirmer template path
	 *
	 * @param string $layoutFile
	 *
	 * @return void
	 */
	public function setTemplateFile(string $layoutFile)
	{
		$this->setTemplateFilePath($layoutFile, self::TEMPLATE_CONFIRMER);
	}

	/**
	 * @return string
	 */
	public function getTemplateFile() : string
	{
		// ...try to get default component layout file
		return $this->templateFile !== NULL ? $this->templateFile : __DIR__ . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'default.latte';
	}

	/**
	 * Overrides signal method formatter
	 * This provide "dynamically named signals"
	 *
	 * @param string $signal
	 *
	 * @return string
	 */
	public static function formatSignalMethod($signal) : string
	{
		if (Utils\Strings::startsWith($signal, 'confirm')) {
			return 'handleShowConfirmer';
		}

		return parent::formatSignalMethod($signal);
	}

	/**
	 * Add confirmation handler to "dynamicaly named signals"
	 *
	 * @param string $name                           Confirmation/signal name
	 * @param callback|Nette\Utils\Callback $handler Callback called when confirmation succeed
	 * @param callback|string $question              Callback ($confirmer, $params) or string containing question text
	 * @param callback|string $heading               Callback ($confirmer, $params) or string containing heading text
	 *
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function addConfirmer(string $name, $handler, $question, $heading)
	{
		// Confirmer name could be only A-z
		if (!preg_match('/[A-Za-z_]+/', $name)) {
			throw new Exceptions\InvalidArgumentException("Confirmation control name contain invalid characters.");
		}

		// Check confirmer
		if ((!$confirmer = $this->getComponent('confirmer-' . $name)) || !$confirmer instanceof Confirmer || $confirmer->isConfigured()) {
			throw new Exceptions\InvalidArgumentException("Confirmation control '$name' could not be created.");
		}

		// Set confirmer handler
		$confirmer->setHandler($handler);
		// Set confirmer heading
		$confirmer->setHeading($heading);
		// Set confirmer question
		$confirmer->setQuestion($question);
	}

	/**
	 * @param string $name
	 *
	 * @return Confirmer
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function getConfirmer(string $name) : Confirmer
	{
		if ((!$confirmer = $this->getComponent('confirmer-' . $name)) || !$confirmer instanceof Confirmer || !$confirmer->isConfigured()) {
			throw new Exceptions\InvalidArgumentException("Confirmation control '$name' does not exists.");
		}

		return $confirmer;
	}

	/**
	 * @return void
	 */
	public function resetConfirmer()
	{
		$this->confirmer = NULL;

		// Invalidate dialog snippets
		$this->redrawControl();
	}

	/**
	 * @return Application\UI\Multiplier
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	protected function createComponentConfirmer() : Application\UI\Multiplier
	{
		return new Application\UI\Multiplier((function () {
			// Check if confirmer factory is available
			if (!$this->confirmerFactory) {
				throw new Exceptions\InvalidStateException("Confirmation control factory does not exist.");
			}

			$confirmer = $this->confirmerFactory->create($this->templateFile);

			if ($this->useAjax) {
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
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 * @throws Exceptions\InvalidStateException
	 */
	public function showConfirm(string $name, array $params = [])
	{
		if (!is_string($name)) {
			throw new Exceptions\InvalidArgumentException('$name must be string.');
		}

		if ((!$this->confirmer = $this['confirmer-' . $name]) || !$this->confirmer->isConfigured()) {
			throw new Exceptions\InvalidStateException("Confirmer '$name' do not exist.");
		}

		// Prepare confirmer for displaying
		$this->confirmer->showConfirm($params);
	}

	/**
	 * Dynamically named signal receiver
	 *
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 * @throws Exceptions\InvalidStateException
	 */
	public function handleShowConfirmer()
	{
		if (!$this->getPresenter() instanceof Application\UI\Presenter) {
			throw new Exceptions\InvalidArgumentException('Confirmer is not attached to presenter.');
		}

		list(, $signal) = $this->getPresenter()->getSignal();

		$name = Utils\Strings::substring($signal, 7);
		$name{0} = strtolower($name{0});

		if (!$this['confirmer-' . $name]->isConfigured()) {
			throw new Exceptions\InvalidArgumentException('Invalid confirmation control.');
		}

		$params = $this->getParameters();

		$this->showConfirm($name, $params);
	}

	/**
	 * @return void
	 */
	public function enableAjax()
	{
		$this->useAjax = TRUE;
	}

	/**
	 * @return void
	 */
	public function disableAjax()
	{
		$this->useAjax = FALSE;
	}

	/**
	 * Render control
	 *
	 * @return void
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

			// If template was not defined before...
			if ($template->getFile() === NULL) {
				// ...try to get base component template file
				$layoutFile = $this->layoutFile !== NULL ? $this->layoutFile : __DIR__ . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'layout.latte';
				$template->setFile($layoutFile);
			}

			// Render component template
			$template->render();

		} else {
			throw new Exceptions\InvalidStateException('Dialog control is without template.');
		}
	}
}
