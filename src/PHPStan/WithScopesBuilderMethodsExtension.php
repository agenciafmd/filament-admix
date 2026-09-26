<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\PHPStan;

use Agenciafmd\Admix\Traits\WithScopes;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Methods\Macro;
use LogicException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ClosureTypeFactory;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;

/**
 * Expõe os scopes do `WithScopes` (ex.: `sort()`, `isActive()`) num `Builder` cujo model não é conhecido.
 *
 * É o caso das closures das Tables do Filament (`->defaultSort(fn (Builder $query): Builder => $query->sort())`),
 * que recebem o `Builder` sem o tipo do model. Quando o model é conhecido, quem decide é o Larastan.
 */
final class WithScopesBuilderMethodsExtension implements MethodsClassReflectionExtension
{
    /**
     * @var array<int, string>|null
     */
    private ?array $scopeNames = null;

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ClosureTypeFactory $closureTypeFactory,
    ) {}

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection->is(Builder::class)
            && $this->hasUnknownModel($classReflection)
            && in_array($methodName, $this->scopeNames(), true);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new Macro(
            $classReflection,
            $methodName,
            $this->closureTypeFactory->fromClosureObject(static fn (): Builder => throw new LogicException('Only used for static analysis.')),
        );
    }

    private function hasUnknownModel(ClassReflection $classReflection): bool
    {
        $modelType = $classReflection->getActiveTemplateTypeMap()->getType('TModel');

        if ($modelType instanceof TemplateType) {
            $modelType = $modelType->getBound();
        }

        return ! $modelType instanceof Type || $modelType->getObjectClassNames() === [Model::class];
    }

    /**
     * @return array<int, string>
     */
    private function scopeNames(): array
    {
        if ($this->scopeNames !== null) {
            return $this->scopeNames;
        }

        $methods = $this->reflectionProvider->getClass(WithScopes::class)
            ->getNativeReflection()
            ->getMethods();

        $this->scopeNames = collect($methods)
            ->filter(static fn (object $method): bool => $method->getAttributes(Scope::class) !== [])
            ->map(static fn (object $method): string => $method->getName())
            ->values()
            ->all();

        return $this->scopeNames;
    }
}
