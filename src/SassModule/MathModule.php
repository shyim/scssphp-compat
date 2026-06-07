<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\SassModule;

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\Exception\SassScriptException;
use ScssPhp\ScssPhp\Node\Number;

/**
 * The built-in `sass:math` module.
 *
 * Implements the functions and constants documented at
 * https://sass-lang.com/documentation/modules/math, to the extent supported by
 * ScssPhp's number model.
 *
 * @internal
 */
final class MathModule
{
    /**
     * Returns the constant variables exposed by the module, keyed by name
     * (without the leading `$`).
     *
     * @return array<string, Number>
     */
    public static function getVariables()
    {
        return [
            'pi'             => new Number(M_PI, ''),
            'e'              => new Number(M_E, ''),
            'epsilon'        => new Number(PHP_FLOAT_EPSILON, ''),
            'max-safe-integer' => new Number((float) (2 ** 53 - 1), ''),
            'min-safe-integer' => new Number((float) (-(2 ** 53 - 1)), ''),
            'max-number'     => new Number(PHP_FLOAT_MAX, ''),
            'min-number'     => new Number(PHP_FLOAT_MIN, ''),
        ];
    }

    /**
     * Whether the module exposes a function with the given (kebab-case) name.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function hasFunction($name)
    {
        return isset(self::FUNCTIONS[$name]);
    }

    /**
     * The argument prototype for a member, used by the compiler to sort
     * positional and keyword arguments.
     *
     * @param string $name
     *
     * @return list<string>|null
     */
    public static function getPrototype($name)
    {
        return isset(self::FUNCTIONS[$name]) ? self::FUNCTIONS[$name] : null;
    }

    /**
     * Invoke a math module function.
     *
     * @param Compiler                   $compiler
     * @param string                     $name     kebab-case member name
     * @param array<array|Number>        $args     positional argument values (already sorted)
     *
     * @return array|Number
     */
    public static function call(Compiler $compiler, $name, array $args)
    {
        $method = self::METHODS[$name];

        return self::$method($compiler, $args);
    }

    /**
     * Argument prototypes, keyed by kebab-case member name. The presence of a
     * key here is also what determines whether the member exists.
     */
    const FUNCTIONS = [
        'ceil'       => ['number'],
        'clamp'      => ['min', 'number', 'max'],
        'floor'      => ['number'],
        'max'        => ['numbers...'],
        'min'        => ['numbers...'],
        'round'      => ['number'],
        'abs'        => ['number'],
        'hypot'      => ['numbers...'],
        'log'        => ['number', 'base:null'],
        'pow'        => ['base', 'exponent'],
        'sqrt'       => ['number'],
        'cos'        => ['number'],
        'sin'        => ['number'],
        'tan'        => ['number'],
        'acos'       => ['number'],
        'asin'       => ['number'],
        'atan'       => ['number'],
        'atan2'      => ['y', 'x'],
        'compatible' => ['number1', 'number2'],
        'is-unitless' => ['number'],
        'unit'       => ['number'],
        'div'        => ['number1', 'number2'],
        'percentage' => ['number'],
        'random'     => ['limit:null'],
    ];

    /**
     * Maps kebab-case member names to the static method implementing them.
     */
    const METHODS = [
        'ceil'       => 'ceil',
        'clamp'      => 'clamp',
        'floor'      => 'floor',
        'max'        => 'max',
        'min'        => 'min',
        'round'      => 'round',
        'abs'        => 'abs',
        'hypot'      => 'hypot',
        'log'        => 'log',
        'pow'        => 'pow',
        'sqrt'       => 'sqrt',
        'cos'        => 'cos',
        'sin'        => 'sin',
        'tan'        => 'tan',
        'acos'       => 'acos',
        'asin'       => 'asin',
        'atan'       => 'atan',
        'atan2'      => 'atan2',
        'compatible' => 'compatible',
        'is-unitless' => 'isUnitless',
        'unit'       => 'unit',
        'div'        => 'div',
        'percentage' => 'percentage',
        'random'     => 'random',
    ];

    // --- Bounding ---

    private static function ceil(Compiler $compiler, array $args)
    {
        $num = $compiler->assertNumber($args[0], 'number');

        return new Number(ceil($num->getDimension()), $num->getNumeratorUnits(), $num->getDenominatorUnits());
    }

    private static function floor(Compiler $compiler, array $args)
    {
        $num = $compiler->assertNumber($args[0], 'number');

        return new Number(floor($num->getDimension()), $num->getNumeratorUnits(), $num->getDenominatorUnits());
    }

