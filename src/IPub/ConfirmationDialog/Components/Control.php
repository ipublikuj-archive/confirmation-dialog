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
use Nette\Forms;
use Nette\Localization;
use Nette\Utils;

use IPub;
use IPub\ConfirmationDialog\SessionStorage;

class Control extends Application\UI\Control
{
	/**
	 * @var array localization strings
	 */
	public static $_strings = array(
		'yes'		=> 'Yes',
		'no'		=> 'No',
		'expired'	=> 'Confirmation token has expired. Please try action again.',
	);

	/**
	 * @var bool
	 */
	public $visible = FALSE;

	/**
	 * @var string
	 */
	public $cssClass = 'confirmation-dialog';

	/**
	 * @var string icon
	 */
	private $icon = FALSE;

	/**
	 * @var string question
	 */
	private $question;

	/**
	 * @var string confirmation heading
	 */
	private $heading;

	/**
	 * @var IPub\ConfirmationDialog\SessionStorage
	 */
	protected $sessionStorage;

	/**
	 * @var array storage of confirmation handlers
	 */
	private static $confirmationHandlers = array();

	/**
	 * @var string
	 */
	protected $templatePath;

	/**
	 * @var Localization\ITranslator
	 */
	protected $translator;

	/**
	 * @var string
	 */
	private static $instance;

	/**
	 * @param SessionStorage $sessionStorage
	 * @param Localization\ITranslator $translator
	 * @param Nette\ComponentModel\IContainer $parent
	 * @param null $name
	 */
	public function __construct(
		SessionStorage $sessionStorage,
		Localization\ITranslator $translator = NULL,
		Nette\ComponentModel\IContainer $parent = NULL, $name = NULL
	) {
		parent::__construct($parent, $name);

		// Get session section for confirmer
		$this->sessionStorage = $sessionStorage;

		// Set control translator
		$this->setTranslator($translator);

		// Store instance
		self::$instance = uniqid();
	}

	/**
	 * Change default component template path
	 *
	 * @param string $templatePath
	 *
	 * @return $this
	 *
	 * @throws \Nette\FileNotFoundException
	 */
	public function setTemplate($templatePath)
	{
		// Check if template file exists...
		if (!is_file($templatePath)) {
			// ...check if extension template is used
			if (is_file(__DIR__ . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR . $templatePath)) {
				$templatePath = __DIR__ . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR . $templatePath;

			} else {
				// ...if not throw exception
				throw new Nette\FileNotFoundException('Template file "'. $templatePath .'" was not found.');
			}
		}

		$this->templatePath = $templatePath;

		return $this;
	}

	/**
	 * @param Localization\ITranslator $translator
	 *
	 * @return $this
	 */
	public function setTranslator(Localization\ITranslator $translator)
	{
		$this->translator = $translator;

		return $this;
	}

	/**
	 * @return Localization\ITranslator|null
	 */
	public function getTranslator()
	{
		if ($this->translator instanceof Localization\ITranslator) {
			return $this->translator;
		}

		return NULL;
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
		$form->addProtection($this->translator ? $this->translator->translate('confirmationDialog.messages.tokenIsExpired') : self::$_strings['expired']);

		// Confirm buttons
		$form->addSubmit('yes', $this->translator ? $this->translator->translate('confirmationDialog.buttons.bYes') : self::$_strings['yes'])
			->onClick[] = array($this, 'confirmClicked');

		$form->addSubmit('no', $this->translator ? $this->translator->translate('confirmationDialog.buttons.bNo') : self::$_strings['no'])
			->onClick[] = array($this, 'cancelClicked');

		return $form;
	}

	/**
	 * Overrides signal method formatter. This provide "dynamically named signals"
	 *
	 * @param string $signal
	 *
	 * @return string
	 */
	public static function formatSignalMethod($signal)
	{
		if (stripos($signal, 'confirm') === 0 &&  isset(self::$confirmationHandlers[self::$instance][lcfirst(substr($signal, 7))])) {
			return '_handleShow';
		}

		parent::formatSignalMethod($signal);
	}

	/**
	 * Access to Yes or No form button controls
	 *
	 * @param string $name Only 'yes' or 'no' is accepted
	 *
	 * @throws Nette\MemberAccessException
	 *
	 * @return Forms\Controls\SubmitButton
	 */
	public function getFormButton($name)
	{
		$name = (string) $name;

		if ($name !== 'yes' && $name !== 'no') {
			throw new Nette\MemberAccessException("Only 'yes' or 'no' is accepted in \$name. '$name' given.");
		}

		return $this['form'][$name];
	}

	/**
	 * Set question
	 *
	 * @param string $text
	 *
	 * @return $this
	 */
	public function setQuestionText($text)
	{
		// Update confirmation question
		$this->question = $text;

		// Redraw confirmation snippets
		$this->redrawControl();

		return $this;
	}

	/**
	 * Set dialog icon
	 *
	 * @param $icon
	 *
	 * @return $this
	 */
	public function setIcon($icon)
	{
		// Update confirmation icon
		$this->icon = $icon;

		// Redraw confirmation snippets
		$this->redrawControl();

		return $this;
	}

	/**
	 * Generate unique token key
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function generateToken($name = '')
	{
		return base_convert(md5(uniqid('confirm' . $name, TRUE)), 16, 36);
	}

	/************** Configuration **************/

