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
 */
class Confirmer extends Control
{
	/**
	 * @var array localization strings
	 */
	public static $strings = [
		'yes'		=> 'Yes',
		'no'		=> 'No',
		'expired'	=> 'Confirmation token has expired. Please try action again.',
	];

	/**
	 * @var string
	 */
	protected $cssClass;

	/**
	 * @var string|callable heading
	 */
	protected $heading;

	/**
	 * @var string|callable question
	 */
	protected $question;

	/**
	 * @var string|callable icon
	 */
	protected $icon = FALSE;

	/**
	 * @var callable
	 */
	protected $handler;

	/**
	 * @var ConfirmationDialog\SessionStorage
	 */
	protected $sessionStorage;

	/**
	 * @var Dialog|Nette\ComponentModel\IContainer
	 */
	protected $dialog;

	/**
	 * @var bool
	 */
	protected $useAjax = TRUE;

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
	 * @param ConfirmationDialog\SessionStorage $sessionStorage
	 */
	public function injectSessionStorage(ConfirmationDialog\SessionStorage $sessionStorage)
	{
		// Get session section for confirmer
		$this->sessionStorage = $sessionStorage;
	}

	/**
	 * Set dialog heading
	 *
	 * @param string|callable $heading
	 *
	 * @return $this
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function setHeading($heading)
	{
		// Check variable type
		if ($this->checkCallableOrString($heading)) {
			// Update confirmation heading
			$this->heading = $heading;

			// Redraw confirmation snippets
			$this->getDialog()->redrawControl();
		}

		return $this;
	}

	/**
	 * Get dialog heding
	 *
	 * @return string
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function getHeading()
	{
		// Check if attribute is callable
		if (is_callable($this->heading)) {
			$heading = (string) $this->callCallableAttribute($this->heading);

		} else {
			$heading = (string) $this->heading;
		}

		return $heading;
	}

	/**
	 * Set dialog question
	 *
	 * @param string|callable $question
	 *
	 * @return $this
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function setQuestion($question)
	{
		// Check variable type
		if ($this->checkCallableOrString($question)) {
			// Update confirmation question
			$this->question = $question;

			// Redraw confirmation snippets
			$this->getDialog()->redrawControl();
		}

		return $this;
	}

	/**
	 * @return string
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function getQuestion()
	{
		// Check if attribute is callable
		if (is_callable($this->question)) {
			$question = (string) $this->callCallableAttribute($this->question);

		} else {
			$question = (string) $this->question;
		}

		return $question;
	}

	/**
	 * Set dialog icon
	 *
	 * @param string|callable $icon
	 *
	 * @return $this
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function setIcon($icon)
	{
		// Check variable type
		if ($this->checkCallableOrString($icon)) {
			// Update confirmation icon
			$this->icon = $icon;

			// Redraw confirmation snippets
			$this->getDialog()->redrawControl();
		}

		return $this;
	}

	/**
	 * @return string
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function getIcon()
	{
		// Check if attribute is callable
		if (is_callable($this->icon)) {
			$icon = (string) $this->callCallableAttribute($this->icon);

		} else {
			$icon = (string) $this->icon;
		}

		return $icon;
	}

	/**
	 * Set dialog handler
	 *
	 * @param callable $handler
	 *
	 * @return $this
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function setHandler($handler)
	{
		if (!is_callable($handler)) {
			throw new Exceptions\InvalidArgumentException('$handler must be callable.');
		}

		// Update confirmation handler
		$this->handler = $handler;

		return $this;
	}

	/**
	 * @return callable
	 */
	public function getHandler()
	{
		return $this->handler;
	}

	/**
	 * Show current confirmer
	 *
	 * @param array $params
	 *
	 * @return $this
	 */
	public function showConfirm($params = [])
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

		// Invalidate confirmer snippets
		$this->redrawControl();

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