    private static function round(Compiler $compiler, array $args)
    {
        $num = $compiler->assertNumber($args[0], 'number');

        return new Number(round($num->getDimension()), $num->getNumeratorUnits(), $num->getDenominatorUnits());
    }

    private static function abs(Compiler $compiler, array $args)
    {
        $num = $compiler->assertNumber($args[0], 'number');

        return new Number(abs($num->getDimension()), $num->getNumeratorUnits(), $num->getDenominatorUnits());
    }

    private static function clamp(Compiler $compiler, array $args)
    {
        $min = $compiler->assertNumber($args[0], 'min');
        $number = $compiler->assertNumber($args[1], 'number');
        $max = $compiler->assertNumber($args[2], 'max');

        if ($min->greaterThan($max)) {
            return $min;
        }
        if ($min->greaterThanOrEqual($number)) {
            return $min;
        }
        if ($max->lessThanOrEqual($number)) {
            return $max;
        }

        return $number;
    }

    // --- Distance ---

    private static function hypot(Compiler $compiler, array $args)
    {
        $numbers = self::numberList($compiler, $args, 'numbers');

        if (\count($numbers) === 0) {
            throw SassScriptException::forArgument('At least one argument must be passed.', 'numbers');
        }

        $first = $numbers[0];
        $sum = 0.0;

        foreach ($numbers as $number) {
            $coerced = self::coerceToUnitsOf($number, $first);
            $sum += $coerced ** 2;
        }

        return new Number(sqrt($sum), $first->getNumeratorUnits(), $first->getDenominatorUnits());
    }

    // --- Exponential ---

    private static function log(Compiler $compiler, array $args)
    {
        $number = $compiler->assertNumber($args[0], 'number');
        $number->assertNoUnits('number');

        if (self::isNull($args[1])) {
            return new Number(log($number->getDimension()), '');
        }

        $base = $compiler->assertNumber($args[1], 'base');
        $base->assertNoUnits('base');

        return new Number(log($number->getDimension()) / log($base->getDimension()), '');
    }

    private static function pow(Compiler $compiler, array $args)
    {
        $base = $compiler->assertNumber($args[0], 'base');
        $base->assertNoUnits('base');
        $exponent = $compiler->assertNumber($args[1], 'exponent');
        $exponent->assertNoUnits('exponent');

        return new Number($base->getDimension() ** $exponent->getDimension(), '');
    }

    private static function sqrt(Compiler $compiler, array $args)
    {
        $number = $compiler->assertNumber($args[0], 'number');
        $number->assertNoUnits('number');

        return new Number(sqrt($number->getDimension()), '');
    }

    // --- Trigonometric ---

    private static function cos(Compiler $compiler, array $args)
    {
        return new Number(cos(self::angleInRadians($compiler, $args[0], 'number')), '');
    }

    private static function sin(Compiler $compiler, array $args)
    {
        return new Number(sin(self::angleInRadians($compiler, $args[0], 'number')), '');
    }

    private static function tan(Compiler $compiler, array $args)
    {
        return new Number(tan(self::angleInRadians($compiler, $args[0], 'number')), '');
    }

    private static function acos(Compiler $compiler, array $args)
    {
        $number = $compiler->assertNumber($args[0], 'number');
        $number->assertNoUnits('number');

        return new Number(rad2deg(acos($number->getDimension())), 'deg');
    }

    private static function asin(Compiler $compiler, array $args)
    {
        $number = $compiler->assertNumber($args[0], 'number');
        $number->assertNoUnits('number');

        return new Number(rad2deg(asin($number->getDimension())), 'deg');
    }

    private static function atan(Compiler $compiler, array $args)
    {
        $number = $compiler->assertNumber($args[0], 'number');
        $number->assertNoUnits('number');

        return new Number(rad2deg(atan($number->getDimension())), 'deg');
    }

    private static function atan2(Compiler $compiler, array $args)
    {
        $y = $compiler->assertNumber($args[0], 'y');
        $x = $compiler->assertNumber($args[1], 'x');

        $xValue = self::coerceToUnitsOf($x, $y);

        return new Number(rad2deg(atan2($y->getDimension(), $xValue)), 'deg');
    }

    // --- Unit ---

    private static function compatible(Compiler $compiler, array $args)
    {
        $number1 = $compiler->assertNumber($args[0], 'number1');
        $number2 = $compiler->assertNumber($args[1], 'number2');

        return $compiler->toBool($number1->isComparableTo($number2));
    }

