<?php

namespace App\Modules\Greeting\Domain;

use InvalidArgumentException;

class GreetingVariant
{
    public const PRIMARY = 'primary';

    public const SECONDARY = 'secondary';

    public const SUCCESS = 'success';

    public const WARNING = 'warning';

    public const DANGER = 'danger';

    protected const VALID_VARIANT = [self::PRIMARY, self::SECONDARY, self::SUCCESS, self::WARNING, self::DANGER];

    private string $name;

    public function __construct(string $name)
    {

        if (! in_array($name, self::VALID_VARIANT)) {
            throw new InvalidArgumentException('Invalid greeting variant: '.$name);
        }
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
