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
abstract class ConfirmerAttributes extends Control
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
	 * @var bool
	 */
	protected $useAjax = TRUE;

	/**
	 * @var Dialog|Nette\ComponentModel\IContainer
	 */
	protected $dialog;

	/**
	 * @var ConfirmationDialog\SessionStorage
	 */
	protected $sessionStorage;

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
	 * @param array $params
	 *
	 * @return mixed
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function callHandler($obj, array $params)
	{
		if (method_exists($this->getDialog()->getParent(), 'tryCall')) {
			$result = call_user_func_array([$this->getDialog()->getParent(), 'tryCall'], ['method' => $this->getHandler()[1], 'params' => $params]);

		} else {
			$result = call_user_func_array([$this->getDialog()->getParent(), $this->getHandler()[1]], $params);
		}

		return $result;
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
	 * @param callable $attribute
	 *
	 * @return string
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	protected function callCallableAttribute($attribute)
	{
		if ($this['form']['secureToken']->value === NULL) {
			throw new Exceptions\InvalidStateException('Token is not set!');
		}

		// Get token from form
		$token = $this['form']['secureToken']->value;

		// Get values stored in session
		$values = $this->getConfirmerValues($token);

		return call_user_func_array($attribute, [$this, $values['params']]);
	}

	/**
	 * @return array
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	protected function getConfirmerValues($token)
	{
		// Get values stored in session
		$values = $this->sessionStorage->get($token);

		// Check for correct values
		if (!is_array($values) || !isset($values['confirmer']) || !isset($values['params'])) {
			throw new Exceptions\InvalidStateException('Confirmer is not configured!');
		}

		return $values;
	}
}