    private static function isUnitless(Compiler $compiler, array $args)
    {
        $number = $compiler->assertNumber($args[0], 'number');

        return $compiler->toBool($number->unitless());
    }

    private static function unit(Compiler $compiler, array $args)
    {
        $number = $compiler->assertNumber($args[0], 'number');

        return [\ScssPhp\ScssPhp\Type::T_STRING, '"', [$number->unitStr()]];
    }

    // --- Other ---

    private static function div(Compiler $compiler, array $args)
    {
        $number1 = $compiler->assertNumber($args[0], 'number1');
        $number2 = $compiler->assertNumber($args[1], 'number2');

        return $number1->dividedBy($number2);
    }

    private static function percentage(Compiler $compiler, array $args)
    {
        $number = $compiler->assertNumber($args[0], 'number');
        $number->assertNoUnits('number');

        return new Number($number->getDimension() * 100, '%');
    }

    private static function random(Compiler $compiler, array $args)
    {
        $limit = self::isNull($args[0]) ? null : $compiler->assertNumber($args[0], 'limit');

        if ($limit === null) {
            $max = mt_getrandmax();

            return new Number(mt_rand(0, $max - 1) / $max, '');
        }

        $n = $compiler->assertInteger($limit, 'limit');

        if ($n < 1) {
            throw SassScriptException::forArgument("Must be greater than 0, was $n.", 'limit');
        }

        return new Number(mt_rand(1, $n), '');
    }

    private static function min(Compiler $compiler, array $args)
    {
        $numbers = self::numberList($compiler, $args, 'numbers');
        $min = null;

        foreach ($numbers as $number) {
            if ($min === null || $min->greaterThan($number)) {
                $min = $number;
            }
        }

        if ($min === null) {
            throw SassScriptException::forArgument('At least one argument must be passed.', 'numbers');
        }

        return $min;
    }

    private static function max(Compiler $compiler, array $args)
    {
        $numbers = self::numberList($compiler, $args, 'numbers');
        $max = null;

        foreach ($numbers as $number) {
            if ($max === null || $max->lessThan($number)) {
                $max = $number;
            }
        }

        if ($max === null) {
            throw SassScriptException::forArgument('At least one argument must be passed.', 'numbers');
        }

        return $max;
    }

    // --- Helpers ---

    /**
     * Extract a variadic list of numbers from the single rest-argument.
     *
     * @param Compiler          $compiler
     * @param array<array>      $args
     * @param string            $varName
     *
     * @return list<Number>
     */
    private static function numberList(Compiler $compiler, array $args, $varName)
    {
        $numbers = [];

        foreach ($args[0][2] as $arg) {
            $numbers[] = $compiler->assertNumber($arg, $varName);
        }

        return $numbers;
    }

    /**
     * Whether an argument value represents Sass null (missing optional arg).
     *
     * @param array|Number|null $value
     *
     * @return bool
     */
    private static function isNull($value)
    {
        return $value === null || (\is_array($value) && isset($value[0]) && $value[0] === \ScssPhp\ScssPhp\Type::T_NULL);
    }

    /**
     * Convert an angle argument to radians, accepting deg/grad/rad/turn or no unit.
     *
     * @param Compiler     $compiler
     * @param array|Number $value
     * @param string       $varName
     *
     * @return float
     */
    private static function angleInRadians(Compiler $compiler, $value, $varName)
    {
        $number = $compiler->assertNumber($value, $varName);

        if ($number->unitless() || $number->hasUnit('rad')) {
            return $number->getDimension();
        }

        if ($number->hasUnit('deg')) {
            return deg2rad($number->getDimension());
        }

        if ($number->hasUnit('grad')) {
            return $number->getDimension() * M_PI / 200;
        }

        if ($number->hasUnit('turn')) {
            return $number->getDimension() * 2 * M_PI;
        }

        throw SassScriptException::forArgument(sprintf('$%s: Expected %s to be an angle.', $varName, $number), $varName);
    }

    /**
     * Return $number's dimension expressed in the units of $reference.
     *
     * @param Number $number
     * @param Number $reference
     *
     * @return float
     */
    private static function coerceToUnitsOf(Number $number, Number $reference)
    {
        $coerced = $number->coerce($reference->getNumeratorUnits(), $reference->getDenominatorUnits());

        return $coerced->getDimension();
    }
}
