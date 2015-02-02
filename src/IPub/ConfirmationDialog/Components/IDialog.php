<?php
/**
 * IDialog.php
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

interface IDialog
{
	/**
	 * @param null|string $templateFile
	 *
	 * @return Dialog
	 */
	public function create($templateFile = NULL);
}