	/**
	 * Add confirmation handler to "dynamicaly named signals"
	 *
	 * @param string $name Confirmation/signal name
	 * @param callback|Nette\Callback $methodCallback Callback called when confirmation succeed
	 * @param callback|string $question Callback ($confirmForm, $params) or string containing question text
	 * @param callback|string $heading Callback ($confirmForm, $params) or string containing heading text
	 *
	 * @return $this
	 *
	 * @throws Nette\InvalidArgumentException
	 */
	public function addConfirmer($name, $methodCallback, $question, $heading = NULL)
	{
		if (!preg_match('/[A-Za-z_]+/', $name)) {
			throw new Nette\InvalidArgumentException("Confirmation name contain invalid characters.");
		}

		if (isset(self::$confirmationHandlers[self::$instance][$name])) {
			throw new Nette\InvalidArgumentException("Confirmation '$name' already exists.");
		}

		if (!is_callable($methodCallback)) {
			throw new Nette\InvalidArgumentException('$methodCallback must be callable.');
		}

		if (!is_callable($question) && !is_string($question)) {
			throw new Nette\InvalidArgumentException('$question must be callback or string.');
		}

		if (!is_callable($heading) && !is_string($heading) && $heading !== NULL) {
			throw new Nette\InvalidArgumentException('$heading must be callback or string.');
		}

		self::$confirmationHandlers[self::$instance][$name] = array(
			'handler'	=> $methodCallback,
			'question'	=> $question,
			'heading'	=> $heading,
		);

		return $this;
	}

	/**
	 * Show dialog for confirmation
	 *
	 * @param string $confirmName
	 * @param array $params
	 *
	 * @throws Nette\InvalidArgumentException
	 * @throws Nette\InvalidStateException
	 */
	public function showConfirm($confirmName, $params = array())
	{
		// Activate visibility
		$this->visible = TRUE;

		if (!is_string($confirmName)) {
			throw new Nette\InvalidArgumentException('$confirmName must be string.');
		}

		if (!isset(self::$confirmationHandlers[self::$instance][$confirmName])) {
			throw new Nette\InvalidStateException("confirmation '$confirmName' do not exist.");
		}

		if (!is_array($params)) {
			throw new Nette\InvalidArgumentException('$params must be array.');
		}

		$confirm = self::$confirmationHandlers[self::$instance][$confirmName];

		if (is_callable($confirm['question'])) {
			$question = call_user_func_array($confirm['question'], array($this, $params));

		} else {
			$question = $confirm['question'];
		}

		if ($question === FALSE) {
			$this->visible = FALSE;

		} else {
			$this->question = $question;
		}

		if (is_callable($confirm['heading'])) {
			$heading = call_user_func_array($confirm['heading'], array($this, $params));

		} else {
			$heading = $confirm['heading'];
		}

		$this->heading = $heading;

		// Generate protection token
		$token = $this->generateToken($confirmName);

		// Set generated token to form
		$this['form']['secureToken']->value = $token;

		// Store token to session
		$this->sessionStorage->set($token, array(
			'confirm'	=> $confirmName,
			'params'	=> $params,
		));

		// Invalidate confirm dialog snippet
		$this->redrawControl();
	}

	/************** Signals processing **************/

	/**
	 * Dynamically named signal receiver
	 */
	function _handleShow($id)
	{
		list($component, $signal) = $this->presenter->getSignal();

		$confirmName = (substr($signal, 7));
		$confirmName{0} = strtolower($confirmName{0});

		$params = $this->getParameters();

		$this->showConfirm($confirmName, $params);
	}

	/**
	 * Confirm YES clicked
	 *
	 * @param Forms\Controls\SubmitButton $button
	 */
	public function confirmClicked(Forms\Controls\SubmitButton $button)
	{
		// Get submitted values from form
		$values = $button->getForm(TRUE)->getValues();

		if ($this->sessionStorage->get($values['secureToken'])) {
			if (self::$_strings['expired'] != '') {
				$this->presenter->flashMessage(self::$_strings['expired']);
			}

			$this->redrawControl();

			return;
		}

		$action = $this->sessionStorage->get($values['secureToken']);
		$this->sessionStorage->clear($values['secureToken']);

		$this->visible = FALSE;
		$this->redrawControl();

		$callback = self::$confirmationHandlers[self::$instance][$action['confirm']]['handler'];

		$args = $action['params'];

		$this->parent->tryCall($callback[1], $args);

		if (!$this->presenter->isAjax() && $this->visible == FALSE) {
			$this->presenter->redirect('this');
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

		if ($this->sessionStorage->get($values['secureToken'])) {
			$this->sessionStorage->clear($values['secureToken']);
		}

		$this->visible = FALSE;
		$this->redrawControl();

		if (!$this->presenter->isAjax()) {
			$this->presenter->redirect('this');
		}
	}

	/************** Rendering **************/

	/**
	 * @return bool
	 */
	public function isVisible()
	{
		return $this->visible;
	}

	/**
	 * Render control
	 *
	 * @throws Nette\InvalidStateException
	 */
	public function render()
	{
		if ($this->visible) {
			if ($this['form']['secureToken']->value === NULL) {
				throw new Nette\InvalidStateException('Token is not set!');
			}
		}

		// Assign vars to template
		$this->template->name		= $this->name;
		$this->template->visible	= $this->visible;
		$this->template->class		= $this->cssClass;
		$this->template->icon		= $this->icon;
		$this->template->question	= $this->question;
		$this->template->heading	= $this->heading;

		// Check if translator is available
		if ($this->getTranslator() instanceof Localization\ITranslator) {
			$this->template->setTranslator($this->getTranslator());
		}

		// Get component template file
		$templatePath = !empty($this->templatePath) ? $this->templatePath : __DIR__ . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR .'default.latte';
		$this->template->setFile($templatePath);

		// Render component template
		$this->template->render();
	}
}