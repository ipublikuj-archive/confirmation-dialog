<?php
/**
 * Confirmer.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:ConfirmationDialog!
 * @subpackage	Components
 * @since		5.0
 *
 * @date		31.03.14
 */

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
 * @package		iPublikuj:ConfirmationDialog!
 * @subpackage	Components
 *
 * @property-read string $name
 * @property-read string $cssClass
 * @property-read string $useAjax
 */
class Confirmer extends ConfirmerAttributes
{
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
	 * Show current confirmer
	 *
	 * @param array $params
	 *
	 * @return $this
	 */
	public function showConfirm(array $params = [])
	{
		// Generate protection token
		$token = $this->generateToken();

		// Set generated token to form
		$this['form']['secureToken']->value = $token;

		// Store token to session
		$this->sessionStorage->set($token, [
			'confirmer'	=> $this->getName(),
			'params'	=> $params,
		]);

		// Invalidate all snippets
		$this->redrawControls();

		return $this;
	}

	/**
	 * Confirm YES clicked
	 *
	 * @param Forms\Controls\SubmitButton $button
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function confirmClicked(Forms\Controls\SubmitButton $button)
	{
		// Get submitted values from form
		$values = $button->getForm(TRUE)->getValues();

		// Get token from post
		$token = $values->secureToken;

		try {
			// Get values stored in session
			$values = $this->getConfirmerValues($token);
			// Remove session data for current confirmer
			$this->sessionStorage->clear($token);

		} catch (Exceptions\InvalidStateException $ex) {
			if (self::$strings['expired'] != '' && $this->getPresenter() instanceof Application\UI\Presenter) {
				$this->getPresenter()->flashMessage(self::$strings['expired']);
			}

			// Invalidate all snippets
			$this->redrawControls();

			return;
		}

		if ($this->callHandler($values['params']) === FALSE) {
			throw new Exceptions\InvalidStateException('Confirm action callback was not successful.');
		}

		// Check if request is done via ajax...
		if ($this->getPresenter() instanceof Application\UI\Presenter && !$this->getPresenter()->isAjax()) {
			// ...if not redirect to actual page
			$this->getPresenter()->redirect('this');

		} else {
			// Invalidate all snippets
			$this->redrawControls();
		}
	}

	/**
	 * Confirm NO clicked
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
			$this->sessionStorage->clear($token);
		}

		// Invalidate all snippets
		$this->redrawControls();

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
	public function isConfigured()
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
	 * @throws Exceptions\InvalidStateException
	 */
	public function render()
	{
		// Create template
		$template = parent::render();

		// Check if control has template
		if ($template instanceof Nette\Bridges\ApplicationLatte\Template) {
			// Assign vars to template
			$template->name		= $this->name;
			$template->class	= $this->cssClass;
			$template->icon		= $this->getIcon();
			$template->question	= $this->getQuestion();
			$template->heading	= $this->getHeading();
			$template->useAjax	= $this->useAjax;

			// If template was not defined before...
			if ($template->getFile() === NULL) {
				// ...try to get base component template file
				if (!empty($templatePath)) {
					$templatePath = $this->templatePath;

				} else {
					$templatePath = $this->getDialog()->getTemplateFile();
				}

				$template->setFile($templatePath);
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
	 * @param string $layoutPath
	 *
	 * @return $this
	 */
	public function setTemplateFile($layoutPath)
	{
		parent::setTemplateFilePath($layoutPath, self::TEMPLATE_CONFIRMER);

		return $this;
	}

	/**
	 * Generate unique token key
	 *
	 * @return string
	 */
	protected function generateToken()
	{
		return base_convert(md5(uniqid('confirm' . $this->getName(), TRUE)), 16, 36);
	}

	/**
	 * Redraw all confirmer & dialog snippets
	 *
	 * @return $this
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	protected function redrawControls()
	{
		$this->getDialog()
			// Invalidate dialog snippets
			->redrawControl();

		// Invalidate confirmer snippets
		$this->redrawControl();

		return $this;
	}
}