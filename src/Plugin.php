<?php declare(strict_types=1);
namespace Orklah\TypeSetters;

use Orklah\TypeSetters\Hooks\TypeSettersHooks;
use SimpleXMLElement;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;

class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        if(class_exists(TypeSettersHooks::class)){
            $registration->registerHooksFromClass(TypeSettersHooks::class);
        }
    }
}
