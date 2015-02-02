<?php
/**
 * TConfirmationDialog.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:ConfirmationDialog!
 * @subpackage	common
 * @since		5.0
 *
 * @date		01.02.15
 */

namespace IPub\ConfirmationDialog;

use Nette;
use Nette\Application;

use IPub;
use IPub\ConfirmationDialog\Components;

trait TConfirmationDialog
{
	/**
	 * @var Components\IDialog
	 */
	protected $confirmationDialogFactory;

	/**
	 * @param Components\IDialog $confirmationDialogFactory
	 */
	public function injectConfirmationDialog(Components\IDialog $confirmationDialogFactory) {
		$this->confirmationDialogFactory = $confirmationDialogFactory;
	}
}
