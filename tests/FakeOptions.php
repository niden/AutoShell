<?php
declare(strict_types=1);

namespace AutoShell;

class FakeOptions extends Options
{
	public readonly ?string $foo;

	public readonly ?string $bar;
}
