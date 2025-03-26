<?php

namespace Meanify\LaravelPermissions\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Permission
{
    public function __construct(
        protected ?string $code = null,
        protected ?string $group = null,
        protected ?string $label = null,
        protected bool $apply = true,
        protected ?string $class = null,
        protected ?string $method = null,
    ) {}

    public function code(): ?string
    {
        return $this->code;
    }

    public function group(): ?string
    {
        return $this->group;
    }

    public function label(): ?string
    {
        return $this->label;
    }

    public function apply(): bool
    {
        return $this->apply;
    }

    public function class(): ?string
    {
        return $this->class;
    }

    public function method(): ?string
    {
        return $this->method;
    }
}