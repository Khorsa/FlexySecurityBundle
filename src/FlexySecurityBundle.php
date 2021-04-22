<?php
namespace flexycms\FlexySecurityBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class FlexySecurityBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}