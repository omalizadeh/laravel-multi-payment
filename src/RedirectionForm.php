<?php

namespace Omalizadeh\MultiPayment;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class RedirectionForm implements Arrayable, Responsable
{
    protected array $inputs;
    protected string $method;
    protected string $action;

    /**
     * @param  string  $action
     * @param  array  $inputs
     * @param  string  $method
     */
    public function __construct(string $action, array $inputs = [], string $method = 'POST')
    {
        $this->action = $action;
        $this->inputs = $inputs;
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * Returns a json response with form info wrapped in data key.
     *
     * @return JsonResponse
     */
    public function toJsonResponse(): JsonResponse
    {
        return response()->json([
            'data' => $this->toArray(),
        ]);
    }

    /**
     * Renders a view that redirects to payment gateway automatically.
     *
     * @return Application|Factory|View
     */
    public function view(): Factory|View|Application
    {
        return view('multipayment::redirect_to_gateway', $this->toArray());
    }

    /**
     * Returns redirection form data as array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'action' => $this->getAction(),
            'inputs' => $this->getInputs(),
            'method' => $this->getMethod(),
        ];
    }

    public function toResponse($request): JsonResponse
    {
        return $this->toJsonResponse();
    }
}
