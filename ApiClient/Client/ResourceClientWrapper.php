<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Client;

use Akeneo\Pim\ApiClient\Client\ResourceClientInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use Akeneo\Pim\ApiClient\Exception\InvalidArgumentException;
use CleverAge\EAVManager\AkeneoProductBundle\Debug\StopwatchInjectableInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Extending Akeneo base resource client to inject stopwatch
 */
class ResourceClientWrapper implements ResourceClientInterface, StopwatchInjectableInterface
{
    /** @var ResourceClientInterface */
    protected $resourceClient;

    /** @var Stopwatch */
    protected $stopwatch;

    /**
     * @param ResourceClientInterface $resourceClient
     */
    public function __construct(ResourceClientInterface $resourceClient)
    {
        $this->resourceClient = $resourceClient;
    }

    /**
     * @param Stopwatch $stopwatch
     */
    public function setStopwatch(Stopwatch $stopwatch = null)
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * Gets a resource.
     *
     * @param string $uri             URI of the resource
     * @param array  $uriParameters   URI parameters of the resource
     * @param array  $queryParameters Query parameters of the request
     *
     * @throws HttpException If the request failed.
     *
     * @return array
     */
    public function getResource($uri, array $uriParameters = [], array $queryParameters = [])
    {
        return $this->wrapMethod(__METHOD__, func_get_args());
    }

    /**
     * Gets a list of resources.
     *
     * @param string $uri               URI of the resource
     * @param array  $uriParameters     URI parameters of the resource
     * @param int    $limit             The maximum number of resources to return.
     *                                  Do note that the server has a default value if you don't specify anything.
     *                                  The server has a maximum limit allowed as well.
     * @param bool   $withCount         Set to true to return the total count of resources.
     *                                  This parameter could decrease drastically the performance when set to true.
     * @param array  $queryParameters   Additional query parameters of the request
     *
     * @throws InvalidArgumentException If a query parameter is invalid.
     * @throws HttpException            If the request failed.
     *
     * @return array
     */
    public function getResources(
        $uri,
        array $uriParameters = [],
        $limit = 10,
        $withCount = false,
        array $queryParameters = []
    ) {
        return $this->wrapMethod(__METHOD__, func_get_args());
    }

    /**
     * Creates a resource.
     *
     * @param string $uri           URI of the resource
     * @param array  $uriParameters URI parameters of the resource
     * @param array  $body          Body of the request
     *
     * @throws HttpException If the request failed.
     *
     * @return int Status code 201 indicating that the resource has been well created.
     */
    public function createResource($uri, array $uriParameters = [], array $body = [])
    {
        return $this->wrapMethod(__METHOD__, func_get_args());
    }

    /**
     * Creates a resource using a multipart request.
     *
     * @param string $uri           URI of the resource
     * @param array  $uriParameters URI parameters of the resources
     * @param array  $requestParts  Parts of the request. Each part is defined with "name", "contents", and "options"
     *
     * @throws InvalidArgumentException If a given request part is invalid.
     * @throws HttpException            If the request failed.
     *
     * @return ResponseInterface the response of the creation request
     */
    public function createMultipartResource($uri, array $uriParameters = [], array $requestParts = [])
    {
        return $this->wrapMethod(__METHOD__, func_get_args());
    }

    /**
     * Creates a resource if it does not exist yet, otherwise updates partially the resource.
     *
     * @param string $uri           URI of the resource
     * @param array  $uriParameters URI parameters of the resource
     * @param array  $body          Body of the request
     *
     * @throws HttpException If the request failed.
     *
     * @return int Status code 201 indicating that the resource has been well created.
     *             Status code 204 indicating that the resource has been well updated.
     */
    public function upsertResource($uri, array $uriParameters = [], array $body = [])
    {
        return $this->wrapMethod(__METHOD__, func_get_args());
    }

    /**
     * Updates or creates several resources.
     *
     * @param string                $uri           URI of the resource
     * @param array                 $uriParameters URI parameters of the resource
     * @param array|StreamInterface $resources     array of resources to create or update.
     *                                             You can pass your own StreamInterface implementation as well.
     *
     * @throws HttpException            If the request failed.
     * @throws InvalidArgumentException If the resources or any part thereof are invalid.
     *
     * @return \Traversable returns an iterable object, each entry corresponding to the response of the upserted
     *                      resource
     */
    public function upsertResourceList($uri, array $uriParameters = [], $resources = [])
    {
        return $this->wrapMethod(__METHOD__, func_get_args());
    }

    /**
     * Deletes a resource.
     *
     * @param string $uri           URI of the resource to delete
     * @param array  $uriParameters URI parameters of the resource
     *
     * @throws HttpException If the request failed
     *
     * @return int Status code 204 indicating that the resource has been well deleted
     */
    public function deleteResource($uri, array $uriParameters = [])
    {
        return $this->wrapMethod(__METHOD__, func_get_args());
    }

    /**
     * Gets a streamed resource.
     *
     * @param string $uri           URI of the resource
     * @param array  $uriParameters URI parameters of the resource
     *
     * @throws HttpException If the request failed
     *
     * @return StreamInterface
     */
    public function getStreamedResource($uri, array $uriParameters = [])
    {
        return $this->wrapMethod(__METHOD__, func_get_args());
    }

    /**
     * @param string     $fullMethod
     * @param array|null $arguments
     *
     * @return mixed
     */
    protected function wrapMethod(string $fullMethod, array $arguments = null)
    {
        $method = substr(strrchr($fullMethod, '::'), 1);
        if (!$this->stopwatch) {
            return call_user_func_array([$this->resourceClient, $method], $arguments);
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $previousCall = $trace[2]; // Look for the method that called this object
        $niceMethod = $previousCall['class'].'::'.$previousCall['function'];

        $this->stopwatch->start($niceMethod, 'akeneo.api');
        $result = call_user_func_array([$this->resourceClient, $method], $arguments);
        $this->stopwatch->stop($niceMethod);

        return $result;
    }
}
