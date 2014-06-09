<?php
/**
 * SessionStorage.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:ConfirmationDialog!
 * @subpackage	common
 * @since		5.0
 *
 * @date		08.06.14
 */

namespace IPub\ConfirmationDialog;

use Nette;

class SessionStorage extends Nette\Object
{
	/**
	 * @var Nette\Http\SessionSection
	 */
	protected $session;

	/**
	 * @param Nette\Http\Session $session
	 */
	public function __construct(Nette\Http\Session $session)
	{
		$this->session = $session->getSection('ConfirmDialog');
	}

	/**
	 * Stores the given ($key, $value) pair, so that future calls to
	 * getPersistentData($key) return $value. This call may be in another request.
	 *
	 * Provides the implementations of the inherited abstract
	 * methods.  The implementation uses PHP sessions to maintain
	 * a store for authorization codes, user ids, CSRF states, and
	 * access tokens.
	 */
	public function set($key, $value)
	{
		$this->session->$key = $value;
	}

	/**
	 * Get the data for $key, persisted by BaseFacebook::setPersistentData()
	 *
	 * @param string $key The key of the data to retrieve
	 * @param mixed $default The default value to return if $key is not found
	 *
	 * @return mixed
	 */
	public function get($key, $default = FALSE)
	{
		return isset($this->session->$key) ? $this->session->$key : $default;
	}

	/**
	 * Clear the data with $key from the persistent storage
	 *
	 * @param string $key
	 *
	 * @return void
	 */
	public function clear($key)
	{
		unset($this->session->$key);
	}

	/**
	 * Clear all data from the persistent storage
	 *
	 * @return void
	 */
	public function clearAll()
	{
		$this->session->remove();
	}

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function &__get($name)
	{
		$value = $this->get($name);

		return $value;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->session->$name);
	}

	/**
	 * @param string $name
	 */
	public function __unset($name)
	{
		$this->clear($name);
	}
}