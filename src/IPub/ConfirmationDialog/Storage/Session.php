<?php
/**
 * SessionStorage.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Storage
 * @since          1.0.0
 *
 * @date           08.06.14
 */

declare(strict_types = 1);

namespace IPub\ConfirmationDialog\Storage;

use Nette;
use Nette\Http;

/**
 * Confirmer session status storage
 *
 * @package        iPublikuj:ConfirmationDialog!
 * @subpackage     Storage
 */
final class Session implements IStorage
{
	/**
	 * Define class name
	 */
	const CLASS_NAME = __CLASS__;

	/**
	 * @var Http\SessionSection
	 */
	private $session;

	/**
	 * @param Http\Session $session
	 */
	public function __construct(Http\Session $session)
	{
		$this->session = $session->getSection('ipub.confirmation-dialog');
	}

	/**
	 * {@inheritdoc}
	 */
	public function set(string $key, $value)
	{
		$this->session->$key = $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get(string $key, $default = FALSE)
	{
		return isset($this->session->$key) ? $this->session->$key : $default;
	}

	/**
	 * {@inheritdoc}
	 */
	public function clear(string $key)
	{
		unset($this->session->$key);
	}

	/**
	 * {@inheritdoc}
	 */
	public function clearAll()
	{
		$this->session->remove();
	}
}
