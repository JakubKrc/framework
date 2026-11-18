<?php

namespace Hubleto\Framework\Services;

use Hubleto\Framework\Core;
use Hubleto\Framework\Interfaces\SessionManagerInterface;

/**
 * Default session manager for Hubleto project.
 */
class SessionManager extends Core implements SessionManagerInterface
{

  private string $salt = '';
  private bool $cookieSent = false;

  public function __construct()
  {
    parent::__construct();

    if (isset($_SESSION) && is_array($_SESSION) && !is_array($_SESSION[$this->salt])) $_SESSION[$this->salt] = [];

    $this->salt = $this->config()->getAsString('sessionSalt');
  }

  /**
   * [Description for getSalt]
   *
   * @return string
   * 
   */
  public function getSalt(): string
  {
    return $this->salt;
  }

  public function start(bool $persist, array $options = []): void
  {
    if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
      if (empty($this->salt)) throw new \Exception('Hubleto: Cannot start session, salt is empty.');

      session_id();
      session_name($this->salt);

      if ($persist) {
        $options['cookie_lifetime'] = 2592000; // 30 days (1 month)
        $options['gc_maxlifetime'] = 2592000; // 30 days (1 month)
      }

      if ($this->cookieSent) $options['use_cookies'] = 0;

      session_start($options);
      $this->cookieSent = true;

      // define('_SESSION_ID', session_id());
    }
  }

  public function prolongSession(int $seconds = 2592000): void
  {
    if (session_status() == PHP_SESSION_ACTIVE) {
      setcookie(
        session_name(),
        session_id(),
        time() + $seconds,
        ini_get('session.cookie_path'),
        ini_get('session.cookie_domain'),
        ini_get('session.cookie_secure'),
        ini_get('session.cookie_httponly')
      );
    }
  }

  public function stop(): void
  {
    if (session_status() == PHP_SESSION_ACTIVE) {
      session_write_close();
    }
  }

  public function set(string $path, mixed $value, string $key = '')
  {
    $this->sessionManager()->start(false);
    if (empty($key)) $key = $this->salt;
    if (!isset($_SESSION[$key])) $_SESSION[$key] = [];
    $_SESSION[$key][$path] = $value;
    $this->sessionManager()->stop(false);
  }

  public function get(string $path = '', string $key = ''): mixed
  {
    $this->sessionManager()->start(false);
    if (empty($key)) $key = $this->salt;
    if ($path == '') $value = $_SESSION[$key] ?? [];
    else $value = $_SESSION[$key][$path] ?? null;
    $this->sessionManager()->stop();
    return $value;
  }

  public function push(string $path, mixed $value): void
  {
    $this->sessionManager()->start(false);
    if (!is_array($_SESSION[$this->salt][$path])) $_SESSION[$this->salt][$path] = [];
    $_SESSION[$this->salt][$path][] = $value;
    $this->sessionManager()->stop();
  }

  public function isset(string $path): bool
  {
    $this->sessionManager()->start(false);
    $isset = isset($_SESSION[$this->salt][$path]);
    $this->sessionManager()->stop();
    return $isset;
  }

  public function unset(string $path): void
  {
    $this->sessionManager()->start(false);
    // Note: do not call $this->isset() here. It closes the session, so the removal below would not be saved.
    unset($_SESSION[$this->salt][$path]);
    $this->sessionManager()->stop();
  }

  public function clear(): void
  {
    $this->sessionManager()->start(false);
    unset($_SESSION[$this->salt]);
    $this->sessionManager()->stop();
  }

  /**
   * Issues a new session ID (the session data is kept) and sends it to the browser.
   *
   * It should be called whenever the privilege level of the session changes (e.g. after signing in),
   * so a session ID known to somebody else before the sign-in (session fixation) becomes useless.
   *
   * It is not possible when the response has already started (the cookie with the new ID could not be sent)
   * and it is skipped in CLI (there are no cookies). In such cases it returns false and the session keeps its ID.
   *
   * @param bool $deleteOldSession If true, the session stored under the old ID is deleted.
   *
   * @return bool true if the session ID has been changed.
   *
   */
  public function regenerateId(bool $deleteOldSession = true): bool
  {
    if (PHP_SAPI === 'cli') return false;

    if (headers_sent()) {
      $this->logger()->warning('Hubleto: Cannot regenerate session ID, headers have already been sent.');
      return false;
    }

    if (empty($this->salt)) throw new \Exception('Hubleto: Cannot regenerate session ID, salt is empty.');

    // start() disables cookies if the session has already been started in this request, so the cookie with
    // the new ID would not be sent. Therefore the session is re-opened here with cookies enabled. PHP removes
    // the Set-Cookie header sent by the previous start, so the browser receives only the new ID.
    $this->stop();
    session_name($this->salt);
    if (!session_start(['use_cookies' => 1])) return false;
    $this->cookieSent = true;

    $regenerated = session_regenerate_id($deleteOldSession);
    session_write_close();

    return $regenerated;
  }

}