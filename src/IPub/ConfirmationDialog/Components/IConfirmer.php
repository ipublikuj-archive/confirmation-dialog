<?php
/**
 * IConfirmer.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:ConfirmationDialog!
 * @subpackage	Components
 * @since		5.0
 *
 * @date		31.01.15
 */

namespace IPub\ConfirmationDialog\Components;

interface IConfirmer
{
	/**
	 * @param NULL $templateFile
	 *
	 * @return Confirmer
	 */
	public function create($templateFile = NULL);
}