<?php
/**
 * Confirmer.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Components
 * @since          1.0.0
 *
 * @date           31.03.14
 */

declare(strict_types = 1);

namespace IPub\ConfirmationDialog\Components;

use Nette;
use Nette\Application;
use Nette\Forms;
use Nette\Localization;

use IPub;
use IPub\ConfirmationDialog;
use IPub\ConfirmationDialog\Exceptions;

/**
 * Confirmation dialog confirmer control
 *
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Components
 *
 * @property-read string $name
 * @property-read string $cssClass
 * @property-read string $useAjax
 */
final class Confirmer extends ConfirmerAttributes
{
	/**
	 * Define class name
	 */
	const CLASS_NAME = __CLASS__;

	/**
	 * @var Control|Nette\ComponentModel\IContainer
	 */
	private $dialog;

	/**
	 * @param NULL|string $templateFile
	 */
	public function __construct(string $templateFile = NULL)
	{
		list(, $parent, $name) = func_get_args() + [NULL, NULL, NULL];

		parent::__construct($parent, $name);

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
	 */
	public function showConfirm(array $params = [])
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

		if ($this->getQuestion() !== FALSE) {
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
	 * @throws Exceptions\HandlerNotCallableException
	 */
	public function confirmClicked(Forms\Controls\SubmitButton $button)
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

			$this->callHandler($this->getDialog()->getParent(), $values['params']);

		} catch (Exceptions\InvalidStateException $ex) {
			if (self::$strings['expired'] != '' && $this->getPresenter() instanceof Application\UI\Presenter) {
				$this->getPresenter()->flashMessage(self::$strings['expired']);
			}
		}

		// Check if request is done via ajax...
		if ($this->getPresenter() instanceof Application\UI\Presenter && !$this->getPresenter()->isAjax()) {
			// ...if not redirect to actual page
			$this->getPresenter()->redirect('this');
		}
	}

	/**
	 * Confirm NO clicked
	 * 
	 * @return void
	 *
	 * @param Forms\Controls\SubmitButton $button
	 */
	public function cancelClicked(Forms\Controls\SubmitButton $button)
	{
		// Get submitted values from form
		$values = $button->getForm(TRUE)->getValues();

		// Get token from post
		$token = $values->secureToken;

		if ($this->getConfirmerValues($token)) {
			$this->storage->clear($token);
		}

		$this->getDialog()->resetConfirmer();

		// Check if request is done via ajax...
		if ($this->getPresenter() instanceof Application\UI\Presenter && !$this->getPresenter()->isAjax()) {
			// ...if not redirect to actual page
			$this->getPresenter()->redirect('this');
		}
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
	public function render()
	{
		// Create template
		$template = parent::render();

		// Check if control has template
		if ($template instanceof Nette\Bridges\ApplicationLatte\Template) {
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
	 */
	public function setTemplateFile(string $layoutFile)
	{
		$this->setTemplateFilePath($layoutFile, self::TEMPLATE_CONFIRMER);
	}

	/**
	 * Generate unique token key
	 *
	 * @return string
	 */
	protected function generateToken() : string
	{
		return base_convert(md5(uniqid('confirm' . $this->getName(), TRUE)), 16, 36);
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
}
