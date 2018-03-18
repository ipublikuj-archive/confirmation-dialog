<?php
/**
 * TConfirmationDialog.php
 *
 * @copyright      More in license.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           01.02.15
 */

declare(strict_types = 1);

namespace IPub\ConfirmationDialog;

use IPub\ConfirmationDialog\Components;

/**
 * Confirmation control trait
 *
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     common
 *                 
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
trait TConfirmationDialog
{
	/**
	 * @var Components\IControl
	 */
	protected $confirmationDialogFactory;

	/**
	 * @param Components\IControl $confirmationDialogFactory
	 * 
	 * @return void
	 */
	public function injectConfirmationDialog(Components\IControl $confirmationDialogFactory) : void
	{
		$this->confirmationDialogFactory = $confirmationDialogFactory;
	}
}
