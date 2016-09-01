<?php

namespace Exfriend\CloudFactory;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

class Engine
{
    use SetRequestOptions;
    /**
     * @var RequestCollection
     */
    public $requests;
    /**
     * Thread count
     * @var int
     */
    public $threads = 100;

    public function __construct()
    {
        $this->requests = new RequestCollection();
        $this->options = new Options();
    }

    /**
     * @param mixed $threads
     * @return $this
     */
    public function setThreads($threads)
    {
        $this->threads = $threads;
        return $this;
    }

    /**
     * Clear the request collection and options
     */
    public function clear()
    {
        $this->requests->clear();
        $this->options = new Options();
    }

    /**
     * Run the engine
     * @param Request|array|null $request
     * @return $this
     */
    public function run($request = null)
    {
        if ($request) {
            if (is_array($request)) {
                foreach ($request as $r) {
                    $this->addRequest($r);
                }
            } elseif ($request instanceof Request) {
                $this->addRequest($request);
            }
        }

        $this->handle();

        return $this;
    }

    /**
     * Add new request to process is
     * @param Request $request
     * @return $this
     */
    public function addRequest(Request $request)
    {
        $request->options->merge($this->options->get());
        $this->requests->push($request);

        return $this;
    }

    protected function handle()
    {
        $client = new Client([
            'expect' => false,
            'http_errors' => false,
        ]);

        while ($this->requests->unprocessed()->count()) {
            (new EachPromise((function () use ($client) {
                while ($r = $this->requests->unprocessed()->sortByDesc('priority')->first()) {
                    yield $this->sendRequest($r, $client);
                }
            })(), [
                'concurrency' => $this->threads,
            ]))->promise()->wait();
        }
    }

    /**
     * Send our request to Guzzle and process the result
     *
     * @param Request $ourRequest
     * @param Client $client
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    protected function sendRequest(Request $ourRequest, Client $client)
    {
        $ourRequest->processed = false;
        $ourRequest->processing = true;
        $request = new GuzzleRequest(($ourRequest->options->get(CURLOPT_POSTFIELDS, false) ? 'POST' : 'GET'),
            $ourRequest->url);

        $promise = $client->sendAsync($request, ['curl' => $ourRequest->options->get()])
                          ->then(function ($response) use ($client, $ourRequest) {
                              $body = (string)$response->getBody();
                              $ourRequest->response = $body;
                              if ($ourRequest->remote_encoding) {
                                  $ourRequest->response = mb_convert_encoding($ourRequest, 'UTF-8',
                                      $ourRequest->remote_encoding);
                              }
                              $this->handleProcessedRequest($client, $ourRequest);

                          }, function ($error) use ($ourRequest) {
                              $ourRequest->error = $error;
                          });
        return $promise;
    }

    /**
     * Perform validation
     *
     * @param Client $client
     * @param Request $ourRequest
     * @return bool
     */
    protected function handleProcessedRequest(Client $client, Request $ourRequest)
    {
        $ourRequest->processed = true;
        $ourRequest->processing = false;
        $ourRequest->tries_current++;


        if ($ourRequest->valid()) {
            $ourRequest->fireCallback('success');

            return true;
        }

        $ourRequest->fireCallback('fail');
        if ($ourRequest->tries_current >= $ourRequest->tries_max) {
            $ourRequest->fireCallback('lastfail');
            return false;
        }

        $this->sendRequest($ourRequest, $client);

        return true;
    }

}
