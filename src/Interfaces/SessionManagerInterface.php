<?php

namespace Hubleto\Framework\Interfaces;

interface SessionManagerInterface
{

  public function getSalt(): string;
  public function start(bool $persist, array $options = []): void;

  public function prolongSession(int $seconds = 2592000): void;
  public function stop(): void;
  public function set(string $path, mixed $value, string $key = '');
  public function get(string $path = '', string $key = ''): mixed;
  public function push(string $path, mixed $value): void;
  public function isset(string $path): bool;
  public function unset(string $path): void;
  public function clear(): void;

  /**
   * Issues a new session ID (the session data is kept) and sends it to the browser.
   * Returns false if it is not possible (e.g. the response has already started, or in CLI).
   */
  public function regenerateId(bool $deleteOldSession = true): bool;

}