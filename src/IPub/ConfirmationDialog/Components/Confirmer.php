<?php
/**
 * Confirmer.php
 *
 * @copyright      More in license.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Components
 * @since          1.0.0
 *
 * @date           31.03.14
 */

declare(strict_types = 1);

namespace IPub\ConfirmationDialog\Components;

use Nette\Application;
use Nette\Bridges;
use Nette\ComponentModel;
use Nette\Forms;

use IPub\ConfirmationDialog\Exceptions;
use IPub\ConfirmationDialog\Storage;

/**
 * Confirmation dialog confirmer control
 *
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Components
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 *
 * @property-read string $name
 * @property-read string $cssClass
 * @property-read string $useAjax
 */
final class Confirmer extends ConfirmerAttributes
{
	/**
	 * @var Control|ComponentModel\IContainer
	 */
	private $dialog;

	/**
	 * @param string|NULL $templateFile
	 * @param Storage\IStorage $storage
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function __construct(
		string $templateFile = NULL,
		Storage\IStorage $storage
	) {
		list(, , $parent, $name) = func_get_args() + [NULL, NULL, NULL, NULL];

		parent::__construct($storage, $parent, $name);

		if ($templateFile !== NULL) {
			$this->setTemplateFile($templateFile);
		}
	}

	/**
	 * Show current confirmer
	 *
	 * @param array $params
	 * 
	 * @return void
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function showConfirm(array $params = []) : void
	{
		// Generate protection token
		$token = $this->generateToken();

		// Set generated token to form
		$this['form']['secureToken']->value = $token;

		// Store token to storage
		$this->storage->set($token, [
			'confirmer' => $this->getName(),
			'params'    => $params,
		]);

		if ($this->getQuestion() !== NULL) {
			// Invalidate confirmer snippets
			$this->redrawControl();
			// Invalidate dialog snippets
			$this->getDialog()->redrawControl();
		}
	}

	/**
	 * Confirm YES clicked
	 *
	 * @param Forms\Controls\SubmitButton $button
	 * 
	 * @return void
	 *
	 * @throws Application\AbortException
	 * @throws Exceptions\HandlerNotCallableException
	 * @throws Exceptions\InvalidStateException
	 */
	public function confirmClicked(Forms\Controls\SubmitButton $button) : void
	{
		// Get submitted values from form
		$values = $button->getForm(TRUE)->getValues();

		// Get token from post
		$token = $values->secureToken;

		try {
			// Get values stored in confirmer storage
			$values = $this->getConfirmerValues($token);
			// Remove storage data for current confirmer
			$this->storage->clear($token);

			$this->getDialog()->resetConfirmer();

			$control = $this->getDialog()->getParent();

			if ($control === NULL) {
				throw new Exceptions\InvalidStateException('Confirmer is not attached to parent control.');
			}

			$this->callHandler($control, $values['params']);

		} catch (Exceptions\InvalidStateException $ex) {
			if (self::$strings['expired'] != '' && $this->getPresenter() instanceof Application\UI\Presenter) {
				$this->getPresenter()->flashMessage(self::$strings['expired']);

			} else {
				throw $ex;
			}
		}

		$this->refreshPage();
	}

	/**
	 * Confirm NO clicked
	 * 
	 * @return void
	 *
	 * @param Forms\Controls\SubmitButton $button
	 *
	 * @throws Application\AbortException
	 * @throws Exceptions\InvalidStateException
	 */
	public function cancelClicked(Forms\Controls\SubmitButton $button) : void
	{
		// Get submitted values from form
		$values = $button->getForm(TRUE)->getValues();

		// Get token from post
		$token = $values->secureToken;

		if ($this->getConfirmerValues($token)) {
			$this->storage->clear($token);
		}

		$this->getDialog()->resetConfirmer();

		$this->refreshPage();
	}

	/**
	 * Check if confirmer is fully configured
	 *
	 * @return bool
	 */
	public function isConfigured() : bool
	{
		if ((is_string($this->heading) || is_callable($this->heading)) &&
			(is_string($this->question) || is_callable($this->question)) &&
			is_callable($this->handler)
		) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Render confirmer
	 * 
	 * @return void
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function render() : void
	{
		// Create template
		$template = parent::render();

		// Check if control has template
		if ($template instanceof Bridges\ApplicationLatte\Template) {
			// Assign vars to template
			$template->add('name', $this->name);
			$template->add('class', $this->cssClass);
			$template->add('icon', $this->getIcon());
			$template->add('question', $this->getQuestion());
			$template->add('heading', $this->getHeading());
			$template->add('useAjax', $this->useAjax);

			// If template was not defined before...
			if ($template->getFile() === NULL) {
				// ...try to get base component template file
				$templateFile = !empty($this->templateFile) ? $this->templateFile : $this->getDialog()->getTemplateFile();
				$template->setFile($templateFile);
			}

			// Render component template
			$template->render();

		} else {
			throw new Exceptions\InvalidStateException('Confirmer control is without template.');
		}
	}

	/**
	 * Change default confirmer template path
	 *
	 * @param string $layoutFile
	 * 
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function setTemplateFile(string $layoutFile) : void
	{
		$this->setTemplateFilePath($layoutFile, self::TEMPLATE_CONFIRMER);
	}

	/**
	 * Get parent dialog control
	 *
	 * @return Control
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	protected function getDialog() : Control
	{
		// Check if confirm dialog was loaded before...
		if (!$this->dialog) {
			// ...if not try to lookup for it
			$multiplier = $this->getParent();

			// Check if confirmer is in multiplier
			if ($multiplier instanceof Application\UI\Multiplier) {
				$this->dialog = $multiplier->getParent();

				// Check if parent is right
				if (!$this->dialog instanceof Control) {
					throw new Exceptions\InvalidStateException('Confirmer is not attached to parent control!');
				}

			} else {
				throw new Exceptions\InvalidStateException('Confirmer is not attached to multiplier!');
			}
		}

		return $this->dialog;
	}

	/**
	 * Generate unique token key
	 *
	 * @return string
	 */
	private function generateToken() : string
	{
		return base_convert(md5(uniqid('confirm' . $this->getName(), TRUE)), 16, 36);
	}

	/**
	 * @return void
	 *
	 * @throws Application\AbortException
	 */
	private function refreshPage() : void
	{
		// Check if request is done via ajax...
		if ($this->getPresenter() instanceof Application\UI\Presenter && !$this->getPresenter()->isAjax()) {
			// ...if not redirect to actual page
			$this->getPresenter()->redirect('this');
		}
	}
}
