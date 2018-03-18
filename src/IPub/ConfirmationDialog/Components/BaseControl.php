<?php
/**
 * BaseControl.php
 *
 * @copyright      More in license.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Components
 * @since          1.0.0
 *
 * @date           12.03.14
 */

declare(strict_types = 1);

namespace IPub\ConfirmationDialog\Components;

use Nette\Application;
use Nette\Bridges;
use Nette\Localization;

use IPub\ConfirmationDialog\Exceptions;

/**
 * Abstract control definition
 *
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Components
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 *
 * @property Application\UI\ITemplate $template
 */
abstract class BaseControl extends Application\UI\Control
{
	protected const TEMPLATE_LAYOUT = 'layout';
	protected const TEMPLATE_CONFIRMER = 'template';

	/**
	 * @var string|NULL
	 */
	protected $templateFile = NULL;

	/**
	 * @var string|NULL
	 */
	protected $layoutFile = NULL;

	/**
	 * @var Localization\ITranslator
	 */
	protected $translator;

	/**
	 * @param Localization\ITranslator $translator
	 *
	 * @return void
	 */
	public function injectTranslator(Localization\ITranslator $translator = NULL) : void
	{
		$this->translator = $translator;
	}

	/**
	 * @param Localization\ITranslator $translator
	 *
	 * @return void
	 */
	public function setTranslator(Localization\ITranslator $translator) : void
	{
		$this->translator = $translator;
	}

	/**
	 * @return Localization\ITranslator|NULL
	 */
	public function getTranslator() : ?Localization\ITranslator
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
		if ($this->template instanceof Bridges\ApplicationLatte\Template) {
			// Check if translator is available
			if ($this->getTranslator() instanceof Localization\ITranslator) {
				$this->template->setTranslator($this->getTranslator());
			}

			// Render component template
			return $this->template;
		}

		throw new Exceptions\InvalidStateException('Control is without template.');
	}

	/**
	 * Change default control template path
	 *
	 * @param string $templateFile
	 * @param string $type
	 *
	 * @return void
	 *
	 * @throws Exceptions\FileNotFoundException
	 * @throws Exceptions\InvalidArgumentException
	 */
	protected function setTemplateFilePath(string $templateFile, string $type) : void
	{
		if (!in_array($type, [self::TEMPLATE_CONFIRMER, self::TEMPLATE_LAYOUT])) {
			throw new Exceptions\InvalidArgumentException('Wrong template type');
		}

		// Check if template file exists...
		if (!is_file($templateFile)) {
			$templateFile = $this->transformToTemplateFilePath($templateFile);
		}

		switch ($type)
		{
			case self::TEMPLATE_LAYOUT:
				$this->layoutFile = $templateFile;
				break;

			case self::TEMPLATE_CONFIRMER:
				$this->templateFile = $templateFile;
				break;
		}
	}

	/**
	 * @param string $templateFile
	 *
	 * @return string
	 */
	private function transformToTemplateFilePath(string $templateFile) : string
	{
		// Get component actual dir
		$dir = dirname($this->getReflection()->getFileName());

		$templateName = preg_replace('/.latte/', '', $templateFile);

		// ...check if extension template is used
		if (is_file($dir . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . $templateName . '.latte')) {
			return $dir . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . $templateName . '.latte';
		}

		// ...if not throw exception
		throw new Exceptions\FileNotFoundException(sprintf('Template file "%s" was not found.', $templateFile));
	}
}
