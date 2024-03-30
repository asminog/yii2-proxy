<?php

namespace Asminog\Template;

class Unit
{
    public function test(bool $value): bool
    {
        if ($value) {
            return true;
        }

        return false;
    }
}
