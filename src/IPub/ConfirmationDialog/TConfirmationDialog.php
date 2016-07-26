<?php
/**
 * TConfirmationDialog.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           01.02.15
 */

declare(strict_types = 1);

namespace IPub\ConfirmationDialog;

use Nette;
use Nette\Application;

use IPub;
use IPub\ConfirmationDialog\Components;

trait TConfirmationDialog
{
	/**
	 * @var Components\IControl
	 */
	protected $confirmationDialogFactory;

	/**
	 * @param Components\IControl $confirmationDialogFactory
	 */
	public function injectConfirmationDialog(Components\IControl $confirmationDialogFactory)
	{
		$this->confirmationDialogFactory = $confirmationDialogFactory;
	}
}
