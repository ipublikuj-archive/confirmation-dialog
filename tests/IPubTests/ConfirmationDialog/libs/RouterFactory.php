<?php
/**
 * Test: IPub\ConfirmationDialog\Libraries
 * @testCase
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Tests
 * @since          2.1.1
 *
 * @date           10.12.16
 */

declare(strict_types = 1);

namespace IPubTests\ConfirmationDialog\Libs;

use Nette;
use Nette\Application;
use Nette\Application\Routers;

/**
 * Simple routes factory
 *
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Tests
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class RouterFactory
{
	/**
	 * @return Application\IRouter
	 */
	public static function createRouter() : Application\IRouter
	{
		$router = new Routers\RouteList();
		$router[] = new Routers\Route('<presenter>/<action>[/<id>]', 'Test:default');

		return $router;
	}
}
