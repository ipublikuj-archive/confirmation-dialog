<?php
/**
 * ConfirmerAttributes.php
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

use Nette\Application;

use IPub\ConfirmationDialog;
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
 */
abstract class ConfirmerAttributes extends BaseControl
{
	/**
	 * @var array localization strings
	 */
	public static $strings = [
		'yes'     => 'Yes',
		'no'      => 'No',
		'expired' => 'Confirmation token has expired. Please try action again.',
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
	protected $icon;

	/**
	 * @var callable
	 */
	protected $handler;

	/**
	 * @var bool
	 */
	protected $useAjax = TRUE;

	/**
	 * @var Storage\IStorage
	 */
	protected $storage;

	/**
	 * @param Storage\IStorage $storage
	 */
	public function __construct(Storage\IStorage $storage)
	{
		list(, $parent, $name) = func_get_args() + [NULL, NULL, NULL];

		parent::__construct($parent, $name);

		// Get data storage for confirmer
		$this->storage = $storage;
	}

	/**
	 * Set dialog heading
	 *
	 * @param string|callable $heading
	 *
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function setHeading($heading) : void
	{
		// Check variable type
		if ($this->checkCallableOrString($heading)) {
			// Update confirmation heading
			$this->heading = $heading;
		}
	}

	/**
	 * Get dialog heding
	 *
	 * @return string|NULL
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function getHeading() : ?string
	{
		return $this->getAttribute('heading');
	}

	/**
	 * Set dialog question
	 *
	 * @param string|callable $question
	 *
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function setQuestion($question) : void
	{
		// Check variable type
		if ($this->checkCallableOrString($question)) {
			// Update confirmation question
			$this->question = $question;
		}
	}

	/**
	 * @return string|NULL
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function getQuestion() : ?string
	{
		$question = NULL;

		// Check if attribute is callable
		if (is_callable($this->question)) {
			$question = $this->callCallableAttribute($this->question);

			if ($question !== NULL) {
				$question = (string) $question;
			}

		} elseif ($this->question !== NULL) {
			$question = (string) $this->question;
		}

		return is_bool($question) && $question === FALSE ? NULL : $question;
	}

	/**
	 * Set dialog icon
	 *
	 * @param string|callable $icon
	 *
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function setIcon($icon) : void
	{
		// Check variable type
		if ($this->checkCallableOrString($icon)) {
			// Update confirmation icon
			$this->icon = $icon;
		}
	}

	/**
	 * @return string|NULL
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function getIcon() : ?string
	{
		return $this->getAttribute('icon');
	}

	/**
	 * Set dialog handler
	 *
	 * @param callable $handler
	 *
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function setHandler(callable $handler) : void
	{
		// Update confirmation handler
		$this->handler = $handler;
	}

	/**
	 * @return callable
	 */
	public function getHandler() : callable
	{
		return $this->handler;
	}

	/**
	 * @param Nette\ComponentModel\IContainer $obj
	 * @param array $params
	 *
	 * @return mixed
	 *
	 * @throws Exceptions\HandlerNotCallableException
	 */
	public function callHandler(Nette\ComponentModel\IContainer $obj, array $params)
	{
		$callback = $this->getHandler();

		if ($callback instanceof \Closure) {
			$result = call_user_func_array($callback, $params);

		} elseif (method_exists($obj, 'tryCall')) {
			$result = call_user_func_array([$obj, 'tryCall'], ['method' => $callback[1], 'params' => $params]);

		} else {
			$result = call_user_func_array([$obj, $callback[1]], $params);
		}

		if ($result === FALSE) {
			throw new Exceptions\HandlerNotCallableException('Confirm action callback was not successful.');
		}

		return $result;
	}

	/**
	 * @return void
	 */
	public function enableAjax() : void
	{
		$this->useAjax = TRUE;
	}

	/**
	 * @return void
	 */
	public function disableAjax() : void
	{
		$this->useAjax = FALSE;
	}

	/**
	 * @return Application\UI\Form
	 */
	protected function createComponentForm() : Application\UI\Form
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
	 * @param string $token
	 *
	 * @return array
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	protected function getConfirmerValues(string $token) : array
	{
		// Get values stored in confirmer storage
		$values = $this->storage->get($token);

		// Check for correct values
		if (!is_array($values) || !isset($values['confirmer']) || !isset($values['params'])) {
			throw new Exceptions\InvalidStateException('Confirmer is not configured!');
		}

		return $values;
	}

	/**
	 * @param callable|string $var
	 *
	 * @return bool
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	private function checkCallableOrString($var) : bool
	{
		if (!is_callable($var) && !is_string($var)) {
			throw new Exceptions\InvalidArgumentException(sprintf('%s must be callback or string.', $var));
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
	private function callCallableAttribute(callable $attribute) : string
	{
		if ($this['form']['secureToken']->value === NULL) {
			throw new Exceptions\InvalidStateException('Token is not set!');
		}

		// Get token from form
		$token = $this['form']['secureToken']->value;

		// Get values stored in confirmer storage
		$values = $this->getConfirmerValues($token);

		return call_user_func_array($attribute, [$this, $values['params']]);
	}

	/**
	 * @param string $attribute
	 *
	 * @return string|NULL
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	private function getAttribute(string $attribute) : ?string
	{
		// Check if attribute is callable
		if (is_callable($this->{$attribute})) {
			return (string) $this->callCallableAttribute($this->{$attribute});

		} elseif ($this->{$attribute}) {
			return (string) $this->{$attribute};
		}

		return NULL;
	}
}
