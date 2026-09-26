<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\PHPStan;

use Closure;
use Filament\Support\Concerns\Macroable;
use Larastan\Larastan\Methods\Macro;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\ClosureTypeFactory;

/**
 * Expõe os macros registrados com o `Macroable` do Filament (ex.: `TextInput::generateSlug()`).
 *
 * O Larastan só reconhece o `Macroable` do Laravel. Os macros são lidos em runtime, com a aplicação
 * já inicializada pelo Larastan.
 */
final readonly class FilamentMacroMethodsExtension implements MethodsClassReflectionExtension
{
    public function __construct(private ClosureTypeFactory $closureTypeFactory) {}

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->macro($classReflection, $methodName) instanceof Closure;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $macro = $this->macro($classReflection, $methodName);

        return new Macro(
            $classReflection,
            $methodName,
            $this->closureTypeFactory->fromClosureObject($macro ?? static fn (): null => null),
        );
    }

    private function macro(ClassReflection $classReflection, string $methodName): ?Closure
    {
        $className = $classReflection->getName();

        if (! in_array(Macroable::class, class_uses_recursive($className), true)) {
            return null;
        }

        $macro = $classReflection->getNativeReflection()
            ->getMethod('getMacro')
            ->invoke(null, $methodName);

        return is_callable($macro) ? Closure::fromCallable($macro) : null;
    }
}
