<?php

namespace Hubleto\Framework\Interfaces;

interface LocaleInterface
{

  public function getAvailableLanguages(): array;
  public function getTimezones(): array;
  public function getTimezone(): string;
  public function getIsoCode(): string;
  public function getDateShortFormat(): string;
  public function getDateLongFormat(): string;
  public function getDatetimeFormat(): string;
  public function getTimeFormat(bool $addSeconds = true): string;
  public function getCurrencySymbol(): string;
  public function getCurrencyIsoCode(): string;
  public function getAll(): array;
  public function getDecimalsSeparator(): string;
  public function getThousandsSeparator(): string;
  public function formatCurrency(string|float $value, string $symbol = ''): string;
  public function formatDateShort(string|int $dateOrTimestamp): string;
  public function formatDateLong(string|int $dateOrTimestamp): string;
  public function formatDatetime(string|int $datetimeOrTimestamp): string;
  public function formatTime(string|int $timeOrTimestamp, bool $addSeconds = true): string;

}