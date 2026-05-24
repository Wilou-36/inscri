<?php

/**
 * Kernel application Symfony pour la gestion de l'inscription.
 * 
 * Cette classe représente le kernel principal de l'application Symfony.
 * Elle utilise le trait MicroKernelTrait pour simplifier la configuration du framework.
 */

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