		if (!$this->getConfirmerValues($token)) {
			if (self::$strings['expired'] != '') {
				if ($this->getPresenter() instanceof Application\UI\Presenter) {
					$this->getPresenter()->flashMessage(self::$strings['expired']);
				}
			}

			// Invalidate dialog snippets
			$this->getDialog()->redrawControl();
			// Invalidate confirmer snippets
			$this->redrawControl();

			return;
		}

		// Get values stored in session
		$values = $this->getConfirmerValues($token);
		// Remove session data for current confirmer
		$this->sessionStorage->clear($token);

		$this->getDialog()
			// Invalidate dialog snippets
			->redrawControl();
		// Invalidate confirmer snippets
		$this->redrawControl();

		if (method_exists($this->getDialog()->getParent(), 'tryCall')) {
			if (call_user_func_array([$this->getDialog()->getParent(), 'tryCall'], ['method' => $this->getHandler()[1], 'params' => $values['params']]) === FALSE) {
				throw new Exceptions\InvalidStateException('Confirm action callback was not successful.');
			}

		} else {
			if (call_user_func_array([$this->getDialog()->getParent(), $this->getHandler()[1]], $values['params']) === FALSE) {
				throw new Exceptions\InvalidStateException('Confirm action callback was not successful.');
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

		$this->getDialog()
			// Invalidate dialog snippets
			->redrawControl();
		// Invalidate confirmer snippets
		$this->redrawControl();

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
	 * @return Application\UI\Form
	 */
	protected function createComponentForm()
	{
		// Create confirmation form
		$form = new Application\UI\Form();

		// Security field
		$form->addHidden('secureToken');

		// Form protection
		$form->addProtection($this->translator ? $this->translator->translate('confirmationDialog.messages.tokenIsExpired') : self::$strings['expired']);

		// Confirm buttons
		$form->addSubmit('yes', $this->translator ? $this->translator->translate('confirmationDialog.buttons.bYes') : self::$strings['yes'])
			->onClick[] = [$this, 'confirmClicked'];

		$form->addSubmit('no', $this->translator ? $this->translator->translate('confirmationDialog.buttons.bNo') : self::$strings['no'])
			->onClick[] = [$this, 'cancelClicked'];

		return $form;
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
	 * Get generated token key
	 *
	 * @return string
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	protected function getToken()
	{
		if ($this['form']['secureToken']->value === NULL) {
			throw new Exceptions\InvalidStateException('Token is not set!');
		}

		return $this['form']['secureToken']->value;
	}

	/**
	 * Get parent dialog control
	 *
	 * @return Dialog
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	protected function getDialog()
	{
		// Check if confirm dialog was loaded before...
		if (!$this->dialog) {
			// ...if not try to lookup for it
			$multiplier = $this->getParent();

			// Check if confirmer is in multiplier
			if ($multiplier instanceof Application\UI\Multiplier) {
				$this->dialog = $multiplier->getParent();

				// Check if parent is right
				if (!$this->dialog instanceof Dialog) {
					throw new Exceptions\InvalidStateException('Confirmer is not attached to parent control!');
				}

			} else {
				throw new Exceptions\InvalidStateException('Confirmer is not attached to multiplier!');
			}
		}

		return $this->dialog;
	}

	/**
	 * @param callable|string $var
	 *
	 * @return bool
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	protected function checkCallableOrString($var)
	{
		if (!is_callable($var) && !is_string($var)) {
			throw new Exceptions\InvalidArgumentException('$var must be callback or string.');
		}

		return TRUE;
	}

	/**
	 * @return array
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	protected function getConfirmerValues($token)
	{
		// Get values stored in session
		return $this->sessionStorage->get($token);
	}

	/**
	 * @param callable $attribute
	 *
	 * @return string
	 */
	protected function callCallableAttribute($attribute)
	{
		// Get values stored in session
		$values = $this->getConfirmerValues($this->getToken());

		return call_user_func_array($attribute, [$this, $values['params']]);
	}
}