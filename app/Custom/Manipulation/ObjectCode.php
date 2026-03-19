<?php

namespace App\Custom\Manipulation;

use App\Custom\Manipulation\Contracts\ManipulationCommand;
use App\Custom\Manipulation\Payload\CodePayload;

class ObjectCode implements ManipulationCommand
{
    private string $code;
    private int $sleep;

    public function __construct(string $code, int $sleep = 0)
    {
        $this->code = trim($code);
        $this->sleep = $sleep;
    }

    public function apply(): array
    {
        return (new CodePayload($this->code, $this->sleep))->toArray();
    }

    public function get(): array
    {
        return $this->apply();
    }
}
