<?php
/**
 * BaseControl.php
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

use IPub;
use IPub\ConfirmationDialog\Exceptions;

/**
 * Abstract control definition
 *
 * @package		iPublikuj:ConfirmationDialog!
 * @subpackage	Components
 *
 * @property-read Application\UI\ITemplate $template
 */
abstract class BaseControl extends Application\UI\Control
{
	const TEMPLATE_LAYOUT		= 'layout';
	const TEMPLATE_CONFIRMER	= 'template';

	/**
	 * @var null|string
	 */
	protected $templatePath = NULL;

	/**
	 * @var null|string
	 */
	protected $layoutPath = NULL;

	/**
	 * @var Localization\ITranslator
	 */
	protected $translator;

	/**
	 * @param Localization\ITranslator $translator
	 */
	public function injectTranslator(Localization\ITranslator $translator = NULL)
	{
		$this->translator = $translator;
	}

	/**
	 * Change default control template path
	 *
	 * @param string $templateFile
	 * @param string $type
	 *
	 * @return $this
	 *
	 * @throws Exceptions\FileNotFoundException
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function setTemplateFilePath($templateFile, $type)
	{
		if (!in_array((string) $type, [self::TEMPLATE_CONFIRMER, self::TEMPLATE_LAYOUT])) {
			throw new Exceptions\InvalidArgumentException('Wrong template type');
		}

		// Check if template file exists...
		if (!is_file($templateFile)) {
			// Get component actual dir
			$dir = dirname($this->getReflection()->getFileName());

			// ...check if extension template is used
			if (is_file($dir . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR . $templateFile)) {
				$templateFile = $dir . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . $templateFile;

			} else if (is_file($dir . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR . $templateFile .'.latte')) {
				$templateFile = $dir . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR . $templateFile .'.latte';

			} else {
				// ...if not throw exception
				throw new Exceptions\FileNotFoundException(sprintf('Template file "%s" was not found.', $templateFile));
			}
		}

		if ($type == self::TEMPLATE_LAYOUT) {
			$this->layoutPath = $templateFile;

		} else {
			$this->templatePath = $templateFile;
		}

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
	 * Render control
	 *
	 * @return Application\UI\ITemplate
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function render()
	{
		// Check if control has template
		if ($this->template instanceof Nette\Bridges\ApplicationLatte\Template) {
			// Check if translator is available
			if ($this->getTranslator() instanceof Localization\ITranslator) {
				$this->template->setTranslator($this->getTranslator());
			}

			// Render component template
			return $this->template;

		} else {
			throw new Exceptions\InvalidStateException('Control is without template.');
		}
	}
}