<?php
/**
 * IConfirmer.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Components
 * @since          1.0.0
 *
 * @date           31.01.15
 */

declare(strict_types = 1);

namespace IPub\ConfirmationDialog\Components;

/**
 * Confirmer control factory
 *
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Components
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IConfirmer
{
	/**
	 * @param string|NULL $templateFile
	 *
	 * @return Confirmer
	 */
	public function create(?string $templateFile = NULL) : Confirmer;
}
