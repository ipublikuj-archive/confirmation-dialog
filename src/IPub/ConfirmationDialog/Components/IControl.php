<?php
/**
 * IControl.php
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

interface IControl
{
	const CLASSNAME = __CLASS__;

	/**
	 * @param null|string $layoutFile
	 * @param null|string $templateFile
	 *
	 * @return Control
	 */
	public function create($layoutFile = NULL, $templateFile = NULL);
}