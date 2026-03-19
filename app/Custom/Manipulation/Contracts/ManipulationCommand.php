<?php

namespace App\Custom\Manipulation\Contracts;

interface ManipulationCommand
{
    public function apply(): array;
}
