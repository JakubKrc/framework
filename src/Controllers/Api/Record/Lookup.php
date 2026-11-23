<?php

namespace Hubleto\Framework\Controllers\Api\Record;

class Lookup extends \Hubleto\Framework\Controllers\ApiController {
  public bool $hideDefaultDesktop = true;
  public bool $permittedForAllUsers = true;

  public string $modelClass = '';
  public \Hubleto\Framework\Model $model;

  function __construct()
  {
    parent::__construct();

    $this->modelClass = $this->router()->urlParamAsString('model');
    // $this->permission = $model . ':Read';
    $this->model = $this->getModel($this->modelClass);
  }

  public function response(): array
  {
    $search = $this->router()->urlParamAsString('search');

    // One cache per model, the same key the model clears when a record is saved or deleted.
    $cacheKey = 'cache/lookup/' . $this->model->fullName;
    $useCache = empty($search);

    // The answer depends on the user (read scope) and on the other parameters (form record, filters of the app),
    // so each combination is cached separately.
    $cacheParams = $this->router()->getUrlParams();
    unset($cacheParams['search'], $cacheParams['model']);
    $cacheParams['idUser'] = $this->authProvider()->getUserId();
    $cacheParamsHash = md5((string) json_encode($cacheParams));

    $dataFromCache = false;
    $data = [];

    if ($useCache) {
      $cachedAnswers = $this->config()->getAsJson($cacheKey);
      $cache = $cachedAnswers[$cacheParamsHash] ?? null;
      if (is_array($cache) && isset($cache['expiry']) && $cache['expiry'] > time()) {
        $data = $cache['data'];
        $dataFromCache = true;
      }
    }

    if (!$dataFromCache) {
      $query = $this->model->record->prepareLookupQuery($search);
      $dataRaw = $query->get()->toArray();

      if (is_array($dataRaw)) {
        $data = $this->model->record->prepareLookupData($dataRaw);
      }

      if ($useCache) {
        // Expired answers of other parameters are dropped, so the cache does not grow.
        $validAnswers = [];
        foreach ($this->config()->getAsJson($cacheKey) as $paramsHash => $cachedAnswer) {
          $isValid = is_array($cachedAnswer) && ($cachedAnswer['expiry'] ?? 0) > time();
          if ($isValid) $validAnswers[$paramsHash] = $cachedAnswer;
        }

        $validAnswers[$cacheParamsHash] = [
          'expiry' => time() + 60*5,
          'data' => $data
        ];
        $this->config()->save($cacheKey, json_encode($validAnswers));
      }
    }

    return $data;
  }

}
