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

        /**
         * Mesma busca do `Macroable::getMacro()`: o macro fica em `$macros[$nome][$classe]`, procurando na classe e
         * depois nos pais.
         */
        $macros = $classReflection->getNativeReflection()
            ->getProperty('macros')
            ->getValue();
        $candidates = is_array($macros) ? ($macros[$methodName] ?? null) : null;

        if (! is_array($candidates)) {
            return null;
        }

        foreach ([$className, ...$classReflection->getParentClassesNames()] as $class) {
            $macro = $candidates[$class] ?? null;

            if (is_callable($macro)) {
                return Closure::fromCallable($macro);
            }
        }

        return null;
    }
}
