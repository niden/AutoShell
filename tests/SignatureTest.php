<?php
declare(strict_types=1);

namespace AutoShell;

class SignatureTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array<string, Option> $options
     * @param array<int, string> $input
     * @return mixed[]
     */
    protected function parseOptions(array $options, array $input) : array
    {
        $signature = new Signature(
            argumentParameters: [],
            optionsPosition: null,
            optionsClass: '',
            optionAttributes: $options,
        );

        return $signature->parseOptions($input);
    }

    /**
     * @param mixed[] $expect
     * @param array<string, Option> $options
     */
    protected function assertSameValues(array $expect, array $options) : void
    {
        $actual = [];

        foreach ($options as $option) {
            foreach ($option->names as $name) {
                $actual[$name] = $option->getValue();
            }
        }

        $this->assertSame($expect, $actual);
    }

    public function testParse_noOptions() : void
    {
        $options = [];
        $input = ['abc', 'def'];
        $arguments = $this->parseOptions($options, $input);
        $this->assertCount(0, $options);
        $expect = ['abc', 'def'];
        $this->assertSame($expect, $input);
    }

    public function testParse_undefinedOption() : void
    {
        $options = [];
        $input = ['-z', 'def'];
        $this->expectException(Exception\OptionNotDefined::class);
        $this->expectExceptionMessage("-z is not defined.");
        $this->parseOptions($options, $input);
    }

    public function testParse_longRejected() : void
    {
        $options = [
            'foo_bar' => new Option('foo-bar'),
        ];
        $input = ['--foo-bar'];
        $arguments = $this->parseOptions($options, $input);
        $expect = ['foo-bar' => true];
        $this->assertSameValues($expect, $options);

        $options = [
            'foo_bar' => new Option('foo-bar'),
        ];
        $input = ['--foo-bar=baz'];
        $this->expectException(Exception\ArgumentRejected::class);
        $this->expectExceptionMessage("--foo-bar does not accept an argument.");
        $this->parseOptions($options, $input);
    }

    public function testParse_longRequired() : void
    {
        // '=' as separator
        $options = [
            'foo_bar' => new Option('foo-bar', argument: Option::VALUE_REQUIRED)
        ];
        $input = ['--foo-bar=baz'];
        $arguments = $this->parseOptions($options, $input);
        $expect = ['foo-bar' => 'baz'];
        $this->assertSameValues($expect, $options);

        // ' ' as separator
        $options = [
            'foo_bar' => new Option('foo-bar', argument: Option::VALUE_REQUIRED)
        ];
        $input = ['--foo-bar', 'baz'];
        $arguments = $this->parseOptions($options, $input);
        $this->assertSameValues($expect, $options);

        // missing required value
        $options = [
            'foo_bar' => new Option('foo-bar', argument: Option::VALUE_REQUIRED)
        ];
        $input = ['--foo-bar'];
        $this->expectException(Exception\ArgumentRequired::class);
        $this->expectExceptionMessage("--foo-bar requires an argument.");
        $this->parseOptions($options, $input);
    }

    public function testParse_longOptional() : void
    {
        $options = [
            'foo_bar' => new Option('foo-bar', argument: Option::VALUE_OPTIONAL)
        ];
        $input = ['--foo-bar'];
        $arguments = $this->parseOptions($options, $input);
        $expect = ['foo-bar' => true];
        $this->assertSameValues($expect, $options);

        $options = [
            'foo_bar' => new Option('foo-bar', argument: Option::VALUE_OPTIONAL)
        ];
        $input = ['--foo-bar=baz'];
        $arguments = $this->parseOptions($options, $input);
        $expect = ['foo-bar' => 'baz'];
        $this->assertSameValues($expect, $options);
    }

    public function testParse_longMultiple() : void
    {
        $options = [
            'foo_bar' => new Option('foo-bar', argument: Option::VALUE_OPTIONAL, multiple: true)
        ];

        $input = [
            '--foo-bar',
            '--foo-bar',
            '--foo-bar=baz',
            '--foo-bar=dib',
            '--foo-bar'
        ];
        $arguments = $this->parseOptions($options, $input);
        $expect = ['foo-bar' => [true, true, 'baz', 'dib', true]];
        $this->assertSameValues($expect, $options);
    }

    public function testParse_shortRejected() : void
    {
        $options = [
            'f' => new Option('f')
        ];
        $input = ['-f'];
        $arguments = $this->parseOptions($options, $input);
        $expect = ['f' => true];
        $this->assertSameValues($expect, $options);

        $options = [
            'f' => new Option('f')
        ];
        $input = ['-f', 'baz'];
        $arguments = $this->parseOptions($options, $input);
        $expect = ['f' => true];
        $this->assertSameValues($expect, $options);
        $this->assertSame(['baz'], $arguments);
    }

    public function testParse_shortRequired() : void
    {
        $options = [
            'f' => new Option('f', argument: Option::VALUE_REQUIRED)
        ];
        $input = ['-f', 'baz'];
        $arguments = $this->parseOptions($options, $input);
        $expect = ['f' => 'baz'];
        $this->assertSameValues($expect, $options);

        $options = [
            'f' => new Option('f', argument: Option::VALUE_REQUIRED)
        ];
        $input = ['-f'];
        $this->expectException(Exception\ArgumentRequired::class);
        $this->expectExceptionMessage("-f requires an argument.");
        $this->parseOptions($options, $input);
    }

    public function testParse_shortOptional() : void
    {
        $options = [
            'f' => new Option('f', argument: Option::VALUE_OPTIONAL)
        ];
        $input = ['-f'];
        $arguments = $this->parseOptions($options, $input);
        $expect = ['f' => true];
        $this->assertSameValues($expect, $options);

        $options = [
            'f' => new Option('f', argument: Option::VALUE_OPTIONAL)
        ];
        $input = ['-f', 'baz'];
        $arguments = $this->parseOptions($options, $input);
        $expect = ['f' => 'baz'];
        $this->assertSameValues($expect, $options);
    }

    public function testParse_shortMultiple() : void
    {
        $options = [
            'f' => new Option('f', argument: Option::VALUE_OPTIONAL, multiple: true)
        ];

        $input = ['-f', '-f', '-f', 'baz', '-f', 'dib', '-f'];
        $arguments = $this->parseOptions($options, $input);
        $expect = ['f' => [true, true, 'baz', 'dib', true]];
        $this->assertSameValues($expect, $options);
    }

    public function testParse_shortCluster() : void
    {
        $options = [
            'f' => new Option('f'),
            'b' => new Option('b'),
            'z' => new Option('z'),
        ];

        $input = ['-fbz'];
        $arguments = $this->parseOptions($options, $input);
        $expect = [
            'f' => true,
            'b' => true,
            'z' => true,
        ];
        $this->assertSameValues($expect, $options);
    }

    public function testParse_shortClusterRequired() : void
    {
        $options = [
            'f' => new Option('f'),
            'b' => new Option('b', argument: Option::VALUE_REQUIRED),
            'z' => new Option('z'),
        ];

        $input = ['-fbz'];
        $this->expectException(Exception\ArgumentRequired::class);
        $this->expectExceptionMessage("-b requires an argument.");
        $this->parseOptions($options, $input);
    }

    public function testParseAndGet() : void
    {
        $options = [
            'foo_bar' => new Option('foo-bar', argument: Option::VALUE_REQUIRED),
            'b' => new Option('b'),
            'z' => new Option('z', argument: Option::VALUE_OPTIONAL),
        ];

        $input = [
            'abc',
            '--foo-bar=zim',
            'def',
            '-z',
            'qux',
            '-b',
            'gir',
            '--',
            '--after-double-dash=123',
            '-n',
            '456',
            'ghi',
        ];

        $arguments = $this->parseOptions($options, $input);

        $expectOptv = [
            'foo-bar' => 'zim',
            'b' => true,
            'z' => 'qux',
        ];

        $expectArgv = [
            'abc',
            'def',
            'gir',
            '--after-double-dash=123',
            '-n',
            '456',
            'ghi',
        ];

        $this->assertSameValues($expectOptv, $options);
        $this->assertSame($expectArgv, $arguments);
    }

    public function testMultipleWithAlias() : void
    {
        $options = [
            'foo' => new Option('-f,--foo', argument: Option::VALUE_OPTIONAL, multiple: true)
        ];

        $input = ['-f', '-f', '-f', 'baz', '-f', 'dib', '-f'];
        $arguments = $this->parseOptions($options, $input);
        $expect = [
            'f' => [true, true, 'baz', 'dib', true],
            'foo' => [true, true, 'baz', 'dib', true],
        ];
        $this->assertSameValues($expect, $options);
    }
}