<?php

declare(strict_types=1);

namespace Terlicko\Web;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class SymfonyApplicationKernel extends BaseKernel
{
    use MicroKernelTrait;
}
